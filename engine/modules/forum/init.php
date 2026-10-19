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
// prefix_id kolonu her forum isteğinde doğrulanır; eski bir kilit dosyasının
// kalması kurulum sonrası ALTER işleminin atlanmasına yol açmamalıdır.
$db->query("SHOW COLUMNS FROM " . PREFIX . "_forum_topics LIKE 'prefix_id'");
if (!$db->num_rows()) {
    $db->query("ALTER TABLE " . PREFIX . "_forum_topics ADD prefix_id INT(11) DEFAULT 0 AFTER cat_id");
}

// Ayarlari veritabanindan cek
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
        "enable_email_notify" => 1,
        "enable_topic_rating" => 1,
        "enable_rss" => 1,
        "enable_sitemap" => 1,
        "enable_solved" => 1,
        "enable_best_answer" => 1,
        "enable_similar" => 1,
        "enable_viewers" => 1,
        "enable_online" => 1,
        "enable_follow" => 1,
        "enable_drafts" => 1,
        "enable_edit_history" => 1,
        "enable_opengraph" => 1,
        "enable_unread_badge" => 1,
        "enable_cat_subscribe" => 1,
        "enable_statistics" => 1,
        "enable_captcha_topic" => 1,
        "enable_captcha_reply" => 1,
        "flood_control_sec" => 30,
        "theme_header_color" => "#1d2d44",
    ],
    $forum_cfg,
);

include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/permission_helpers.php",
);
include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/rank_helpers.php",
);
include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/editor_helpers.php",
);
include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/email_helpers.php",
);
include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/antibot_helpers.php",
);

// E-posta bildirim şablonu + ayarı (v2) — yalnızca bir kez çalışır
$forum_email_lock = ENGINE_DIR . "/cache/system/forum_schema_v2.lock";
if (!file_exists($forum_email_lock)) {
    $db->query(
        "INSERT IGNORE INTO " .
            PREFIX .
            "_forum_settings (`name`, `value`) VALUES ('enable_email_notify', '1')",
    );
    $forum_default_tpl = $db->safesql(forum_default_email_template());
    $forum_email_exists = $db->super_query(
        "SELECT id FROM " . PREFIX . "_email WHERE name='forum' LIMIT 0,1",
    );
    if (!$forum_email_exists || empty($forum_email_exists["id"])) {
        $db->query(
            "INSERT INTO " .
                PREFIX .
                "_email (`name`, `template`, `use_html`) VALUES ('forum', '{$forum_default_tpl}', 1)",
        );
    }
    @file_put_contents($forum_email_lock, (string) time());
}

