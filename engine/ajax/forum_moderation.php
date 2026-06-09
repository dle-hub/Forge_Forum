<?php
/*
=====================================================
 Forge Forum Engine — Moderatör Araçları AJAX
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_moderation
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

if( $member_id['user_group'] > 2 && (!isset($_POST['action']) || $_POST['action'] !== 'bump') ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_unauthorized']]);
    exit;
}
if( !$_POST['user_hash'] || $_POST['user_hash'] !== $dle_login_hash ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_csrf']]);
    exit;
}

$action   = isset($_POST['action']) ? totranslit($_POST['action']) : '';
$topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
$mod_id   = intval( $member_id['user_id'] );
$_IP      = $_SERVER['REMOTE_ADDR'];

if( !$topic_id ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_topic_not_found']]);
    exit;
}

$topic = $db->super_query( "SELECT * FROM " . PREFIX . "_forum_topics WHERE id='{$topic_id}'" );
if( !$topic || !$topic['id'] ) {
    echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_topic_not_found']]);
    exit;
}

// Log
function modLog($act,$tid) {
    global $db,$mod_id,$_IP;
    $db->query("INSERT INTO ".PREFIX."_forum_logs (mod_id,action,target_type,topic_id,date,ip)
        VALUES ('{$mod_id}','{$act}','topic','{$tid}',NOW(),'".$db->safesql($_IP)."')");
}

switch($action) {

    case 'toggle_lock':
        $new = $topic['is_locked'] ? 0 : 1;
        $db->query( "UPDATE " . PREFIX . "_forum_topics SET is_locked='{$new}' WHERE id='{$topic_id}'" );
        modLog( $new ? 'lock_topic' : 'unlock_topic', $topic_id );
        echo json_encode(['status'=>'success','action'=>$new?'locked':'unlocked','msg'=> $new ? $lang['forum_ajax_mod_locked'] : $lang['forum_ajax_mod_unlocked']]);
        break;

    case 'toggle_pin':
        $new = $topic['is_pinned'] ? 0 : 1;
        $db->query( "UPDATE " . PREFIX . "_forum_topics SET is_pinned='{$new}' WHERE id='{$topic_id}'" );
        modLog( $new ? 'pin_topic' : 'unpin_topic', $topic_id );
        echo json_encode(['status'=>'success','action'=>$new?'pinned':'unpinned','msg'=> $new ? $lang['forum_ajax_mod_pinned'] : $lang['forum_ajax_mod_unpinned']]);
        break;

    case 'soft_delete':
        $db->query( "UPDATE " . PREFIX . "_forum_topics SET is_deleted=1 WHERE id='{$topic_id}'" );
        $db->query( "UPDATE " . PREFIX . "_forum_posts SET is_deleted=1 WHERE topic_id='{$topic_id}'" );
        modLog( 'soft_delete_topic', $topic_id );
        // Kategori istatistik güncelleme
        $cid = intval($topic['cat_id']);
        $tc = $db->super_query( "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topics WHERE cat_id='{$cid}' AND is_deleted=0 AND is_approved=1" );
        $pc = $db->super_query( "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_posts p JOIN " . PREFIX . "_forum_topics t ON t.id=p.topic_id WHERE t.cat_id='{$cid}' AND p.is_deleted=0 AND p.is_approved=1" );
        $db->query( "UPDATE " . PREFIX . "_forum_cats SET topic_count='".intval($tc['cnt'])."', post_count='".intval($pc['cnt'])."' WHERE id='{$cid}'" );
        echo json_encode(['status'=>'success','action'=>'deleted','msg'=>$lang['forum_ajax_mod_topic_trashed'],'redirect'=>$config['http_home_url'].'forum/']);
        break;

    case 'bump':
        if( !$is_logged ) {
            echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_login']]);
            exit;
        }
        
        $is_mod = ( $member_id['user_group'] <= 2 );
        $is_author = ( intval($topic['user_id']) === intval($member_id['user_id']) );
        
        if( !$is_mod && !$is_author ) {
            echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_mod_bump_self_only']]);
            exit;
        }
        
        // Forum ayarlarından bump limitlerini yükle
        $forum_cfg = [];
        $db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
        while ($row = $db->get_row()) {
            $forum_cfg[$row["name"]] = stripslashes($row["value"]);
        }
        
        $cooldown_hours = isset($forum_cfg['bump_cooldown_hours']) ? intval($forum_cfg['bump_cooldown_hours']) : 24;
        $max_per_day = isset($forum_cfg['bump_max_per_day']) ? intval($forum_cfg['bump_max_per_day']) : 3;
        
        if( !$is_mod ) {
            // 1. Cooldown Kontrolü
            $last_log = $db->super_query("SELECT date FROM " . PREFIX . "_forum_logs 
                                          WHERE mod_id = '{$mod_id}' AND action = 'bump_topic' AND topic_id = '{$topic_id}' 
                                          ORDER BY date DESC LIMIT 1");
            if( $last_log && isset($last_log['date']) ) {
                $last_time = strtotime($last_log['date']);
                $diff_hours = (time() - $last_time) / 3600;
                if( $diff_hours < $cooldown_hours ) {
                    $wait = ceil($cooldown_hours - $diff_hours);
                    echo json_encode(['status'=>'error','msg'=>sprintf($lang['forum_ajax_mod_bump_cooldown'], $wait)]);
                    exit;
                }
            }
            
            // 2. Günlük Limit Kontrolü (Son 24 saatteki bump sayısı)
            $today_count = $db->super_query("SELECT COUNT(*) as cnt FROM " . PREFIX . "_forum_logs 
                                             WHERE mod_id = '{$mod_id}' AND action = 'bump_topic' 
                                             AND date >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            if( $today_count && intval($today_count['cnt']) >= $max_per_day ) {
                echo json_encode(['status'=>'error','msg'=>sprintf($lang['forum_ajax_mod_bump_limit_reached'], $max_per_day)]);
                exit;
            }
        }
        
        // Güncelle ve Logla
        $db->query("UPDATE " . PREFIX . "_forum_topics SET last_bump_date = NOW() WHERE id = '{$topic_id}'");
        modLog('bump_topic', $topic_id);
        
        echo json_encode(['status'=>'success','msg'=>$lang['forum_ajax_mod_bump_success']]);
        break;

    case 'bulk_delete_posts':
        $post_ids_raw = isset($_POST['post_ids']) ? $_POST['post_ids'] : '';
        if (empty($post_ids_raw)) {
            echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_mod_bulk_no_posts']]);
            exit;
        }

        $post_ids = array_map('intval', explode(',', $post_ids_raw));
        $post_ids = array_filter($post_ids, function($val) { return $val > 0; });

        if (empty($post_ids)) {
            echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_mod_bulk_invalid_ids']]);
            exit;
        }

        $post_list = implode(',', $post_ids);

        // Konunun ilk mesajını (konu başlangıç mesajı) bulalım
        $first_post = $db->super_query("SELECT id FROM " . PREFIX . "_forum_posts WHERE topic_id='{$topic_id}' ORDER BY date ASC LIMIT 1");
        if ($first_post && isset($first_post['id']) && in_array(intval($first_post['id']), $post_ids)) {
            echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_mod_bulk_delete_first_post']]);
            exit;
        }

        // Seçilen mesajları yumuşak sil
        $db->query("UPDATE " . PREFIX . "_forum_posts SET is_deleted=1 WHERE id IN ({$post_list}) AND topic_id='{$topic_id}'");

        // Konu istatistiklerini onar
        $rc = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_posts WHERE topic_id='{$topic_id}' AND is_deleted=0 AND is_approved=1");
        $lp = $db->super_query("SELECT id, user_id, date FROM " . PREFIX . "_forum_posts WHERE topic_id='{$topic_id}' AND is_deleted=0 AND is_approved=1 ORDER BY date DESC LIMIT 1");
        
        $replies = max(0, intval($rc["cnt"]) - 1);
        $lastPostId = $lp["id"] ? intval($lp["id"]) : 0;
        $lastUserId = $lp["user_id"] ? intval($lp["user_id"]) : 0;
        $lastPostDate = $lp["date"] ? "'" . $db->safesql($lp["date"]) . "'" : "NULL";

        $db->query("UPDATE " . PREFIX . "_forum_topics SET 
            replies='{$replies}', 
            last_post_id='{$lastPostId}', 
            last_user_id='{$lastUserId}', 
            last_post_date={$lastPostDate} 
            WHERE id='{$topic_id}'");

        // Kategori istatistiklerini onar
        $cid = intval($topic['cat_id']);
        $tc = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topics WHERE cat_id='{$cid}' AND is_deleted=0 AND is_approved=1");
        $pc = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_posts p JOIN " . PREFIX . "_forum_topics t ON t.id=p.topic_id WHERE t.cat_id='{$cid}' AND p.is_deleted=0 AND p.is_approved=1");
        $db->query("UPDATE " . PREFIX . "_forum_cats SET topic_count='".intval($tc['cnt'])."', post_count='".intval($pc['cnt'])."' WHERE id='{$cid}'");

        modLog('bulk_delete_posts', $topic_id);

        echo json_encode(['status'=>'success','msg'=>$lang['forum_ajax_mod_bulk_delete_success']]);
        break;

    default:
        echo json_encode(['status'=>'error','msg'=>$lang['forum_ajax_unknown_action']]);
}
exit;
