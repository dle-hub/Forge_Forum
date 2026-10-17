<?php
/*
=====================================================
 Forge Forum Engine — AJAX Kullanıcı Takip (Toggle)
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_follow
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

$__follow = $db->super_query("SELECT value FROM " . PREFIX . "_forum_settings WHERE name='enable_follow'");
if ($__follow && isset($__follow["value"]) && $__follow["value"] === "0") {
    echo json_encode(["success" => false, "message" => $lang["forum_access_denied"]]);
    exit();
}

$target_id = isset($_POST["target_id"]) ? intval($_POST["target_id"]) : 0;
$uid = intval($member_id["user_id"]);

if ($target_id < 1 || $target_id === $uid) {
    echo json_encode(["success" => false, "message" => $lang["forum_follow_self"]]);
    exit();
}

$target = $db->super_query(
    "SELECT user_id FROM " . USERPREFIX . "_users WHERE user_id='{$target_id}'",
);
if (!$target || empty($target["user_id"])) {
    echo json_encode(["success" => false, "message" => $lang["forum_ajax_invalid_msg"]]);
    exit();
}

$exists = $db->super_query(
    "SELECT id FROM " . PREFIX . "_forum_follows WHERE user_id='{$uid}' AND target_id='{$target_id}'",
);

$followers = 0;
if (!empty($exists["id"])) {
    $db->query(
        "DELETE FROM " . PREFIX . "_forum_follows WHERE user_id='{$uid}' AND target_id='{$target_id}'",
    );
    $following = false;
    $msg = $lang["forum_follow_unfollowed"] ?? "Takip bırakıldı.";
} else {
    $db->query(
        "INSERT IGNORE INTO " . PREFIX . "_forum_follows (user_id, target_id, date) VALUES ('{$uid}', '{$target_id}', NOW())",
    );
    $following = true;
    $msg = $lang["forum_follow_followed"] ?? "Kullanıcı takip ediliyor.";
}

$fc = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_follows WHERE target_id='{$target_id}'",
);
$followers = intval($fc["cnt"]);

echo json_encode([
    "success" => true,
    "following" => $following,
    "followers" => $followers,
    "label" => $following
        ? $lang["forum_follow_unfollow"] ?? "Takibi Bırak"
        : $lang["forum_follow_follow"] ?? "Takip Et",
    "message" => $msg,
]);
exit();
