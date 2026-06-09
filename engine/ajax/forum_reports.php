<?php
/*
=====================================================
 Forge Forum Engine — Şikayet AJAX Controller
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_reports
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

header('Content-Type: application/json');
$action   = isset($_POST['action']) ? totranslit($_POST['action']) : '';
$id       = isset($_POST['id']) ? intval($_POST['id']) : 0;
$mod_id   = intval($member_id['user_id']);
$_IP      = $_SERVER['REMOTE_ADDR'];

// Sadece 'add' işlemine tüm giriş yapmış üyeler izinli, diğer işlemler admin/mod yetkisi (<=2) gerektirir!
if ($action !== 'add') {
    if( $member_id['user_group'] > 2 ) {
        echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_unauthorized']]);
        exit;
    }
}

function reportLog($act,$target,$tid) {
    global $db,$mod_id,$_IP;
    $db->query("INSERT INTO ".PREFIX."_forum_logs (mod_id,action,target_type,topic_id,date,ip)
        VALUES ('{$mod_id}','{$act}','{$target}','{$tid}',NOW(),'".$db->safesql($_IP)."')");
}

switch($action) {

    // =========================================
    // YENİ ŞİKAYET EKLE (Tüm Giriş Yapmış Üyeler)
    // =========================================
    case 'add':
        if (!$is_logged) {
            echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_login']]);
            exit;
        }
        $target_id   = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
        $target_type = isset($_POST['target_type']) ? totranslit($_POST['target_type']) : 'post';
        $reason      = isset($_POST['reason']) ? trim(strip_tags($_POST['reason'])) : '';

        if (!$target_id) {
            echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_invalid_id']]);
            exit;
        }
        if (empty($reason)) {
            echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_report_empty_reason']]);
            exit;
        }
        if (mb_strlen($reason) > 500) {
            echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_report_reason_length']]);
            exit;
        }

        // Aynı üye aynı içeriğe aktif şikayet açmış mı kontrolü
        $chk = $db->super_query("SELECT id FROM ".PREFIX."_forum_reports WHERE reporter_id='{$mod_id}' AND target_id='{$target_id}' AND target_type='{$target_type}' AND status=0");
        if ($chk['id']) {
            echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_report_duplicate']]);
            exit;
        }

        $reason_esc = $db->safesql($reason);
        $db->query("INSERT INTO ".PREFIX."_forum_reports (reporter_id, target_id, target_type, reason, date, status)
            VALUES ('{$mod_id}', '{$target_id}', '{$target_type}', '{$reason_esc}', NOW(), 0)");

        echo json_encode(['success'=>true,'message'=>$lang['forum_ajax_report_sent']]);
        break;

    // =========================================
    // ÇÖZÜLDÜ / REDDEDİLDİ İŞARETLE
    // =========================================
    case 'resolve':
        if(!$id){ echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_invalid_id']]); exit; }
        $newStatus = isset($_POST['status']) ? intval($_POST['status']) : 1; // 1=çözüldü, 2=reddedildi
        $db->query("UPDATE ".PREFIX."_forum_reports SET status='{$newStatus}', mod_id='{$mod_id}', resolved_date=NOW() WHERE id='{$id}'");
        $log_label = $newStatus==1 ? 'çözüldü' : 'reddedildi';
        reportLog('report_'.$log_label,'report',$id);
        $msg = $newStatus==1 ? $lang['forum_ajax_report_resolved'] : $lang['forum_ajax_report_rejected'];
        echo json_encode(['success'=>true,'message'=>$msg]);
        break;

    // =========================================
    // İÇERİĞİ ÇÖPE AT (Soft Delete)
    // =========================================
    case 'trash_content':
        if(!$id){ echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_invalid_id']]); exit; }

        // Önce şikayeti bul
        $report = $db->super_query("SELECT * FROM ".PREFIX."_forum_reports WHERE id='{$id}'");
        if(!$report['id']){ echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_report_not_found']]); exit; }

        $targetType = $report['target_type'];
        $targetId   = intval($report['target_id']);

        if($targetType == 'topic') {
            $db->query("UPDATE ".PREFIX."_forum_topics SET is_deleted=1 WHERE id='{$targetId}'");
            $db->query("UPDATE ".PREFIX."_forum_posts SET is_deleted=1 WHERE topic_id='{$targetId}'");
            reportLog('trash_topic_via_report','topic',$targetId);
        } else {
            $db->query("UPDATE ".PREFIX."_forum_posts SET is_deleted=1 WHERE id='{$targetId}'");
            reportLog('trash_post_via_report','post',$targetId);
        }

        // Şikayeti de çözüldü olarak işaretle
        $db->query("UPDATE ".PREFIX."_forum_reports SET status=1, mod_id='{$mod_id}', resolved_date=NOW() WHERE id='{$id}'");

        echo json_encode(['success'=>true,'message'=>$lang['forum_ajax_report_trash_resolved']]);
        break;

    default:
        echo json_encode(['success'=>false,'error'=>$lang['forum_ajax_unknown_action']]);
}
}
exit;
