<?php
/*
=====================================================
 Forge Forum Engine — Detaylı İstatistikler
-----------------------------------------------------
 File: engine/modules/forum/actions/statistics.php
 URL: index.php?do=forum&action=statistics
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (empty($forum_cfg["enable_statistics"])) {
    msgbox($lang['forum_access_denied'], $lang['forum_no_perm']);
    return;
}

$P = PREFIX;
$U = USERPREFIX;
$__stats_access = forum_cat_access_where("c");

$metatags['title'] = ($lang['forum_stats_title'] ?? "Forum İstatistikleri") . " - " . $forum_title;
$forum_speedbar[$config['http_home_url'] . 'forum/'] = $lang['forum_breadcrumb_forum'];
$forum_speedbar[''] = $lang['forum_stats_title'] ?? "Forum İstatistikleri";

// -------------------------------------------------
// 1. GENEL SAYILAR
// -------------------------------------------------
$r = $db->super_query("SELECT COUNT(*) AS c FROM {$P}_forum_topics t INNER JOIN {$P}_forum_cats c ON c.id=t.cat_id WHERE t.is_deleted=0 AND t.is_approved=1 AND {$__stats_access}");
$tot_topics = intval($r['c']);

$r = $db->super_query("SELECT COUNT(*) AS c FROM {$P}_forum_posts p INNER JOIN {$P}_forum_topics t ON t.id=p.topic_id INNER JOIN {$P}_forum_cats c ON c.id=t.cat_id WHERE p.is_deleted=0 AND p.is_approved=1 AND {$__stats_access}");
$tot_posts = intval($r['c']);

$r = $db->super_query("SELECT COUNT(*) AS c FROM {$U}_users");
$tot_users = intval($r['c']);

$r = $db->super_query("SELECT COUNT(*) AS c FROM {$P}_forum_topics t INNER JOIN {$P}_forum_cats c ON c.id=t.cat_id WHERE t.is_deleted=0 AND t.is_approved=1 AND DATE(t.date)=CURDATE() AND {$__stats_access}");
$today_topics = intval($r['c']);

$r = $db->super_query("SELECT COUNT(*) AS c FROM {$P}_forum_posts p INNER JOIN {$P}_forum_topics t ON t.id=p.topic_id INNER JOIN {$P}_forum_cats c ON c.id=t.cat_id WHERE p.is_deleted=0 AND p.is_approved=1 AND DATE(p.date)=CURDATE() AND {$__stats_access}");
$today_posts = intval($r['c']);

// Günlük ortalama mesaj
$first = $db->super_query("SELECT MIN(p.date) AS d FROM {$P}_forum_posts p INNER JOIN {$P}_forum_topics t ON t.id=p.topic_id INNER JOIN {$P}_forum_cats c ON c.id=t.cat_id WHERE p.is_deleted=0 AND {$__stats_access}");
$days = 1;
if (!empty($first['d'])) {
    $d = strtotime($first['d']);
    if ($d > 0) {
        $days = max(1, (int) ceil((time() - $d) / 86400));
    }
}
$per_day = number_format($tot_posts / $days, 1, ',', '.');

// Son üye
$newest = $db->super_query("SELECT user_id, name FROM {$U}_users ORDER BY user_id DESC LIMIT 1");
$newest_html = '—';
if (!empty($newest['user_id'])) {
    $newest_name = htmlspecialchars(stripslashes((string) $newest['name']), ENT_QUOTES, 'UTF-8');
    $newest_html = '<a href="' . $config['http_home_url'] . 'index.php?do=forum&action=profile&id=' . intval($newest['user_id']) . '">' . $newest_name . '</a>';
}

// -------------------------------------------------
// 2. KATEGORİ TABLOSU
// -------------------------------------------------
$cats = [];
$db->query(
    "SELECT c.id, c.name, c.alt_name, c.parent_id FROM {$P}_forum_cats c WHERE {$__stats_access} ORDER BY c.parent_id ASC, c.posi ASC",
);
while ($c = $db->get_row()) {
    $cats[] = $c;
}

$cat_rows = '';
foreach ($cats as $c) {
    $cid = intval($c['id']);

    $tc = $db->super_query(
        "SELECT COUNT(*) AS c FROM {$P}_forum_topics WHERE cat_id='{$cid}' AND is_deleted=0 AND is_approved=1",
    );
    $pc = $db->super_query(
        "SELECT COUNT(*) AS c FROM {$P}_forum_posts p
         INNER JOIN {$P}_forum_topics t ON t.id = p.topic_id
         WHERE t.cat_id='{$cid}' AND p.is_deleted=0 AND p.is_approved=1",
    );
    $lp = $db->super_query(
        "SELECT MAX(last_post_date) AS d FROM {$P}_forum_topics WHERE cat_id='{$cid}' AND is_deleted=0 AND is_approved=1",
    );

    $name   = htmlspecialchars(stripslashes((string) $c['name']), ENT_QUOTES, 'UTF-8');
    $indent = intval($c['parent_id']) > 0 ? '&nbsp;&nbsp;&mdash; ' : '';
    $curl   = $config['http_home_url'] . 'forum/' . rawurlencode((string) $c['alt_name']) . '/';
    $lastd  = !empty($lp['d']) ? htmlspecialchars((string) $lp['d'], ENT_QUOTES, 'UTF-8') : '—';

    $cat_rows .= '<tr>'
        . '<td>' . $indent . '<a href="' . $curl . '">' . $name . '</a></td>'
        . '<td class="num">' . intval($tc['c']) . '</td>'
        . '<td class="num">' . intval($pc['c']) . '</td>'
        . '<td>' . $lastd . '</td>'
        . '</tr>';
}
if ($cat_rows === '') {
    $cat_rows = '<tr><td colspan="4" style="text-align:center;color:var(--forum-text-secondary)">' . ($lang['forum_stats_no_data'] ?? 'Veri yok.') . '</td></tr>';
}
$cats_table = '<table class="forum-statstable"><thead><tr><th>' . ($lang['forum_stats_col_cat'] ?? 'Kategori') . '</th><th class="num">' . ($lang['forum_stats_col_topics'] ?? 'Konu') . '</th><th class="num">' . ($lang['forum_stats_col_posts'] ?? 'Mesaj') . '</th><th>' . ($lang['forum_stats_col_last'] ?? 'Son Mesaj') . '</th></tr></thead><tbody>' . $cat_rows . '</tbody></table>';

// -------------------------------------------------
// 3. EN AKTİF ÜYELER (mesaj sayısına göre)
// -------------------------------------------------
$poster_rows = '';
$rank = 0;
$db->query(
    "SELECT p.user_id, COUNT(*) AS cnt, u.name, u.foto
     FROM {$P}_forum_posts p
     INNER JOIN {$P}_forum_topics t ON t.id = p.topic_id
     INNER JOIN {$P}_forum_cats c ON c.id = t.cat_id
     INNER JOIN {$U}_users u ON u.user_id = p.user_id
     WHERE p.is_deleted=0 AND p.is_approved=1 AND p.user_id > 0 AND {$__stats_access}
     GROUP BY p.user_id
     ORDER BY cnt DESC
     LIMIT 10",
);
while ($tp = $db->get_row()) {
    $rank++;
    $uid_p  = intval($tp['user_id']);
    $pname  = htmlspecialchars(stripslashes((string) $tp['name']), ENT_QUOTES, 'UTF-8');
    $purl   = $config['http_home_url'] . 'index.php?do=forum&action=profile&id=' . $uid_p;
    $avatar = forum_user_avatar_html($tp['foto'] ?? '', stripslashes((string) $tp['name']), 40, 24);
    $poster_rows .= '<tr>'
        . '<td class="num" style="width:36px">' . $rank . '</td>'
        . '<td><a href="' . $purl . '" style="display:inline-flex;align-items:center;gap:8px">' . $avatar . $pname . '</a></td>'
        . '<td class="num">' . intval($tp['cnt']) . '</td>'
        . '</tr>';
}
if ($poster_rows === '') {
    $poster_rows = '<tr><td colspan="3" style="text-align:center;color:var(--forum-text-secondary)">' . ($lang['forum_stats_no_data'] ?? 'Veri yok.') . '</td></tr>';
}
$posters_table = '<table class="forum-statstable"><thead><tr><th class="num">#</th><th>' . ($lang['forum_stats_col_member'] ?? 'Üye') . '</th><th class="num">' . ($lang['forum_stats_col_posts'] ?? 'Mesaj') . '</th></tr></thead><tbody>' . $poster_rows . '</tbody></table>';

// -------------------------------------------------
// 4. EN ÇOK GÖRÜNTÜLENEN KONULAR
// -------------------------------------------------
if (!function_exists('forum_stats_topic_url')) {
    function forum_stats_topic_url($row, $config)
    {
        return $config['http_home_url'] . 'forum/topic/' . intval($row['id']) . '-' . rawurlencode((string) $row['alt_name']) . '.html';
    }
}

$viewed_rows = '';
$db->query(
    "SELECT t.id, t.title, t.alt_name, t.views, t.replies FROM {$P}_forum_topics t
     INNER JOIN {$P}_forum_cats c ON c.id=t.cat_id
     WHERE t.is_deleted=0 AND t.is_approved=1 AND {$__stats_access}
     ORDER BY t.views DESC LIMIT 10",
);
while ($tv = $db->get_row()) {
    $turl = forum_stats_topic_url($tv, $config);
    $ttl  = htmlspecialchars(forum_word_filter(stripslashes((string) $tv['title'])), ENT_QUOTES, 'UTF-8');
    $viewed_rows .= '<tr>'
        . '<td><a href="' . $turl . '">' . $ttl . '</a></td>'
        . '<td class="num">' . intval($tv['views']) . '</td>'
        . '<td class="num">' . intval($tv['replies']) . '</td>'
        . '</tr>';
}
if ($viewed_rows === '') {
    $viewed_rows = '<tr><td colspan="3" style="text-align:center;color:var(--forum-text-secondary)">' . ($lang['forum_stats_no_data'] ?? 'Veri yok.') . '</td></tr>';
}
$viewed_table = '<table class="forum-statstable"><thead><tr><th>' . ($lang['forum_stats_col_topic'] ?? 'Konu') . '</th><th class="num">' . ($lang['forum_stats_col_views'] ?? 'Görüntüleme') . '</th><th class="num">' . ($lang['forum_stats_col_replies'] ?? 'Cevap') . '</th></tr></thead><tbody>' . $viewed_rows . '</tbody></table>';

// -------------------------------------------------
// 5. EN ÇOK CEVAPLANAN KONULAR
// -------------------------------------------------
$replied_rows = '';
$db->query(
    "SELECT t.id, t.title, t.alt_name, t.views, t.replies FROM {$P}_forum_topics t
     INNER JOIN {$P}_forum_cats c ON c.id=t.cat_id
     WHERE t.is_deleted=0 AND t.is_approved=1 AND {$__stats_access}
     ORDER BY t.replies DESC LIMIT 10",
);
while ($tr = $db->get_row()) {
    $turl = forum_stats_topic_url($tr, $config);
    $ttl  = htmlspecialchars(forum_word_filter(stripslashes((string) $tr['title'])), ENT_QUOTES, 'UTF-8');
    $replied_rows .= '<tr>'
        . '<td><a href="' . $turl . '">' . $ttl . '</a></td>'
        . '<td class="num">' . intval($tr['replies']) . '</td>'
        . '<td class="num">' . intval($tr['views']) . '</td>'
        . '</tr>';
}
if ($replied_rows === '') {
    $replied_rows = '<tr><td colspan="3" style="text-align:center;color:var(--forum-text-secondary)">' . ($lang['forum_stats_no_data'] ?? 'Veri yok.') . '</td></tr>';
}
$replied_table = '<table class="forum-statstable"><thead><tr><th>' . ($lang['forum_stats_col_topic'] ?? 'Konu') . '</th><th class="num">' . ($lang['forum_stats_col_replies'] ?? 'Cevap') . '</th><th class="num">' . ($lang['forum_stats_col_views'] ?? 'Görüntüleme') . '</th></tr></thead><tbody>' . $replied_rows . '</tbody></table>';

// -------------------------------------------------
// ŞABLON
// -------------------------------------------------
$tpl->load_template("forum/statistics.tpl");
forum_set_ui_lang($tpl, $lang);
$tpl->set("{forum_breadcrumb}", "{forum_breadcrumb}");
$tpl->set("{http_home_url}", $config['http_home_url']);
$tpl->set("{stats_title}", htmlspecialchars($lang['forum_stats_title'] ?? "Forum İstatistikleri", ENT_QUOTES, 'UTF-8'));
$tpl->set("{stat_topics}", $tot_topics);
$tpl->set("{stat_posts}", $tot_posts);
$tpl->set("{stat_members}", $tot_users);
$tpl->set("{stat_topics_today}", $today_topics);
$tpl->set("{stat_posts_today}", $today_posts);
$tpl->set("{stat_per_day}", $per_day);
$tpl->set("{stat_members_label}", htmlspecialchars($lang['forum_stats_members_card'] ?? "Üye", ENT_QUOTES, 'UTF-8'));
$tpl->set("{stat_newest}", $newest_html);
$tpl->set("{stats_categories}", $cats_table);
$tpl->set("{stats_top_posters}", $posters_table);
$tpl->set("{stats_top_viewed}", $viewed_table);
$tpl->set("{stats_top_replied}", $replied_table);
$tpl->set("{stats_cat_title}", htmlspecialchars($lang['forum_stats_sec_cats'] ?? "Kategoriler", ENT_QUOTES, 'UTF-8'));
$tpl->set("{stats_posters_title}", htmlspecialchars($lang['forum_stats_sec_posters'] ?? "En Aktif Üyeler", ENT_QUOTES, 'UTF-8'));
$tpl->set("{stats_viewed_title}", htmlspecialchars($lang['forum_stats_sec_viewed'] ?? "En Çok Görüntülenen Konular", ENT_QUOTES, 'UTF-8'));
$tpl->set("{stats_replied_title}", htmlspecialchars($lang['forum_stats_sec_replied'] ?? "En Çok Cevaplanan Konular", ENT_QUOTES, 'UTF-8'));

$tpl->compile("content");
$tpl->clear();
