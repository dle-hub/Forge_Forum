<?php
/*
=====================================================
 Forge Forum Engine — Bildirim Yönetim Eylemi
-----------------------------------------------------
 File: engine/modules/forum/actions/notifications.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

// 1. Yetki Kontrolü
if (!$is_logged) {
    msgbox($lang['forum_access_denied'], $lang['forum_notif_login_required']);
    return;
}

$uid = intval($member_id['user_id']);

// 2. AJAX Alt Eylemler
$subaction = isset($_GET['subaction']) ? totranslit($_GET['subaction']) : '';

if ($subaction === 'delete') {
    $notif_id = intval($_GET['id']);
    $db->query("DELETE FROM " . PREFIX . "_forum_notifs WHERE id = '{$notif_id}' AND user_id = '{$uid}'");
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

if ($subaction === 'mark_all_read') {
    $db->query("UPDATE " . PREFIX . "_forum_notifs SET is_read = 1 WHERE user_id = '{$uid}'");
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// 3. Bildirimleri Listele ve Okundu Yap
$metatags['title'] = sprintf($lang['forum_notif_title'], $forum_title);
$forum_speedbar[$config['http_home_url'] . 'forum/'] = $lang['forum_breadcrumb_forum'];
$forum_speedbar[''] = $lang['forum_notif_breadcrumb'];



// Son 50 bildirimi çek
$db->query("
    SELECT n.*, u.name as sender_name, t.title as topic_title, t.alt_name as topic_alt
    FROM " . PREFIX . "_forum_notifs n
    LEFT JOIN " . USERPREFIX . "_users u ON u.user_id = n.from_user
    LEFT JOIN " . PREFIX . "_forum_topics t ON t.id = n.topic_id
    WHERE n.user_id = '{$uid}'
    ORDER BY n.date DESC LIMIT 50
");

$notif_rows = [];
while ($row = $db->get_row()) {
    $notif_rows[] = $row;
}

// Tümünü okundu olarak işaretle (sayfa yüklendiğinde)
$db->query("UPDATE " . PREFIX . "_forum_notifs SET is_read = 1 WHERE user_id = '{$uid}' AND is_read = 0");

$rows_html = "";
foreach ($notif_rows as $n) {
    $tpl2 = new dle_template();
    $tpl2->dir = TEMPLATE_DIR;
    $tpl2->load_template("forum/notification_row.tpl");

    $tpl2->set("{id}", intval($n['id']));
    $tpl2->set("{sender}", htmlspecialchars($n['sender_name'] ?: $lang['forum_ajax_quote_guest'], ENT_QUOTES, 'UTF-8'));
    $tpl2->set("{topic_title}", htmlspecialchars(stripslashes($n['topic_title']), ENT_QUOTES, 'UTF-8'));
    $tpl2->set("{date}", $n['date']);
    
    $notif_url = $config['http_home_url'] . 'forum/topic/' . $n['topic_id'] . '-' . $n['topic_alt'] . '.html';
    if (intval($n['post_id']) > 0) {
        $notif_url .= '#post-' . intval($n['post_id']);
    }
    $tpl2->set("{notif_url}", $notif_url);

    // Tip blokları
    if ($n['type'] === 'reply') {
        $tpl2->set("[type-reply]", "");
        $tpl2->set("[/type-reply]", "");
        $tpl2->set_block("'\\[type-like\\](.*?)\\[/type-like\\]'si", "");
        $tpl2->set_block("'\\[type-mention\\](.*?)\\[/type-mention\\]'si", "");
    } elseif ($n['type'] === 'like') {
        $tpl2->set_block("'\\[type-reply\\](.*?)\\[/type-reply\\]'si", "");
        $tpl2->set("[type-like]", "");
        $tpl2->set("[/type-like]", "");
        $tpl2->set_block("'\\[type-mention\\](.*?)\\[/type-mention\\]'si", "");
    } elseif ($n['type'] === 'mention') {
        $tpl2->set_block("'\\[type-reply\\](.*?)\\[/type-reply\\]'si", "");
        $tpl2->set_block("'\\[type-like\\](.*?)\\[/type-like\\]'si", "");
        $tpl2->set("[type-mention]", "");
        $tpl2->set("[/type-mention]", "");
    } else {
        $tpl2->set_block("'\\[type-reply\\](.*?)\\[/type-reply\\]'si", "");
        $tpl2->set_block("'\\[type-like\\](.*?)\\[/type-like\\]'si", "");
        $tpl2->set_block("'\\[type-mention\\](.*?)\\[/type-mention\\]'si", "");
    }

    // Okunmuş/Okunmamış durumu (yüklenirkenki durum)
    if (intval($n['is_read']) === 0) {
        $tpl2->set("[notif-unread]", "");
        $tpl2->set("[/notif-unread]", "");
        $tpl2->set_block("'\\[notif-read\\](.*?)\\[/notif-read\\]'si", "");
    } else {
        $tpl2->set_block("'\\[notif-unread\\](.*?)\\[/notif-unread\\]'si", "");
        $tpl2->set("[notif-read]", "");
        $tpl2->set("[/notif-read]", "");
    }

    $tpl2->compile("notif_row");
    $rows_html .= $tpl2->result["notif_row"];
    unset($tpl2);
}

$tpl->load_template("forum/notifications.tpl");
$tpl->set("{forum_breadcrumb}", "{forum_breadcrumb}");
$tpl->set("{notification_rows}", $rows_html ?: '<div class="p-8 text-center text-gray-400 text-sm">' . $lang['forum_notif_empty'] . '</div>');
$tpl->set("{http_home_url}", $config["http_home_url"]);

$tpl->compile("content");
$tpl->clear();
