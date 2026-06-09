<?php
/*
=====================================================
 Forge Forum Engine — Üye Forum Profili
-----------------------------------------------------
 File: engine/modules/forum/actions/profile.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

// -------------------------------------------------
// 1. KULLANICIYI BUL
// -------------------------------------------------
$profile_user = null;
$uid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if( !$uid && isset($_GET['user']) ) {
    $uname = $db->safesql( totranslit( $_GET['user'] ) );
    $row = $db->super_query( "SELECT user_id FROM " . PREFIX . "_users WHERE name = '{$uname}'" );
    $uid = $row['user_id'] ? intval($row['user_id']) : 0;
}
if( !$uid && $f_topic_id > 0 ) {
    // URL'den user_id gelmiş olabilir
    $uid = $f_topic_id;
}

if( !$uid ) {
    msgbox( $lang['forum_err_title'], $lang['forum_prof_user_not_found'] );
    return;
}

$u = $db->super_query(
    "SELECT user_id, name, email, reg_date, lastdate, user_group,
            foto, signature, land, fullname,
            forum_points, forum_rank_id, forum_post_count
     FROM " . PREFIX . "_users WHERE user_id = '{$uid}'"
);
if( !$u || !$u['user_id'] ) {
    msgbox( $lang['forum_err_title'], $lang['forum_prof_user_not_found'] );
    return;
}

// -------------------------------------------------
// 2. RÜTBE
// -------------------------------------------------
$rank = forum_get_user_rank($uid, intval($u["forum_points"]));

// -------------------------------------------------
// 3. İSTATİSTİKLER
// -------------------------------------------------
$topic_count = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topics
     WHERE user_id='{$uid}' AND is_deleted=0 AND is_approved=1"
);
$topic_count = intval( $topic_count['cnt'] );

$post_count = intval( $u['forum_post_count'] );
$points     = intval( $u['forum_points'] );

$reg_date   = $u['reg_date'];
$last_date  = $u['lastdate'];

// -------------------------------------------------
// 4. SON KONULAR
// -------------------------------------------------
$recent_topics = array();
$db->query(
    "SELECT id, title, alt_name, date, replies, views
     FROM " . PREFIX . "_forum_topics
     WHERE user_id='{$uid}' AND is_deleted=0 AND is_approved=1
     ORDER BY date DESC LIMIT 5"
);
while( $row = $db->get_row() ) $recent_topics[] = $row;

// -------------------------------------------------
// 5. SON MESAJLAR
// -------------------------------------------------
$recent_posts = array();
$db->query(
    "SELECT p.id, p.text, p.date, t.id AS tid, t.title AS topic_title, t.alt_name AS topic_alt
     FROM " . PREFIX . "_forum_posts p
     LEFT JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
     WHERE p.user_id='{$uid}' AND p.is_deleted=0 AND p.is_approved=1
     ORDER BY p.date DESC LIMIT 5"
);
while( $row = $db->get_row() ) $recent_posts[] = $row;

// -------------------------------------------------
// 6. SEO
// -------------------------------------------------
$uname = stripslashes( $u['name'] );
$metatags['title'] = sprintf($lang['forum_prof_title'], $uname);
$metatags['description'] = sprintf($lang['forum_prof_meta_desc'], $uname, $points, $post_count);
$forum_speedbar[ $config['http_home_url'].'forum/' ] = $lang['forum_breadcrumb_forum'];
$forum_speedbar[''] = $uname;

// -------------------------------------------------
// 7. TPL
// -------------------------------------------------
$tpl->load_template( 'forum/profile.tpl' );

// Breadcrumb
$home_url  = $config['http_home_url'];
$sep       = '<span style="color:var(--mybb-text-muted); margin:0 5px; font-size:10px;">/</span>';
$bc_style  = 'style="display:flex; align-items:center; flex-wrap:wrap; background:var(--mybb-bg-card); border:1px solid var(--mybb-border-color); border-radius:var(--mybb-radius-sm); padding:0.4rem 0.85rem; margin-bottom:1rem; font-size:11px; font-weight:bold;"';
$link_s    = 'style="color:var(--mybb-text-secondary); text-decoration:none;" onmouseover="this.style.color=\'#2563eb\';" onmouseout="this.style.color=\'var(--mybb-text-secondary)\';"';
$breadcrumb_html  = '<nav ' . $bc_style . '>';
$breadcrumb_html .= '<a href="' . $home_url . '" ' . $link_s . '><i class="fa fa-home" style="margin-right:3px;"></i>' . $lang['forum_breadcrumb_home'] . '</a>';
$breadcrumb_html .= $sep;
$breadcrumb_html .= '<a href="' . $home_url . 'forum/" ' . $link_s . '>' . $lang['forum_breadcrumb_forum'] . '</a>';
$breadcrumb_html .= $sep;
$breadcrumb_html .= '<span style="color:var(--mybb-text-primary);">' . htmlspecialchars($uname, ENT_QUOTES, 'UTF-8') . '</span>';
$breadcrumb_html .= '</nav>';

$tpl->set( '{forum_breadcrumb}', $breadcrumb_html );

$tpl->set( '{user_name}',    htmlspecialchars($uname, ENT_QUOTES, 'UTF-8') );
$tpl->set( '{user_id}',      $uid );
$tpl->set( '{points}',       $points );
$tpl->set( '{topic_count}',  $topic_count );
$tpl->set( '{post_count}',   $post_count );
$tpl->set( '{reg_date}',     langdate('d.m.Y', intval($u['reg_date'])) );
$tpl->set( '{last_date}',    $u['lastdate'] ? langdate('d.m.Y H:i', intval($u['lastdate'])) : '—' );
$tpl->set( '{rank_title}',   $rank ? htmlspecialchars($rank['title'],ENT_QUOTES,'UTF-8') : '' );
$tpl->set( '{rank_color}',   $rank ? htmlspecialchars($rank['color'],ENT_QUOTES,'UTF-8') : '#4b5563' );
$tpl->set( '{rank_icon}',    $rank ? htmlspecialchars($rank['icon'],ENT_QUOTES,'UTF-8') : 'fa-user' );
$tpl->set( '{rank_badge}',   $rank ? htmlspecialchars($rank['badge_text'],ENT_QUOTES,'UTF-8') : '' );
$tpl->set( '{rank_html}',    $rank ? forum_rank_render_badge_html($rank, 'profile') : '' );
if ($rank) {
    $tpl->set("[has-forum-rank]", "");
    $tpl->set("[/has-forum-rank]", "");
} else {
    $tpl->set_block("'\\[has-forum-rank\\](.*?)\\[/has-forum-rank\\]'si", "");
}
$tpl->set( '{http_home_url}',$config['http_home_url'] );
$tpl->set( '{user_land}',    htmlspecialchars($u['land'] ?? '', ENT_QUOTES, 'UTF-8') );
$tpl->set( '{user_fullname}',htmlspecialchars($u['fullname'] ?? '', ENT_QUOTES, 'UTF-8') );

// Avatar (DLE: foto alanında e-posta = Gravatar, dosya adı/URL = upload)
$tpl->set(
    "{avatar_html}",
    forum_user_avatar_html($u["foto"] ?? "", $uname, 120, 46),
);

// Group name & color
global $user_group;
$g_id    = intval($u['user_group']);
$g_name  = isset($user_group[$g_id]) ? htmlspecialchars($user_group[$g_id]['group_name'], ENT_QUOTES, 'UTF-8') : $lang['forum_prof_member'];
$g_color = '#4b5563';
if ($g_id == 1)      $g_color = '#e11d48';
elseif ($g_id == 2)  $g_color = '#2563eb';
elseif ($g_id == 3)  $g_color = '#059669';
$tpl->set( '{group_name}',  $g_name );
$tpl->set( '{group_color}', $g_color );

// Online status
$is_online   = ($u['lastdate'] && (time() - intval($u['lastdate'])) < 1200);
$online_dot  = $is_online
    ? '<span style="display:inline-block;width:10px;height:10px;background:#22c55e;border-radius:50%;border:2px solid #fff;margin-left:6px;vertical-align:middle;" title="' . $lang['forum_prof_online'] . '"></span>'
    : '<span style="display:inline-block;width:10px;height:10px;background:#d1d5db;border-radius:50%;border:2px solid #fff;margin-left:6px;vertical-align:middle;" title="' . $lang['forum_prof_offline'] . '"></span>';
$tpl->set( '{online_dot}', $online_dot );

// PM & Email
$pm_url    = ($is_logged && $uid != intval($member_id['user_id']))
    ? $config['http_home_url'] . 'index.php?do=pm&doaction=newpm&username=' . urlencode($uname)
    : '';
$email_url = (!empty($u['email']))
    ? $config['http_home_url'] . 'index.php?do=feedback&user=' . $uid
    : '';
$tpl->set( '{pm_url}',    $pm_url );
$tpl->set( '{email_url}', $email_url );

// Block tags
$tpl->set_block("'\\[has-pm\\](.*?)\\[/has-pm\\]'si",    $pm_url    ? "\\1" : "");
$tpl->set_block("'\\[has-email\\](.*?)\\[/has-email\\]'si", $email_url ? "\\1" : "");
$tpl->set_block("'\\[has-land\\](.*?)\\[/has-land\\]'si",  !empty($u['land']) ? "\\1" : "");
$tpl->set_block("'\\[has-fullname\\](.*?)\\[/has-fullname\\]'si", !empty($u['fullname']) ? "\\1" : "");

// Son konular
$topics_html = '';
foreach( $recent_topics as $t ) {
    $t_title   = htmlspecialchars( stripslashes($t['title']), ENT_QUOTES, 'UTF-8' );
    $t_url     = $config['http_home_url'] . 'forum/topic/' . $t['id'] . '-' . $t['alt_name'] . '.html';
    $t_date    = langdate('d.m.Y', strtotime($t['date']));
    $topics_html .= <<<T
    <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 1rem; border-bottom:1px solid var(--mybb-border-color); gap:8px;">
        <a href="{$t_url}" style="font-size:11px; font-weight:600; color:var(--mybb-text-primary); text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;" onmouseover="this.style.color='#2563eb';" onmouseout="this.style.color='var(--mybb-text-primary)';">{$t_title}</a>
        <span style="font-size:10px; color:var(--mybb-text-muted); white-space:nowrap; flex-shrink:0;"><i class="fa fa-eye" style="margin-right:2px;"></i>{$t['views']} &nbsp;<i class="fa fa-comment" style="margin-right:2px;"></i>{$t['replies']}</span>
    </div>
T;
}
$tpl->set( '{recent_topics}', $topics_html ?: '<div style="padding:1rem; font-size:11px; color:var(--mybb-text-muted); text-align:center;"><i class="fa fa-inbox" style="display:block; font-size:24px; margin-bottom:6px; opacity:0.4;"></i>' . $lang['forum_prof_no_topics'] . '</div>' );

// Son mesajlar
$posts_html = '';
foreach( $recent_posts as $p ) {
    $p_text  = htmlspecialchars( forum_plain_text_snippet( $p['text'], 90 ), ENT_QUOTES, 'UTF-8' );
    $p_title = htmlspecialchars( stripslashes( $p['topic_title'] ?: '—' ), ENT_QUOTES, 'UTF-8' );
    $p_url   = forum_topic_post_url($p['tid'], $p['topic_alt'], intval($p['id']));
    $p_date  = langdate('d.m.Y', strtotime($p['date']));
    $posts_html .= <<<P
    <div style="padding:8px 1rem; border-bottom:1px solid var(--mybb-border-color);">
        <div style="font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:0.04em; color:var(--mybb-text-muted); margin-bottom:3px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><i class="fa fa-folder-o" style="margin-right:3px; color:#6366f1;"></i>{$p_title}</div>
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px;">
            <a href="{$p_url}" style="font-size:11px; color:var(--mybb-text-primary); text-decoration:none; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; flex:1;" onmouseover="this.style.color='#2563eb';" onmouseout="this.style.color='var(--mybb-text-primary)';">{$p_text}</a>
            <span style="font-size:10px; color:var(--mybb-text-muted); white-space:nowrap; flex-shrink:0; padding-top:1px;">{$p_date}</span>
        </div>
    </div>
P;
}
$tpl->set( '{recent_posts}', $posts_html ?: '<div style="padding:1rem; font-size:11px; color:var(--mybb-text-muted); text-align:center;"><i class="fa fa-comments-o" style="display:block; font-size:24px; margin-bottom:6px; opacity:0.4;"></i>' . $lang['forum_prof_no_posts'] . '</div>' );

$tpl->compile('content');
$tpl->clear();
