<?php
/*
=====================================================
 Forge Forum Engine — AJAX Okundu İşaretle
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_read
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

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

$uid = intval($member_id['user_id']);
$cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;

if ($cat_id > 0) {
    $db->query(
        "INSERT INTO " . PREFIX . "_forum_readlog (user_id, topic_id, last_read_post, date)
         SELECT '{$uid}', t.id, t.last_post_id, NOW()
         FROM " . PREFIX . "_forum_topics t
         WHERE t.cat_id = '{$cat_id}' AND t.is_deleted = 0
         ON DUPLICATE KEY UPDATE last_read_post = VALUES(last_read_post), date = NOW()",
    );
} else {
    $db->query(
        "INSERT INTO " . PREFIX . "_forum_readlog (user_id, topic_id, last_read_post, date)
         SELECT '{$uid}', t.id, t.last_post_id, NOW()
         FROM " . PREFIX . "_forum_topics t
         WHERE t.is_deleted = 0
         ON DUPLICATE KEY UPDATE last_read_post = VALUES(last_read_post), date = NOW()",
    );
}

echo json_encode(['success'=>true]);
exit;
