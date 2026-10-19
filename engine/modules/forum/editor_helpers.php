<?php
/*
=====================================================
 Forge Forum Engine — Editör yardımcıları
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (!function_exists("forum_load_cfg")) {
    function forum_load_cfg(array $extra_defaults = [])
    {
        global $db;

        $forum_cfg = [];
        $db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
        while ($row = $db->get_row()) {
            $forum_cfg[$row["name"]] = stripslashes($row["value"]);
        }

        $defaults = array_merge(
            [
                "editor_enabled" => "1",
                "editor_type" => "froala",
                "editor_type_topic" => "",
                "editor_type_reply" => "",
                "editor_upload_groups" => "1,2,3",
                "upload_groups" => "1,2,3,4",
            ],
            $extra_defaults,
        );

        return array_merge($defaults, $forum_cfg);
    }
}

if (!function_exists("forum_normalize_editor_type")) {
    function forum_normalize_editor_type($type)
    {
        $type = strtolower(trim((string) $type));
        if ($type === "tiny_mce" || $type === "tiny-mce") {
            $type = "tinymce";
        }

        return in_array($type, ["froala", "tinymce"], true) ? $type : "";
    }
}

if (!function_exists("forum_get_editor_type")) {
    /**
     * @param string $context 'topic' (konu ekle/düzenle) veya 'reply' (yorum/cevap)
     */
    function forum_get_editor_type(array $forum_cfg, $context = "topic")
    {
        $context = $context === "reply" ? "reply" : "topic";
        $key = $context === "reply" ? "editor_type_reply" : "editor_type_topic";

        $type = forum_normalize_editor_type($forum_cfg[$key] ?? "");
        if ($type) {
            return $type;
        }

        $type = forum_normalize_editor_type($forum_cfg["editor_type"] ?? "");
        if ($type) {
            return $type;
        }

        return "froala";
    }
}

if (!function_exists("forum_prepare_editor_content")) {
    function forum_prepare_editor_content($text)
    {
        if (!class_exists("ParseFilter")) {
            include_once ENGINE_DIR . "/classes/parse.class.php";
        }

        $parse = new ParseFilter();
        $text = stripslashes((string) $text);

        $text = preg_replace(
            '/\[img\](https?:\/\/[^\s\]]+)\[\/img\]/i',
            '<img src="$1" alt="">',
            $text,
        );
        $text = preg_replace(
            '/\[img=(https?:\/\/[^\s\]]+)\](.*?)\[\/img\]/i',
            '<img src="$1" alt="$2">',
            $text,
        );
        $text = preg_replace(
            '/\[thumb\](https?:\/\/[^\s\]]+)\[\/thumb\]/i',
            '<a href="$1" target="_blank"><img src="$1" alt=""></a>',
            $text,
        );
        $text = preg_replace(
            '/\[thumb=(https?:\/\/[^\s\]]+)\](.*?)\[\/thumb\]/i',
            '<a href="$1" target="_blank"><img src="$1" alt="$2"></a>',
            $text,
        );

        return $parse->decodeBBCodes($text, true, true);
    }
}

