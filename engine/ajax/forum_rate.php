<?php
/*
=====================================================
 Forge Forum Engine — AJAX Konu Puanlama (5 yıldız)
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_rate
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

$__rate = $db->super_query("SELECT value FROM " . PREFIX . "_forum_settings WHERE name='enable_topic_rating'");
if ($__rate && isset($__rate["value"]) && $__rate["value"] === "0") {
    echo json_encode(["status" => "error", "msg" => $lang["forum_access_denied"]]);
    exit();
}

include_once DLEPlugins::Check(ENGINE_DIR . "/modules/forum/editor_helpers.php");

$forum_cfg = [];
$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
while ($row = $db->get_row()) {
    $forum_cfg[$row["name"]] = stripslashes($row["value"]);
}

$topic_id = isset($_POST["topic_id"]) ? intval($_POST["topic_id"]) : 0;
$rating = isset($_POST["rating"]) ? intval($_POST["rating"]) : 0;

if ($topic_id < 1 || $rating < 1 || $rating > 5) {
    echo json_encode(["status" => "error", "msg" => $lang["forum_ajax_invalid_msg"]]);
    exit();
}

$topic = $db->super_query(
    "SELECT id, user_id, is_locked, is_deleted FROM " . PREFIX . "_forum_topics WHERE id='{$topic_id}'",
);
if (!$topic || !$topic["id"] || $topic["is_deleted"]) {
    echo json_encode(["status" => "error", "msg" => $lang["forum_ajax_topic_not_found"]]);
    exit();
}
if ($topic["is_locked"] && intval($member_id["user_group"]) > 2) {
    echo json_encode(["status" => "error", "msg" => $lang["forum_ajax_locked"] ?? ""]);
    exit();
}

$uid = intval($member_id["user_id"]);

// Kendi konusunu puanlayamaz
if (intval($topic["user_id"]) === $uid) {
    echo json_encode(["status" => "error", "msg" => $lang["forum_rating_self"]]);
    exit();
}

$db->query(
    "INSERT INTO " . PREFIX . "_forum_topic_ratings (topic_id, user_id, rating, date)
     VALUES ('{$topic_id}', '{$uid}', '{$rating}', NOW())
     ON DUPLICATE KEY UPDATE rating = VALUES(rating), date = NOW()",
);

$rrow = $db->super_query(
    "SELECT COUNT(*) AS cnt, AVG(rating) AS avg FROM " . PREFIX . "_forum_topic_ratings WHERE topic_id='{$topic_id}'",
);
$count = intval($rrow["cnt"]);
$avg = $count > 0 ? round(floatval($rrow["avg"]), 1) : 0;

echo json_encode([
    "status" => "ok",
    "avg" => $avg,
    "count" => $count,
    "your" => $rating,
    "stars_html" => forum_rating_stars_html($avg),
    "msg" => $lang["forum_rating_saved"] ?? "",
]);
exit();
