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