if (!function_exists("forum_sanitize_html")) {
    /**
     * Whitelist tabanlı HTML sanitizasyonu (HTMLPurifier).
     * Hem kayıt (save) hem görüntüleme (render) yolunda XSS koruması için kullanılır;
     * editör içeriği her iki yönde de aynı allow-list ile temizlenir.
     */
    function forum_sanitize_html($html)
    {
        $html = (string) $html;
        if ($html === "") {
            return "";
        }

        if (!class_exists("HTMLPurifier_Config")) {
            require_once ENGINE_DIR .
                "/classes/htmlpurifier/HTMLPurifier.standalone.php";
        }

        static $purifier = null;
        if ($purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set("Core.Encoding", "UTF-8");
            $config->set(
                "HTML.Allowed",
                "p,b,i,u,s,strong,em,span[style],div[style],br,ul,ol,li,a[href|target|rel],img[src|alt|width|height|style],pre,code,blockquote,h1,h2,h3,h4,h5,h6,hr,table[style],tr,td[style],th[style],sub,sup",
            );
            $config->set(
                "CSS.AllowedProperties",
                "color,background-color,font-size,font-weight,font-style,text-align,text-decoration,margin,padding,border,width,max-width,height,float",
            );
            // Disk tabanlı tanım önbelleğini devre dışı bırak (yazılabilir dizin gerektirmez)
            $config->set("Cache.DefinitionImpl", null);
            // DLE'nin HTMLPurifier derlemesinde doctype modülleri yok; img'yi elle tanımla.
            $config->set("HTML.DefinitionID", "forum-html");
            $config->set("HTML.DefinitionRev", 2);
            if ($def = $config->maybeGetRawHTMLDefinition()) {
                $def->addElement("img", "Inline", "Empty", "Common", [
                    "src*" => "URI",
                    "alt" => "Text",
                    "width" => "Pixels",
                    "height" => "Pixels",
                    "style" => "Text",
                ]);
            }
            $purifier = new HTMLPurifier($config);
        }

        return $purifier->purify($html);
    }
}

if (!function_exists("forum_topic_post_url")) {
    function forum_topic_post_url($topic_id, $topic_alt, $post_id = 0)
    {
        global $config, $db, $forum_cfg;

        $tid = intval($topic_id);
        $pid = intval($post_id);
        $alt = (string) $topic_alt;
        $perpage = max(1, intval($forum_cfg["posts_per_page"] ?? 10));

        $base = $config["http_home_url"] . "forum/topic/" . $tid . "-" . $alt;

        if ($pid <= 0) {
            return $base . ".html";
        }

        $row = $db->super_query(
            "SELECT COUNT(*) AS cnt FROM " .
                PREFIX .
                "_forum_posts WHERE topic_id = '{$tid}' AND is_deleted = 0 AND is_approved = 1 AND id <= '{$pid}'",
        );
        $position = max(1, intval($row["cnt"] ?? 1));
        $page = (int) ceil($position / $perpage);

        if ($page > 1) {
            return $base . "/page/" . $page . "/#post-" . $pid;
        }

        return $base . ".html#post-" . $pid;
    }
}

if (!function_exists("forum_first_unread_post_id")) {
    // Bir konudaki ilk okunmamış mesajın ID'sini döndürür (yoksa 0).
    // $last_read_post: kullanıcının en son okuduğu mesaj ID'si (0 = hiç okumamış).
    function forum_first_unread_post_id($topic_id, $last_read_post)
    {
        global $db;

        $tid = intval($topic_id);
        $lr  = intval($last_read_post);

        $row = $db->super_query(
            "SELECT MIN(id) AS pid FROM " .
                PREFIX .
                "_forum_posts WHERE topic_id = '{$tid}' AND is_deleted = 0 AND is_approved = 1 AND id > '{$lr}'",
        );

        return intval($row["pid"] ?? 0);
    }
}

if (!function_exists("forum_topic_unread_url")) {
    // "İlk okunmamışa git" bağlantısı: doğru sayfa + #post-ID (sayfalama destekli).
    // Okunmamış yoksa konu başlangıcına döner.
    function forum_topic_unread_url($topic_id, $topic_alt, $last_read_post)
    {
        global $config;

        $tid = intval($topic_id);
        $pid = forum_first_unread_post_id($tid, $last_read_post);

        if ($pid > 0) {
            return forum_topic_post_url($tid, $topic_alt, $pid);
        }

        return $config["http_home_url"] .
            "forum/topic/" .
            $tid .
            "-" .
            $topic_alt .
            ".html";
    }
}

