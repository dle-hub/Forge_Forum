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
$author  = isset($_GET['author']) ? trim((string) $_GET['author']) : '';
$date_from = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
$date_to   = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) { $date_from = ''; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))   { $date_to = ''; }
$page    = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$perpage = 25;
$offset  = ($page-1) * $perpage;

$has_search = (!empty($q) || !empty($mode) || $author !== '' || $date_from !== '' || $date_to !== '');

$results = array();
$total   = 0;

// -------------------------------------------------
// 2. SEO
// -------------------------------------------------
$metatags['title'] = sprintf($lang['forum_search_title'], $forum_title);
$metatags['description'] = $lang['forum_search_desc'];
$metatags['robots'] = 'noindex, nofollow';
$forum_speedbar[''] = $lang['forum_search_breadcrumb'];

// -------------------------------------------------
// 3. ARAMA VEYA FİLTRELEME YAP
// -------------------------------------------------
if( $has_search ) {
    
    if( !empty($mode) ) {
        if( $mode === 'new' ) {
            $metatags['title'] = sprintf($lang['forum_search_new_title'], $forum_title);
            $forum_speedbar[''] = $lang['forum_search_new_breadcrumb'];

            // Okunmamis (session bazli) filtre — category.php/topic.php ile ayni mantik:
            // konu goruntulendiginde $_SESSION['forum_read_<id>'] = last_post_date olarak isaretlenir.
            // Okunmamis = hic goruntulenmemis VEYA son mesaj tarihi okunma tarihinden yeni.
            $unread = array();
            $db->query(
                "SELECT t.*, u.name AS author_name, c.name AS cat_name
                 FROM " . PREFIX . "_forum_topics t
                 LEFT JOIN " . PREFIX . "_users u ON u.user_id = t.user_id
                 LEFT JOIN " . PREFIX . "_forum_cats c ON c.id = t.cat_id
                 WHERE t.is_deleted = 0 AND t.is_approved = 1
                       AND " . forum_cat_access_where("c") . "
                 ORDER BY t.last_bump_date DESC
                 LIMIT 300"
            );
            while( $row = $db->get_row() ) {
                $read_key  = "forum_read_" . intval($row['id']);
                $last_read = isset($_SESSION[$read_key]) ? $_SESSION[$read_key] : null;
                $last_post = !empty($row['last_post_date']) ? $row['last_post_date'] : $row['date'];
                if( !$last_read OR $last_post > $last_read ) {
                    $unread[] = $row;
                }
            }

            $total = count($unread);
            if( $total > 0 ) {
                $results = array_slice($unread, $offset, $perpage);
            }
        } elseif( $mode === 'today' ) {
            $metatags['title'] = sprintf($lang['forum_search_today_title'], $forum_title);
            $forum_speedbar[''] = $lang['forum_search_today_breadcrumb'];
            
            $today_start = date('Y-m-d 00:00:00');
            $cnt_row = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topics t
                INNER JOIN " . PREFIX . "_forum_cats c ON c.id = t.cat_id
                WHERE t.last_bump_date >= '{$today_start}' AND t.is_deleted=0 AND t.is_approved=1
                AND " . forum_cat_access_where("c"));
            $total = intval($cnt_row['cnt']);
            
            if( $total > 0 ) {
                $db->query(
                    "SELECT t.*, u.name AS author_name, c.name AS cat_name
                     FROM " . PREFIX . "_forum_topics t
                     LEFT JOIN " . PREFIX . "_users u ON u.user_id=t.user_id
                     LEFT JOIN " . PREFIX . "_forum_cats c ON c.id=t.cat_id
                     WHERE t.last_bump_date >= '{$today_start}' AND t.is_deleted=0 AND t.is_approved=1
                       AND " . forum_cat_access_where("c") . "
                     ORDER BY t.last_bump_date DESC
                     LIMIT {$offset}, {$perpage}"
                );
                while($row=$db->get_row()) $results[] = $row;
            }
        }
    } else {
        // Kelime / yazar / tarih filtreli arama (kelime varsa min 3 karakter)
        if( !empty($q) && mb_strlen($q) < 3 ) {
            msgbox( $lang['forum_err_title'], $lang['forum_search_min_len'] );
            return;
        }

        $safe_q = $db->safesql( $q );
        if( !empty($q) ) {
            $metatags['title'] = sprintf($lang['forum_search_results_title'], htmlspecialchars($q, ENT_QUOTES, 'UTF-8'), $forum_title);
        }

        // Yazar filtresi: kullanıcı adını id'ye çevir
        $author_ok = false;
        $author_id = 0;
        if( $author !== '' ) {
            $author_sql = $db->safesql( $author );
            $arow = $db->super_query( "SELECT user_id FROM " . USERPREFIX . "_users WHERE name='{$author_sql}'" );
            if( $arow && !empty($arow['user_id']) ) {
                $author_id = intval( $arow['user_id'] );
                $author_ok = true;
            }
        }

        if( $in === 'topics' ) {
            // Konu başlıklarında / etiketlerde ara + yazar + tarih
            $w = "t.is_deleted=0 AND t.is_approved=1 AND " . forum_cat_access_where("c");
            if( !empty($q) ) {
                $w .= " AND (t.title LIKE '%{$safe_q}%' OR t.id IN (
                     SELECT tt.topic_id FROM " . PREFIX . "_forum_topic_tags tt
                     INNER JOIN " . PREFIX . "_forum_tags tag ON tag.id = tt.tag_id
                     WHERE tag.name = '{$safe_q}' OR tag.alt_name = '{$safe_q}'
                 ))";
            }
            if( $author !== '' ) {
                $w .= $author_ok ? " AND t.user_id='{$author_id}'" : " AND t.user_id='-1'";
            }
            if( $date_from !== '' ) $w .= " AND t.date >= '{$date_from} 00:00:00'";
            if( $date_to !== '' )   $w .= " AND t.date <= '{$date_to} 23:59:59'";

            $total = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topics t
                INNER JOIN " . PREFIX . "_forum_cats c ON c.id = t.cat_id
                WHERE {$w}");
            $total = intval($total['cnt']);

            if($total > 0) {
                $db->query(
                    "SELECT t.*, u.name AS author_name, c.name AS cat_name
                     FROM " . PREFIX . "_forum_topics t
                     LEFT JOIN " . PREFIX . "_users u ON u.user_id=t.user_id
                     LEFT JOIN " . PREFIX . "_forum_cats c ON c.id=t.cat_id
                     WHERE {$w}
                     ORDER BY t.last_bump_date DESC
                     LIMIT {$offset}, {$perpage}"
                );
                while($row=$db->get_row()) $results[] = $row;
            }
        } else {
            // Mesaj içeriklerinde ara + yazar + tarih
            $w = "p.is_deleted=0 AND p.is_approved=1 AND t.is_deleted=0 AND t.is_approved=1 AND " . forum_cat_access_where("c");
            if( !empty($q) ) $w .= " AND p.text LIKE '%{$safe_q}%'";
            if( $author !== '' ) {
                $w .= $author_ok ? " AND p.user_id='{$author_id}'" : " AND p.user_id='-1'";
            }
            if( $date_from !== '' ) $w .= " AND p.date >= '{$date_from} 00:00:00'";
            if( $date_to !== '' )   $w .= " AND p.date <= '{$date_to} 23:59:59'";

            $total = $db->super_query(
                "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_posts p
                 INNER JOIN " . PREFIX . "_forum_topics t ON t.id=p.topic_id
                 INNER JOIN " . PREFIX . "_forum_cats c ON c.id=t.cat_id
                 WHERE {$w}"
            );
            $total = intval($total['cnt']);

            if($total > 0) {
                $db->query(
                    "SELECT p.*, u.name AS author_name, t.title AS topic_title, t.alt_name AS topic_alt, t.id AS tid
                     FROM " . PREFIX . "_forum_posts p
                     LEFT JOIN " . PREFIX . "_users u ON u.user_id=p.user_id
                     INNER JOIN " . PREFIX . "_forum_topics t ON t.id=p.topic_id
                     INNER JOIN " . PREFIX . "_forum_cats c ON c.id=t.cat_id
                     WHERE {$w}
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
        if ($author !== '') $link .= "&author=" . urlencode($author);
        if ($date_from !== '') $link .= "&date_from=" . urlencode($date_from);
        if ($date_to !== '') $link .= "&date_to=" . urlencode($date_to);
        if (empty($q) && empty($mode)) $link .= "&in=" . $in;
        $pagination .= '<a href="' . $link . '" class="px-3 py-1.5 text-sm rounded border '.$act.'">'.$i.'</a>';
    }
    $pagination .= '</div>';
}

// -------------------------------------------------
// 5. SONUÇ HTML
// -------------------------------------------------
$results_html = '';
if( $has_search && count($results) ) {
    foreach($results as $r) {
        if($in === 'topics') {
            $r_title = htmlspecialchars(stripslashes($r['title']), ENT_QUOTES, 'UTF-8');
            $r_author= htmlspecialchars($r['author_name']?:$lang['forum_ajax_quote_guest'], ENT_QUOTES, 'UTF-8');
            $r_cat   = htmlspecialchars($r['cat_name']?:'—', ENT_QUOTES, 'UTF-8');
            $r_url   = $config['http_home_url'].'forum/topic/'.$r['id'].'-'.$r['alt_name'].'.html';
            $results_html .= <<<R
            <article class="forum-node">
                <span class="icon-action forum-node__icon" aria-hidden="true"><i class="fa fa-comment"></i></span>
                <div class="forum-node__info">
                    <h3 class="forum-node__title"><a href="{$r_url}">{$r_title}</a></h3>
                    <div class="forum-node__meta">
                        <span class="meta-item">{$lang['forum_ui_category_label']} {$r_cat}</span>
                        <span class="meta-dot" aria-hidden="true"></span>
                        <span class="meta-item">{$lang['forum_ui_author_label']} {$r_author}</span>
                    </div>
                </div>
                <div class="forum-node__stats">
                    <span class="forum-node__stat">{$lang['forum_search_col_replies']} <b>{$r['replies']}</b></span>
                    <span class="forum-node__stat">{$lang['forum_search_col_views']} <b>{$r['views']}</b></span>
                </div>
                <div class="forum-node__last">
                    <span class="forum-node__lastmeta">{$r['last_bump_date']}</span>
                </div>
            </article>
R;
        } else {
            $r_text = htmlspecialchars(mb_substr(strip_tags(stripslashes($r['text'])),0,120), ENT_QUOTES, 'UTF-8');
            $r_topic= htmlspecialchars(stripslashes($r['topic_title']?:'—'), ENT_QUOTES, 'UTF-8');
            $r_author=htmlspecialchars($r['author_name']?:$lang['forum_ajax_quote_guest'], ENT_QUOTES, 'UTF-8');
            $r_url  = forum_topic_post_url($r['tid'], $r['topic_alt'], intval($r['id']));
            $results_html .= <<<R
            <article class="forum-node">
                <span class="icon-action forum-node__icon" aria-hidden="true"><i class="fa fa-comment-o"></i></span>
                <div class="forum-node__info">
                    <h3 class="forum-node__title"><a href="{$r_url}">{$lang['forum_ui_topic_label']} {$r_topic}</a></h3>
                    <div class="forum-node__meta">
                        <span class="meta-item">{$lang['forum_ui_written_by']} {$r_author}</span>
                        <span class="meta-dot" aria-hidden="true"></span>
                        <span class="meta-item">{$r_text}...</span>
                    </div>
                </div>
                <div class="forum-node__stats">
                    <span class="forum-node__stat">{$lang['forum_search_col_msg_id']} <b>#{$r['id']}</b></span>
                </div>
                <div class="forum-node__last">
                    <span class="forum-node__lastmeta">{$r['date']}</span>
                </div>
            </article>
R;
        }
    }
} elseif( $has_search ) {
    $results_html = '<div class="p-8 text-center text-gray-400 font-bold">' . $lang['forum_search_no_results'] . '</div>';
}

// -------------------------------------------------
// 6. TPL
// -------------------------------------------------
$tpl->load_template( 'forum/search.tpl' );
forum_set_ui_lang($tpl, $lang);
$tpl->set( '{q}', htmlspecialchars($q, ENT_QUOTES, 'UTF-8') );
$tpl->set( '{author}', htmlspecialchars($author, ENT_QUOTES, 'UTF-8') );
$tpl->set( '{date_from}', htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8') );
$tpl->set( '{date_to}', htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8') );
$tpl->set( '{in_topics}', $in==='topics' ? 'checked' : '' );
$tpl->set( '{in_posts}',  $in==='posts'  ? 'checked' : '' );
$tpl->set( '{total}', $total );
$tpl->set( '{results}', $results_html );
$tpl->set( '{pagination}', $pagination );
$tpl->set( '{http_home_url}', $config['http_home_url'] );

if( $has_search ) {
    $tpl->set_block("'\\[results\\](.*?)\\[/results\\]'si", "\\1");
} else {
    $tpl->set_block("'\\[results\\](.*?)\\[/results\\]'si", "");
}

$tpl->compile('content');
$tpl->clear();
