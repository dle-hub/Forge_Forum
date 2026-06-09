<?php
/*
=====================================================
 Forge Forum Engine — Çöp Kutusu AJAX Controller
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_trash
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

if( $member_id['user_group'] > 2 ) {
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_unauthorized']]);
    exit;
}

header('Content-Type: application/json');
$action = isset($_POST['action']) ? totranslit($_POST['action']) : '';
$id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
$type   = isset($_POST['type']) ? totranslit($_POST['type']) : 'topic';
$mod_id = intval($member_id['user_id']);
$_IP    = $_SERVER['REMOTE_ADDR'];

// Log helper
function trashLog($target, $targetId, $act) {
    global $db, $mod_id, $_IP;
    $db->query("INSERT INTO ".PREFIX."_forum_logs (mod_id, action, target_type, topic_id, date, ip)
        VALUES ('{$mod_id}','{$act}','{$target}','{$targetId}',NOW(),'".$db->safesql($_IP)."')");
}

switch($action) {

    // =========================================
    // GERİ YÜKLE (Restore)
    // =========================================
    case 'restore':
        if(!$id){ echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_invalid_id']]); exit; }

        if($type == 'topic') {
            $db->query("UPDATE ".PREFIX."_forum_topics SET is_deleted=0 WHERE id='{$id}'");
            // Mesajları da geri yükle
            $db->query("UPDATE ".PREFIX."_forum_posts SET is_deleted=0 WHERE topic_id='{$id}'");
            trashLog('topic', $id, 'restore_topic');
        } else {
            $db->query("UPDATE ".PREFIX."_forum_posts SET is_deleted=0 WHERE id='{$id}'");
            // Konu da silinmişse onu da geri yükle
            $post = $db->super_query("SELECT topic_id FROM ".PREFIX."_forum_posts WHERE id='{$id}'");
            if($post['topic_id']) {
                $db->query("UPDATE ".PREFIX."_forum_topics SET is_deleted=0 WHERE id='".intval($post['topic_id'])."'");
            }
            trashLog('post', $id, 'restore_post');
        }
        echo json_encode(['success'=>true,'message'=>$lang['forum_ajax_trash_restored']]);
        break;

    // =========================================
    // KALICI SİL (Hard Delete)
    // =========================================
    case 'hard_delete':
        if(!$id){ echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_invalid_id']]); exit; }

        if($type == 'topic') {
            $tid = $id;
            // Mesajları sil
            $db->query("DELETE FROM ".PREFIX."_forum_posts WHERE topic_id='{$tid}'");
            // Beğenileri sil
            $db->query("DELETE FROM ".PREFIX."_forum_likes WHERE post_id IN (SELECT id FROM ".PREFIX."_forum_posts WHERE topic_id='{$tid}')");
            // Anket varsa sil
            $poll = $db->super_query("SELECT id FROM ".PREFIX."_forum_polls WHERE topic_id='{$tid}'");
            if($poll['id']) {
                $db->query("DELETE FROM ".PREFIX."_forum_poll_votes WHERE poll_id='".intval($poll['id'])."'");
                $db->query("DELETE FROM ".PREFIX."_forum_poll_options WHERE poll_id='".intval($poll['id'])."'");
                $db->query("DELETE FROM ".PREFIX."_forum_polls WHERE id='".intval($poll['id'])."'");
            }
            // Etiket bağlarını sil
            $db->query("DELETE FROM ".PREFIX."_forum_topic_tags WHERE topic_id='{$tid}'");
            // Abonelikleri sil
            $db->query("DELETE FROM ".PREFIX."_forum_subs WHERE topic_id='{$tid}'");
            // Konuyu sil
            $db->query("DELETE FROM ".PREFIX."_forum_topics WHERE id='{$tid}'");
            trashLog('topic', $tid, 'hard_delete_topic');
        } else {
            $pid = $id;
            // Beğenileri sil
            $db->query("DELETE FROM ".PREFIX."_forum_likes WHERE post_id='{$pid}'");
            // Mesajı sil
            $db->query("DELETE FROM ".PREFIX."_forum_posts WHERE id='{$pid}'");
            trashLog('post', $pid, 'hard_delete_post');
        }
        echo json_encode(['success'=>true,'message'=>$lang['forum_ajax_trash_deleted_perm']]);
        break;

    // =========================================
    // ÇÖPÜ BOŞALT (Tümünü Hard Delete)
    // =========================================
    case 'empty_trash':
        $subtype = isset($_POST['subtype']) ? totranslit($_POST['subtype']) : 'all';

        if($subtype == 'topics' || $subtype == 'all') {
            $db->query("SELECT id FROM ".PREFIX."_forum_topics WHERE is_deleted=1");
            while($t = $db->get_row()) {
                $tid = intval($t['id']);
                $db->query("DELETE FROM ".PREFIX."_forum_posts WHERE topic_id='{$tid}'");
                $db->query("DELETE FROM ".PREFIX."_forum_likes WHERE post_id IN (SELECT id FROM ".PREFIX."_forum_posts WHERE topic_id='{$tid}')");
                $db->query("DELETE FROM ".PREFIX."_forum_polls WHERE topic_id='{$tid}'");
                $db->query("DELETE FROM ".PREFIX."_forum_topic_tags WHERE topic_id='{$tid}'");
                $db->query("DELETE FROM ".PREFIX."_forum_subs WHERE topic_id='{$tid}'");
                $db->query("DELETE FROM ".PREFIX."_forum_topics WHERE id='{$tid}'");
            }
        }
        if($subtype == 'posts' || $subtype == 'all') {
            $db->query("DELETE FROM ".PREFIX."_forum_likes WHERE post_id IN (SELECT id FROM ".PREFIX."_forum_posts WHERE is_deleted=1)");
            $db->query("DELETE FROM ".PREFIX."_forum_posts WHERE is_deleted=1");
        }
        trashLog('system', 0, 'empty_trash_'.$subtype);
        echo json_encode(['success'=>true,'message'=>$lang['forum_ajax_trash_cleared']]);
        break;

    default:
        echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_unknown_action']]);
}
}
exit;
