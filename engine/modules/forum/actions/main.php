<?php
/*
=====================================================
 Forge Forum Engine — Ana Sayfa (Kategori Listesi)
-----------------------------------------------------
 File: engine/modules/forum/actions/main.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

include_once DLEPlugins::Check(ENGINE_DIR . "/inc/forum/cat_icon_helper.php");

// SEO meta
$metatags["title"] = $forum_title;
$metatags["description"] = $lang['forum_main_meta_desc'];

// Kategorileri çek
$categories = [];
$db->query(
    "SELECT * FROM " .
        PREFIX .
        "_forum_cats WHERE parent_id=0 ORDER BY posi ASC",
);
while ($row = $db->get_row()) {
    $categories[] = $row;
}

$tpl->load_template("forum/index.tpl");

$cat_list = "";
foreach ($categories as $cat) {
    if ($cat["permissions"] != "all" && $is_logged) {
        $allowed = explode(",", $cat["permissions"]);
        if (!in_array($member_id["user_group"], $allowed)) {
            continue;
        }
    }

    $cat_link = $config["http_home_url"] . "forum/" . $cat["alt_name"] . "/";
    $icon     = trim((string) ($cat["icon"] ?? ""));
    $name     = htmlspecialchars($cat["name"], ENT_QUOTES, "UTF-8");
    $desc     = htmlspecialchars($cat["description"], ENT_QUOTES, "UTF-8");
    $topics   = intval($cat["topic_count"]);
    $posts    = intval($cat["post_count"]);

    // Alt kategoriler (Sub-categories)
    $sub_cats = [];
    $db->query(
        "SELECT * FROM " .
            PREFIX .
            "_forum_cats WHERE parent_id='{$cat["id"]}' ORDER BY posi ASC",
    );
    while ($srow = $db->get_row()) {
        $sub_cats[] = $srow;
    }

    $subs = "";
    foreach ($sub_cats as $sub) {
        $sub_link   = $config["http_home_url"] . "forum/" . $sub["alt_name"] . "/";
        $sub_name   = htmlspecialchars($sub["name"], ENT_QUOTES, "UTF-8");
        $sub_topics = intval($sub["topic_count"]);
        $sub_posts  = intval($sub["post_count"]);

        // 3. seviye alt forumlar
        $sub_sub_list = [];
        $db->query("SELECT * FROM " . PREFIX . "_forum_cats WHERE parent_id='{$sub["id"]}' ORDER BY posi ASC");
        while ($ss = $db->get_row()) {
            $ss_link        = $config["http_home_url"] . "forum/" . $ss["alt_name"] . "/";
            $ss_name        = htmlspecialchars($ss["name"], ENT_QUOTES, "UTF-8");
            $sub_sub_list[] = '<a href="' . $ss_link . '">' . $ss_name . '</a>';
        }
        $sub_subs = implode(", ", $sub_sub_list);

        // Son mesaj bilgisi
        $lp = null;
        if (intval($sub['last_post_id']) > 0) {
            $lp = $db->super_query("
                SELECT p.date, u.name as username, u.foto,
                       t.title as topic_title, t.id as topic_id, t.alt_name as topic_alt, p.id as post_id
                FROM " . PREFIX . "_forum_posts p
                JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
                LEFT JOIN " . PREFIX . "_users u ON u.user_id = p.user_id
                WHERE p.id = '{$sub['last_post_id']}'
            ");
            if (!isset($lp['post_id'])) $lp = null;
        }

        if ($lp) {
            $lp_topic_title_full = htmlspecialchars(stripslashes($lp['topic_title']), ENT_QUOTES, 'UTF-8');
            $lp_topic_title      = mb_strlen($lp_topic_title_full) > 22
                                 ? mb_substr($lp_topic_title_full, 0, 20) . '...'
                                 : $lp_topic_title_full;
            $lp_user = htmlspecialchars($lp['username'] ?: $lang['forum_ajax_quote_guest'], ENT_QUOTES, 'UTF-8');
            $lp_url  = forum_topic_post_url($lp['topic_id'], $lp['topic_alt'], intval($lp['post_id']));
            $lp_date = $lp['date'];

            // Avatar
            if (!empty($lp['foto'])) {
                if (count(explode('@', $lp['foto'])) == 2) {
                    $av_src = 'https://www.gravatar.com/avatar/' . md5(trim($lp['foto'])) . '?s=36';
                } elseif (strpos($lp['foto'], '//') === 0 || strpos($lp['foto'], 'http') === 0) {
                    $av_src = $lp['foto'];
                } else {
                    $av_src = $config['http_home_url'] . 'uploads/fotos/' . $lp['foto'];
                }
                $lp_avatar = '<img src="' . htmlspecialchars($av_src, ENT_QUOTES, 'UTF-8') . '" alt="' . $lp_user . '" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:2px solid var(--mybb-border-color);flex-shrink:0;">';
            } else {
                $lp_avatar = '<span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:#e0eaf8;border:2px solid var(--mybb-border-color);flex-shrink:0;"><i class="fa fa-user" style="font-size:13px;color:#93c5fd;"></i></span>';
            }
        }

        // --- subforum_row.tpl ile derle ---
        $tpl2 = new dle_template();
        $tpl2->dir = TEMPLATE_DIR;
        $tpl2->load_template("forum/subforum_row.tpl");

        $tpl2->set("{sf_url}",         $sub_link);
        $tpl2->set("{sf_name}",        $sub_name);
        $tpl2->set("{sf_description}", htmlspecialchars($sub['description'], ENT_QUOTES, 'UTF-8'));
        $tpl2->set("{sf_topics}",      $sub_topics);
        $tpl2->set("{sf_posts}",       $sub_posts);

        $sf_icon = trim((string) ($sub["icon"] ?? ""));
        if ($sf_icon !== "") {
            $tpl2->set(
                "{sf_icon_html}",
                forum_cat_icon_render_html(
                    $sf_icon,
                    "",
                    "font-size:13px;color:var(--mybb-text-secondary);width:16px;height:16px;object-fit:contain;",
                ),
            );
            $tpl2->set("[sf-has-icon]", "");
            $tpl2->set("[/sf-has-icon]", "");
            $tpl2->set_block("'\\[sf-no-icon\\](.*?)\\[/sf-no-icon\\]'si", "");
        } else {
            $tpl2->set("{sf_icon_html}", "");
            $tpl2->set_block("'\\[sf-has-icon\\](.*?)\\[/sf-has-icon\\]'si", "");
            $tpl2->set("[sf-no-icon]", "");
            $tpl2->set("[/sf-no-icon]", "");
        }

        if ($sub_subs) {
            $tpl2->set("{sf_subforums}", $sub_subs);
            $tpl2->set("[sf-has-subforums]",  "");
            $tpl2->set("[/sf-has-subforums]", "");
        } else {
            $tpl2->set("{sf_subforums}", "");
            $tpl2->set_block("'\\[sf-has-subforums\\](.*?)\\[/sf-has-subforums\\]'si", "");
        }

        if ($lp) {
            $tpl2->set("[sf-has-lastpost]",  "");
            $tpl2->set("[/sf-has-lastpost]", "");
            $tpl2->set_block("'\\[sf-no-lastpost\\](.*?)\\[/sf-no-lastpost\\]'si", "");
            $tpl2->set("{sf_last_url}",        $lp_url);
            $tpl2->set("{sf_last_topic}",      $lp_topic_title);
            $tpl2->set("{sf_last_topic_full}", $lp_topic_title_full);
            $tpl2->set("{sf_last_date}",       $lp_date);
            $tpl2->set("{sf_last_user}",       $lp_user);
            $tpl2->set("{sf_last_avatar}",     $lp_avatar);
        } else {
            $tpl2->set_block("'\\[sf-has-lastpost\\](.*?)\\[/sf-has-lastpost\\]'si", "");
            $tpl2->set("[sf-no-lastpost]",  "");
            $tpl2->set("[/sf-no-lastpost]", "");
            $tpl2->set("{sf_last_url}",        "");
            $tpl2->set("{sf_last_topic}",      "");
            $tpl2->set("{sf_last_topic_full}", "");
            $tpl2->set("{sf_last_date}",       "");
            $tpl2->set("{sf_last_user}",       "");
            $tpl2->set("{sf_last_avatar}",     "");
        }
        unset($lp);

        $tpl2->compile("sf_row");
        $subs .= $tpl2->result["sf_row"];
        unset($tpl2);
    }

    // Kategori wrapper — yapısal kısım (sadece başlık içeriği değişkendir, HTML iskeleti sabit)
    $cat_icon_html = forum_cat_icon_render_html(
        $icon,
        "mr-2",
        "font-size:14px;opacity:0.85;width:16px;height:16px;object-fit:contain;",
    );
    $cat_list .= '<div class="mybb-category-block">'
        . '<div class="mybb-cat-header">'
        .   '<span class="mybb-cat-title">' . $cat_icon_html . $name . '</span>'
        .   '<span class="mybb-cat-toggle"><i class="fa fa-minus"></i></span>'
        . '</div>'
        . ( $subs
            ? '<div class="mybb-sub-header">'
              .   '<div class="mybb-col-forum">' . $lang['forum_main_col_forum'] . '</div>'
              .   '<div class="mybb-col-stats">' . $lang['forum_main_col_stats'] . '</div>'
              .   '<div class="mybb-col-lastpost">' . $lang['forum_main_col_lastpost'] . '</div>'
              . '</div>'
              . '<div class="mybb-cat-rows">' . $subs . '</div>'
            : '<div class="mybb-cat-rows" style="padding: 0.9rem 1.2rem; color: var(--mybb-text-muted); font-size: 12px; font-style: italic;">'
              .   '<i class="fa fa-folder-o mr-2"></i>' . $lang['forum_main_no_subforums']
              . '</div>'
          )
        . '</div>';
}

$tpl->set(
    "{categories}",
    $cat_list ?: '<div class="alert alert-info">' . $lang['forum_main_no_cats'] . '</div>',
);
$tpl->set("{forum_title}",    $forum_title);
$tpl->set("{http_home_url}",  $config["http_home_url"]);

// -------------------------------------------------
// Online Üyeler & İstatistikler
// -------------------------------------------------
$online_time = time() - 900;
$db->query("SELECT user_id, name, user_group FROM " . USERPREFIX . "_users WHERE lastdate > '{$online_time}' ORDER BY name ASC");
$online_members       = [];
$online_members_count = 0;
while ($row = $db->get_row()) {
    $online_members_count++;
    $profile_url = $config['http_home_url'] . "user/" . urlencode($row['name']) . "/";
    $g = intval($row['user_group']);
    if ($g == 1)     $om_style = 'color:#e11d48; font-weight:bold;';
    elseif ($g == 2) $om_style = 'color:#2563eb; font-weight:bold;';
    elseif ($g == 3) $om_style = 'color:#059669; font-weight:bold;';
    else             $om_style = 'color:#374151;';
    $online_members[] = '<a href="' . $profile_url . '" style="' . $om_style . '" class="hover:underline">'
        . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</a>';
}
$online_members_html = $online_members_count > 0
    ? implode(', ', $online_members)
    : '<span class="text-gray-400 italic">' . $lang['forum_main_no_active_users'] . '</span>';

$total_topics_row = $db->super_query("SELECT COUNT(*) as cnt FROM " . PREFIX . "_forum_topics WHERE is_deleted=0");
$total_topics     = intval($total_topics_row['cnt']);

$total_posts_row = $db->super_query("SELECT COUNT(*) as cnt FROM " . PREFIX . "_forum_posts WHERE is_deleted=0");
$total_posts     = intval($total_posts_row['cnt']);

$total_users_row = $db->super_query("SELECT COUNT(*) as cnt FROM " . USERPREFIX . "_users");
$total_users     = intval($total_users_row['cnt']);

$newest_user_row = $db->super_query("SELECT name FROM " . USERPREFIX . "_users ORDER BY user_id DESC LIMIT 1");
$newest_user     = htmlspecialchars($newest_user_row['name'], ENT_QUOTES, 'UTF-8');
$newest_user_url = $config['http_home_url'] . "user/" . urlencode($newest_user_row['name']) . "/";
$newest_user_html = '<a href="' . $newest_user_url . '" class="font-bold text-blue-700 hover:underline">' . $newest_user . '</a>';

$tpl->set("{online_summary}", sprintf($lang['forum_main_online_summary'], $online_members_count));
$tpl->set("{online_list}",    $online_members_html);
$tpl->set("{total_posts}",    $total_posts);
$tpl->set("{total_threads}",  $total_topics);
$tpl->set("{total_members}",  $total_users);
$tpl->set("{newest_member}",  $newest_user_html);

// DLE NATIVE: compile('content') — echo KESINLIKLE kullanilmaz!
$tpl->compile("content");
$tpl->clear();