if (!function_exists("forum_unread_counts_map")) {
    // Kullanıcı için kategori bazlı okunmamış konu sayıları: [cat_id => count].
    // Konu ya hiç okunmamış (readlog yok) ya da son okumadan sonra yeni mesaj gelmişse okunmamış sayılır.
    function forum_unread_counts_map($user_id)
    {
        global $db;

        $uid = intval($user_id);
        $map = [];

        if ($uid <= 0) {
            return $map;
        }

        $db->query(
            "SELECT t.cat_id, COUNT(*) AS cnt
             FROM " .
                PREFIX .
                "_forum_topics t
             LEFT JOIN " .
                PREFIX .
                "_forum_readlog r ON r.topic_id = t.id AND r.user_id = '{$uid}'
             WHERE t.is_deleted = 0 AND t.is_approved = 1
               AND (r.id IS NULL OR r.last_read_post < t.last_post_id)
             GROUP BY t.cat_id",
        );

        while ($row = $db->get_row()) {
            $map[intval($row["cat_id"])] = intval($row["cnt"]);
        }

        return $map;
    }
}

if (!function_exists("forum_user_avatar_url")) {
    function forum_user_avatar_url($foto, $size = 100)
    {
        global $config;

        $foto = trim(stripslashes((string) $foto));
        if ($foto === "") {
            return "";
        }

        if (filter_var($foto, FILTER_VALIDATE_EMAIL)) {
            return "https://www.gravatar.com/avatar/" .
                md5(strtolower(trim($foto))) .
                "?s=" .
                max(16, intval($size));
        }

        if (strpos($foto, "//") === 0) {
            $scheme =
                stripos($config["http_home_url"] ?? "", "https://") === 0
                    ? "https:"
                    : "http:";

            return $scheme . $foto;
        }

        if (preg_match("#^https?://#i", $foto)) {
            return $foto;
        }

        $parsed = @parse_url($foto);
        if (!empty($parsed["host"])) {
            return $foto;
        }

        return $config["http_home_url"] . "uploads/fotos/" . $foto;
    }
}

if (!function_exists("forum_user_avatar_html")) {
    function forum_user_avatar_html(
        $foto,
        $user_name,
        $size = 100,
        $fallback_icon_size = 46,
    ) {
        $url = forum_user_avatar_url($foto, $size);
        $name = htmlspecialchars((string) $user_name, ENT_QUOTES, "UTF-8");

        if ($url !== "") {
            return '<img src="' .
                htmlspecialchars($url, ENT_QUOTES, "UTF-8") .
                '" alt="' .
                $name .
                '" style="width:100%;height:100%;object-fit:cover;">';
        }

        return '<i class="fa fa-user" style="font-size:' .
            intval($fallback_icon_size) .
            'px;color:#93c5fd;"></i>';
    }
}

if (!function_exists("forum_plain_text_snippet")) {
    function forum_plain_text_snippet($text, $limit = 85)
    {
        $text = html_entity_decode(stripslashes((string) $text), ENT_QUOTES, "UTF-8");
        $text = strip_tags($text);

        for ($i = 0; $i < 5; $i++) {
            $next = preg_replace(
                '/\[quote(?:=[^\]]+)?\](.*?)\[\/quote\]/isu',
                '$1 ',
                $text,
            );
            if ($next === $text) {
                break;
            }
            $text = $next;
        }

        $paired = [
            "b",
            "i",
            "u",
            "s",
            "code",
            "hide",
            "spoiler",
            "left",
            "right",
            "center",
            "color",
            "size",
        ];
        foreach ($paired as $tag) {
            $text = preg_replace(
                '/\[' . $tag . '(?:=[^\]]+)?\](.*?)\[\/' . $tag . '\]/isu',
                '$1 ',
                $text,
            );
        }

        $text = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/isu', '$2 ', $text);
        $text = preg_replace('/\[url\](.*?)\[\/url\]/isu', '$1 ', $text);
        $text = preg_replace('/\[email=(.*?)\](.*?)\[\/email\]/isu', '$2 ', $text);
        $text = preg_replace('/\[email\](.*?)\[\/email\]/isu', '$1 ', $text);
        $text = preg_replace(
            '/\[(?:img|thumb|video|audio|media|attach|upload)[^\]]*\]/iu',
            '',
            $text,
        );
        $text = preg_replace('/\[\/[^\]]+\]/iu', '', $text);
        $text = preg_replace('/\[[^\]]+\]/iu', '', $text);
        $text = preg_replace('/\s+/u', ' ', trim($text));

        if ($limit > 0 && mb_strlen($text) > $limit) {
            $text = mb_substr($text, 0, $limit) . '...';
        }

        return $text;
    }
}

