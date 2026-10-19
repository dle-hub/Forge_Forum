<?php
/*
=====================================================
 Forge Forum Engine — Kimler Çevrimiçi
-----------------------------------------------------
 File: engine/modules/forum/actions/online.php
 URL: index.php?do=forum&action=online
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (empty($forum_cfg["enable_online"])) {
    msgbox($lang['forum_access_denied'], $lang['forum_no_perm']);
    return;
}

$online_secs = 900; // 15 dakika
$online_since = time() - $online_secs;

$metatags['title'] = ($lang['forum_online_title'] ?? "Kimler Çevrimiçi") . " - " . $forum_title;
$forum_speedbar[$config['http_home_url'] . 'forum/'] = $lang['forum_breadcrumb_forum'];
$forum_speedbar[''] = $lang['forum_online_title'] ?? "Kimler Çevrimiçi";

global $user_group;

$rows_html = "";
$count = 0;

$db->query(
    "SELECT user_id, name, user_group, lastdate, foto FROM " . USERPREFIX . "_users
     WHERE lastdate > '{$online_since}' AND user_id > 0
     ORDER BY name ASC",
);
while ($o = $db->get_row()) {
    $count++;
    $oid = intval($o["user_id"]);
    $oname = htmlspecialchars(stripslashes($o["name"]), ENT_QUOTES, "UTF-8");
    $gid = intval($o["user_group"]);
    $gname = isset($user_group[$gid])
        ? htmlspecialchars($user_group[$gid]["group_name"], ENT_QUOTES, "UTF-8")
        : ($lang['forum_prof_member'] ?? "Üye");
    $avatar = forum_user_avatar_html($o["foto"] ?? "", stripslashes($o["name"]), 80, 32);
    $profile_url = $config['http_home_url'] . "index.php?do=forum&action=profile&id=" . $oid;

    $rows_html .=
        '<a class="forum-online__item" href="' . $profile_url . '">' .
        '<span class="forum-online__avatar">' . $avatar . "</span>" .
        '<span class="forum-online__name">' . $oname . "</span>" .
        '<span class="forum-online__group">' . $gname . "</span>" .
        "</a>";
}

$tpl->load_template("forum/online.tpl");
forum_set_ui_lang($tpl, $lang);
$tpl->set("{forum_breadcrumb}", "{forum_breadcrumb}");
$tpl->set("{online_title}", htmlspecialchars($lang['forum_online_title'] ?? "Kimler Çevrimiçi", ENT_QUOTES, "UTF-8"));
$tpl->set("{online_count}", $count);
$tpl->set("{online_rows}", $rows_html ?: '<div class="forum-rows__empty">' . ($lang['forum_online_none'] ?? "Şu anda çevrimiçi üye yok.") . "</div>");
$tpl->set("{http_home_url}", $config['http_home_url']);

$tpl->compile("content");
$tpl->clear();
