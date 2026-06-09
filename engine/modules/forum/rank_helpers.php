<?php
/*
=====================================================
 Forge Forum Engine — Rütbe yardımcıları
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (!function_exists("forum_users_table")) {
    function forum_users_table()
    {
        if (defined("USERPREFIX") && USERPREFIX !== "") {
            return USERPREFIX . "_users";
        }

        return PREFIX . "_users";
    }
}

if (!function_exists("forum_ensure_rank_schema")) {
    function forum_ensure_rank_schema()
    {
        global $db;

        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $db->query(
            "SHOW COLUMNS FROM " . PREFIX . "_forum_ranks LIKE 'image'",
        );
        if (!$db->num_rows()) {
            $db->query(
                "ALTER TABLE " .
                    PREFIX .
                    "_forum_ranks ADD COLUMN `image` varchar(255) NOT NULL DEFAULT '' AFTER `icon`",
            );
        }

        $users_table = forum_users_table();
        $db->query("SHOW COLUMNS FROM `{$users_table}` LIKE 'forum_manual_rank_id'");
        if (!$db->num_rows()) {
            $after = "forum_rank_id";
            $db->query("SHOW COLUMNS FROM `{$users_table}` LIKE 'forum_rank_id'");
            if (!$db->num_rows()) {
                $after = "user_id";
            }
            $db->query(
                "ALTER TABLE `{$users_table}` ADD COLUMN `forum_manual_rank_id` INT(11) NOT NULL DEFAULT '0' AFTER `{$after}`",
            );
        }
    }
}

forum_ensure_rank_schema();

if (!function_exists("forum_ranks_enabled")) {
    function forum_ranks_enabled()
    {
        global $forum_cfg;

        return !empty($forum_cfg["enable_ranks"]) &&
            (string) $forum_cfg["enable_ranks"] !== "0";
    }
}

if (!function_exists("forum_rank_by_points")) {
    function forum_rank_by_points($points)
    {
        global $db;

        if (!forum_ranks_enabled()) {
            return null;
        }

        $points = intval($points);
        $rank = null;
        $db->query(
            "SELECT * FROM " .
                PREFIX .
                "_forum_ranks ORDER BY points ASC, id ASC",
        );
        while ($row = $db->get_row()) {
            if ($points >= intval($row["points"])) {
                $rank = $row;
            }
        }

        return $rank;
    }
}

if (!function_exists("forum_rank_by_id")) {
    function forum_rank_by_id($rank_id)
    {
        global $db;

        $rank_id = intval($rank_id);
        if ($rank_id <= 0) {
            return null;
        }

        $rank = $db->super_query(
            "SELECT * FROM " .
                PREFIX .
                "_forum_ranks WHERE id='{$rank_id}'",
        );

        return !empty($rank["id"]) ? $rank : null;
    }
}

if (!function_exists("forum_rank_normalize_icon")) {
    function forum_rank_normalize_icon($icon)
    {
        $icon = trim((string) $icon);
        if ($icon === "") {
            $icon = "fa-user";
        }
        if (strpos($icon, "fa ") !== 0) {
            $icon = preg_replace("/^fa-/", "fa fa-", $icon);
            if (strpos($icon, "fa ") !== 0) {
                $icon = "fa fa-" . ltrim($icon, "fa-");
            }
        }

        return $icon;
    }
}

if (!function_exists("forum_rank_image_url")) {
    function forum_rank_image_url($image)
    {
        global $config;

        $image = trim((string) $image);
        if ($image === "") {
            return "";
        }

        if (preg_match("/^https?:\/\//i", $image)) {
            return $image;
        }

        $image = str_replace(["\\", ".."], ["/", ""], $image);
        $image = ltrim($image, "/");
        if (stripos($image, "ranks/") === 0) {
            $image = substr($image, 6);
        }
        $image = basename($image);
        if ($image === "") {
            return "";
        }

        $skin = "";
        if (defined("TEMPLATE_DIR") && TEMPLATE_DIR) {
            $skin = basename(TEMPLATE_DIR);
        }
        if ($skin === "") {
            $skin = (string) ($config["skin"] ?? "Default");
        }

        $rel = "templates/" . $skin . "/forum/ranks/" . $image;
        if (!file_exists(ROOT_DIR . "/" . $rel)) {
            $alt = "templates/Default/forum/ranks/" . $image;
            if (file_exists(ROOT_DIR . "/" . $alt)) {
                $rel = $alt;
            }
        }

        return $config["http_home_url"] . $rel;
    }
}

if (!function_exists("forum_rank_render_badge_html")) {
    function forum_rank_render_badge_html($rank, $context = "post")
    {
        if (!$rank || empty($rank["title"])) {
            return "";
        }

        $name = htmlspecialchars($rank["title"], ENT_QUOTES, "UTF-8");
        $color = htmlspecialchars($rank["color"] ?: "#001f3f", ENT_QUOTES, "UTF-8");
        $icon = htmlspecialchars(
            forum_rank_normalize_icon($rank["icon"] ?? "fa-user"),
            ENT_QUOTES,
            "UTF-8",
        );
        $img_url = forum_rank_image_url($rank["image"] ?? "");

        if ($img_url !== "") {
            $img_src = htmlspecialchars($img_url, ENT_QUOTES, "UTF-8");
            if ($context === "profile") {
                return '<span class="mybb-forum-rank mybb-forum-rank--image mybb-forum-rank--profile" title="' .
                    $name .
                    '"><img src="' .
                    $img_src .
                    '" alt="' .
                    $name .
                    '" class="mybb-forum-rank-img"></span>';
            }

            return '<div class="mybb-forum-rank mybb-forum-rank--image" title="' .
                $name .
                '"><img src="' .
                $img_src .
                '" alt="' .
                $name .
                '" class="mybb-forum-rank-img"></div>';
        }

        $icon_html = '<i class="' . $icon . ' mybb-forum-rank-icon"></i>';

        if ($context === "profile") {
            return '<span class="mybb-forum-rank mybb-forum-rank--text mybb-forum-rank--profile" style="border-color:' .
                $color .
                "40;background:" .
                $color .
                "14;color:" .
                $color .
                ';">' .
                $icon_html .
                "<span>" .
                $name .
                "</span></span>";
        }

        return '<div class="mybb-forum-rank mybb-forum-rank--text" style="background-color:' .
            $color .
            ';">' .
            $icon_html .
            "<span>" .
            $name .
            "</span></div>";
    }
}

if (!function_exists("forum_sync_user_rank")) {
    function forum_sync_user_rank($user_id, $points = null)
    {
        global $db;

        $user_id = intval($user_id);
        if ($user_id <= 0) {
            return null;
        }

        $users_table = forum_users_table();
        $user = $db->super_query(
            "SELECT forum_points, forum_manual_rank_id FROM `{$users_table}` WHERE user_id='{$user_id}'",
        );
        if (!$user) {
            return null;
        }

        if ($points === null) {
            $points = intval($user["forum_points"] ?? 0);
        } else {
            $points = intval($points);
        }

        if (!forum_ranks_enabled()) {
            $db->query(
                "UPDATE `{$users_table}` SET forum_rank_id='0', forum_manual_rank_id='0' WHERE user_id='{$user_id}'",
            );
            return null;
        }

        $manual_id = intval($user["forum_manual_rank_id"] ?? 0);
        if ($manual_id > 0) {
            $rank = forum_rank_by_id($manual_id);
            $rank_id = $rank ? intval($rank["id"]) : 0;
            $db->query(
                "UPDATE `{$users_table}` SET forum_rank_id='{$rank_id}' WHERE user_id='{$user_id}'",
            );
            return $rank;
        }

        $rank = forum_rank_by_points($points);
        $rank_id = $rank ? intval($rank["id"]) : 0;
        $db->query(
            "UPDATE `{$users_table}` SET forum_rank_id='{$rank_id}' WHERE user_id='{$user_id}'",
        );

        return $rank;
    }
}

if (!function_exists("forum_get_user_rank")) {
    function forum_get_user_rank($user_id, $points = null)
    {
        if (!forum_ranks_enabled()) {
            return null;
        }

        $user_id = intval($user_id);
        if ($user_id <= 0) {
            return null;
        }

        global $db;
        $users_table = forum_users_table();
        $user = $db->super_query(
            "SELECT forum_points, forum_manual_rank_id FROM `{$users_table}` WHERE user_id='{$user_id}'",
        );
        if (!$user) {
            return null;
        }

        $manual_id = intval($user["forum_manual_rank_id"] ?? 0);
        if ($manual_id > 0) {
            $rank = forum_rank_by_id($manual_id);
            if ($rank) {
                return $rank;
            }
        }

        if ($points === null) {
            $points = intval($user["forum_points"] ?? 0);
        }

        return forum_rank_by_points(intval($points));
    }
}

if (!function_exists("forum_assign_manual_rank")) {
    function forum_assign_manual_rank($user_id, $rank_id)
    {
        global $db;

        $user_id = intval($user_id);
        $rank_id = intval($rank_id);
        if ($user_id <= 0) {
            return false;
        }

        if ($rank_id > 0 && !forum_rank_by_id($rank_id)) {
            return false;
        }

        $users_table = forum_users_table();
        $db->query(
            "UPDATE `{$users_table}` SET forum_manual_rank_id='{$rank_id}' WHERE user_id='{$user_id}'",
        );
        forum_sync_user_rank($user_id);

        return true;
    }
}

if (!function_exists("forum_sync_all_user_ranks")) {
    function forum_sync_all_user_ranks()
    {
        global $db;

        $users = [];
        $users_table = forum_users_table();
        $db->query(
            "SELECT user_id, forum_points, forum_manual_rank_id FROM `{$users_table}` WHERE user_id > 0",
        );
        while ($row = $db->get_row()) {
            $users[] = $row;
        }

        foreach ($users as $user) {
            forum_sync_user_rank(
                intval($user["user_id"]),
                intval($user["forum_points"]),
            );
        }
    }
}
