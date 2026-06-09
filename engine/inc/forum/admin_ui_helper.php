<?php
/*
=====================================================
 Forge Forum Engine — Admin arayüz yardımcıları
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (!function_exists("forum_admin_action_dropdown")) {
    /**
     * @param array<int, array{href?: string, label: string, icon?: string, onclick?: string, danger?: bool, divider_before?: bool}> $items
     */
    function forum_admin_action_dropdown(array $items)
    {
        if (!$items) {
            return "";
        }

        $html =
            '<div class="btn-group">' .
            '<a href="#" class="dropdown-toggle nocolor" data-toggle="dropdown" aria-expanded="false">' .
            '<i class="fa fa-bars"></i><span class="caret"></span></a>' .
            '<ul class="dropdown-menu text-left dropdown-menu-right">';

        foreach ($items as $item) {
            if (!empty($item["divider_before"])) {
                $html .= '<li class="divider"></li>';
            }

            $href = htmlspecialchars($item["href"] ?? "#", ENT_QUOTES, "UTF-8");
            $label = htmlspecialchars($item["label"] ?? "", ENT_QUOTES, "UTF-8");
            $icon = htmlspecialchars($item["icon"] ?? "fa fa-circle-o", ENT_QUOTES, "UTF-8");
            $iconClass = !empty($item["danger"])
                ? " position-left text-danger"
                : " position-left";
            $onclick = "";

            if (!empty($item["onclick"])) {
                $onclick =
                    ' onclick="' .
                    htmlspecialchars($item["onclick"], ENT_QUOTES, "UTF-8") .
                    '"';
            }

            $html .=
                "<li><a href=\"{$href}\"{$onclick}><i class=\"{$icon}{$iconClass}\"></i>{$label}</a></li>";
        }

        $html .= "</ul></div>";

        return $html;
    }
}
