<?php
/*
=====================================================
 Forge Forum Engine — AJAX Kategori Aboneliği (Toggle)
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_cat_subscribe
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
if (isset($forum_cfg['enable_cat_subscribe']) && !intval($forum_cfg['enable_cat_subscribe'])) {
    echo json_encode(['success'=>false,'message'=>$lang['forum_cat_sub_disabled']]);
    exit;
}

$cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
if (!$cat_id) {
    echo json_encode(['success'=>false,'message'=>$lang['forum_cat_not_found']]);
    exit;
}

$cat = $db->super_query("SELECT id FROM " . PREFIX . "_forum_cats WHERE id='{$cat_id}'");
if (!$cat || empty($cat['id'])) {
    echo json_encode(['success'=>false,'message'=>$lang['forum_cat_not_found']]);
    exit;
}

$uid = intval($member_id['user_id']);

$exists = $db->super_query("SELECT id FROM " . PREFIX . "_forum_cat_subs WHERE cat_id='{$cat_id}' AND user_id='{$uid}'");

if (!empty($exists['id'])) {
    $db->query("DELETE FROM " . PREFIX . "_forum_cat_subs WHERE cat_id='{$cat_id}' AND user_id='{$uid}'");
    echo json_encode(['success'=>true, 'subscribed'=>false, 'message'=>$lang['forum_cat_sub_unsubscribed']]);
} else {
    $db->query("INSERT INTO " . PREFIX . "_forum_cat_subs (cat_id, user_id, date) VALUES ('{$cat_id}', '{$uid}', NOW())");
    echo json_encode(['success'=>true, 'subscribed'=>true, 'message'=>$lang['forum_cat_sub_subscribed']]);
}
exit;
