<?php
/*
=====================================================
 Forge Forum Engine — Genel Ayarlar (Tam)
-----------------------------------------------------
 File: engine/inc/forum/settings.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/

if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

$defaults = [
    "topics_per_page" => "20",
    "posts_per_page" => "15",
    "hot_topic_replies" => "30",
    "points_per_topic" => "10",
    "points_per_post" => "5",
    "points_per_like" => "2",
    "allow_guest_view" => "1",
    "allow_guest_post" => "0",
    "require_approval" => "0",
    "flood_control_sec" => "30",
    "min_post_length" => "10",
    "max_post_length" => "30000",
    "quote_max_depth" => "3",
    "enable_mentions" => "1",
    "enable_quotes" => "1",
    "bump_cooldown_hours" => "24",
    "bump_max_per_day" => "3",
    "link_min_posts" => "5",
    "link_min_points" => "50",
    "max_attachment_mb" => "5",
    "max_attachments" => "10",
    "allowed_filetypes" => "jpg,jpeg,png,gif,webp,pdf,zip,rar",
    "enable_likes" => "1",
    "enable_dislikes" => "1",
    "enable_notifications" => "1",
    "enable_subscriptions" => "1",
    "enable_email_notify" => "1",
    "enable_topic_rating" => "1",
    "enable_rss" => "1",
    "enable_sitemap" => "1",
    "enable_solved" => "1",
    "enable_best_answer" => "1",
    "enable_similar" => "1",
    "enable_viewers" => "1",
    "enable_online" => "1",
    "enable_follow" => "1",
    "enable_drafts" => "1",
    "enable_edit_history" => "1",
    "enable_opengraph" => "1",
    "enable_unread_badge" => "1",
    "enable_cat_subscribe" => "1",
    "enable_statistics" => "1",
    "enable_captcha_topic" => "1",
    "enable_captcha_reply" => "1",
    "enable_topic_edit_owner" => "1",
    "enable_topic_edit_mods" => "1",
    "enable_ranks" => "1",
    "enable_polls" => "1",
    "editor_enabled" => "1",
    "editor_type" => "froala",
    "editor_type_topic" => "",
    "editor_type_reply" => "",
    "editor_upload_groups" => "1,2,3",
    "upload_groups" => "1,2,3,4",
    "upload_min_posts" => "0",
    "upload_max_per_day" => "5",
    "poll_max_options" => "20",
    "poll_max_selections" => "5",
    "poll_default_days" => "30",
    "theme_header_color" => "#1d2d44",
    "theme_mode" => "theme",
];

$settings = [];
$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
while ($row = $db->get_row()) {
    $settings[$row["name"]] = stripslashes($row["value"]);
}
$cfg = array_merge($defaults, $settings);

if (isset($_POST["save_settings"])) {
    if ($_POST["user_hash"] !== $dle_login_hash) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }
    if (!empty($_POST["editor_type_topic"])) {
        $_POST["editor_type"] = trim($_POST["editor_type_topic"]);
    }

    foreach (array_keys($defaults) as $key) {
        if (in_array($key, ["editor_type_topic", "editor_type_reply"], true)) {
            $value = isset($_POST[$key]) ? trim($_POST[$key]) : "";
        } elseif ($key === "theme_header_color") {
            $value = forum_settings_normalize_color($_POST[$key] ?? "#1d2d44");
        } elseif ($key === "theme_mode") {
            $value = in_array($_POST[$key] ?? "", ["theme", "light", "dark", "system"], true) ? $_POST[$key] : "theme";
        } else {
            $value = isset($_POST[$key]) ? trim($_POST[$key]) : "0";
        }
        $sk = $db->safesql($key);
        $sv = $db->safesql($value);
        $db->query(
            "INSERT INTO " .
                PREFIX .
                "_forum_settings (name, value)
            VALUES ('{$sk}', '{$sv}') ON DUPLICATE KEY UPDATE value = '{$sv}'",
        );
    }

    $forum_cfg = array_merge($defaults, []);
    $db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
    while ($row = $db->get_row()) {
        $forum_cfg[$row["name"]] = stripslashes($row["value"]);
    }
    include_once DLEPlugins::Check(
        ENGINE_DIR . "/modules/forum/rank_helpers.php",
    );
    forum_sync_all_user_ranks();

    msg(
        "success",
        $lang['forum_set_success_title'],
        $lang['forum_set_success_desc'],
        "?mod=forum&action=settings",
    );
    return;
}

function showRow($t, $d, $f)
{
    echo "<tr><td class=\"col-xs-6 col-sm-6 col-md-7\"><h6 class=\"media-heading text-semibold\">{$t}</h6><div class=\"text-muted text-size-small hidden-xs\">{$d}</div></td><td class=\"col-xs-6 col-sm-6 col-md-5\">{$f}</td></tr>";
}
function forumSettingsPanelOpen($id, $title, $visible = false)
{
    $style = $visible ? "" : " style=\"display:none\"";
    echo "<div id=\"{$id}\" class=\"panel panel-flat forum-settings-panel\"{$style}>";
    echo "<div class=\"panel-body border-bottom\">{$title}</div>";
    echo "<table class=\"table table-striped\">";
}
function forumSettingsPanelClose()
{
    echo "</table></div>";
}
function yn($n, $s)
{
    global $lang;
    $y = $s == "1" ? " selected" : "";
    $n2 = $s == "0" ? " selected" : "";
    return "<select class=\"uniform\" name=\"{$n}\" data-width=\"150\"><option value=\"1\"{$y}>" . $lang['forum_set_on'] . "</option><option value=\"0\"{$n2}>" . $lang['forum_set_off'] . "</option></select>";
}
function editorTypeSelect($n, $s)
{
    $froala = $s == "froala" ? " selected" : "";
    $tinymce = $s == "tinymce" ? " selected" : "";
    return "<select class=\"uniform\" name=\"{$n}\" data-width=\"150\"><option value=\"froala\"{$froala}>Froala Editor</option><option value=\"tinymce\"{$tinymce}>TinyMCE</option></select>";
}
function themeModeSelect($n, $s)
{
    global $lang;
    $s = in_array($s, ["theme", "light", "dark", "system"], true) ? $s : "theme";
    $theme = $s === "theme" ? " selected" : "";
    $light = $s === "light" ? " selected" : "";
    $dark = $s === "dark" ? " selected" : "";
    $system = $s === "system" ? " selected" : "";
    $theme_label = $lang["forum_set_theme_follow"] ?? "Tema ile aynı (Otomatik)";
    $light_label = $lang["forum_set_theme_light"] ?? "Açık (Light)";
    $dark_label = $lang["forum_set_theme_dark"] ?? "Koyu (Dark)";
    $system_label = $lang["forum_set_theme_system"] ?? "Sistem (Otomatik)";
    return "<select class=\"uniform\" name=\"{$n}\" data-width=\"180\"><option value=\"theme\"{$theme}>{$theme_label}</option><option value=\"light\"{$light}>{$light_label}</option><option value=\"dark\"{$dark}>{$dark_label}</option><option value=\"system\"{$system}>{$system_label}</option></select>";
}
function num($n, $v, $w = "100")
{
    return "<input type=\"number\" name=\"{$n}\" class=\"form-control width-{$w}\" value=\"" .
        intval($v) .
        "\">";
}
function txt($n, $v, $w = "350")
{
    return "<input type=\"text\" name=\"{$n}\" class=\"form-control width-{$w}\" value=\"" .
        htmlspecialchars($v, ENT_QUOTES, "UTF-8") .
        "\">";
}
function forum_settings_normalize_color($color)
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
function themeHeaderColorPicker($name, $value)
{
    global $lang;
    $value = forum_settings_normalize_color($value);
    $presets = [
        "#1d2d44",
        "#0f172a",
        "#1e3a5f",
        "#2563eb",
        "#1d4ed8",
        "#059669",
        "#0f766e",
        "#7c3aed",
        "#6d28d9",
        "#dc2626",
        "#b91c1c",
        "#d97706",
        "#374151",
        "#111827",
    ];
    $swatches = "";
    foreach ($presets as $preset) {
        $swatches .=
            '<button type="button" class="forum-theme-swatch" data-color="' .
            $preset .
            '" style="width:30px;height:30px;background:' .
            $preset .
            ';border:2px solid #fff;box-shadow:0 0 0 1px #d1d5db;border-radius:4px;margin:0 6px 6px 0;cursor:pointer;" title="' .
            $preset .
            '"></button>';
    }
    return '<div class="forum-theme-color-field" data-default="#1d2d44">'
        . '<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">'
        . '<input type="color" id="forum_theme_header_picker" value="' .
        htmlspecialchars($value, ENT_QUOTES, "UTF-8") .
        '" style="width:54px;height:38px;padding:2px;border:1px solid #d1d5db;border-radius:4px;cursor:pointer;background:#fff;">'
        . '<input type="text" name="' .
        $name .
        '" id="forum_theme_header_hex" class="form-control" style="width:110px;" value="' .
        htmlspecialchars($value, ENT_QUOTES, "UTF-8") .
        '" maxlength="7" placeholder="#1d2d44">'
        . '<span id="forum_theme_header_preview" style="display:inline-flex;align-items:center;justify-content:space-between;min-width:180px;padding:8px 12px;border-radius:4px;color:#fff;font-size:12px;font-weight:600;background:' .
        htmlspecialchars($value, ENT_QUOTES, "UTF-8") .
        ';">' .
        htmlspecialchars($lang["forum_set_theme_header_preview"] ?? "Kategori Başlığı", ENT_QUOTES, "UTF-8") .
        ' <i class="fa fa-minus" style="opacity:.75;"></i></span>'
        . "</div>"
        . '<div style="margin-top:10px;">' .
        $swatches .
        "</div></div>";
}
$forum_set_search = $lang["forum_set_search"] ?? "Ayarlarda ara...";
$forum_set_not_found = $lang["forum_set_not_found"] ?? "Sonuç bulunamadı";
$editor_topic_label = $lang["forum_set_editor_topic"] ?? "Konu ekleme / düzenleme editörü";
$editor_topic_desc = $lang["forum_set_editor_topic_desc"] ?? "Yeni konu ve konu düzenleme için editör.";
$editor_reply_label = $lang["forum_set_editor_reply"] ?? "Yorum / cevap editörü";
$editor_reply_desc = $lang["forum_set_editor_reply_desc"] ?? "Hızlı cevap kutusu için editör.";
?>

<script>
function forumSettingsChangeTab(obj, panelId) {
    $("#forum-settings-nav > ul.navbar-nav > li").removeClass("active");
    $(obj).parent().addClass("active");
    $(".forum-settings-panel").hide();
    $("#" + panelId).show();
    return false;
}
$(function() {
    var $settingTabs = $("#forum-settings-nav > ul.navbar-nav > li");
    $("#forum_settings_search").on("keyup", function() {
        var findText = $(this).val().toLowerCase();
        var tabs = 0;
        var totalcount = 0;
        $(".forum-settings-panel").each(function() {
            var count = 0;
            var $panel = $(this);
            $panel.find(".table tr").each(function() {
                var $row = $(this);
                var text = $row.find("td:eq(0)").text().toLowerCase();
                if (findText && text.indexOf(findText) === -1) {
                    $row.hide();
                } else {
                    count++;
                    totalcount++;
                    $row.show();
                }
            });
            var $tab = $settingTabs.eq(tabs);
            if (count > 0) {
                $tab.show();
            } else if (findText) {
                $tab.hide();
            } else {
                $tab.show();
            }
            tabs++;
        });
        if (findText && !$settingTabs.filter(":visible").hasClass("active")) {
            $settingTabs.filter(":visible").first().find("a").click();
        }
        $("#forum_settings_found").text((findText && !totalcount) ? "<?php echo addslashes($forum_set_not_found); ?>" : "");
        if (!findText) {
            $(".forum-settings-panel .table tr").show();
            $settingTabs.show();
        }
    });
    function forumThemeSyncColor(color) {
        color = (color || "").toLowerCase();
        if (!/^#([0-9a-f]{3}|[0-9a-f]{6})$/.test(color)) {
            return;
        }
        if (color.length === 4) {
            color = "#" + color[1] + color[1] + color[2] + color[2] + color[3] + color[3];
        }
        $("#forum_theme_header_picker").val(color);
        $("#forum_theme_header_hex").val(color);
        $("#forum_theme_header_preview").css("background", color);
    }
    $("#forum_theme_header_picker").on("input change", function() {
        forumThemeSyncColor(this.value);
    });
    $("#forum_theme_header_hex").on("input change", function() {
        forumThemeSyncColor(this.value);
    });
    $(document).on("click", ".forum-theme-swatch", function() {
        forumThemeSyncColor($(this).data("color"));
    });
});
</script>

<div style="position:relative">
    <input type="text" class="form-control mb-10" id="forum_settings_search" placeholder="<?php echo htmlspecialchars($forum_set_search, ENT_QUOTES, "UTF-8"); ?>" autocomplete="off">
    <span id="forum_settings_found" class="text-muted text-size-small hidden-xs" style="position:absolute;top:.208em;right:0;"></span>
</div>

<div class="navbar navbar-default navbar-component navbar-xs systemsettings" id="forum-settings-filter">
    <ul class="nav navbar-nav visible-xs-block">
        <li class="full-width text-center"><a data-toggle="collapse" data-target="#forum-settings-nav"><i class="fa fa-bars"></i></a></li>
    </ul>
    <div class="navbar-collapse collapse" id="forum-settings-nav">
        <ul class="nav navbar-nav">
            <li class="active"><a href="#" onclick="return forumSettingsChangeTab(this, 'forum-set-view');" class="tip" title="<?php echo htmlspecialchars($lang['forum_set_section_view'], ENT_QUOTES, "UTF-8"); ?>"><i class="fa fa-eye position-left"></i><?php echo $lang['forum_set_section_view']; ?></a></li>
            <li><a href="#" onclick="return forumSettingsChangeTab(this, 'forum-set-theme');" class="tip" title="<?php echo htmlspecialchars($lang['forum_set_section_theme'], ENT_QUOTES, "UTF-8"); ?>"><i class="fa fa-paint-brush position-left"></i><?php echo $lang['forum_set_section_theme']; ?></a></li>
            <li><a href="#" onclick="return forumSettingsChangeTab(this, 'forum-set-points');" class="tip" title="<?php echo htmlspecialchars($lang['forum_set_section_points'], ENT_QUOTES, "UTF-8"); ?>"><i class="fa fa-star position-left"></i><?php echo $lang['forum_set_section_points']; ?></a></li>
            <li><a href="#" onclick="return forumSettingsChangeTab(this, 'forum-set-perms');" class="tip" title="<?php echo htmlspecialchars($lang['forum_set_section_perms'], ENT_QUOTES, "UTF-8"); ?>"><i class="fa fa-shield position-left"></i><?php echo $lang['forum_set_section_perms']; ?></a></li>
            <li><a href="#" onclick="return forumSettingsChangeTab(this, 'forum-set-editor');" class="tip" title="<?php echo htmlspecialchars($lang['forum_set_section_editor'], ENT_QUOTES, "UTF-8"); ?>"><i class="fa fa-pencil position-left"></i><?php echo $lang['forum_set_section_editor']; ?></a></li>
            <li><a href="#" onclick="return forumSettingsChangeTab(this, 'forum-set-bump');" class="tip" title="<?php echo htmlspecialchars($lang['forum_set_section_bump'], ENT_QUOTES, "UTF-8"); ?>"><i class="fa fa-level-up position-left"></i><?php echo $lang['forum_set_section_bump']; ?></a></li>
            <li><a href="#" onclick="return forumSettingsChangeTab(this, 'forum-set-files');" class="tip" title="<?php echo htmlspecialchars($lang['forum_set_section_attachments'], ENT_QUOTES, "UTF-8"); ?>"><i class="fa fa-paperclip position-left"></i><?php echo $lang['forum_set_section_attachments']; ?></a></li>
            <li><a href="#" onclick="return forumSettingsChangeTab(this, 'forum-set-polls');" class="tip" title="<?php echo htmlspecialchars($lang['forum_set_section_polls'], ENT_QUOTES, "UTF-8"); ?>"><i class="fa fa-bar-chart position-left"></i><?php echo $lang['forum_set_section_polls']; ?></a></li>
            <li><a href="#" onclick="return forumSettingsChangeTab(this, 'forum-set-features');" class="tip" title="<?php echo htmlspecialchars($lang['forum_set_section_features'], ENT_QUOTES, "UTF-8"); ?>"><i class="fa fa-toggle-on position-left"></i><?php echo $lang['forum_set_section_features']; ?></a></li>
        </ul>
    </div>
</div>

<form method="post" action="?mod=forum&action=settings" class="systemsettings">
    <input type="hidden" name="user_hash" value="<?php echo $dle_login_hash; ?>">

<?php
forumSettingsPanelOpen("forum-set-view", $lang["forum_set_section_view"], true);
showRow($lang['forum_set_tpp'], $lang['forum_set_tpp_desc'], num("topics_per_page", $cfg["topics_per_page"]));
showRow($lang['forum_set_ppp'], $lang['forum_set_ppp_desc'], num("posts_per_page", $cfg["posts_per_page"]));
showRow($lang['forum_set_hot'], $lang['forum_set_hot_desc'], num("hot_topic_replies", $cfg["hot_topic_replies"]));
showRow($lang['forum_set_min_len'], $lang['forum_set_min_len_desc'], num("min_post_length", $cfg["min_post_length"]));
showRow($lang['forum_set_max_len'], $lang['forum_set_max_len_desc'], num("max_post_length", $cfg["max_post_length"], "150"));
forumSettingsPanelClose();

forumSettingsPanelOpen("forum-set-theme", $lang["forum_set_section_theme"]);
showRow(
    $lang['forum_set_theme_mode'] ?? 'Tema Modu',
    $lang['forum_set_theme_mode_desc'] ?? 'Forumun renk şeması (Açık / Koyu).',
    themeModeSelect("theme_mode", $cfg["theme_mode"]),
);
showRow(
    $lang['forum_set_theme_header'],
    $lang['forum_set_theme_header_desc'],
    themeHeaderColorPicker("theme_header_color", $cfg["theme_header_color"]),
);
forumSettingsPanelClose();

forumSettingsPanelOpen("forum-set-points", $lang["forum_set_section_points"]);
showRow($lang['forum_set_pt'], $lang['forum_set_pt_desc'], num("points_per_topic", $cfg["points_per_topic"]));
showRow($lang['forum_set_pp'], $lang['forum_set_pp_desc'], num("points_per_post", $cfg["points_per_post"]));
showRow($lang['forum_set_pl'], $lang['forum_set_pl_desc'], num("points_per_like", $cfg["points_per_like"]));
forumSettingsPanelClose();

forumSettingsPanelOpen("forum-set-perms", $lang["forum_set_section_perms"]);
showRow($lang['forum_set_guest_view'], $lang['forum_set_guest_view_desc'], yn("allow_guest_view", $cfg["allow_guest_view"]));
showRow($lang['forum_set_guest_post'], $lang['forum_set_guest_post_desc'], yn("allow_guest_post", $cfg["allow_guest_post"]));
showRow($lang['forum_set_approval'], $lang['forum_set_approval_desc'], yn("require_approval", $cfg["require_approval"]));
showRow($lang['forum_set_flood'], $lang['forum_set_flood_desc'], num("flood_control_sec", $cfg["flood_control_sec"]));
showRow($lang['forum_set_link_min_posts'], $lang['forum_set_link_min_posts_desc'], num("link_min_posts", $cfg["link_min_posts"]));
showRow($lang['forum_set_link_min_points'], $lang['forum_set_link_min_points_desc'], num("link_min_points", $cfg["link_min_points"]));
forumSettingsPanelClose();

forumSettingsPanelOpen("forum-set-editor", $lang["forum_set_section_editor"]);
showRow($lang['forum_set_wysiwyg'], $lang['forum_set_wysiwyg_desc'], yn("editor_enabled", $cfg["editor_enabled"]));
showRow(
    $editor_topic_label,
    $editor_topic_desc,
    editorTypeSelect(
        "editor_type_topic",
        $cfg["editor_type_topic"] !== "" ? $cfg["editor_type_topic"] : $cfg["editor_type"],
    ),
);
showRow(
    $editor_reply_label,
    $editor_reply_desc,
    editorTypeSelect(
        "editor_type_reply",
        $cfg["editor_type_reply"] !== "" ? $cfg["editor_type_reply"] : $cfg["editor_type"],
    ),
);
showRow($lang['forum_set_upload_groups'], $lang['forum_set_upload_groups_desc'], txt("editor_upload_groups", $cfg["editor_upload_groups"], "150"));
showRow($lang['forum_set_quotes'], $lang['forum_set_quotes_desc'], yn("enable_quotes", $cfg["enable_quotes"]));
showRow($lang['forum_set_quote_depth'], $lang['forum_set_quote_depth_desc'], num("quote_max_depth", $cfg["quote_max_depth"], "80"));
showRow($lang['forum_set_mentions'], $lang['forum_set_mentions_desc'], yn("enable_mentions", $cfg["enable_mentions"]));
forumSettingsPanelClose();

forumSettingsPanelOpen("forum-set-bump", $lang["forum_set_section_bump"]);
showRow($lang['forum_set_bump_cooldown'], $lang['forum_set_bump_cooldown_desc'], num("bump_cooldown_hours", $cfg["bump_cooldown_hours"]));
showRow($lang['forum_set_bump_limit'], $lang['forum_set_bump_limit_desc'], num("bump_max_per_day", $cfg["bump_max_per_day"], "80"));
forumSettingsPanelClose();

forumSettingsPanelOpen("forum-set-files", $lang["forum_set_section_attachments"]);
showRow($lang['forum_set_att_mb'], $lang['forum_set_att_mb_desc'], num("max_attachment_mb", $cfg["max_attachment_mb"]));
showRow($lang['forum_set_att_count'], $lang['forum_set_att_count_desc'], num("max_attachments", $cfg["max_attachments"], "80"));
showRow($lang['forum_set_att_types'], $lang['forum_set_att_types_desc'], txt("allowed_filetypes", $cfg["allowed_filetypes"]));
showRow($lang['forum_set_att_groups'], $lang['forum_set_att_groups_desc'], txt("upload_groups", $cfg["upload_groups"], "150"));
showRow($lang['forum_set_att_min_posts'], $lang['forum_set_att_min_posts_desc'], num("upload_min_posts", $cfg["upload_min_posts"]));
showRow($lang['forum_set_att_limit'], $lang['forum_set_att_limit_desc'], num("upload_max_per_day", $cfg["upload_max_per_day"]));
forumSettingsPanelClose();

forumSettingsPanelOpen("forum-set-polls", $lang["forum_set_section_polls"]);
showRow($lang['forum_set_polls'], $lang['forum_set_polls_desc'], yn("enable_polls", $cfg["enable_polls"]));
showRow($lang['forum_set_poll_opts'], $lang['forum_set_poll_opts_desc'], num("poll_max_options", $cfg["poll_max_options"]));
showRow($lang['forum_set_poll_sels'], $lang['forum_set_poll_sels_desc'], num("poll_max_selections", $cfg["poll_max_selections"], "80"));
showRow($lang['forum_set_poll_days'], $lang['forum_set_poll_days_desc'], num("poll_default_days", $cfg["poll_default_days"]));
forumSettingsPanelClose();

forumSettingsPanelOpen("forum-set-features", $lang["forum_set_section_features"]);
showRow($lang['forum_set_likes'], $lang['forum_set_likes_desc'], yn("enable_likes", $cfg["enable_likes"]));
showRow($lang['forum_set_dislikes'], $lang['forum_set_dislikes_desc'], yn("enable_dislikes", $cfg["enable_dislikes"]));
showRow($lang['forum_set_notifications'], $lang['forum_set_notifications_desc'], yn("enable_notifications", $cfg["enable_notifications"]));
showRow($lang['forum_set_subscriptions'], $lang['forum_set_subscriptions_desc'], yn("enable_subscriptions", $cfg["enable_subscriptions"]));
showRow($lang['forum_set_email_notify'], $lang['forum_set_email_notify_desc'], yn("enable_email_notify", $cfg["enable_email_notify"]));
showRow($lang['forum_set_rating'], $lang['forum_set_rating_desc'], yn("enable_topic_rating", $cfg["enable_topic_rating"]));
showRow($lang['forum_set_ranks'], $lang['forum_set_ranks_desc'], yn("enable_ranks", $cfg["enable_ranks"]));
forumSettingsPanelClose();

forumSettingsPanelOpen("forum-set-modules", $lang["forum_set_section_modules"]);
showRow($lang['forum_set_rss'], $lang['forum_set_rss_desc'], yn("enable_rss", $cfg["enable_rss"]));
showRow($lang['forum_set_sitemap'], $lang['forum_set_sitemap_desc'], yn("enable_sitemap", $cfg["enable_sitemap"]));
showRow($lang['forum_set_solved'], $lang['forum_set_solved_desc'], yn("enable_solved", $cfg["enable_solved"]));
showRow($lang['forum_set_best'], $lang['forum_set_best_desc'], yn("enable_best_answer", $cfg["enable_best_answer"]));
showRow($lang['forum_set_similar'], $lang['forum_set_similar_desc'], yn("enable_similar", $cfg["enable_similar"]));
showRow($lang['forum_set_viewers'], $lang['forum_set_viewers_desc'], yn("enable_viewers", $cfg["enable_viewers"]));
showRow($lang['forum_set_online'], $lang['forum_set_online_desc'], yn("enable_online", $cfg["enable_online"]));
showRow($lang['forum_set_follow'], $lang['forum_set_follow_desc'], yn("enable_follow", $cfg["enable_follow"]));
showRow($lang['forum_set_drafts'], $lang['forum_set_drafts_desc'], yn("enable_drafts", $cfg["enable_drafts"]));
showRow($lang['forum_set_edithist'], $lang['forum_set_edithist_desc'], yn("enable_edit_history", $cfg["enable_edit_history"]));
showRow($lang['forum_set_og'], $lang['forum_set_og_desc'], yn("enable_opengraph", $cfg["enable_opengraph"]));
showRow($lang['forum_set_unread'], $lang['forum_set_unread_desc'], yn("enable_unread_badge", $cfg["enable_unread_badge"]));
showRow($lang['forum_set_cat_sub'], $lang['forum_set_cat_sub_desc'], yn("enable_cat_subscribe", $cfg["enable_cat_subscribe"]));
showRow($lang['forum_set_stats'], $lang['forum_set_stats_desc'], yn("enable_statistics", $cfg["enable_statistics"]));
showRow($lang['forum_set_captcha_topic'], $lang['forum_set_captcha_topic_desc'], yn("enable_captcha_topic", $cfg["enable_captcha_topic"]));
showRow($lang['forum_set_captcha_reply'], $lang['forum_set_captcha_reply_desc'], yn("enable_captcha_reply", $cfg["enable_captcha_reply"]));
showRow($lang['forum_set_edit_owner'], $lang['forum_set_edit_owner_desc'], yn("enable_topic_edit_owner", $cfg["enable_topic_edit_owner"]));
showRow($lang['forum_set_edit_mods'], $lang['forum_set_edit_mods_desc'], yn("enable_topic_edit_mods", $cfg["enable_topic_edit_mods"]));
forumSettingsPanelClose();
?>

    <div style="margin-bottom:2.08em;margin-top:1em;">
        <button type="submit" name="save_settings" class="btn bg-teal btn-raised position-left"><i class="fa fa-floppy-o position-left"></i><?php echo $lang['forum_set_save_btn']; ?></button>
    </div>
</form>