if (!function_exists("forum_register_editor_assets")) {
    function forum_register_editor_assets($editor_type, array &$js_array, array &$css_array, array $lang)
    {
        $editor_type = forum_normalize_editor_type($editor_type) ?: "froala";

        if ($editor_type === "froala") {
            $js_array[] = "public/editor/froala/editor.js";
            if (
                file_exists(
                    ROOT_DIR .
                        "/public/editor/froala/languages/{$lang['language_code']}.js",
                )
            ) {
                $js_array[] =
                    "public/editor/froala/languages/{$lang['language_code']}.js";
            }
            $css_array[] = "public/editor/froala/css/editor.css";
        } else {
            $js_array[] = "public/editor/tiny_mce/tinymce.min.js";
            $css_array[] =
                "public/editor/tiny_mce/plugins/dlebutton/dlebutton.css";
        }
    }
}

if (!function_exists("forum_ensure_uploads_table")) {
    function forum_ensure_uploads_table()
    {
        global $db;

        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        // Şema kontrolü yalnızca bir kez çalışır (kilit dosyası ile atlanır)
        $uploads_schema_lock = ENGINE_DIR . '/cache/system/forum_uploads_schema_v1.lock';
        if (file_exists($uploads_schema_lock)) {
            return;
        }

        $db->query(
            "CREATE TABLE IF NOT EXISTS " .
                PREFIX .
                "_forum_uploads (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            post_id INT(11) NOT NULL DEFAULT 0,
            filename VARCHAR(255) NOT NULL,
            filepath VARCHAR(255) NOT NULL,
            filesize INT(11) NOT NULL,
            date DATETIME NOT NULL,
            driver INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY post_id (post_id),
            KEY date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        );

        $check_column = $db->super_query(
            "SHOW COLUMNS FROM " . PREFIX . "_forum_uploads LIKE 'post_id'",
        );
        if (!$check_column) {
            $db->query(
                "ALTER TABLE " .
                    PREFIX .
                    "_forum_uploads ADD post_id INT(11) NOT NULL DEFAULT 0 AFTER user_id, ADD INDEX (post_id)",
            );
        }

        $check_driver = $db->super_query(
            "SHOW COLUMNS FROM " . PREFIX . "_forum_uploads LIKE 'driver'",
        );
        if (!$check_driver) {
            $db->query(
                "ALTER TABLE " .
                    PREFIX .
                    "_forum_uploads ADD driver INT(11) NOT NULL DEFAULT 0",
            );
        }

        @file_put_contents($uploads_schema_lock, (string) time());
    }
}

if (!function_exists("forum_link_pending_uploads")) {
    function forum_link_pending_uploads($post_id, $user_id)
    {
        global $db;

        $post_id = intval($post_id);
        $user_id = intval($user_id);
        if ($post_id < 1 || $user_id < 1) {
            return;
        }

        forum_ensure_uploads_table();

        $db->query(
            "UPDATE " .
                PREFIX .
                "_forum_uploads SET post_id = '{$post_id}' WHERE user_id = '{$user_id}' AND post_id = 0 AND date >= NOW() - INTERVAL 2 HOUR",
        );
    }
}

if (!function_exists("forum_rating_stars_html")) {
    /**
     * Yıldız HTML'i üretir. Salt-okunur modda $avg'e göre dolu/yarım/boş,
     * etkileşimli modda tıklanabilir yıldızlar (data-value) üretir.
     */
    function forum_rating_stars_html($avg, $interactive = false, $current = 0, $topic_id = 0)
    {
        if ($interactive) {
            $current = intval($current);
            $tid = intval($topic_id);
            $out = "";
            for ($i = 1; $i <= 5; $i++) {
                $cls = $i <= $current ? "fa-star" : "fa-star-o";
                $out .=
                    '<a href="#" class="forum-rating-star" data-value="' .
                    $i .
                    '" aria-label="' .
                    $i .
                    '" onclick="forumRateTopic(' .
                    $tid .
                    "," .
                    $i .
                    ');return false;"><i class="fa ' .
                    $cls .
                    '"></i></a>';
            }
            return $out;
        }

        $avg = floatval($avg);
        $full = intval(floor($avg));
        $half = $avg - $full >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;
        return str_repeat('<i class="fa fa-star"></i>', max(0, $full)) .
            ($half ? '<i class="fa fa-star-half-o"></i>' : "") .
            str_repeat('<i class="fa fa-star-o"></i>', max(0, $empty));
    }
}

