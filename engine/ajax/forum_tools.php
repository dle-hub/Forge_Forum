<?php
/*
=====================================================
 Forge Forum Engine — Bakım AJAX Controller
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_tools
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

// Dil dosyasını yükle
if (!is_array($lang)) {
    $lang = [];
}
$forum_lang_dir = $config['langs'] ? totranslit($config['langs']) : 'Turkish';
if (file_exists(ROOT_DIR . '/language/' . $forum_lang_dir . '/forum.lng')) {
    include_once (ROOT_DIR . '/language/' . $forum_lang_dir . '/forum.lng');
} else {
    if (file_exists(ROOT_DIR . '/language/Turkish/forum.lng')) {
        include_once (ROOT_DIR . '/language/Turkish/forum.lng');
    }
}

if ($member_id["user_group"] != 1) {
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "error" => $lang['forum_ajax_unauthorized']]);
    exit();
}

$forum_cfg = [];
$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
while ($row = $db->get_row()) {
    $forum_cfg[$row["name"]] = stripslashes($row["value"]);
}
$forum_cfg = array_merge(["enable_ranks" => 1], $forum_cfg);

include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/rank_helpers.php",
);

header("Content-Type: application/json");

/**
 * Eksik _users kolonlarını otomatik oluşturur
 */
function ensureForumUserColumns($db)
{
    $table = PREFIX . "_users";
    $needed = ["forum_post_count", "forum_points", "forum_rank_id", "forum_manual_rank_id"];
    // SHOW COLUMNS sonucunu tek satır olarak değil list şeklinde al
    $existing = [];
    $db->query("SHOW COLUMNS FROM `{$table}`");
    while ($row = $db->get_row()) {
        if (in_array($row["Field"], $needed)) {
            $existing[] = $row["Field"];
        }
    }
    foreach ($needed as $col) {
        if (!in_array($col, $existing)) {
            @$db->query(
                "ALTER TABLE `{$table}` ADD COLUMN `{$col}` INT(11) DEFAULT '0'",
            );
        }
    }
}

$action = isset($_POST["action"]) ? totranslit($_POST["action"]) : "";
$step = isset($_POST["step"]) ? intval($_POST["step"]) : 0;
$limit = 500;

$response = [
    "success" => false,
    "step" => $step,
    "total" => 0,
    "processed" => 0,
    "done" => false,
    "message" => "",
];

