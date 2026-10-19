<?php
/*
=====================================================
 Forge Forum Engine — Ön Yüz Moderasyon Paneli
-----------------------------------------------------
 File: engine/modules/forum/actions/mod.php
 URL: index.php?do=forum&action=mod
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (!$is_logged || intval($member_id["user_group"]) > 2) {
    msgbox($lang['forum_access_denied'], $lang['forum_no_perm']);
    return;
}

$metatags['title'] = ($lang['forum_mod_panel'] ?? "Moderasyon") . " - " . $forum_title;
$metatags['header_title'] = '<meta name="robots" content="noindex, nofollow">';
$forum_speedbar[$config['http_home_url'] . 'forum/'] = $lang['forum_breadcrumb_forum'];
$forum_speedbar[''] = $lang['forum_mod_panel'] ?? "Moderasyon";

$admin_base = $config['http_home_url'] . "admin.php?mod=forum&action=";

// -------------------------------------------------
// 1. ONAY BEKLEYEN KONULAR
// -------------------------------------------------
$pending_rows = "";
$pending_count = 0;
$db->query(
    "SELECT id, title, alt_name, user_id, date FROM " . PREFIX . "_forum_topics
     WHERE is_approved=0 AND is_deleted=0
     ORDER BY date DESC LIMIT 50",
);
while ($p = $db->get_row()) {
    $pending_count++;
    $pid = intval($p["id"]);
    $ptitle = htmlspecialchars(stripslashes($p["title"]), ENT_QUOTES, "UTF-8");
    $purl = $config['http_home_url'] . "forum/topic/" . $pid . "-" . $p["alt_name"] . ".html";
    $pdate = htmlspecialchars((string) $p["date"], ENT_QUOTES, "UTF-8");
    $pending_rows .=
        '<div class="forum-node">' .
        '<label class="forum-pending-check-wrap"><input type="checkbox" class="forum-pending-check" value="' . $pid . '"></label>' .
        '<span class="icon-action forum-node__icon" aria-hidden="true"><i class="fa fa-clock-o"></i></span>' .
        '<div class="forum-node__info"><h3 class="forum-node__title"><a href="' . $purl . '">' . $ptitle . "</a></h3>" .
        '<div class="forum-node__meta"><span class="meta-item">' . $pdate . "</span></div></div>" .
        '<div class="forum-node__last" style="display:flex;gap:6px;">' .
        '<button type="button" class="soft-button" onclick="forumApproveTopic(' . $pid . ')"><i class="fa fa-check"></i> ' .
        ($lang['forum_mod_approve'] ?? "Onayla") .
        "</button>" .
        '<button type="button" class="forum-btn-mini forum-report-btn" onclick="forumRejectTopic(' . $pid . ')"><i class="fa fa-trash"></i> ' .
        ($lang['forum_mod_delete'] ?? "Sil") .
        "</button></div></div>";
}

// -------------------------------------------------
// 2. ONAY BEKLEYEN MESAJ SAYISI
// -------------------------------------------------
$pending_posts = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_posts WHERE is_approved=0 AND is_deleted=0",
);
$pending_posts = intval($pending_posts["cnt"]);

// -------------------------------------------------
// 3. AÇIK ŞİKAYETLER
// -------------------------------------------------
$report_rows = "";
$report_count = 0;
$db->query(
    "SELECT r.*, u.name AS reporter_name FROM " . PREFIX . "_forum_reports r
     LEFT JOIN " . USERPREFIX . "_users u ON u.user_id = r.reporter_id
     WHERE r.status=0
     ORDER BY r.date DESC LIMIT 50",
);
while ($rep = $db->get_row()) {
    $report_count++;
    $target_link = "";
    $target_label = "";
    if ($rep["target_type"] === "post") {
        $tp = $db->super_query(
            "SELECT p.id, t.id AS tid, t.alt_name FROM " . PREFIX . "_forum_posts p
             INNER JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
             WHERE p.id='" . intval($rep["target_id"]) . "'",
        );
        if ($tp && $tp["tid"]) {
            $target_link = forum_topic_post_url(intval($tp["tid"]), $tp["alt_name"], intval($rep["target_id"]));
            $target_label = ($lang['forum_mod_report_post'] ?? "Mesaj") . " #" . intval($rep["target_id"]);
        }
    } else {
        $tt = $db->super_query(
            "SELECT id, alt_name FROM " . PREFIX . "_forum_topics WHERE id='" . intval($rep["target_id"]) . "'",
        );
        if ($tt && $tt["id"]) {
            $target_link = $config['http_home_url'] . "forum/topic/" . intval($tt["id"]) . "-" . $tt["alt_name"] . ".html";
            $target_label = ($lang['forum_mod_report_topic'] ?? "Konu") . " #" . intval($tt["id"]);
        }
    }
    $reporter = htmlspecialchars(stripslashes($rep["reporter_name"] ?: ($lang['forum_guest'] ?? "Misafir")), ENT_QUOTES, "UTF-8");
    $reason = htmlspecialchars(mb_substr(strip_tags(stripslashes((string) $rep["reason"])), 0, 160), ENT_QUOTES, "UTF-8");
    $rdate = htmlspecialchars((string) $rep["date"], ENT_QUOTES, "UTF-8");

    $report_rows .=
        '<div class="forum-node">' .
        '<span class="icon-action forum-node__icon" aria-hidden="true"><i class="fa fa-flag"></i></span>' .
        '<div class="forum-node__info"><h3 class="forum-node__title">' .
        ($target_link !== "" ? '<a href="' . $target_link . '">' . $target_label . "</a>" : $target_label) .
        "</h3>" .
        '<div class="forum-node__meta"><span class="meta-item">' . ($lang['forum_mod_reporter'] ?? "Bildiren") . ": " . $reporter . "</span>" .
        '<span class="meta-dot" aria-hidden="true"></span><span class="meta-item">' . $reason . "</span>" .
        '<span class="meta-dot" aria-hidden="true"></span><span class="meta-item">' . $rdate . "</span></div></div>" .
        '<div class="forum-node__last" style="display:flex;gap:6px;flex-wrap:wrap;">' .
        '<button type="button" class="soft-button" onclick="forumReportResolve(' . intval($rep["id"]) . ',1)"><i class="fa fa-check"></i> ' .
        ($lang['forum_mod_resolve'] ?? "Çözüldü") .
        "</button>" .
        '<button type="button" class="forum-btn-mini" onclick="forumReportResolve(' . intval($rep["id"]) . ',2)"><i class="fa fa-ban"></i> ' .
        ($lang['forum_mod_reject'] ?? "Reddet") .
        "</button>" .
        '<button type="button" class="forum-btn-mini forum-report-btn" onclick="forumReportTrash(' . intval($rep["id"]) . ')"><i class="fa fa-trash"></i> ' .
        ($lang['forum_mod_trash'] ?? "Çöpe At") .
        "</button></div></div>";
}

// -------------------------------------------------
// 4. ŞABLON
// -------------------------------------------------
$tpl->load_template("forum/mod.tpl");
forum_set_ui_lang($tpl, $lang);
$tpl->set("{forum_breadcrumb}", "{forum_breadcrumb}");
$tpl->set("{mod_title}", htmlspecialchars($lang['forum_mod_panel'] ?? "Moderasyon", ENT_QUOTES, "UTF-8"));
$tpl->set("{pending_count}", $pending_count);
$tpl->set("{pending_rows}", $pending_rows ?: '<div class="forum-rows__empty">' . ($lang['forum_mod_none'] ?? "Bekleyen kayıt yok.") . "</div>");
$tpl->set("{pending_posts}", $pending_posts);
$tpl->set("{report_count}", $report_count);
$tpl->set("{report_rows}", $report_rows ?: '<div class="forum-rows__empty">' . ($lang['forum_mod_none'] ?? "Bekleyen kayıt yok.") . "</div>");
$tpl->set("{admin_approval_url}", $admin_base . "approval");
$tpl->set("{admin_reports_url}", $admin_base . "reports");
$tpl->set("{admin_topics_url}", $admin_base . "topics");
$tpl->set("{http_home_url}", $config['http_home_url']);
$tpl->set("{dle_login_hash}", $dle_login_hash);

$tpl->compile("content");
$tpl->clear();
