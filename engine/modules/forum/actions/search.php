<?php
/*
=====================================================
 Forge Forum Engine — Gelişmiş Arama
-----------------------------------------------------
 File: engine/modules/forum/actions/search.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

// -------------------------------------------------
// 1. PARAMETRELER
// -------------------------------------------------
$q       = isset($_GET['q']) ? trim($_GET['q']) : '';
$mode    = isset($_GET['mode']) ? totranslit($_GET['mode']) : '';
$in      = isset($_GET['in']) ? totranslit($_GET['in']) : 'topics'; // topics veya posts
$page    = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$perpage = 25;
$offset  = ($page-1) * $perpage;

$results = array();
$total   = 0;

// -------------------------------------------------
// 2. SEO
// -------------------------------------------------
$metatags['title'] = sprintf($lang['forum_search_title'], $forum_title);
$metatags['description'] = $lang['forum_search_desc'];
$metatags['header_title'] = '<meta name="robots" content="noindex, nofollow">';
$forum_speedbar[''] = $lang['forum_search_breadcrumb'];

// -------------------------------------------------
// 3. ARAMA VEYA FİLTRELEME YAP
// -------------------------------------------------
if( !empty($q) || !empty($mode) ) {
    
    if( !empty($mode) ) {
        if( $mode === 'new' ) {
            $metatags['title'] = sprintf($lang['forum_search_new_title'], $forum_title);
            $forum_speedbar[''] = $lang['forum_search_new_breadcrumb'];
            
            $cnt_row = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topics WHERE is_deleted=0 AND is_approved=1");
            $total = min(50, intval($cnt_row['cnt']));
            
            if( $total > 0 ) {
                $db->query(
                    "SELECT t.*, u.name AS author_name, c.name AS cat_name
                     FROM " . PREFIX . "_forum_topics t
                     LEFT JOIN " . PREFIX . "_users u ON u.user_id=t.user_id
                     LEFT JOIN " . PREFIX . "_forum_cats c ON c.id=t.cat_id
                     WHERE t.is_deleted=0 AND t.is_approved=1
                     ORDER BY t.last_bump_date DESC
                     LIMIT {$offset}, {$perpage}"
                );
                while($row=$db->get_row()) $results[] = $row;
            }
        } elseif( $mode === 'today' ) {
            $metatags['title'] = sprintf($lang['forum_search_today_title'], $forum_title);
            $forum_speedbar[''] = $lang['forum_search_today_breadcrumb'];
            
            $today_start = date('Y-m-d 00:00:00');
            $cnt_row = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topics WHERE last_bump_date >= '{$today_start}' AND is_deleted=0 AND is_approved=1");
            $total = intval($cnt_row['cnt']);
            
            if( $total > 0 ) {
                $db->query(
                    "SELECT t.*, u.name AS author_name, c.name AS cat_name
                     FROM " . PREFIX . "_forum_topics t
                     LEFT JOIN " . PREFIX . "_users u ON u.user_id=t.user_id
                     LEFT JOIN " . PREFIX . "_forum_cats c ON c.id=t.cat_id
                     WHERE t.last_bump_date >= '{$today_start}' AND t.is_deleted=0 AND t.is_approved=1
                     ORDER BY t.last_bump_date DESC
                     LIMIT {$offset}, {$perpage}"
                );
                while($row=$db->get_row()) $results[] = $row;
            }
        }
    } else {
        // Standart kelime araması (Min 3 karakter)
        if( mb_strlen($q) < 3 ) {
            msgbox( $lang['forum_err_title'], $lang['forum_search_min_len'] );
            return;
        }

        $safe_q = $db->safesql( $q );
        $metatags['title'] = sprintf($lang['forum_search_results_title'], htmlspecialchars($q, ENT_QUOTES, 'UTF-8'), $forum_title);

        if( $in === 'topics' ) {
            // Konu başlıklarında veya etiketlerde ara
            $total = $db->super_query(
                "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topics
                 WHERE (title LIKE '%{$safe_q}%' OR id IN (
                     SELECT tt.topic_id FROM " . PREFIX . "_forum_topic_tags tt
                     INNER JOIN " . PREFIX . "_forum_tags tag ON tag.id = tt.tag_id
                     WHERE tag.name = '{$safe_q}' OR tag.alt_name = '{$safe_q}'
                 )) AND is_deleted=0 AND is_approved=1"
            );
            $total = intval($total['cnt']);

            if($total > 0) {
                $db->query(
                    "SELECT t.*, u.name AS author_name, c.name AS cat_name
                     FROM " . PREFIX . "_forum_topics t
                     LEFT JOIN " . PREFIX . "_users u ON u.user_id=t.user_id
                     LEFT JOIN " . PREFIX . "_forum_cats c ON c.id=t.cat_id
                     WHERE (t.title LIKE '%{$safe_q}%' OR t.id IN (
                         SELECT tt.topic_id FROM " . PREFIX . "_forum_topic_tags tt
                         INNER JOIN " . PREFIX . "_forum_tags tag ON tag.id = tt.tag_id
                         WHERE tag.name = '{$safe_q}' OR tag.alt_name = '{$safe_q}'
                     )) AND t.is_deleted=0 AND t.is_approved=1
                     ORDER BY t.last_bump_date DESC
                     LIMIT {$offset}, {$perpage}"
                );
                while($row=$db->get_row()) $results[] = $row;
            }
        } else {
            // Mesaj içeriklerinde ara
            $total = $db->super_query(
                "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_posts
                 WHERE text LIKE '%{$safe_q}%' AND is_deleted=0 AND is_approved=1"
            );
            $total = intval($total['cnt']);

            if($total > 0) {
                $db->query(
                    "SELECT p.*, u.name AS author_name, t.title AS topic_title, t.alt_name AS topic_alt, t.id AS tid
                     FROM " . PREFIX . "_forum_posts p
                     LEFT JOIN " . PREFIX . "_users u ON u.user_id=p.user_id
                     LEFT JOIN " . PREFIX . "_forum_topics t ON t.id=p.topic_id
                     WHERE p.text LIKE '%{$safe_q}%' AND p.is_deleted=0 AND p.is_approved=1
                     ORDER BY p.date DESC
                     LIMIT {$offset}, {$perpage}"
                );
                while($row=$db->get_row()) $results[] = $row;
            }
        }
    }
}

// -------------------------------------------------
// 4. SAYFALAMA
// -------------------------------------------------
$pages = ceil($total / $perpage);
$pagination = '';
if($pages > 1) {
    $pagination .= '<div class="flex justify-center gap-1 py-4">';
    for($i=1; $i<=$pages; $i++) {
        $act = ($i==$page) ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100';
        $link = "?do=forum&action=search&page=" . $i;
        if (!empty($q)) $link .= "&q=" . urlencode($q) . "&in=" . $in;
        if (!empty($mode)) $link .= "&mode=" . urlencode($mode);
        $pagination .= '<a href="' . $link . '" class="px-3 py-1.5 text-sm rounded border '.$act.'">'.$i.'</a>';
    }
    $pagination .= '</div>';
}

// -------------------------------------------------
// 5. SONUÇ HTML
// -------------------------------------------------
$results_html = '';
if( ( !empty($q) || !empty($mode) ) && count($results) ) {
    foreach($results as $r) {
        if($in === 'topics') {
            $r_title = htmlspecialchars(stripslashes($r['title']), ENT_QUOTES, 'UTF-8');
            $r_author= htmlspecialchars($r['author_name']?:$lang['forum_ajax_quote_guest'], ENT_QUOTES, 'UTF-8');
            $r_cat   = htmlspecialchars($r['cat_name']?:'—', ENT_QUOTES, 'UTF-8');
            $r_url   = $config['http_home_url'].'forum/topic/'.$r['id'].'-'.$r['alt_name'].'.html';
            $results_html .= <<<R
            <div class="mybb-row">
                <div class="mybb-col-forum">
                    <div class="mybb-indicator"><span class="mybb-double-circle"></span></div>
                    <div class="mybb-forum-info">
                        <div class="mybb-forum-name"><a href="{$r_url}">{$r_title}</a></div>
                        <div class="mybb-forum-desc">Kategori: {$r_cat} &nbsp;•&nbsp; Yazar: {$r_author}</div>
                    </div>
                </div>
                <div class="mybb-col-stats">
                    <div class="mybb-stats-box">
                        <div class="mybb-stats-item"><span>{$lang['forum_search_col_replies']}</span> <span class="mybb-stats-num">{$r['replies']}</span></div>
                        <div class="mybb-stats-item"><span>{$lang['forum_search_col_views']}</span> <span class="mybb-stats-num">{$r['views']}</span></div>
                    </div>
                </div>
                <div class="mybb-col-lastpost">
                    <div class="mybb-lastpost-card">
                        <div class="mybb-lastpost-title"><a href="{$r_url}">{$lang['forum_search_col_lastpost']}</a></div>
                        <div class="mybb-lastpost-meta">{$r['last_bump_date']}</div>
                    </div>
                </div>
            </div>
R;
        } else {
            $r_text = htmlspecialchars(mb_substr(strip_tags(stripslashes($r['text'])),0,120), ENT_QUOTES, 'UTF-8');
            $r_topic= htmlspecialchars(stripslashes($r['topic_title']?:'—'), ENT_QUOTES, 'UTF-8');
            $r_author=htmlspecialchars($r['author_name']?:$lang['forum_ajax_quote_guest'], ENT_QUOTES, 'UTF-8');
            $r_url  = forum_topic_post_url($r['tid'], $r['topic_alt'], intval($r['id']));
            $results_html .= <<<R
            <div class="mybb-row">
                <div class="mybb-col-forum">
                    <div class="mybb-indicator"><span class="mybb-double-circle"></span></div>
                    <div class="mybb-forum-info">
                        <div class="mybb-forum-name"><a href="{$r_url}">Konu: {$r_topic}</a></div>
                        <div class="mybb-forum-desc">Yazan: {$r_author} &nbsp;•&nbsp; Mesaj: {$r_text}...</div>
                    </div>
                </div>
                <div class="mybb-col-stats">
                    <div class="mybb-stats-box">
                        <div class="mybb-stats-item"><span>{$lang['forum_search_col_msg_id']}</span> <span class="mybb-stats-num">#{$r['id']}</span></div>
                    </div>
                </div>
                <div class="mybb-col-lastpost">
                    <div class="mybb-lastpost-card">
                        <div class="mybb-lastpost-title"><a href="{$r_url}">{$lang['forum_search_col_sent']}</a></div>
                        <div class="mybb-lastpost-meta">{$r['date']}</div>
                    </div>
                </div>
            </div>
R;
        }
    }
} elseif( !empty($q) || !empty($mode) ) {
    $results_html = '<div class="p-8 text-center text-gray-400 font-bold">' . $lang['forum_search_no_results'] . '</div>';
}

// -------------------------------------------------
// 6. TPL
// -------------------------------------------------
$tpl->load_template( 'forum/search.tpl' );
$tpl->set( '{q}', htmlspecialchars($q, ENT_QUOTES, 'UTF-8') );
$tpl->set( '{in_topics}', $in==='topics' ? 'checked' : '' );
$tpl->set( '{in_posts}',  $in==='posts'  ? 'checked' : '' );
$tpl->set( '{total}', $total );
$tpl->set( '{results}', $results_html );
$tpl->set( '{pagination}', $pagination );
$tpl->set( '{http_home_url}', $config['http_home_url'] );

if( !empty($q) || !empty($mode) ) {
    $tpl->set_block("'\\[results\\](.*?)\\[/results\\]'si", "\\1");
} else {
    $tpl->set_block("'\\[results\\](.*?)\\[/results\\]'si", "");
}

$tpl->compile('content');
$tpl->clear();
