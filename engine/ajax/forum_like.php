<?php
/*
=====================================================
 Forge Forum Engine — AJAX Beğeni Sistemi (Toggle)
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_like
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

// Forum ayarlarını çek
$forum_cfg = [];
$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
while ($row = $db->get_row()) {
    $forum_cfg[$row["name"]] = stripslashes($row["value"]);
}
$forum_cfg = array_merge(
    [
        "enable_notifications" => 1,
        "enable_ranks" => 1,
        "points_per_like" => 2,
    ],
    $forum_cfg
);

include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/rank_helpers.php",
);

$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$type    = isset($_POST['type']) ? totranslit($_POST['type']) : 'like'; // like veya dislike
if( !in_array( $type, ['like','dislike'] ) ) $type = 'like';
if( !$post_id ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_invalid_msg']]);
    exit;
}

$uid = intval( $member_id['user_id'] );

// Mesajı kontrol et
$post = $db->super_query( "SELECT id, user_id, topic_id, likes, dislikes FROM " . PREFIX . "_forum_posts WHERE id='{$post_id}'" );
if( !$post || !$post['id'] ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_post_not_found']]);
    exit;
}

// Kendi mesajını beğenemez
if( intval($post['user_id']) === $uid ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_like_self']]);
    exit;
}

$likes    = intval( $post['likes'] );
$dislikes = intval( $post['dislikes'] );
$author_id= intval( $post['user_id'] );
$tid      = intval( $post['topic_id'] );

// Daha önce bu türde oy verdi mi?
$existing = $db->super_query(
    "SELECT id FROM " . PREFIX . "_forum_likes
     WHERE post_id='{$post_id}' AND user_id='{$uid}' AND type='{$type}'" );

if( $existing['id'] ) {
    // TOGGLE OFF: Oyu geri al
    $db->query( "DELETE FROM " . PREFIX . "_forum_likes WHERE id='{$existing['id']}'" );

    if( $type === 'like' ) {
        $newLikes = max(0, $likes - 1);
        $db->query( "UPDATE " . PREFIX . "_forum_posts SET likes='{$newLikes}' WHERE id='{$post_id}'" );
        // Puan azaltma
        $pts = intval($forum_cfg['points_per_like']) ?: 2;
        $db->query( "UPDATE " . PREFIX . "_users SET forum_points = GREATEST(0, forum_points - {$pts}) WHERE user_id='{$author_id}'" );
        forum_sync_user_rank($author_id);
        echo json_encode(['status'=>'unliked','type'=>'like','new_count'=>$newLikes,'new_dislikes'=>$dislikes]);
    } else {
        $newDislikes = max(0, $dislikes - 1);
        $db->query( "UPDATE " . PREFIX . "_forum_posts SET dislikes='{$newDislikes}' WHERE id='{$post_id}'" );
        echo json_encode(['status'=>'unliked','type'=>'dislike','new_count'=>$likes,'new_dislikes'=>$newDislikes]);
    }
    exit;
} else {
    // TOGGLE ON: Oy ver
    // Karşıt oyu varsa veritabanından önceden temizleyelim ki benzersiz anahtar hatası (Duplicate Key) oluşmasın!
    $opposite_type = ($type === 'like') ? 'dislike' : 'like';
    $opposite = $db->super_query( "SELECT id FROM " . PREFIX . "_forum_likes WHERE post_id='{$post_id}' AND user_id='{$uid}' AND type='{$opposite_type}'" );
    
    if( $opposite['id'] ) {
        $db->query( "DELETE FROM " . PREFIX . "_forum_likes WHERE id='{$opposite['id']}'" );
        if( $type === 'like' ) {
            $dislikes = max(0, $dislikes - 1);
        } else {
            $likes = max(0, $likes - 1);
        }
    }

    $db->query( "INSERT INTO " . PREFIX . "_forum_likes (post_id, user_id, type, date)
        VALUES ('{$post_id}','{$uid}','{$type}',NOW())" );

    if( $type === 'like' ) {
        $newLikes = $likes + 1;
        $db->query( "UPDATE " . PREFIX . "_forum_posts SET likes='{$newLikes}', dislikes='{$dislikes}' WHERE id='{$post_id}'" );
        // Puan ekle
        $pts = intval($forum_cfg['points_per_like']) ?: 2;
        $db->query( "UPDATE " . PREFIX . "_users SET forum_points = forum_points + {$pts} WHERE user_id='{$author_id}'" );
        forum_sync_user_rank($author_id);
        // Bildirim
        if( $forum_cfg['enable_notifications'] ) {
            $db->query( "INSERT INTO " . PREFIX . "_forum_notifs (user_id, from_user, type, topic_id, post_id, date)
                VALUES ('{$author_id}','{$uid}','like','{$tid}','{$post_id}',NOW())" );
        }
        echo json_encode(['status'=>'liked','type'=>'like','new_count'=>$newLikes,'new_dislikes'=>$dislikes]);
    } else {
        $newDislikes = $dislikes + 1;
        $db->query( "UPDATE " . PREFIX . "_forum_posts SET dislikes='{$newDislikes}', likes='{$likes}' WHERE id='{$post_id}'" );
        echo json_encode(['status'=>'liked','type'=>'dislike','new_count'=>$likes,'new_dislikes'=>$newDislikes]);
    }
    exit;
}
