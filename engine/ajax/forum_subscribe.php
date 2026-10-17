<?php
/*
=====================================================
 Forge Forum Engine — AJAX Abonelik Sistemi (Toggle)
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_subscribe
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

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

header('Content-Type: application/json; charset=utf-8');

if( !$is_logged ) {
    echo json_encode(['success'=>false,'message'=>$lang['forum_ajax_login']]);
    exit;
}
if( !isset($_POST['user_hash']) || $_POST['user_hash'] !== $dle_login_hash ) {
    echo json_encode(['success'=>false,'message'=>$lang['forum_ajax_csrf']]);
    exit;
}

// Forum ayarları
$forum_cfg = [];
$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
while ($row = $db->get_row()) {
    $forum_cfg[$row["name"]] = stripslashes($row["value"]);
}
if (isset($forum_cfg['enable_subscriptions']) && !intval($forum_cfg['enable_subscriptions'])) {
    echo json_encode(['success'=>false,'message'=>$lang['forum_sub_disabled']]);
    exit;
}

$topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
if (!$topic_id) {
    echo json_encode(['success'=>false,'message'=>$lang['forum_ajax_topic_not_found']]);
    exit;
}

$topic = $db->super_query("SELECT id FROM " . PREFIX . "_forum_topics WHERE id='{$topic_id}'");
if (!$topic || empty($topic['id'])) {
    echo json_encode(['success'=>false,'message'=>$lang['forum_ajax_topic_not_found']]);
    exit;
}

$uid = intval($member_id['user_id']);

$exists = $db->super_query("SELECT id FROM " . PREFIX . "_forum_subs WHERE topic_id='{$topic_id}' AND user_id='{$uid}'");

if (!empty($exists['id'])) {
    $db->query("DELETE FROM " . PREFIX . "_forum_subs WHERE topic_id='{$topic_id}' AND user_id='{$uid}'");
    echo json_encode(['success'=>true, 'subscribed'=>false, 'message'=>$lang['forum_sub_unsubscribed']]);
} else {
    $db->query("INSERT INTO " . PREFIX . "_forum_subs (topic_id, user_id, date) VALUES ('{$topic_id}', '{$uid}', NOW())");
    echo json_encode(['success'=>true, 'subscribed'=>true, 'message'=>$lang['forum_sub_subscribed']]);
}
exit;