if (!function_exists("forum_user_is_cat_mod")) {
    /**
     * Kullanıcı belirtilen kategorinin moderatörü mü?
     */
    function forum_user_is_cat_mod($user_id, $cat_id)
    {
        global $db;
        $user_id = intval($user_id);
        $cat_id = intval($cat_id);
        if ($user_id <= 0 || $cat_id <= 0) {
            return false;
        }
        static $cache = [];
        $k = $user_id . ":" . $cat_id;
        if (array_key_exists($k, $cache)) {
            return $cache[$k];
        }
        $r = $db->super_query(
            "SELECT id FROM " . PREFIX . "_forum_cat_mods WHERE user_id='{$user_id}' AND cat_id='{$cat_id}'",
        );
        $cache[$k] = !empty($r["id"]);
        return $cache[$k];
    }
}

if (!function_exists("forum_user_can_moderate_topic")) {
    /**
     * Kullanıcı bu kategoriyi yönetebilir mi? (global mod/yönetici veya kategori modu)
     */
    function forum_user_can_moderate_topic($user_id, $user_group, $cat_id)
    {
        $user_id = intval($user_id);
        if ($user_id <= 0) {
            return false;
        }
        if (intval($user_group) <= 2) {
            return true;
        }
        return forum_user_is_cat_mod($user_id, $cat_id);
    }
}

