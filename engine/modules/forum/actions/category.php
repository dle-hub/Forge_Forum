<?php
/*
=====================================================
 Forge Forum Engine — Kategori Sayfası
-----------------------------------------------------
 File: engine/modules/forum/actions/category.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

// -------------------------------------------------
// 1. KATEGORİYİ BUL
// -------------------------------------------------
$cat_id = 0;
if( $f_topic_id > 0 ) {
    $cat_id = $f_topic_id;
} elseif( !empty( $f_cat ) ) {
    $row = $db->super_query( "SELECT id FROM " . PREFIX . "_forum_cats WHERE alt_name = '{$f_cat}'" );
    $cat_id = $row['id'] ? intval( $row['id'] ) : 0;
}

if( !$cat_id ) { msgbox( $lang['forum_err_title'], $lang['forum_cat_not_found'] ); return; }

$category = $db->super_query( "SELECT * FROM " . PREFIX . "_forum_cats WHERE id = '{$cat_id}'" );
if( !$category['id'] ) { msgbox( $lang['forum_err_title'], $lang['forum_cat_not_found'] ); return; }

// Yetki kontrolü (misafir dahil tüm gruplar için geçerli)
if( $category['permissions'] != 'all' ) {
    $allowed = explode( ',', $category['permissions'] );
    if( !in_array( $member_id['user_group'], $allowed ) ) {
        msgbox( $lang['forum_access_denied'], $lang['forum_cat_no_perm'] );
        return;
    }
}

// SEO meta
$cat_name = stripslashes( $category['name'] );
$metatags['title']       = $cat_name . " — " . $forum_title;
$metatags['description'] = stripslashes( $category['description'] ) ?: sprintf($lang['forum_cat_topics_meta'], $cat_name);

// Speedbar
$cat_alt = $category['alt_name'];
$forum_speedbar[ $config['http_home_url'] . 'forum/' . $cat_alt . '/' ] = $cat_name;

// -------------------------------------------------
// 2. SAYFALAMA
// -------------------------------------------------
$perpage = max( 1, intval( $forum_cfg['topics_per_page'] ) );
$offset  = ( $f_page - 1 ) * $perpage;

$total = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topics
     WHERE cat_id = '{$cat_id}' AND is_deleted = 0 AND is_approved = 1"
);
$total   = intval( $total['cnt'] );
$pages   = ceil( $total / $perpage );

// -------------------------------------------------
// 3. KONULARI ÇEK
// -------------------------------------------------
$topics = [];

// Sabit konular (sayfalamadan bağımsız)
$db->query(
    "SELECT t.*, u.name AS author_name, u.user_id AS author_id,
            lu.name AS last_name, lu.user_id AS last_uid, lu.foto AS last_foto
     FROM " . PREFIX . "_forum_topics t
     LEFT JOIN " . PREFIX . "_users u ON u.user_id = t.user_id
     LEFT JOIN " . PREFIX . "_users lu ON lu.user_id = t.last_user_id
     WHERE t.cat_id = '{$cat_id}'
       AND t.is_deleted = 0 AND t.is_approved = 1
       AND t.is_pinned = 1
     ORDER BY t.last_post_date DESC"
);
while( $row = $db->get_row() ) { $row['is_pinned'] = 1; $topics[] = $row; }

// Normal konular (sayfalama)
$db->query(
    "SELECT t.*, u.name AS author_name, u.user_id AS author_id,
            lu.name AS last_name, lu.user_id AS last_uid, lu.foto AS last_foto
     FROM " . PREFIX . "_forum_topics t
     LEFT JOIN " . PREFIX . "_users u ON u.user_id = t.user_id
     LEFT JOIN " . PREFIX . "_users lu ON lu.user_id = t.last_user_id
     WHERE t.cat_id = '{$cat_id}'
       AND t.is_deleted = 0 AND t.is_approved = 1
       AND t.is_pinned = 0
     ORDER BY t.last_bump_date DESC
     LIMIT {$offset}, {$perpage}"
);
while( $row = $db->get_row() ) { $row['is_pinned'] = 0; $topics[] = $row; }

// -------------------------------------------------
// 4. TPL'YE GÖNDER
// -------------------------------------------------
$tpl->load_template( 'forum/category.tpl' );
forum_set_ui_lang($tpl, $lang);

$tpl->set( '{cat_name}',         htmlspecialchars( $cat_name, ENT_QUOTES, 'UTF-8' ) );
$tpl->set( '{cat_description}',  htmlspecialchars( stripslashes( $category['description'] ), ENT_QUOTES, 'UTF-8' ) );
$tpl->set( '{cat_total_topics}', $total );
$tpl->set( '{http_home_url}',    $config['http_home_url'] );
$tpl->set( '{cat_alt}',          $cat_alt );
$tpl->set( '{cat_id}',           $cat_id );

// Konu Öneklerini Belleğe Al
$all_prefixes = [];
$db->query("SELECT id, name, color, icon FROM " . PREFIX . "_forum_prefixes");
while ($prow = $db->get_row()) {
    $all_prefixes[$prow['id']] = $prow;
}

// -------------------------------------------------
// 5. KONU SATIRLARINI topic_row.tpl İLE DERLE
// -------------------------------------------------
// Kalıcı okundu kaydı (readlog) — kullanıcı bazlı
$read_map = [];
if ($is_logged && intval($member_id['user_id']) > 0) {
    $ruid = intval($member_id['user_id']);
    $db->query("SELECT topic_id, date, last_read_post FROM " . PREFIX . "_forum_readlog WHERE user_id = '{$ruid}'");
    while ($rr = $db->get_row()) {
        $read_map[intval($rr['topic_id'])] = [
            'date'           => $rr['date'],
            'last_read_post' => intval($rr['last_read_post']),
        ];
    }
}

$topic_rows = '';
foreach( $topics as $t ) {

    // --- Okundu / Okunmadı Tespiti (kalıcı readlog + oturum fallback) ---
    $topic_read_key = "forum_read_" . $t['id'];
    $read_info      = $read_map[intval($t['id'])] ?? null;
    $last_read_date = $read_info['date'] ?? (isset($_SESSION[$topic_read_key]) ? $_SESSION[$topic_read_key] : null);
    $last_read_post = $read_info['last_read_post'] ?? 0;
    $last_post_date = $t['last_post_date'] ?? $t['date'];
    $is_unread      = !$last_read_date || ($last_post_date > $last_read_date);

    // "İlk okunmamışa git" bağlantısı (rozet özelliği açık + konu okunmamışsa)
    $show_unread_link = ($is_unread && !empty($forum_cfg['enable_unread_badge']));
    $first_unread_url = $show_unread_link
        ? forum_topic_unread_url($t['id'], $t['alt_name'], $last_read_post)
        : '';

    // --- Satır CSS sınıfı ---
    if ($t['is_pinned']) {
        $row_class   = 'sabit-row';
        $title_style = '';
    } elseif ($t['is_locked']) {
        $row_class   = '';
        $title_style = 'opacity: 0.75;';
    } elseif ($is_unread) {
        $row_class   = 'forum-row-unread';
        $title_style = 'font-weight: 700;';
    } else {
        $row_class   = '';
        $title_style = '';
    }

    // --- Veri ---
    $title   = htmlspecialchars( forum_word_filter( stripslashes( $t['title'] ) ), ENT_QUOTES, 'UTF-8' );
    $author  = htmlspecialchars( $t['author_name'] ?: $lang['forum_ajax_quote_guest'], ENT_QUOTES, 'UTF-8' );
    $last_by = htmlspecialchars( $t['last_name']   ?: $author,   ENT_QUOTES, 'UTF-8' );
    $replies = intval( $t['replies'] );
    $views   = intval( $t['views'] );
    $topic_url = $config['http_home_url'] . 'forum/topic/' . $t['id'] . '-' . $t['alt_name'] . '.html';
    $last_url  = forum_topic_post_url($t['id'], $t['alt_name'], intval($t['last_post_id']));

    // --- Prefix verisi ---
    $has_prefix  = false;
    $pfx_name    = '';
    $pfx_color   = '';
    $pfx_icon    = '';
    if (isset($t['prefix_id']) && $t['prefix_id'] > 0 && isset($all_prefixes[$t['prefix_id']])) {
        $pfx         = $all_prefixes[$t['prefix_id']];
        $has_prefix  = true;
        $pfx_name    = htmlspecialchars($pfx["name"],  ENT_QUOTES, "UTF-8");
        $pfx_color   = htmlspecialchars($pfx["color"], ENT_QUOTES, "UTF-8");
        $pfx_icon    = htmlspecialchars($pfx["icon"],  ENT_QUOTES, "UTF-8");
    }

    // --- topic_row.tpl ile derle ---
    $tpl2 = new dle_template();
    $tpl2->dir = TEMPLATE_DIR;
    $tpl2->load_template("forum/topic_row.tpl");

    $tpl2->set("{t_row_class}",   $row_class);
    $tpl2->set("{t_title_style}", $title_style);
    $tpl2->set("{t_title}",       $title);
    $tpl2->set("{t_url}",         $topic_url);
    $tpl2->set("{t_author}",      $author);
    $tpl2->set("{t_date}",        $t['date']);
    $tpl2->set("{t_replies}",     $replies);
    $tpl2->set("{t_views}",       $views);
    $tpl2->set("{t_last_url}",          $last_url);
    $tpl2->set("{t_last_topic_short}",   $lang['forum_t_last_msg']);
    $tpl2->set("{t_last_date}",          $t['last_post_date'] ?? '');
    $tpl2->set("{t_last_user}",          $last_by);

    // Son mesaj avatar
    $last_foto = $t['last_foto'] ?? '';
    if (!empty($last_foto)) {
        if (count(explode('@', $last_foto)) == 2) {
            $av_src = 'https://www.gravatar.com/avatar/' . md5(trim($last_foto)) . '?s=36';
        } elseif (strpos($last_foto, '//') === 0 || strpos($last_foto, 'http') === 0) {
            $av_src = $last_foto;
        } else {
            $av_src = $config['http_home_url'] . 'uploads/fotos/' . $last_foto;
        }
        $t_last_avatar = '<img src="' . htmlspecialchars($av_src, ENT_QUOTES, 'UTF-8') . '" alt="' . $last_by . '" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:2px solid var(--forum-border-color);flex-shrink:0;">';
    } else {
        $t_last_avatar = '<span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:#e0eaf8;border:2px solid var(--forum-border-color);flex-shrink:0;"><i class="fa fa-user" style="font-size:13px;color:#93c5fd;"></i></span>';
    }
    $tpl2->set("{t_last_avatar}",        $t_last_avatar);
    $tpl2->set("{t_first_unread_url}",   $first_unread_url);

    // "İlk okunmamışa git" bloğu
    if ($show_unread_link) {
        $tpl2->set("[t-first-unread]",  "");
        $tpl2->set("[/t-first-unread]", "");
    } else {
        $tpl2->set_block("'\\[t-first-unread\\](.*?)\\[/t-first-unread\\]'si", "");
    }

    // Son mesaj bloğu
    if (!empty($t['last_post_id'])) {
        $tpl2->set("[t-has-lastpost]",  "");
        $tpl2->set("[/t-has-lastpost]", "");
    } else {
        $tpl2->set_block("'\\[t-has-lastpost\\](.*?)\\[/t-has-lastpost\\]'si", "");
    }

    // Sabit bloğu
    if ($t['is_pinned']) {
        $tpl2->set("[t-pinned]",  "");
        $tpl2->set("[/t-pinned]", "");
    } else {
        $tpl2->set_block("'\\[t-pinned\\](.*?)\\[/t-pinned\\]'si", "");
    }

    // Kilitli bloğu
    if ($t['is_locked']) {
        $tpl2->set("[t-locked]",  "");
        $tpl2->set("[/t-locked]", "");
    } else {
        $tpl2->set_block("'\\[t-locked\\](.*?)\\[/t-locked\\]'si", "");
    }

    // Okunmadı / Okundu blokları
    if (!$t['is_pinned'] && !$t['is_locked']) {
        if ($is_unread) {
            $tpl2->set("[t-unread]",  "");
            $tpl2->set("[/t-unread]", "");
            $tpl2->set_block("'\\[t-read\\](.*?)\\[/t-read\\]'si", "");
        } else {
            $tpl2->set_block("'\\[t-unread\\](.*?)\\[/t-unread\\]'si", "");
            $tpl2->set("[t-read]",  "");
            $tpl2->set("[/t-read]", "");
        }
    } else {
        $tpl2->set_block("'\\[t-unread\\](.*?)\\[/t-unread\\]'si", "");
        $tpl2->set_block("'\\[t-read\\](.*?)\\[/t-read\\]'si",    "");
    }

    // Prefix bloğu
    if ($has_prefix) {
        $tpl2->set("[t-has-prefix]",  "");
        $tpl2->set("[/t-has-prefix]", "");
        $tpl2->set("{t_prefix_name}",  $pfx_name);
        $tpl2->set("{t_prefix_color}", $pfx_color);
        $tpl2->set("{t_prefix_icon}",  $pfx_icon);
    } else {
        $tpl2->set_block("'\\[t-has-prefix\\](.*?)\\[/t-has-prefix\\]'si", "");
        $tpl2->set("{t_prefix_name}",  "");
        $tpl2->set("{t_prefix_color}", "");
        $tpl2->set("{t_prefix_icon}",  "");
    }

    $tpl2->compile("topic_row_compiled");
    $topic_rows .= $tpl2->result["topic_row_compiled"];
    unset($tpl2);
}

$tpl->set( '{topic_rows}', $topic_rows ?: '<div class="p-8 text-center text-gray-400">' . $lang['forum_no_topics'] . '</div>' );

// -------------------------------------------------
// 6. SAYFALAMA
// -------------------------------------------------
$pagination = '';
if( $pages > 1 ) {
    $pagination .= '<div class="flex justify-center gap-1 py-4">';
    $base = $config['http_home_url'] . 'forum/' . $cat_alt . '/page/';
    for( $i = 1; $i <= $pages; $i++ ) {
        $active = ( $i == $f_page ) ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100';
        $pagination .= '<a href="' . $base . $i . '/" class="px-3 py-1.5 text-sm rounded border ' . $active . '">' . $i . '</a>';
    }
    $pagination .= '</div>';
}
$tpl->set( '{pagination}', $pagination );

// -------------------------------------------------
// Kategori okunmamış konu sayısı (rozet)
// -------------------------------------------------
$cat_unread = 0;
if ($is_logged && !empty($forum_cfg['enable_unread_badge'])) {
    $__umap     = forum_unread_counts_map( intval( $member_id['user_id'] ) );
    $cat_unread = intval( $__umap[ $cat_id ] ?? 0 );
}
$tpl->set( '{cat_unread_count}', $cat_unread );
if ($cat_unread > 0) {
    $tpl->set( '[cat-has-unread]', '' );
    $tpl->set( '[/cat-has-unread]', '' );
    $tpl->set_block( "'\\[cat-no-unread\\](.*?)\\[/cat-no-unread\\]'si", '' );
} else {
    $tpl->set_block( "'\\[cat-has-unread\\](.*?)\\[/cat-has-unread\\]'si", '' );
    $tpl->set( '[cat-no-unread]', '' );
    $tpl->set( '[/cat-no-unread]', '' );
}

// -------------------------------------------------
// Kategori aboneliği (buton durumu)
// -------------------------------------------------
$cat_sub_enabled = ( $is_logged && !empty( $forum_cfg['enable_cat_subscribe'] ) );
$cat_subscribed  = false;
if ( $cat_sub_enabled ) {
    $uid_cs     = intval( $member_id['user_id'] );
    $cs_row     = $db->super_query( "SELECT id FROM " . PREFIX . "_forum_cat_subs WHERE cat_id='{$cat_id}' AND user_id='{$uid_cs}'" );
    $cat_subscribed = !empty( $cs_row['id'] );
}

if ( $cat_sub_enabled ) {
    $tpl->set( '[cat-sub-enabled]', '' );
    $tpl->set( '[/cat-sub-enabled]', '' );
    $tpl->set_block( "'\\[cat-sub-disabled\\](.*?)\\[/cat-sub-disabled\\]'si", '' );
} else {
    $tpl->set_block( "'\\[cat-sub-enabled\\](.*?)\\[/cat-sub-enabled\\]'si", '' );
    $tpl->set( '[cat-sub-disabled]', '' );
    $tpl->set( '[/cat-sub-disabled]', '' );
}

if ( $cat_subscribed ) {
    $tpl->set( '[cat-subscribed]', '' );
    $tpl->set( '[/cat-subscribed]', '' );
    $tpl->set_block( "'\\[cat-not-subscribed\\](.*?)\\[/cat-not-subscribed\\]'si", '' );
} else {
    $tpl->set_block( "'\\[cat-subscribed\\](.*?)\\[/cat-subscribed\\]'si", '' );
    $tpl->set( '[cat-not-subscribed]', '' );
    $tpl->set( '[/cat-not-subscribed]', '' );
}
$tpl->set( '{dle_login_hash}', $dle_login_hash );

$tpl->compile('content');
$tpl->clear();
