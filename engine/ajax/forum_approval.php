<?php
/*
=====================================================
 Forge Forum Engine — Onay Kuyruğu AJAX Controller
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_approval
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
    echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_unauthorized']]);
    exit;
}

header('Content-Type: application/json');
$action = isset($_POST['action']) ? totranslit($_POST['action']) : '';
$id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
$type   = isset($_POST['type']) ? totranslit($_POST['type']) : 'topic';
$mod_id = intval($member_id['user_id']);
$_IP    = $_SERVER['REMOTE_ADDR'];

function approvalLog($act,$target,$tid) {
    global $db,$mod_id,$_IP;
    $db->query("INSERT INTO ".PREFIX."_forum_logs (mod_id,action,target_type,topic_id,date,ip)
        VALUES ('{$mod_id}','{$act}','{$target}','{$tid}',NOW(),'".$db->safesql($_IP)."')");
}

switch($action) {

    // =========================================
    // ONAYLA (Approve)
    // =========================================
    case 'approve':
        if(!$id){ echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_invalid_id']]); exit; }

        if($type == 'topic') {
            $topic = $db->super_query("SELECT * FROM ".PREFIX."_forum_topics WHERE id='{$id}'");
            if(!$topic['id']){ echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_topic_not_found']]); exit; }

            $db->query("UPDATE ".PREFIX."_forum_topics SET is_approved=1 WHERE id='{$id}'");
            // Konuya ait onaysız mesajları da onayla
            $db->query("UPDATE ".PREFIX."_forum_posts SET is_approved=1 WHERE topic_id='{$id}' AND is_approved=0");

            // Kategori sayacını güncelle
            $cid = intval($topic['cat_id']);
            $postCnt = $db->super_query("SELECT COUNT(*) AS cnt FROM ".PREFIX."_forum_posts p JOIN ".PREFIX."_forum_topics t ON t.id=p.topic_id WHERE t.cat_id='{$cid}' AND p.is_deleted=0 AND t.is_deleted=0");
            $topicCnt= $db->super_query("SELECT COUNT(*) AS cnt FROM ".PREFIX."_forum_topics WHERE cat_id='{$cid}' AND is_deleted=0 AND is_approved=1");
            $db->query("UPDATE ".PREFIX."_forum_cats SET topic_count='".intval($topicCnt['cnt'])."', post_count='".intval($postCnt['cnt'])."' WHERE id='{$cid}'");

            // Kullanıcı sayaçlarını güncelle
            $uid = intval($topic['user_id']);
            $tc = $db->super_query("SELECT COUNT(*) AS cnt FROM ".PREFIX."_forum_topics WHERE user_id='{$uid}' AND is_deleted=0 AND is_approved=1");
            $pc = $db->super_query("SELECT COUNT(*) AS cnt FROM ".PREFIX."_forum_posts WHERE user_id='{$uid}' AND is_deleted=0 AND is_approved=1");
            $db->query("UPDATE ".PREFIX."_users SET forum_post_count='".intval($pc['cnt'])."' WHERE user_id='{$uid}'");

            approvalLog('approve_topic','topic',$id);
        } else {
            $post = $db->super_query("SELECT * FROM ".PREFIX."_forum_posts WHERE id='{$id}'");
            if(!$post['id']){ echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_post_not_found']]); exit; }

            $db->query("UPDATE ".PREFIX."_forum_posts SET is_approved=1 WHERE id='{$id}'");

            // Konu istatistiklerini güncelle
            $tid = intval($post['topic_id']);
            $replies = $db->super_query("SELECT COUNT(*) AS cnt FROM ".PREFIX."_forum_posts WHERE topic_id='{$tid}' AND is_deleted=0 AND is_approved=1");
            $lastPost= $db->super_query("SELECT id,user_id,date FROM ".PREFIX."_forum_posts WHERE topic_id='{$tid}' AND is_deleted=0 AND is_approved=1 ORDER BY date DESC LIMIT 1");
            $replies = max(0, intval($replies['cnt'])-1);
            $db->query("UPDATE ".PREFIX."_forum_topics SET
                replies='{$replies}',
                last_post_id='".($lastPost['id']?intval($lastPost['id']):'0')."',
                last_user_id='".($lastPost['user_id']?intval($lastPost['user_id']):'0')."',
                last_post_date='".($lastPost['date']?$db->safesql($lastPost['date']):'NULL')."'
                WHERE id='{$tid}'");

            // Kullanıcı sayacı
            $uid = intval($post['user_id']);
            $pc = $db->super_query("SELECT COUNT(*) AS cnt FROM ".PREFIX."_forum_posts WHERE user_id='{$uid}' AND is_deleted=0 AND is_approved=1");
            $db->query("UPDATE ".PREFIX."_users SET forum_post_count='".intval($pc['cnt'])."' WHERE user_id='{$uid}'");

            approvalLog('approve_post','post',$id);
        }
        echo json_encode(['success'=>true,'message'=>$lang['forum_ajax_approval_approved']]);
        break;

    // =========================================
    // REDDET VE SİL (Hard Delete)
    // =========================================
    case 'reject':
        if(!$id){ echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_invalid_id']]); exit; }

        if($type == 'topic') {
            $tid = $id;
            $db->query("DELETE FROM ".PREFIX."_forum_posts WHERE topic_id='{$tid}'");
            $db->query("DELETE FROM ".PREFIX."_forum_likes WHERE post_id IN (SELECT id FROM ".PREFIX."_forum_posts WHERE topic_id='{$tid}')");
            $db->query("DELETE FROM ".PREFIX."_forum_polls WHERE topic_id='{$tid}'");
            $db->query("DELETE FROM ".PREFIX."_forum_topic_tags WHERE topic_id='{$tid}'");
            $db->query("DELETE FROM ".PREFIX."_forum_subs WHERE topic_id='{$tid}'");
            $db->query("DELETE FROM ".PREFIX."_forum_topics WHERE id='{$tid}'");
            approvalLog('reject_topic','topic',$tid);
        } else {
            $pid = $id;
            $db->query("DELETE FROM ".PREFIX."_forum_likes WHERE post_id='{$pid}'");
            $db->query("DELETE FROM ".PREFIX."_forum_posts WHERE id='{$pid}'");
            approvalLog('reject_post','post',$pid);
        }
        echo json_encode(['success'=>true,'message'=>$lang['forum_ajax_approval_rejected']]);
        break;

    // =========================================
    // IP BAN + SİL
    // =========================================
    case 'ipban':
        if(!$id){ echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_invalid_id']]); exit; }

        if($type == 'topic') {
            $item = $db->super_query("SELECT t.ip AS tip, t.user_id FROM ".PREFIX."_forum_topics t WHERE t.id='{$id}'");
        } else {
            $item = $db->super_query("SELECT p.ip AS tip, p.user_id FROM ".PREFIX."_forum_posts p WHERE p.id='{$id}'");
        }

        $banIP = $item['tip'];
        $banUID= intval($item['user_id']);

        // IP'yi DLE ban listesine ekle
        if($banIP) {
            $db->query("INSERT IGNORE INTO ".PREFIX."_banned (ip, users_id, date, reason) VALUES ('".$db->safesql($banIP)."','{$banUID}',NOW(),'Forum: Onaylanmamış spam içerik')");
        }

        // İçeriği sil
        if($type == 'topic') {
            $tid = $id;
            $db->query("DELETE FROM ".PREFIX."_forum_posts WHERE topic_id='{$tid}'");
            $db->query("DELETE FROM ".PREFIX."_forum_topics WHERE id='{$tid}'");
        } else {
            $db->query("DELETE FROM ".PREFIX."_forum_posts WHERE id='{$id}'");
        }

        approvalLog('ipban_'.$type,$type,$id);
        echo json_encode(['success'=>true,'message'=>sprintf($lang['forum_ajax_approval_banned'], $banIP)]);
        break;

    default:
        echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_unknown_action']]);
}
}
exit;
