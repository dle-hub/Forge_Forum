<?php
/*
=====================================================
 Forge Forum Engine — Frontend Router (Trafik Polisi)
-----------------------------------------------------
 File: engine/modules/forum/init.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

// Veritabanı Otomatik Geçiş (Auto-Migration)
$db->query("SHOW COLUMNS FROM " . PREFIX . "_forum_topics LIKE 'prefix_id'");
if (!$db->num_rows()) {
    $db->query("ALTER TABLE " . PREFIX . "_forum_topics ADD prefix_id INT(11) DEFAULT 0 AFTER cat_id");
}

// Programmatik Bellek Temizleme (Programmatic Cache Clear)
clear_cache();

// Ayarlari veritabanindan cek (cache sorunu cozuldu)
$forum_cfg = [];
$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
while ($row = $db->get_row()) {
    $forum_cfg[$row["name"]] = stripslashes($row["value"]);
}

$forum_cfg = array_merge(
    [
        "topics_per_page" => 20,
        "posts_per_page" => 15,
        "allow_guest_view" => 1,
        "allow_guest_post" => 0,
        "require_approval" => 0,
        "enable_likes" => 1,
        "enable_dislikes" => 1,
        "enable_notifications" => 1,
        "enable_subscriptions" => 1,
        "enable_ranks" => 1,
        "enable_polls" => 1,
        "editor_enabled" => 1,
        "editor_type" => "froala",
        "editor_type_topic" => "",
        "editor_type_reply" => "",
        "editor_upload_groups" => "1,2,3",
        "enable_quotes" => 1,
        "enable_mentions" => 1,
        "flood_control_sec" => 30,
        "theme_header_color" => "#1d2d44",
    ],
    $forum_cfg,
);

include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/rank_helpers.php",
);
include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/editor_helpers.php",
);

if (!function_exists("forum_normalize_header_color")) {
    function forum_normalize_header_color($color)
    {
        $color = strtolower(trim((string) $color));
        if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $color, $m)) {
            return "#1d2d44";
        }
        if (strlen($color) === 4) {
            return "#" .
                $m[1][0] .
                $m[1][0] .
                $m[1][1] .
                $m[1][1] .
                $m[1][2] .
                $m[1][2];
        }
        return $color;
    }
}

if (!$forum_cfg["allow_guest_view"] && !$is_logged) {
    msgbox($lang['forum_access_denied'], $lang['forum_only_members']);
    return;
}

$forum_title = "Forum";

// Breadcrumb kokleri
$forum_speedbar = [];
$forum_speedbar[$config["http_home_url"]] = $lang['forum_breadcrumb_home'];
$forum_speedbar[$config["http_home_url"] . "forum/"] = $lang['forum_breadcrumb_forum'];

$f_action = isset($_REQUEST["action"])
    ? totranslit($_REQUEST["action"])
    : "main";
$f_cat = isset($_REQUEST["cat"])
    ? $db->safesql(totranslit($_REQUEST["cat"]))
    : "";
$f_topic_id = isset($_REQUEST["topic_id"])
    ? intval($_REQUEST["topic_id"])
    : (isset($_REQUEST["id"])
        ? intval($_REQUEST["id"])
        : 0);
$f_post_id = isset($_REQUEST["post_id"]) ? intval($_REQUEST["post_id"]) : 0;
$f_page_is_last =
    isset($_REQUEST["page"]) && (string) $_REQUEST["page"] === "last";
$f_page = $f_page_is_last
    ? 1
    : (isset($_REQUEST["page"])
        ? max(1, intval($_REQUEST["page"]))
        : 1);

// -------------------------------------------------
// YASAKLI KELİME SANSÜRÜ (WORD FILTER) SİSTEMİ
// -------------------------------------------------
function forum_word_filter($text) {
    global $db;
    static $banned_words = null;
    if ($banned_words === null) {
        $banned_words = [];
        $db->query("SELECT word, replacement FROM " . PREFIX . "_forum_banned_words");
        while ($row = $db->get_row()) {
            $banned_words[] = $row;
        }
    }
    if (empty($banned_words) || empty($text)) {
        return $text;
    }
    foreach ($banned_words as $w) {
        if (empty($w['word'])) continue;
        $word = preg_quote($w['word'], '/');
        $text = preg_replace('/' . $word . '/ui', $w['replacement'], $text);
    }
    return $text;
}

// -------------------------------------------------
// MENTION (ETİKETLEME) VE BİLDİRİM SİSTEMİ
// -------------------------------------------------
function forum_parse_mentions($text, $topic_id, $post_id, $from_uid) {
    global $db, $forum_cfg;
    if (!$forum_cfg["enable_mentions"]) {
        return;
    }
    
    // @KullaniciAdi desenini yakalar (harf, rakam, alt çizgi, çizgi, nokta ve Türkçe karakterler dahil)
    preg_match_all('/@([a-zA-Z0-9_\-\.ğüşöçİĞÜŞÖÇ]+)/u', $text, $matches);
    if (!empty($matches[1])) {
        $usernames = array_unique($matches[1]);
        foreach ($usernames as $username) {
            $username_escaped = $db->safesql(trim($username));
            // DLE kullanıcı tablosunda ara
            $user = $db->super_query("SELECT user_id FROM " . USERPREFIX . "_users WHERE name = '{$username_escaped}'");
            if ($user && isset($user['user_id'])) {
                $target_uid = intval($user['user_id']);
                // Kendine bildirim gönderme
                if ($target_uid == $from_uid) {
                    continue;
                }
                
                // Mükerrer bildirim kontrolü
                $exists = $db->super_query("SELECT id FROM " . PREFIX . "_forum_notifs 
                                            WHERE user_id = '{$target_uid}' AND from_user = '{$from_uid}' 
                                            AND type = 'mention' AND topic_id = '{$topic_id}' AND post_id = '{$post_id}'");
                if (!$exists || !isset($exists['id'])) {
                    $db->query("INSERT INTO " . PREFIX . "_forum_notifs 
                                (user_id, from_user, type, topic_id, post_id, is_read, date)
                                VALUES ('{$target_uid}', '{$from_uid}', 'mention', '{$topic_id}', '{$post_id}', 0, NOW())");
                }
            }
        }
    }
}

// FastRoute degiskenlerini action dosyalarina ilet
$_REQUEST["topic_id"] = $_GET["topic_id"] = $f_topic_id;
$_REQUEST["cat"] = $_GET["cat"] = $f_cat;

$action_file = ENGINE_DIR . "/modules/forum/actions/" . $f_action . ".php";

if (file_exists($action_file)) {
    include DLEPlugins::Check($action_file);
} elseif (file_exists(ENGINE_DIR . "/modules/forum/actions/main.php")) {
    include DLEPlugins::Check(ENGINE_DIR . "/modules/forum/actions/main.php");
} else {
    die($lang['forum_err_main_missing']);
}

// Global olarak derlenen şablonlarda üyelik / bildirim etiketlerini post-process et
if (isset($tpl->result['content'])) {
    $header_color = forum_normalize_header_color(
        $forum_cfg["theme_header_color"] ?? "#1d2d44",
    );
    $theme_style =
        '<style>.forum-wrapper{--mybb-bg-header:' .
        htmlspecialchars($header_color, ENT_QUOTES, "UTF-8") .
        ";}</style>";
    $tpl->result["content"] = $theme_style . $tpl->result["content"];
    if ($is_logged && $forum_cfg["enable_notifications"]) {
        $notif_count_row = $db->super_query("SELECT COUNT(*) as cnt FROM " . PREFIX . "_forum_notifs WHERE user_id = '{$member_id['user_id']}' AND is_read = 0");
        $notif_count = intval($notif_count_row['cnt']);
        
        $tpl->result['content'] = preg_replace("'\\[is-logged\\](.*?)\\[/is-logged\\]'si", "\\1", $tpl->result['content']);
        $tpl->result['content'] = preg_replace("'\\[not-logged\\](.*?)\\[/not-logged\\]'si", "", $tpl->result['content']);
        
        if ($notif_count > 0) {
            $tpl->result['content'] = preg_replace("'\\[has-forum-notifs\\](.*?)\\[/has-forum-notifs\\]'si", "\\1", $tpl->result['content']);
            $tpl->result['content'] = str_replace("{forum_notif_count}", $notif_count, $tpl->result['content']);
        } else {
            $tpl->result['content'] = preg_replace("'\\[has-forum-notifs\\](.*?)\\[/has-forum-notifs\\]'si", "", $tpl->result['content']);
        }
    } else {
        $tpl->result['content'] = preg_replace("'\\[is-logged\\](.*?)\\[/is-logged\\]'si", "", $tpl->result['content']);
        $tpl->result['content'] = preg_replace("'\\[not-logged\\](.*?)\\[/not-logged\\]'si", "\\1", $tpl->result['content']);
        $tpl->result['content'] = preg_replace("'\\[has-forum-notifs\\](.*?)\\[/has-forum-notifs\\]'si", "", $tpl->result['content']);
    }
}