try {
    switch ($action) {
        // =========================================
        // KULLANICI İSTATİSTİKLERİNİ ONAR
        // =========================================
        case "repair_users":
            ensureForumUserColumns($db);

            $total = $db->super_query(
                "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_users",
            );
            $total = intval($total["cnt"]);
            $offset = $step * $limit;

            if ($offset >= $total) {
                $response = [
                    "success" => true,
                    "step" => $step,
                    "total" => $total,
                    "processed" => $total,
                    "done" => true,
                    "message" => $lang['forum_ajax_tools_users_rebuilt'],
                ];
                break;
            }

            // Önce ID'leri diziye çek (query_id ezilmesin diye)
            $ids = [];
            $db->query(
                "SELECT user_id FROM " .
                    PREFIX .
                    "_users ORDER BY user_id ASC LIMIT {$offset},{$limit}",
            );
            while ($u = $db->get_row()) {
                $ids[] = intval($u["user_id"]);
            }

            // Sonra tek tek işle
            $processed = 0;
            foreach ($ids as $uid) {
                $pc = $db->super_query(
                    "SELECT COUNT(*) AS cnt FROM " .
                        PREFIX .
                        "_forum_posts WHERE user_id='{$uid}' AND is_deleted=0",
                );
                $tc = $db->super_query(
                    "SELECT COUNT(*) AS cnt FROM " .
                        PREFIX .
                        "_forum_topics WHERE user_id='{$uid}' AND is_deleted=0",
                );
                $pts = $db->super_query(
                    "SELECT SUM(likes) AS total FROM " .
                        PREFIX .
                        "_forum_posts WHERE user_id='{$uid}' AND is_deleted=0",
                );
                $postCount = intval($pc["cnt"]);
                $topicCount = intval($tc["cnt"]);
                $likePoints = intval($pts["total"]) * 2;
                $points = $topicCount * 10 + $postCount * 5 + $likePoints;
                $db->query(
                    "UPDATE " .
                        PREFIX .
                        "_users SET forum_post_count='{$postCount}', forum_points='{$points}' WHERE user_id='{$uid}'",
                );
                forum_sync_user_rank($uid, $points);
                $processed++;
            }

            $response = [
                "success" => true,
                "step" => $step + 1,
                "total" => $total,
                "processed" => $offset + $processed,
                "done" => false,
                "message" =>
                    $lang['forum_ajax_tools_users_processing'] .
                    ($offset + $processed) .
                    " / {$total}",
            ];
            break;

        // =========================================
        // KATEGORİ İSTATİSTİKLERİNİ ONAR
        // =========================================
        case "repair_cats":
            $cats = $db->super_query(
                "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_cats",
            );
            $total = intval($cats["cnt"]);
            $offset = $step * $limit;

            if ($offset >= $total) {
                $response = [
                    "success" => true,
                    "step" => $step,
                    "total" => $total,
                    "processed" => $total,
                    "done" => true,
                    "message" => $lang['forum_ajax_tools_cats_rebuilt'],
                ];
                break;
            }

            // Önce ID'leri diziye çek
            $catIds = [];
            $db->query(
                "SELECT id FROM " .
                    PREFIX .
                    "_forum_cats ORDER BY id ASC LIMIT {$offset},{$limit}",
            );
            while ($c = $db->get_row()) {
                $catIds[] = intval($c["id"]);
            }

            // Alt kategorileri de topla (recursive, önceden çek)
            $allSubs = [];
            $db->query(
                "SELECT id, parent_id FROM " .
                    PREFIX .
                    "_forum_cats ORDER BY parent_id ASC",
            );
            $parentMap = [];
            while ($sc = $db->get_row()) {
                $pid = intval($sc["parent_id"]);
                if (!isset($parentMap[$pid])) {
                    $parentMap[$pid] = [];
                }
                $parentMap[$pid][] = intval($sc["id"]);
            }

            $processed = 0;
            foreach ($catIds as $cid) {
                // Alt kategorileri recursive bul
                $allCatIds = [$cid];
                $stack = [$cid];
                while ($pid = array_shift($stack)) {
                    if (isset($parentMap[$pid])) {
                        foreach ($parentMap[$pid] as $sid) {
                            $allCatIds[] = $sid;
                            $stack[] = $sid;
                        }
                    }
                }
                $catList = implode(",", $allCatIds);

                $tc = $db->super_query(
                    "SELECT COUNT(*) AS cnt FROM " .
                        PREFIX .
                        "_forum_topics WHERE cat_id IN ({$catList}) AND is_deleted=0",
                );
                $pc = $db->super_query(
                    "SELECT COUNT(*) AS cnt FROM " .
                        PREFIX .
                        "_forum_posts p JOIN " .
                        PREFIX .
                        "_forum_topics t ON t.id=p.topic_id WHERE t.cat_id IN ({$catList}) AND p.is_deleted=0 AND t.is_deleted=0",
                );
                $lp = $db->super_query(
                    "SELECT p.id, p.user_id, p.date FROM " .
                        PREFIX .
                        "_forum_posts p JOIN " .
                        PREFIX .
                        "_forum_topics t ON t.id=p.topic_id WHERE t.cat_id IN ({$catList}) AND p.is_deleted=0 AND t.is_deleted=0 ORDER BY p.date DESC LIMIT 1",
                );

                $db->query(
                    "UPDATE " .
                        PREFIX .
                        "_forum_cats SET
                topic_count='" .
                        intval($tc["cnt"]) .
                        "',
                post_count='" .
                        intval($pc["cnt"]) .
                        "',
                last_post_id='" .
                        ($lp["id"] ? intval($lp["id"]) : "0") .
                        "'
                WHERE id='{$cid}'",
                );
                $processed++;
            }

            $response = [
                "success" => true,
                "step" => $step + 1,
                "total" => $total,
                "processed" => $offset + $processed,
                "done" => false,
                "message" =>
                    $lang['forum_ajax_tools_cats_processing'] .
                    ($offset + $processed) .
                    " / {$total}",
            ];
            break;

        // =========================================
        // KONU İSTATİSTİKLERİNİ ONAR
        // =========================================
        case "repair_topics":
            $topics = $db->super_query(
                "SELECT COUNT(*) AS cnt FROM " .
                    PREFIX .
                    "_forum_topics WHERE is_deleted=0",
            );
            $total = intval($topics["cnt"]);
            $offset = $step * $limit;

            if ($offset >= $total) {
                $response = [
                    "success" => true,
                    "step" => $step,
                    "total" => $total,
                    "processed" => $total,
                    "done" => true,
                    "message" => $lang['forum_ajax_tools_topics_rebuilt'],
                ];
                break;
            }

            // Önce ID'leri diziye çek
            $topicIds = [];
            $db->query(
                "SELECT id FROM " .
                    PREFIX .
                    "_forum_topics WHERE is_deleted=0 ORDER BY id ASC LIMIT {$offset},{$limit}",
            );
            while ($t = $db->get_row()) {
                $topicIds[] = intval($t["id"]);
            }

            // Sonra işle
            $processed = 0;
            foreach ($topicIds as $tid) {
                $rc = $db->super_query(
                    "SELECT COUNT(*) AS cnt FROM " .
                        PREFIX .
                        "_forum_posts WHERE topic_id='{$tid}' AND is_deleted=0",
                );
                $lp = $db->super_query(
                    "SELECT id, user_id, date FROM " .
                        PREFIX .
                        "_forum_posts WHERE topic_id='{$tid}' AND is_deleted=0 ORDER BY date DESC LIMIT 1",
                );

                $replies = max(0, intval($rc["cnt"]) - 1);
                $lastPostId = $lp["id"] ? intval($lp["id"]) : "0";
                $lastUserId = $lp["user_id"] ? intval($lp["user_id"]) : "0";
                $lastPostDate = $lp["date"]
                    ? "'" . $db->safesql($lp["date"]) . "'"
                    : "NULL";

                $db->query(
                    "UPDATE " .
                        PREFIX .
                        "_forum_topics SET
                replies='{$replies}',
                last_post_id='{$lastPostId}',
                last_user_id='{$lastUserId}',
                last_post_date={$lastPostDate}
                WHERE id='{$tid}'",
                );
                $processed++;
            }

            $response = [
                "success" => true,
                "step" => $step + 1,
                "total" => $total,
                "processed" => $offset + $processed,
                "done" => false,
                "message" =>
                    $lang['forum_ajax_tools_topics_processing'] .
                    ($offset + $processed) .
                    " / {$total}",
            ];
            break;

        // =========================================
        // ÖNBELLEK TEMİZLE
        // =========================================
        case "clear_cache":
            if (function_exists("dle_cache")) {
                dle_cache("forum", false);
            }
            if (function_exists("clear_cache")) {
                clear_cache();
            }
            $response = [
                "success" => true,
                "done" => true,
                "message" => $lang['forum_ajax_tools_cache_cleared'],
            ];
            break;

        default:
            $response["error"] = $lang['forum_ajax_unknown_action'];
    }
} catch (Exception $e) {
    $response = [
        "success" => false,
        "step" => $step,
        "total" => 0,
        "processed" => 0,
        "done" => false,
        "message" => "",
        "error" => $lang['forum_ajax_tools_error'] . $e->getMessage(),
    ];
} catch (Throwable $e) {
    $response = [
        "success" => false,
        "step" => $step,
        "total" => 0,
        "processed" => 0,
        "done" => false,
        "message" => "",
        "error" => $lang['forum_ajax_tools_sys_error'] . $e->getMessage(),
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();
