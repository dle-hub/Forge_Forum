<?php
/*
=====================================================
 Forge Forum Engine — AJAX Çözüldü İşareti (toggle)
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_solve
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
if ($topic_id < 1) {
    echo json_encode(["status" => "error", "msg" => $lang["forum_ajax_invalid_msg"]]);
    exit();
}

// Özellik kapalıysa reddet
$__solved = $db->super_query("SELECT value FROM " . PREFIX . "_forum_settings WHERE name='enable_solved'");
if ($__solved && isset($__solved["value"]) && $__solved["value"] === "0") {
    echo json_encode(["status" => "error", "msg" => $lang["forum_access_denied"]]);
    exit();
}

$topic = $db->super_query(
    "SELECT id, user_id, cat_id, is_solved, is_deleted FROM " . PREFIX . "_forum_topics WHERE id='{$topic_id}'",
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

$new = intval($topic["is_solved"]) ? 0 : 1;
$db->query("UPDATE " . PREFIX . "_forum_topics SET is_solved='{$new}' WHERE id='{$topic_id}'");

echo json_encode([
    "status" => "ok",
    "solved" => $new,
    "label" => $new
        ? $lang["forum_unsolve"] ?? "Çözüldü işaretini kaldır"
        : $lang["forum_solve"] ?? "Çözüldü olarak işaretle",
]);
exit();
