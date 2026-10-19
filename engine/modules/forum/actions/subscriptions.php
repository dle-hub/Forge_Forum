<?php
/*
=====================================================
 Forge Forum Engine — Aboneliklerim Eylemi
-----------------------------------------------------
 File: engine/modules/forum/actions/subscriptions.php
 URL: index.php?do=forum&action=subscriptions
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (!$is_logged) {
    msgbox($lang['forum_access_denied'], $lang['forum_notif_login_required']);
    return;
}

$uid = intval($member_id['user_id']);

$metatags['title'] = $lang['forum_sub_my_title'] . " - " . $forum_title;
$forum_speedbar[$config['http_home_url'] . 'forum/'] = $lang['forum_breadcrumb_forum'];
$forum_speedbar[''] = $lang['forum_sub_my_title'];

$db->query(
    "SELECT s.date AS sub_date, t.id, t.title, t.alt_name, t.replies, t.views, t.last_post_date,
            c.name AS cat_name, c.alt_name AS cat_alt
     FROM " . PREFIX . "_forum_subs s
     INNER JOIN " . PREFIX . "_forum_topics t ON t.id = s.topic_id
     LEFT JOIN " . PREFIX . "_forum_cats c ON c.id = t.cat_id
     WHERE s.user_id = '{$uid}' AND t.is_deleted = 0
     ORDER BY t.last_post_date DESC, s.date DESC",
);

$rows_html = "";
$count = 0;
while ($r = $db->get_row()) {
    $count++;
    $tid = intval($r['id']);
    $title = htmlspecialchars(stripslashes($r['title']), ENT_QUOTES, "UTF-8");
    $turl = $config['http_home_url'] . 'forum/topic/' . $tid . '-' . $r['alt_name'] . '.html';
    $cat = htmlspecialchars(stripslashes($r['cat_name'] ?: ''), ENT_QUOTES, "UTF-8");
    $cat_url = $config['http_home_url'] . 'forum/' . ($r['cat_alt'] ?: '') . '/';
    $sub_date = htmlspecialchars((string) $r['sub_date'], ENT_QUOTES, "UTF-8");
    $last = htmlspecialchars((string) ($r['last_post_date'] ?: ''), ENT_QUOTES, "UTF-8");
    $replies = intval($r['replies']);
    $views = intval($r['views']);

    $rows_html .= '<article class="forum-node">'
        . '<span class="icon-action forum-node__icon" aria-hidden="true"><i class="fa fa-bell"></i></span>'
        . '<div class="forum-node__info">'
        .   '<h3 class="forum-node__title"><a href="' . $turl . '">' . $title . '</a></h3>'
        .   '<div class="forum-node__meta">'
        .     ($cat !== '' ? '<span class="meta-item"><i class="fa fa-folder-o" aria-hidden="true"></i> ' . $cat . '</span><span class="meta-dot" aria-hidden="true"></span>' : '')
        .     '<span class="meta-item">' . $lang['forum_ui_subscription'] . ' ' . $sub_date . '</span>'
        .   '</div>'
        . '</div>'
        . '<div class="forum-node__stats">'
        .   '<span class="forum-node__stat"><b>' . $replies . '</b> ' . $lang['forum_ui_replies_lc'] . '</span>'
        .   '<span class="forum-node__stat"><b>' . $views . '</b> ' . $lang['forum_ui_views_lc'] . '</span>'
        . '</div>'
        . '<div class="forum-node__last">'
        .   '<span class="forum-node__lastmeta">' . $last . '</span>'
        .   '<button type="button" onclick="forumUnsubscribe(' . $tid . ')" class="icon-action" title="' . htmlspecialchars($lang['forum_ui_unsubscribe'], ENT_QUOTES, "UTF-8") . '"><i class="fa fa-bell-slash-o" aria-hidden="true"></i></button>'
        . '</div>'
        . '</article>';
}

$tpl->load_template("forum/subscriptions.tpl");
$tpl->set("{forum_breadcrumb}", "{forum_breadcrumb}");
$tpl->set("{sub_title}", htmlspecialchars($lang['forum_sub_my_title'], ENT_QUOTES, "UTF-8"));
$tpl->set("{sub_count}", $count);
$tpl->set("{subscription_rows}", $rows_html ?: '<div class="forum-rows__empty">' . htmlspecialchars($lang['forum_sub_empty'], ENT_QUOTES, "UTF-8") . '</div>');
$tpl->set("{http_home_url}", $config["http_home_url"]);
$tpl->set("{dle_login_hash}", $dle_login_hash);

$tpl->compile("content");
$tpl->clear();
