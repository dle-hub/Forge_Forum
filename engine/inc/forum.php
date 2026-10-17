<?php
/*
=====================================================
 Forge Forum Engine — Modular Admin Router
-----------------------------------------------------
 File: engine/inc/forum.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/

if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (file_exists(DLEPlugins::Check(ROOT_DIR . '/language/' . $selected_language . '/forum.lng'))) {
    include_once (DLEPlugins::Check(ROOT_DIR . '/language/' . $selected_language . '/forum.lng'));
} else {
    if (file_exists(DLEPlugins::Check(ROOT_DIR . '/language/Turkish/forum.lng'))) {
        include_once (DLEPlugins::Check(ROOT_DIR . '/language/Turkish/forum.lng'));
    }
}

if ($member_id["user_group"] > 2) {
    msg("error", $lang['forum_access_denied'], $lang['forum_no_perm']);
    return;
}

$action = isset($_REQUEST["action"]) ? totranslit($_REQUEST["action"]) : "main";
$sub_dir = ENGINE_DIR . "/inc/forum/";
$self_url = "?mod=forum";

// -------------------------------------------------
// ÖNCE ALT MODÜLÜ ÇALIŞTIR (POST → msg → die)
// -------------------------------------------------
$module_file = $sub_dir . $action . ".php";

ob_start();
if (file_exists($module_file)) {
    include $module_file;
} else {
    include $sub_dir . "main.php";
}
$module_output = ob_get_clean();

// -------------------------------------------------
// SAYFA İSKELETİ
// -------------------------------------------------
echoheader(
    "<i class=\"fa fa-comments position-left\"></i><span class=\"text-semibold\">{$lang['forum_admin_title']}</span>",
    $lang['forum_admin_subtitle'],
);
?>
<style>
/* Forge Forum — DLE 21 yönetim paletinde bulunmayan (Limitless dönemi) renk sınıfları */
.bg-warning { background-color: #ffb300 !important; border-color: #ffb300 !important; color: #fff !important; }
.bg-warning-600 { background-color: #ffa000 !important; border-color: #ffa000 !important; color: #fff !important; }
.bg-success { background-color: #43a047 !important; border-color: #43a047 !important; color: #fff !important; }
.bg-success-600 { background-color: #388e3c !important; border-color: #388e3c !important; color: #fff !important; }
.bg-blue-600 { background-color: #1e88e5 !important; border-color: #1e88e5 !important; color: #fff !important; }
.bg-indigo-600 { background-color: #3949ab !important; border-color: #3949ab !important; color: #fff !important; }
.bg-danger { background-color: #f44336 !important; }
.bg-primary { background-color: #2196f3 !important; }
.text-white { color: #fff !important; }
/* Rozet (label) sınıfları — DLE 21'de eksik/tanımsız olanlar */
.label-default { background-color: #78909c !important; border-color: #78909c !important; color: #fff !important; }
.label-primary { background-color: #2196f3 !important; border-color: #2196f3 !important; color: #fff !important; }
.label-success { background-color: #43a047 !important; border-color: #43a047 !important; color: #fff !important; }
.label-info { background-color: #00bcd4 !important; border-color: #00bcd4 !important; color: #fff !important; }
.label-warning { background-color: #ffb300 !important; border-color: #ffb300 !important; color: #333 !important; }
.label-danger { background-color: #f44336 !important; border-color: #f44336 !important; color: #fff !important; }
/* Koyu tema: DLE 21 .btn-default metnini beyaz yapar ama zemini açık (#f5f5f5) bırakır.
   Anahtar (İptal / Üret / Listeye Geri Dön vb.) okunmaz. Zemin DLE koyu paletine çevrilir. */
.dle_theme_dark .btn-default,
.dle_theme_dark .btn-default:active,
.dle_theme_dark .btn-default.active,
.dle_theme_dark .open > .dropdown-toggle.btn-default {
    background-color: #495264 !important;
    border-color: rgba(255, 255, 255, .18) !important;
    color: #fff !important;
}
.dle_theme_dark .btn-default:hover,
.dle_theme_dark .btn-default:focus,
.dle_theme_dark .btn-default.focus,
.dle_theme_dark .open > .dropdown-toggle.btn-default:hover,
.dle_theme_dark .open > .dropdown-toggle.btn-default:focus {
    background-color: #55607a !important;
    border-color: rgba(255, 255, 255, .32) !important;
}
.dle_theme_dark .btn-default.disabled,
.dle_theme_dark .btn-default[disabled],
.dle_theme_dark fieldset[disabled] .btn-default {
    background-color: rgba(255, 255, 255, .08) !important;
    border-color: rgba(255, 255, 255, .12) !important;
    color: rgba(255, 255, 255, .45) !important;
}
/* bootstrap-select (medya yükleme popup'ı) material-underline stili korunur */
.dle_theme_dark .bootstrap-select .btn-default,
.dle_theme_dark .bootstrap-select .btn-default:hover,
.dle_theme_dark .bootstrap-select .btn-default:focus,
.dle_theme_dark .bootstrap-select .btn-default:active,
.dle_theme_dark .bootstrap-select .btn-default.active,
.dle_theme_dark .bootstrap-select.btn-group.open > .btn-default.dropdown-toggle {
    background-color: transparent !important;
    border-color: transparent !important;
    border-bottom-color: rgba(255, 255, 255, .4) !important;
    border-width: .07em 0 !important;
    color: #fff !important;
}
</style>
<div class="navbar navbar-default navbar-component navbar-xs mb-20">
    <ul class="nav navbar-nav visible-xs-block">
        <li class="full-width text-center">
            <a data-toggle="collapse" data-target="#forum-nav"><i class="fa fa-bars"></i></a>
        </li>
    </ul>
    <div class="navbar-collapse collapse" id="forum-nav">
        <ul class="nav navbar-nav">
            <li<?php echo $action == "main" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>"><i class="fa fa-home position-left"></i> <?php echo $lang['forum_menu_main']; ?></a>
            </li>
            <li<?php echo $action == "topics" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=topics"><i class="fa fa-list position-left"></i> <?php echo $lang['forum_menu_topics']; ?></a>
            </li>
            <li class="dropdown<?php echo in_array($action, [
                "categories",
                "prefixes",
            ])
                ? " active"
                : ""; ?>">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-folder-open position-left"></i> <?php echo $lang['forum_menu_config']; ?> <span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="<?php echo $self_url; ?>&action=categories"><i class="fa fa-sitemap"></i> <?php echo $lang['forum_menu_categories']; ?></a></li>
                    <li><a href="<?php echo $self_url; ?>&action=prefixes"><i class="fa fa-tag"></i> <?php echo $lang['forum_menu_prefixes']; ?></a></li>
                </ul>
            </li>
            <li<?php echo $action == "permissions" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=permissions"><i class="fa fa-lock position-left"></i> <?php echo $lang['forum_menu_permissions']; ?></a>
            </li>
            <li<?php echo $action == "polls" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=polls"><i class="fa fa-bar-chart position-left"></i> <?php echo $lang['forum_menu_polls']; ?></a>
            </li>
            <li<?php echo $action == "tags" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=tags"><i class="fa fa-tags position-left"></i> <?php echo $lang['forum_menu_tags']; ?></a>
            </li>
            <li<?php echo $action == "promotions" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=promotions"><i class="fa fa-arrow-up position-left"></i> <?php echo $lang['forum_menu_promotions']; ?></a>
            </li>
            <li<?php echo $action == "tools" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=tools"><i class="fa fa-wrench position-left"></i> <?php echo $lang['forum_menu_tools']; ?></a>
            </li>
            <li<?php echo $action == "trash" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=trash"><i class="fa fa-trash-o position-left"></i> <?php echo $lang['forum_menu_trash']; ?></a>
            </li>
            <li<?php echo $action == "reports" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=reports"><i class="fa fa-flag position-left"></i> <?php echo $lang['forum_menu_reports']; ?></a>
            </li>
            <li<?php echo $action == "approval" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=approval"><i class="fa fa-check-square-o position-left"></i> <?php echo $lang['forum_menu_approval']; ?></a>
            </li>
            <li<?php echo $action == "ranks" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=ranks"><i class="fa fa-trophy position-left"></i> <?php echo $lang['forum_menu_ranks']; ?></a>
            </li>
            <li<?php echo $action == "wordfilter" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=wordfilter"><i class="fa fa-filter position-left"></i> <?php echo $lang['forum_menu_wordfilter']; ?></a>
            </li>
            <li<?php echo $action == "settings" ? ' class="active"' : ""; ?>>
                <a href="<?php echo $self_url; ?>&action=settings"><i class="fa fa-gears position-left"></i> <?php echo $lang['forum_menu_settings']; ?></a>
            </li>
        </ul>
    </div>
</div>
<?php
echo $module_output;
echofooter();

