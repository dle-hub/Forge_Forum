<?php
/*
=====================================================
 Forge Forum Engine — Etiket Sayfası
-----------------------------------------------------
 File: engine/modules/forum/actions/tag.php
 URL: index.php?do=forum&action=tag&tag={alt_name}
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

$tag_alt = isset($_GET['tag']) ? $db->safesql(totranslit($_GET['tag'])) : '';
$tag = $tag_alt
    ? $db->super_query("SELECT id, name, alt_name FROM " . PREFIX . "_forum_tags WHERE alt_name = '{$tag_alt}'")
    : null;

if (!$tag || empty($tag['id'])) {
    msgbox($lang['forum_err_title'], $lang['forum_main_no_subforums']);
    return;
}

$tag_id = intval($tag['id']);
$tag_name = htmlspecialchars(stripslashes($tag['name']), ENT_QUOTES, "UTF-8");

$metatags['title'] = $lang['forum_ui_tag_label'] . ' ' . $tag_name . " - " . $forum_title;
$forum_speedbar[$config['http_home_url'] . 'forum/'] = $lang['forum_breadcrumb_forum'];
$forum_speedbar[''] = $lang['forum_ui_tag_label'] . ' ' . $tag_name;

$perpage = intval($forum_cfg["topics_per_page"]) ?: 20;
$page = max(1, intval($f_page));
$offset = ($page - 1) * $perpage;

$tag_access = forum_cat_access_where("c");

$total_row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topic_tags tt
     INNER JOIN " . PREFIX . "_forum_topics t ON t.id = tt.topic_id
     INNER JOIN " . PREFIX . "_forum_cats c ON c.id = t.cat_id
     WHERE tt.tag_id = '{$tag_id}' AND t.is_deleted = 0 AND t.is_approved = 1
       AND {$tag_access}",
);
$total = intval($total_row['cnt']);

$db->query(
    "SELECT t.*, c.name AS cat_name, c.alt_name AS cat_alt, u.name AS author_name
     FROM " . PREFIX . "_forum_topic_tags tt
     INNER JOIN " . PREFIX . "_forum_topics t ON t.id = tt.topic_id
     LEFT JOIN " . PREFIX . "_forum_cats c ON c.id = t.cat_id
     LEFT JOIN " . USERPREFIX . "_users u ON u.user_id = t.user_id
     WHERE tt.tag_id = '{$tag_id}' AND t.is_deleted = 0 AND t.is_approved = 1
       AND {$tag_access}
     ORDER BY t.is_pinned DESC, t.last_post_date DESC
     LIMIT {$offset}, {$perpage}",
);

$rows_html = "";
while ($r = $db->get_row()) {
    $tid = intval($r['id']);
    $title = htmlspecialchars(stripslashes($r['title']), ENT_QUOTES, "UTF-8");
    $turl = $config['http_home_url'] . 'forum/topic/' . $tid . '-' . $r['alt_name'] . '.html';
    $cat = htmlspecialchars(stripslashes($r['cat_name'] ?: ''), ENT_QUOTES, "UTF-8");
    $author = htmlspecialchars($r['author_name'] ?: $lang['forum_ajax_quote_guest'], ENT_QUOTES, "UTF-8");
    $date = htmlspecialchars((string) $r['last_post_date'], ENT_QUOTES, "UTF-8");
    $replies = intval($r['replies']);
    $views = intval($r['views']);

    $rows_html .= '<article class="forum-node' . ($r['is_pinned'] ? ' sabit-row' : '') . '">'
        . '<span class="icon-action forum-node__icon" aria-hidden="true"><i class="fa fa-comment-o"></i></span>'
        . '<div class="forum-node__info">'
        .   '<h3 class="forum-node__title"><a href="' . $turl . '">' . $title . '</a></h3>'
        .   '<div class="forum-node__meta">'
        .     ($cat !== '' ? '<span class="meta-item"><i class="fa fa-folder-o" aria-hidden="true"></i> ' . $cat . '</span><span class="meta-dot" aria-hidden="true"></span>' : '')
        .     '<span class="meta-item meta-item--author">' . $author . '</span>'
        .   '</div>'
        . '</div>'
        . '<div class="forum-node__stats">'
        .   '<span class="forum-node__stat"><b>' . $replies . '</b> ' . $lang['forum_ui_replies_lc'] . '</span>'
        .   '<span class="forum-node__stat"><b>' . $views . '</b> ' . $lang['forum_ui_views_lc'] . '</span>'
        . '</div>'
        . '<div class="forum-node__last"><span class="forum-node__lastmeta">' . $date . '</span></div>'
        . '</article>';
}

// Sayfalama
$pagination = "";
$pages = $perpage > 0 ? (int) ceil($total / $perpage) : 1;
if ($pages > 1) {
    $base = $config['http_home_url'] . 'index.php?do=forum&action=tag&tag=' . urlencode($tag['alt_name']) . '&page=';
    $pagination .= '<div class="flex justify-center gap-1 py-4">';
    for ($i = 1; $i <= $pages; $i++) {
        $act = ($i == $page) ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100';
        $pagination .= '<a href="' . $base . $i . '" class="px-3 py-1.5 text-sm rounded border ' . $act . '">' . $i . '</a>';
    }
    $pagination .= '</div>';
}

$tpl->load_template("forum/tag.tpl");
$tpl->set("{forum_breadcrumb}", "{forum_breadcrumb}");
$tpl->set("{tag_name}", $tag_name);
$tpl->set("{topic_rows}", $rows_html ?: '<div class="forum-rows__empty">' . $lang['forum_ui_tag_empty'] . '</div>');
$tpl->set("{pagination}", $pagination);
$tpl->set("{http_home_url}", $config["http_home_url"]);

$tpl->compile("content");
$tpl->clear();
