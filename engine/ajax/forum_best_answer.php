<?php
/*
=====================================================
 Forge Forum Engine — AJAX En İyi Cevap Seçimi
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_best_answer
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (!is_array($lang)) {
    $lang = [];
}
$forum_lang_dir = $config["langs"] ? totranslit($config["langs"]) : "Turkish";
if (file_exists(ROOT_DIR . "/language/" . $forum_lang_dir . "/forum.lng")) {
    include_once ROOT_DIR . "/language/" . $forum_lang_dir . "/forum.lng";
} elseif (file_exists(ROOT_DIR . "/language/Turkish/forum.lng")) {
    include_once ROOT_DIR . "/language/Turkish/forum.lng";
}

header("Content-Type: application/json; charset=utf-8");

if (!$is_logged) {
    echo json_encode(["status" => "error", "msg" => $lang["forum_ajax_login"]]);
    exit();
}
if (!isset($_POST["user_hash"]) || $_POST["user_hash"] !== $dle_login_hash) {
    echo json_encode(["status" => "error", "msg" => $lang["forum_ajax_csrf"]]);
    exit();
}

$topic_id = isset($_POST["topic_id"]) ? intval($_POST["topic_id"]) : 0;
$post_id = isset($_POST["post_id"]) ? intval($_POST["post_id"]) : 0;
if ($topic_id < 1 || $post_id < 1) {
    echo json_encode(["status" => "error", "msg" => $lang["forum_ajax_invalid_msg"]]);
    exit();
}

$__best = $db->super_query("SELECT value FROM " . PREFIX . "_forum_settings WHERE name='enable_best_answer'");
if ($__best && isset($__best["value"]) && $__best["value"] === "0") {
    echo json_encode(["status" => "error", "msg" => $lang["forum_access_denied"]]);
    exit();
}

$topic = $db->super_query(
    "SELECT id, user_id, cat_id, is_locked, is_deleted, best_post_id FROM " . PREFIX . "_forum_topics WHERE id='{$topic_id}'",
);
if (!$topic || !$topic["id"] || $topic["is_deleted"]) {
    echo json_encode(["status" => "error", "msg" => $lang["forum_ajax_topic_not_found"]]);
    exit();
}

$uid = intval($member_id["user_id"]);
$grp = intval($member_id["user_group"]);

// Yetki: konu sahibi veya moderatör/yönetici (ya da kategori moderatörü)
if ($uid !== intval($topic["user_id"]) && $grp > 2) {
    include_once DLEPlugins::Check(ENGINE_DIR . "/modules/forum/editor_helpers.php");
    $is_cat_mod = function_exists("forum_user_is_cat_mod")
        ? forum_user_is_cat_mod($uid, intval($topic["cat_id"]))
        : false;
    if (!$is_cat_mod) {
        echo json_encode(["status" => "error", "msg" => $lang["forum_access_denied"]]);
        exit();
    }
}

// Mesaj bu konuya mı ait?
$post = $db->super_query(
    "SELECT id FROM " . PREFIX . "_forum_posts WHERE id='{$post_id}' AND topic_id='{$topic_id}' AND is_deleted=0",
);
if (!$post || !$post["id"]) {
    echo json_encode(["status" => "error", "msg" => $lang["forum_ajax_post_not_found"]]);
    exit();
}

// Toggle: aynı mesaj tekrar seçilirse kaldır
$current = intval($topic["best_post_id"]);
$new = $current === $post_id ? 0 : $post_id;

$db->query("UPDATE " . PREFIX . "_forum_topics SET best_post_id='{$new}' WHERE id='{$topic_id}'");
if ($new > 0) {
    $db->query("UPDATE " . PREFIX . "_forum_topics SET is_solved='1' WHERE id='{$topic_id}'");
} else {
    $db->query("UPDATE " . PREFIX . "_forum_topics SET is_solved='0' WHERE id='{$topic_id}'");
}

echo json_encode([
    "status" => "ok",
    "best_post_id" => $new,
    "msg" => $new > 0
        ? $lang["forum_best_saved"] ?? "En iyi cevap seçildi."
        : $lang["forum_best_cleared"] ?? "En iyi cevap kaldırıldı.",
]);
exit();