if (!function_exists("forum_set_ui_lang")) {
    /**
     * Ön yüz şablonlarındaki ortak arayüz etiketlerini dil dosyasından doldurur.
     * Şablonlarda {forum_ui_*} olarak kullanılır.
     */
    function forum_set_ui_lang($tpl, $lang)
    {
        $map = [
            "forum_ui_new_posts" => "forum_search_new_breadcrumb",
            "forum_ui_today_posts" => "forum_search_today_breadcrumb",
            "forum_ui_online" => "forum_online_title",
            "forum_ui_subscriptions" => "forum_sub_my_title",
            "forum_ui_moderation" => "forum_mod_panel",
            "forum_ui_forum_stats" => "forum_stats_title",
            "forum_ui_topic_label" => "forum_stat_topics",
            "forum_ui_posts_label" => "forum_stat_posts",
            "forum_ui_members_label" => "forum_stat_users",
            "forum_ui_stats_nav" => "forum_ui_stats_nav",
            "forum_ui_notifications" => "forum_ui_notifications",
            "forum_ui_mark_all_read" => "forum_ui_mark_all_read",
            "forum_ui_search" => "forum_ui_search",
            "forum_ui_search_placeholder" => "forum_ui_search_placeholder",
            "forum_ui_full_list" => "forum_ui_full_list",
            "forum_ui_newest_member" => "forum_ui_newest_member",
            "forum_ui_notifications_title" => "forum_ui_notifications_title",
            "forum_ui_mark_all_notifs_read" => "forum_ui_mark_all_notifs_read",
            "forum_ui_notifications_recent" => "forum_ui_notifications_recent",
            "forum_ui_no_notifications" => "forum_ui_no_notifications",
            "forum_ui_search_title" => "forum_ui_search_title",
            "forum_ui_back_to_forum" => "forum_ui_back_to_forum",
            "forum_ui_search_filters" => "forum_ui_search_filters",
            "forum_ui_new_topic" => "forum_ui_new_topic",
            "forum_ui_topic_details" => "forum_ui_topic_details",
            "forum_ui_topic_edit" => "forum_ui_topic_edit",
            "forum_ui_move_topic" => "forum_ui_move_topic",
            "forum_ui_merge_topic" => "forum_ui_merge_topic",
            "forum_ui_split_topic" => "forum_ui_split_topic",
            "forum_ui_trash_topic" => "forum_ui_trash_topic",
            "forum_ui_subscribe" => "forum_ui_subscribe",
            "forum_ui_unsubscribe" => "forum_ui_unsubscribe",
            "forum_ui_bump" => "forum_ui_bump",
            "forum_stats_today_topics" => "forum_stats_today_topics",
            "forum_stats_today_posts" => "forum_stats_today_posts",
            "forum_stats_per_day" => "forum_stats_per_day",
            "forum_stats_newest_prefix" => "forum_stats_newest_prefix",
            "forum_search_placeholder" => "forum_search_placeholder",
            "forum_search_in_topics" => "forum_search_in_topics",
            "forum_search_in_posts" => "forum_search_in_posts",
            "forum_search_author" => "forum_search_author",
            "forum_search_author_ph" => "forum_search_author_ph",
            "forum_search_date_from" => "forum_search_date_from",
            "forum_search_date_to" => "forum_search_date_to",
            "forum_search_btn" => "forum_search_btn",
            "forum_search_results_prefix" => "forum_search_results_prefix",
            "forum_search_found" => "forum_search_found",
            "forum_search_col_content" => "forum_search_col_content",
            "forum_search_col_details" => "forum_search_col_details",
            "forum_search_col_activity" => "forum_search_col_activity",
            "forum_new_cat_select" => "forum_new_cat_select",
            "forum_new_title_label" => "forum_new_title_label",
            "forum_new_title_ph" => "forum_new_title_ph",
            "forum_new_text_label" => "forum_new_text_label",
            "forum_new_draft_loaded" => "forum_new_draft_loaded",
            "forum_new_draft_discard" => "forum_new_draft_discard",
            "forum_new_tags_label" => "forum_new_tags_label",
            "forum_new_tags_ph" => "forum_new_tags_ph",
            "forum_new_poll_toggle" => "forum_new_poll_toggle",
            "forum_new_poll_question" => "forum_new_poll_question",
            "forum_new_poll_question_ph" => "forum_new_poll_question_ph",
            "forum_new_poll_options" => "forum_new_poll_options",
            "forum_new_poll_options_ph" => "forum_new_poll_options_ph",
            "forum_new_poll_max" => "forum_new_poll_max",
            "forum_new_poll_days" => "forum_new_poll_days",
            "forum_new_poll_multiple" => "forum_new_poll_multiple",
            "forum_new_editor_hint" => "forum_new_editor_hint",
            "forum_new_submit" => "forum_new_submit",
            "forum_edit_heading" => "forum_edit_heading",
            "forum_edit_info_before" => "forum_edit_info_before",
            "forum_edit_info_after" => "forum_edit_info_after",
            "forum_edit_title_ph" => "forum_edit_title_ph",
            "forum_edit_poll_toggle" => "forum_edit_poll_toggle",
            "forum_edit_cancel" => "forum_edit_cancel",
            "forum_edit_save" => "forum_edit_save",
            "forum_ui_posts_selected" => "forum_ui_posts_selected",
            "forum_ui_add_quotes" => "forum_ui_add_quotes",
            "forum_ui_clear" => "forum_ui_clear",
            "forum_ui_viewing_suffix" => "forum_ui_viewing_suffix",
            "forum_ui_quick_reply" => "forum_ui_quick_reply",
            "forum_ui_editor_reply_hint" => "forum_ui_editor_reply_hint",
            "forum_ui_reply_submit" => "forum_ui_reply_submit",
            "forum_ui_login_required_reply" => "forum_ui_login_required_reply",
            "forum_ui_register" => "forum_ui_register",
            "forum_ui_login" => "forum_ui_login",
            "forum_ui_add_file" => "forum_ui_add_file",
            "forum_ui_uploading" => "forum_ui_uploading",
            "forum_ui_upload_ok" => "forum_ui_upload_ok",
            "forum_ui_upload_fail" => "forum_ui_upload_fail",
            "forum_ui_like_saved" => "forum_ui_like_saved",
            "forum_ui_like_removed" => "forum_ui_like_removed",
            "forum_ui_dislike_saved" => "forum_ui_dislike_saved",
            "forum_ui_dislike_removed" => "forum_ui_dislike_removed",
            "forum_ui_connection_error" => "forum_ui_connection_error",
            "forum_ui_mod_confirm_trash" => "forum_ui_mod_confirm_trash",
            "forum_ui_mod_confirm_generic" => "forum_ui_mod_confirm_generic",
            "forum_ui_mod_title" => "forum_ui_mod_title",
            "forum_ui_mod_move_label" => "forum_ui_mod_move_label",
            "forum_ui_mod_merge_label" => "forum_ui_mod_merge_label",
            "forum_ui_mod_merge_hint" => "forum_ui_mod_merge_hint",
            "forum_ui_mod_split_label" => "forum_ui_mod_split_label",
            "forum_ui_mod_split_select" => "forum_ui_mod_split_select",
            "forum_ui_apply" => "forum_ui_apply",
            "forum_ui_cancel" => "forum_ui_cancel",
            "forum_ui_similar" => "forum_ui_similar",
            "forum_ui_error" => "forum_ui_error",
            "forum_ui_mod_merge_ph" => "forum_ui_mod_merge_ph",
            "forum_ui_pinned_badge" => "forum_ui_pinned_badge",
            "forum_ui_locked_badge" => "forum_ui_locked_badge",
            "forum_ui_done" => "forum_ui_done",
            "forum_ui_report_reason" => "forum_ui_report_reason",
            "forum_ui_report_title" => "forum_ui_report_title",
            "forum_ui_report_empty" => "forum_ui_report_empty",
            "forum_ui_bulk_delete" => "forum_ui_bulk_delete",
            "forum_ui_bulk_confirm_suffix" => "forum_ui_bulk_confirm_suffix",
            "forum_ui_bulk_title" => "forum_ui_bulk_title",
            "forum_ui_unread_topics" => "forum_ui_unread_topics",
            "forum_ui_mark_read" => "forum_ui_mark_read",
            "forum_ui_cat_subscribe" => "forum_ui_cat_subscribe",
            "forum_ui_cat_unsubscribe" => "forum_ui_cat_unsubscribe",
            "forum_ui_category_topics" => "forum_ui_category_topics",
            "forum_ui_best_answer" => "forum_ui_best_answer",
            "forum_ui_message" => "forum_ui_message",
            "forum_ui_message_label" => "forum_ui_message_label",
            "forum_ui_points_label" => "forum_ui_points_label",
            "forum_ui_edit_history" => "forum_ui_edit_history",
            "forum_ui_topic_owner" => "forum_ui_topic_owner",
            "forum_ui_send_email" => "forum_ui_send_email",
            "forum_ui_send_pm" => "forum_ui_send_pm",
            "forum_ui_find_posts" => "forum_ui_find_posts",
            "forum_ui_like" => "forum_ui_like",
            "forum_ui_dislike" => "forum_ui_dislike",
            "forum_ui_edit" => "forum_ui_edit",
            "forum_ui_report" => "forum_ui_report",
            "forum_ui_multiquote_toggle" => "forum_ui_multiquote_toggle",
            "forum_ui_mark_best" => "forum_ui_mark_best",
            "forum_ui_select_bulk" => "forum_ui_select_bulk",
            "forum_ui_back_to_topic" => "forum_ui_back_to_topic",
            "forum_ui_old_versions" => "forum_ui_old_versions",
            "forum_ui_mod_pending_topics" => "forum_ui_mod_pending_topics",
            "forum_ui_mod_pending_posts" => "forum_ui_mod_pending_posts",
            "forum_ui_mod_pending_topics_cnt" => "forum_ui_mod_pending_topics_cnt",
            "forum_ui_mod_pending_posts_cnt" => "forum_ui_mod_pending_posts_cnt",
            "forum_ui_mod_open_reports" => "forum_ui_mod_open_reports",
            "forum_ui_mod_queue" => "forum_ui_mod_queue",
            "forum_ui_mod_reports" => "forum_ui_mod_reports",
            "forum_ui_mod_topics" => "forum_ui_mod_topics",
            "forum_ui_select_all" => "forum_ui_select_all",
            "forum_ui_approve_selected" => "forum_ui_approve_selected",
            "forum_ui_delete_selected" => "forum_ui_delete_selected",
            "forum_ui_confirm_delete_topic" => "forum_ui_confirm_delete_topic",
            "forum_ui_followers" => "forum_ui_followers",
            "forum_ui_following" => "forum_ui_following",
            "forum_ui_points" => "forum_ui_points",
            "forum_ui_topics" => "forum_ui_topics",
            "forum_ui_posts" => "forum_ui_posts",
            "forum_ui_joined" => "forum_ui_joined",
            "forum_ui_last_seen" => "forum_ui_last_seen",
            "forum_ui_recent_topics" => "forum_ui_recent_topics",
            "forum_ui_recent_posts" => "forum_ui_recent_posts",
            "forum_ui_unread_topic" => "forum_ui_unread_topic",
            "forum_ui_new_badge" => "forum_ui_new_badge",
            "forum_ui_subforums" => "forum_ui_subforums",
            "forum_ui_no_posts_yet" => "forum_ui_no_posts_yet",
            "forum_ui_first_unread" => "forum_ui_first_unread",
            "forum_ui_replies_lc" => "forum_ui_replies_lc",
            "forum_ui_views_lc" => "forum_ui_views_lc",
            "forum_ui_topic_label" => "forum_ui_topic_label",
            "forum_ui_tag_label" => "forum_ui_tag_label",
            "forum_ui_tag_empty" => "forum_ui_tag_empty",
            "forum_ui_category_label" => "forum_ui_category_label",
            "forum_ui_author_label" => "forum_ui_author_label",
            "forum_ui_written_by" => "forum_ui_written_by",
            "forum_ui_notif_delete" => "forum_ui_notif_delete",
            "forum_ui_btn_email" => "forum_ui_btn_email",
            "forum_ui_btn_pm" => "forum_ui_btn_pm",
            "forum_ui_btn_find" => "forum_ui_btn_find",
            "forum_ui_btn_edit" => "forum_ui_btn_edit",
            "forum_ui_btn_report" => "forum_ui_btn_report",
            "forum_ui_btn_multiquote" => "forum_ui_btn_multiquote",
            "forum_ui_btn_best" => "forum_ui_btn_best",
            "forum_ui_btn_quote" => "forum_ui_btn_quote",
            "forum_ui_email" => "forum_ui_email",
            "forum_ui_send_message" => "forum_ui_send_message",
            "forum_ui_find_posts_btn" => "forum_ui_find_posts_btn",
            "forum_ui_moderation" => "forum_ui_moderation",
            "forum_ui_js_failed" => "forum_ui_js_failed",
            "forum_ui_js_connection" => "forum_ui_js_connection",
            "forum_ui_js_no_selection" => "forum_ui_js_no_selection",
            "forum_ui_js_approve_suffix" => "forum_ui_js_approve_suffix",
            "forum_ui_js_delete_suffix" => "forum_ui_js_delete_suffix",
            "forum_ui_confirm_trash_report" => "forum_ui_confirm_trash_report",
            "forum_ui_online_members" => "forum_ui_online_members",
            "forum_ui_online" => "forum_ui_online",
            "forum_ui_topic_title_col" => "forum_ui_topic_title_col",
            "forum_ui_statistics" => "forum_ui_statistics",
            "forum_ui_last_post_col" => "forum_ui_last_post_col",
            "forum_ui_none" => "forum_ui_none",
            "forum_ui_solved_badge" => "forum_ui_solved_badge",
            "forum_ui_clear" => "forum_ui_clear",
            "forum_ui_file" => "forum_ui_file",
            "forum_ui_replies_label" => "forum_ui_replies_label",
            "forum_ui_views_label" => "forum_ui_views_label",
            "forum_ui_subscription" => "forum_ui_subscription",
        ];
        foreach ($map as $tag => $key) {
            $tpl->set("{" . $tag . "}", $lang[$key] ?? "");
        }
    }
}
