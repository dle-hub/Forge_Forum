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
    $breadcrumb_html .=
        '<nav class="flex mb-5 px-4 py-3 text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm" aria-label="Breadcrumb">';
    $breadcrumb_html .=
        '<ol class="inline-flex items-center space-x-1 md:space-x-3 list-none p-0 m-0">';

    $counter = 0;
    $total = count($forum_speedbar);

    foreach ($forum_speedbar as $url => $title) {
        $counter++;
        $title = htmlspecialchars(
            strip_tags(stripslashes($title)),
            ENT_QUOTES,
            "UTF-8",
        );
        $breadcrumb_html .= '<li class="inline-flex items-center">';

        if ($counter > 1) {
            $breadcrumb_html .=
                '<svg class="w-6 h-6 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
        }

        if ($url !== "" && $counter < $total) {
            if ($counter == 1) {
                $breadcrumb_html .=
                    '<a href="' .
                    $url .
                    '" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition">';
                $breadcrumb_html .=
                    '<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h2a1 1 0 001-1v-6.586l.707.707a1 1 0 001.414-1.414l-7-7z"></path></svg>';
                $breadcrumb_html .= $title . "</a>";
            } else {
                $breadcrumb_html .=
                    '<a href="' .
                    $url .
                    '" class="text-sm font-medium text-gray-700 hover:text-blue-600 transition">' .
                    $title .
                    "</a>";
            }
        } else {
            $breadcrumb_html .=
                '<span class="text-sm font-semibold text-gray-500 truncate max-w-xs md:max-w-md block" aria-current="page">' .
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
