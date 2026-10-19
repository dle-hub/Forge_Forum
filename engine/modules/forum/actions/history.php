<?php
/*
=====================================================
 Forge Forum Engine — Mesaj Düzenleme Geçmişi
-----------------------------------------------------
 File: engine/modules/forum/actions/history.php
 URL: index.php?do=forum&action=history&post_id={id}
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

$post_id = isset($_REQUEST["post_id"]) ? intval($_REQUEST["post_id"]) : 0;
if ($post_id < 1) {
    msgbox($lang['forum_err_title'], $lang['forum_ajax_post_not_found']);
    return;
}

$post = $db->super_query(
    "SELECT p.id, p.user_id, p.topic_id, p.text, p.updated_at, t.title AS topic_title, t.alt_name AS topic_alt
     FROM " . PREFIX . "_forum_posts p
     LEFT JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
     WHERE p.id = '{$post_id}'",
);
if (!$post || !$post["id"]) {
    msgbox($lang['forum_err_title'], $lang['forum_ajax_post_not_found']);
    return;
}

$uid = $is_logged ? intval($member_id["user_id"]) : 0;
$grp = $is_logged ? intval($member_id["user_group"]) : 5;
if ($uid !== intval($post["user_id"]) && $grp > 2) {
    msgbox($lang['forum_access_denied'], $lang['forum_no_perm']);
    return;
}

$metatags['title'] = ($lang['forum_history_title'] ?? "Düzenleme Geçmişi") . " - " . $forum_title;
$metatags['header_title'] = '<meta name="robots" content="noindex, nofollow">';
$forum_speedbar[$config['http_home_url'] . 'forum/'] = $lang['forum_breadcrumb_forum'];
$forum_speedbar[''] = $lang['forum_history_title'] ?? "Düzenleme Geçmişi";

$rows_html = "";
$count = 0;
$db->query(
    "SELECT h.id, h.editor_id, h.old_text, h.date, u.name AS editor_name
     FROM " . PREFIX . "_forum_edit_history h
     LEFT JOIN " . USERPREFIX . "_users u ON u.user_id = h.editor_id
     WHERE h.post_id = '{$post_id}'
     ORDER BY h.date DESC, h.id DESC
     LIMIT 100",
);
while ($h = $db->get_row()) {
    $count++;
    $editor = htmlspecialchars(stripslashes($h["editor_name"] ?: ($lang['forum_guest'] ?? "Misafir")), ENT_QUOTES, "UTF-8");
    $hdate = htmlspecialchars((string) $h["date"], ENT_QUOTES, "UTF-8");
    $old = htmlspecialchars(strip_tags(stripslashes((string) $h["old_text"])), ENT_QUOTES, "UTF-8");
    $rows_html .=
        '<div class="forum-node forum-history__row">' .
        '<span class="icon-action forum-node__icon" aria-hidden="true"><i class="fa fa-history"></i></span>' .
        '<div class="forum-node__info">' .
        '<div class="forum-node__meta"><span class="meta-item"><i class="fa fa-user-o" aria-hidden="true"></i> ' . $editor . "</span>" .
        '<span class="meta-dot" aria-hidden="true"></span><span class="meta-item">' . $hdate . "</span></div>" .
        '<pre class="forum-history__text">' . $old . "</pre>" .
        "</div></div>";
}

$current_text = htmlspecialchars(strip_tags(stripslashes((string) $post["text"])), ENT_QUOTES, "UTF-8");
$topic_link = forum_topic_post_url(intval($post["topic_id"]), $post["topic_alt"] ?? "", $post_id);

$tpl->load_template("forum/history.tpl");
forum_set_ui_lang($tpl, $lang);
$tpl->set("{forum_breadcrumb}", "{forum_breadcrumb}");
$tpl->set("{history_title}", htmlspecialchars($lang['forum_history_title'] ?? "Düzenleme Geçmişi", ENT_QUOTES, "UTF-8"));
$tpl->set("{topic_title}", htmlspecialchars(stripslashes((string) $post["topic_title"]), ENT_QUOTES, "UTF-8"));
$tpl->set("{topic_link}", $topic_link);
$tpl->set("{history_count}", $count);
$tpl->set("{history_rows}", $rows_html ?: '<div class="forum-rows__empty">' . ($lang['forum_history_none'] ?? "Bu mesaj için düzenleme kaydı yok.") . "</div>");
$tpl->set("{current_text}", $current_text);
$tpl->set("{http_home_url}", $config["http_home_url"]);

$tpl->compile("content");
$tpl->clear();