// Konu puanlama tablosu (v3) — yalnızca bir kez çalışır
$forum_rating_lock = ENGINE_DIR . "/cache/system/forum_schema_v3.lock";
if (!file_exists($forum_rating_lock)) {
    $db->query(
        "CREATE TABLE IF NOT EXISTS `" .
            PREFIX .
            "_forum_topic_ratings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `topic_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `rating` tinyint(1) NOT NULL DEFAULT '0',
            `date` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `topic_user` (`topic_id`,`user_id`),
            KEY `topic_id` (`topic_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );
    $db->query(
        "INSERT IGNORE INTO " .
            PREFIX .
            "_forum_settings (`name`, `value`) VALUES ('enable_topic_rating', '1')",
    );
    @file_put_contents($forum_rating_lock, (string) time());
}

// Çözüldü kolonu + kim görüntülüyor tablosu (v4) — yalnızca bir kez çalışır
$forum_solved_lock = ENGINE_DIR . "/cache/system/forum_schema_v4.lock";
if (!file_exists($forum_solved_lock)) {
    $db->query("SHOW COLUMNS FROM " . PREFIX . "_forum_topics LIKE 'is_solved'");
    if (!$db->num_rows()) {
        $db->query(
            "ALTER TABLE " .
                PREFIX .
                "_forum_topics ADD COLUMN `is_solved` tinyint(1) NOT NULL DEFAULT '0' AFTER `is_locked`",
        );
    }
    $db->query(
        "CREATE TABLE IF NOT EXISTS `" .
            PREFIX .
            "_forum_viewing` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `topic_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `date` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `topic_user` (`topic_id`,`user_id`),
            KEY `topic_id` (`topic_id`),
            KEY `date` (`date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );
    @file_put_contents($forum_solved_lock, (string) time());
}

// En iyi cevap kolonu (v5) — yalnızca bir kez çalışır
$forum_best_lock = ENGINE_DIR . "/cache/system/forum_schema_v5.lock";
if (!file_exists($forum_best_lock)) {
    $db->query("SHOW COLUMNS FROM " . PREFIX . "_forum_topics LIKE 'best_post_id'");
    if (!$db->num_rows()) {
        $db->query(
            "ALTER TABLE " .
                PREFIX .
                "_forum_topics ADD COLUMN `best_post_id` int(11) NOT NULL DEFAULT '0' AFTER `is_solved`",
        );
    }
    @file_put_contents($forum_best_lock, (string) time());
}

// Kullanıcı takip tablosu (v6) — yalnızca bir kez çalışır
$forum_follow_lock = ENGINE_DIR . "/cache/system/forum_schema_v6.lock";
if (!file_exists($forum_follow_lock)) {
    $db->query(
        "CREATE TABLE IF NOT EXISTS `" .
            PREFIX .
            "_forum_follows` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `target_id` int(11) NOT NULL,
            `date` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_target` (`user_id`,`target_id`),
            KEY `target_id` (`target_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );
    @file_put_contents($forum_follow_lock, (string) time());
}

// Mesaj taslakları tablosu (v7) — yalnızca bir kez çalışır
$forum_draft_lock = ENGINE_DIR . "/cache/system/forum_schema_v7.lock";
if (!file_exists($forum_draft_lock)) {
    $db->query(
        "CREATE TABLE IF NOT EXISTS `" .
            PREFIX .
            "_forum_drafts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `type` varchar(20) NOT NULL DEFAULT 'reply',
            `topic_id` int(11) NOT NULL DEFAULT '0',
            `cat_id` int(11) NOT NULL DEFAULT '0',
            `title` varchar(255) NOT NULL DEFAULT '',
            `text` mediumtext NOT NULL,
            `date` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_type_topic` (`user_id`,`type`,`topic_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );
    @file_put_contents($forum_draft_lock, (string) time());
}

// Kategori moderatörleri tablosu (v8) — yalnızca bir kez çalışır
$forum_catmod_lock = ENGINE_DIR . "/cache/system/forum_schema_v8.lock";
if (!file_exists($forum_catmod_lock)) {
    $db->query(
        "CREATE TABLE IF NOT EXISTS `" .
            PREFIX .
            "_forum_cat_mods` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cat_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `cat_user` (`cat_id`,`user_id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );
    @file_put_contents($forum_catmod_lock, (string) time());
}

// Kategori abonelik tablosu + e-posta şablonu (v9) — yalnızca bir kez çalışır
$forum_catsub_lock = ENGINE_DIR . "/cache/system/forum_schema_v9.lock";
if (!file_exists($forum_catsub_lock)) {
    $db->query(
        "CREATE TABLE IF NOT EXISTS `" .
            PREFIX .
            "_forum_cat_subs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `cat_id` int(11) NOT NULL,
            `date` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_cat` (`user_id`,`cat_id`),
            KEY `cat_id` (`cat_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );
    $db->query(
        "INSERT IGNORE INTO " .
            PREFIX .
            "_forum_settings (`name`, `value`) VALUES ('enable_cat_subscribe', '1')",
    );
    if (function_exists("forum_default_cat_email_template")) {
        $forum_cat_tpl = $db->safesql(forum_default_cat_email_template());
        $forum_cat_exists = $db->super_query(
            "SELECT id FROM " . PREFIX . "_email WHERE name='forum_cat' LIMIT 0,1",
        );
        if (!$forum_cat_exists || empty($forum_cat_exists["id"])) {
            $db->query(
                "INSERT INTO " .
                    PREFIX .
                    "_email (`name`, `template`, `use_html`) VALUES ('forum_cat', '{$forum_cat_tpl}', 1)",
            );
        }
    }
    @file_put_contents($forum_catsub_lock, (string) time());
}

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

// -------------------------------------------------
// ŞABLON SETİ FALLBACK + VARLIK YÜKLEME
// Aktif temada forum/ klasörü yoksa (ör. Smart) Default seti kullanılır.
// forum.css / forum.js sadece main.tpl'de elle dahil edilmemişse eklenir.
// -------------------------------------------------
$forum_tpl_fallback_dir = "";
$forum_asset_skin = isset($config["skin"]) && $config["skin"] !== "" ? $config["skin"] : "Default";
$forum_tpl_root = rtrim($tpl->dir, "/\\");

if (!is_dir($forum_tpl_root . "/forum")) {
    $forum_default_tpl_root = ROOT_DIR . "/templates/Default";
    if (is_dir($forum_default_tpl_root . "/forum")) {
        $forum_tpl_fallback_dir = $tpl->dir;
        $tpl->dir = $forum_default_tpl_root;
        $forum_asset_skin = "Default";
    }
}

$forum_asset_base = "templates/" . $forum_asset_skin . "/forum/";
$forum_main_tpl = ROOT_DIR . "/templates/" . $forum_asset_skin . "/main.tpl";
$forum_main_src = is_file($forum_main_tpl) ? (string) file_get_contents($forum_main_tpl) : "";
$forum_theme_main_src = "";
if ($forum_tpl_fallback_dir !== "") {
    $forum_theme_main_tpl = rtrim($forum_tpl_fallback_dir, "/\\") . "/main.tpl";
    $forum_theme_main_src = is_file($forum_theme_main_tpl) ? (string) file_get_contents($forum_theme_main_tpl) : "";
}

if (is_file(ROOT_DIR . "/" . $forum_asset_base . "forum.css") && stripos($forum_main_src, "forum/forum.css") === false) {
    $tpl->css_array[] = $forum_asset_base . "forum.css";
}
if (is_file(ROOT_DIR . "/" . $forum_asset_base . "forum.js") && stripos($forum_main_src, "forum/forum.js") === false) {
    $tpl->js_array[] = $forum_asset_base . "forum.js";
}

// Font Awesome 4.7 — eklenti şablonları fa-* sınıflarını kullanır.
// Bazı temalar (ör. Air) Font Awesome yüklemediği için forum sayfalarında garanti altına alınır.
if (
    is_file(ROOT_DIR . "/public/fonts/fontawesome/styles.min.css") &&
    stripos($forum_main_src, "fontawesome") === false &&
    stripos($forum_theme_main_src, "fontawesome") === false
) {
    $tpl->css_array[] = "public/fonts/fontawesome/styles.min.css";
}

$action_file = ENGINE_DIR . "/modules/forum/actions/" . $f_action . ".php";

if (file_exists($action_file)) {
    include DLEPlugins::Check($action_file);
} elseif (file_exists(ENGINE_DIR . "/modules/forum/actions/main.php")) {
    include DLEPlugins::Check(ENGINE_DIR . "/modules/forum/actions/main.php");
} else {
    die($lang['forum_err_main_missing']);
}

if ($forum_tpl_fallback_dir !== "") {
    $tpl->dir = $forum_tpl_fallback_dir;
}

// Global olarak derlenen şablonlarda üyelik / bildirim etiketlerini post-process et
if (isset($tpl->result['content'])) {
    $header_color = forum_normalize_header_color(
        $forum_cfg["theme_header_color"] ?? "#1d2d44",
    );
    $theme_style =
        '<style>.forum-wrapper{--forum-bg-header:' .
        htmlspecialchars($header_color, ENT_QUOTES, "UTF-8") .
        ";}</style>";
    $tpl->result["content"] = $theme_style . $tpl->result["content"];

    // Tema modu (açık/koyu/sistem): .forum-wrapper'a data-theme ekle
    $forum_theme_mode = in_array($forum_cfg["theme_mode"] ?? "theme", ["theme", "light", "dark", "system"], true)
        ? $forum_cfg["theme_mode"]
        : "theme";

    // "Sistem" modu: cihaz tercihine göre html[data-forum-scheme] ayarla (FOUC olmadan)
    if ($forum_theme_mode === "system") {
        $tpl->result["content"] =
            '<script>try{var _ffm=window.matchMedia("(prefers-color-scheme: dark)");function _ffa(){document.documentElement.setAttribute("data-forum-scheme",_ffm.matches?"dark":"light");}_ffa();if(_ffm.addEventListener){_ffm.addEventListener("change",_ffa);}else if(_ffm.addListener){_ffm.addListener(_ffa);}}catch(e){}</script>' .
            $tpl->result["content"];
    }

    $tpl->result["content"] = str_replace(
        '<div class="forum-wrapper">',
        '<div class="forum-wrapper" data-theme="' . $forum_theme_mode . '">',
        $tpl->result["content"],
    );
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

