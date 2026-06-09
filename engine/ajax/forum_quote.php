<?php
/*
=====================================================
 Forge Forum Engine — AJAX Alıntı Sistemi
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_quote
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
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
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_login']]);
    exit;
}
if( !$_POST['user_hash'] || $_POST['user_hash'] !== $dle_login_hash ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_csrf']]);
    exit;
}

$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
if( !$post_id ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_invalid_msg']]);
    exit;
}

// Mesajı + yazarı çek
$post = $db->super_query(
    "SELECT p.*, u.name AS author_name, t.is_locked, t.is_deleted AS topic_deleted
     FROM " . PREFIX . "_forum_posts p
     LEFT JOIN " . PREFIX . "_users u ON u.user_id = p.user_id
     LEFT JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
     WHERE p.id = '{$post_id}'"
);

if( !$post || !$post['id'] ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_post_not_found']]);
    exit;
}
if( $post['is_deleted'] || $post['topic_deleted'] ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_quote_post_deleted']]);
    exit;
}
if( $post['is_locked'] && $member_id['user_group'] > 2 ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_quote_topic_locked']]);
    exit;
}

// Mesajı BBCode/plain text'e çevir (HTML taglarını temizle)
$text = strip_tags( stripslashes( $post['text'] ) );
$text = trim( $text );

// Çok uzunsa kısalt
if( mb_strlen( $text ) > 2000 ) {
    $text = mb_substr( $text, 0, 2000 ) . '...';
}

$author = $post['author_name'] ?: $lang['forum_ajax_quote_guest'];

// Alıntı formatı
$quote = "[quote={$author}]{$text}[/quote]\n";

echo json_encode(['status'=>'success','text'=>$quote]);
exit;
