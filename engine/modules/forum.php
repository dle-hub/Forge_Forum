<?php
/*
=====================================================
 Forge Forum Engine — Core Loader (Custom Breadcrumb)
-----------------------------------------------------
 File: engine/modules/forum.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

$forum_lang_dir = $config['langs'];
if (isset($config["lang_" . $config['skin']]) && $config["lang_" . $config['skin']] != '' && is_dir(ROOT_DIR . '/language/' . $config["lang_" . $config['skin']])) {
    $forum_lang_dir = $config["lang_" . $config['skin']];
}

if (file_exists(DLEPlugins::Check(ROOT_DIR . '/language/' . $forum_lang_dir . '/forum.lng'))) {
    include_once (DLEPlugins::Check(ROOT_DIR . '/language/' . $forum_lang_dir . '/forum.lng'));
} else {
    if (file_exists(DLEPlugins::Check(ROOT_DIR . '/language/Turkish/forum.lng'))) {
        include_once (DLEPlugins::Check(ROOT_DIR . '/language/Turkish/forum.lng'));
    }
}

global $db, $is_logged, $member_id, $config, $tpl;
global $forum_speedbar;

$forum_speedbar = [];

$init_file = ENGINE_DIR . "/modules/forum/init.php";
if (file_exists($init_file)) {
    include DLEPlugins::Check($init_file);
} else {
    msgbox($lang['forum_err_title'], $lang['forum_err_init_missing']);
    return;
}

// Foruma ozel premium breadcrumb
$breadcrumb_html = "";
if (is_array($forum_speedbar) && count($forum_speedbar) > 0) {
    $breadcrumb_html .= '<nav class="forum-breadcrumb" aria-label="Breadcrumb">';
    $breadcrumb_html .= '<ol class="forum-breadcrumb__list">';

    $counter = 0;
    $total = count($forum_speedbar);

    foreach ($forum_speedbar as $url => $title) {
        $counter++;
        $title = htmlspecialchars(
            strip_tags(stripslashes($title)),
            ENT_QUOTES,
            "UTF-8",
        );
        $breadcrumb_html .= '<li class="forum-breadcrumb__item">';

        if ($counter > 1) {
            $breadcrumb_html .=
                '<span class="forum-breadcrumb__sep" aria-hidden="true">&rsaquo;</span>';
        }

        if ($url !== "" && $counter < $total) {
            $breadcrumb_html .= '<a href="' . $url . '">';
            if ($counter === 1) {
                $breadcrumb_html .=
                    '<i class="fa fa-home forum-breadcrumb__home" aria-hidden="true"></i>';
            }
            $breadcrumb_html .= $title . "</a>";
        } else {
            $breadcrumb_html .=
                '<span class="forum-breadcrumb__current" aria-current="page">' .
                $title .
                "</span>";
        }

        $breadcrumb_html .= "</li>";
    }

    $breadcrumb_html .= "</ol></nav>";
}

// Compile sonrasi string replace (action compile olduktan sonra)
if (!empty($breadcrumb_html) && !empty($tpl->result["content"])) {
    $tpl->result["content"] = str_replace(
        "{forum_breadcrumb}",
        $breadcrumb_html,
        $tpl->result["content"],
    );
}
