<?php
/*
=====================================================
 Forge Forum Engine — AJAX Mesaj Taslakları (Drafts)
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_draft
 actions: save | get | delete
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
    echo json_encode(["success" => false, "message" => $lang["forum_ajax_login"]]);
    exit();
}
if (!isset($_POST["user_hash"]) || $_POST["user_hash"] !== $dle_login_hash) {
    echo json_encode(["success" => false, "message" => $lang["forum_ajax_csrf"]]);
    exit();
}

$__drafts = $db->super_query("SELECT value FROM " . PREFIX . "_forum_settings WHERE name='enable_drafts'");
if ($__drafts && isset($__drafts["value"]) && $__drafts["value"] === "0") {
    echo json_encode(["success" => false, "message" => $lang["forum_access_denied"]]);
    exit();
}

$uid = intval($member_id["user_id"]);
$action = isset($_POST["action"]) ? totranslit($_POST["action"]) : "get";
$type = isset($_POST["type"]) ? totranslit($_POST["type"]) : "reply";
if (!in_array($type, ["new_topic", "reply"], true)) {
    $type = "reply";
}
$topic_id = isset($_POST["topic_id"]) ? intval($_POST["topic_id"]) : 0;
$cat_id = isset($_POST["cat_id"]) ? intval($_POST["cat_id"]) : 0;

if ($action === "save") {
    $text = isset($_POST["text"]) ? (string) $_POST["text"] : "";
    $title = isset($_POST["title"]) ? trim((string) $_POST["title"]) : "";

    // Boş taslak kaydetme
    if (trim(strip_tags($text)) === "" && $title === "") {
        $db->query(
            "DELETE FROM " . PREFIX . "_forum_drafts WHERE user_id='{$uid}' AND type='{$type}' AND topic_id='{$topic_id}'",
        );
        echo json_encode(["success" => true, "saved" => false]);
        exit();
    }

    if (mb_strlen($text) > 100000) {
        $text = mb_substr($text, 0, 100000);
    }
    $safe_text = $db->safesql($text);
    $safe_title = $db->safesql(mb_substr($title, 0, 255));
    $now = date("Y-m-d H:i:s");

    $db->query(
        "INSERT INTO " . PREFIX . "_forum_drafts (user_id, type, topic_id, cat_id, title, text, date)
         VALUES ('{$uid}', '{$type}', '{$topic_id}', '{$cat_id}', '{$safe_title}', '{$safe_text}', '{$now}')
         ON DUPLICATE KEY UPDATE cat_id=VALUES(cat_id), title=VALUES(title), text=VALUES(text), date=VALUES(date)",
    );
    echo json_encode(["success" => true, "saved" => true, "time" => $now]);
    exit();
}

if ($action === "delete") {
    $db->query(
        "DELETE FROM " . PREFIX . "_forum_drafts WHERE user_id='{$uid}' AND type='{$type}' AND topic_id='{$topic_id}'",
    );
    echo json_encode(["success" => true]);
    exit();
}

// get
$row = $db->super_query(
    "SELECT title, text, cat_id, date FROM " . PREFIX . "_forum_drafts
     WHERE user_id='{$uid}' AND type='{$type}' AND topic_id='{$topic_id}'",
);
echo json_encode([
    "success" => true,
    "draft" => $row && !empty($row["date"])
        ? [
            "title" => stripslashes($row["title"]),
            "text" => stripslashes($row["text"]),
            "cat_id" => intval($row["cat_id"]),
            "date" => $row["date"],
        ]
        : null,
]);
exit();
