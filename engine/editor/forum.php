<?php
/*
=====================================================
 Forge Forum Engine — Editor Konfigurasyonu
-----------------------------------------------------
 File: engine/editor/forum.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
 NOT: Bu editor tamamen DLE'nin upload.php'sinden
 bagimsizdir. Tum yukleme isleri forum_upload.php
 uzerinden gerceklesir.
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/editor_helpers.php",
);

if (!is_array($forum_cfg) || empty($forum_cfg)) {
    $forum_cfg = forum_load_cfg();
}

$f_editor_context = isset($f_editor_context)
    ? $f_editor_context
    : (isset($f_editor_mode) && $f_editor_mode === "reply" ? "reply" : "topic");

$f_editor_id      = isset($f_editor_id)     ? $f_editor_id              : "forum-editor";
$f_editor_height  = isset($f_editor_height) ? intval($f_editor_height)  : 300;
$f_editor_mode    = isset($f_editor_mode)   ? $f_editor_mode            : "full"; // full | reply
$f_editor_news_id = isset($f_editor_news_id) ? intval($f_editor_news_id) : 0;

// Koyu tema tespiti
$dark_theme   = "";
$template_dir = defined("TEMPLATE_DIR")
    ? TEMPLATE_DIR
    : ROOT_DIR . "/templates/" . $config["skin"];
if (is_file($template_dir . "/info.json")) {
    $t_data = json_decode(trim(file_get_contents($template_dir . "/info.json")), true);
    if (isset($t_data["type"]) && $t_data["type"] == "dark") {
        $dark_theme = " dle_theme_dark";
    }
}

// Yükleme yetkisi
$_fup_groups_str = isset($forum_cfg["upload_groups"])
    ? $forum_cfg["upload_groups"]
    : (isset($forum_cfg["editor_upload_groups"]) ? $forum_cfg["editor_upload_groups"] : "1,2,3,4");
$_fup_groups  = array_map("trim", explode(",", $_fup_groups_str));
$_fup_uid     = intval($member_id["user_id"] ?? 0);
$_fup_gid     = (string)($member_id["user_group"] ?? 0);
$can_upload   = $_fup_uid > 0 && in_array($_fup_gid, $_fup_groups);

// Izin verilen uzantılar
$_fup_types_raw = isset($forum_cfg["allowed_filetypes"]) ? $forum_cfg["allowed_filetypes"] : "jpg,jpeg,png,gif,webp,pdf,zip,rar";
$_fup_accept = implode(",", array_map(function ($e) { return "." . trim($e); }, explode(",", $_fup_types_raw)));
$_fup_img_exts  = "gif,jpg,png,jpeg,bmp,webp,avif";
$_fup_endpoint  = "index.php?controller=ajax&mod=forum_upload";

// Admin tespiti (DLE CP veya tam yetkili grup)
$f_editor_admin_panel = !empty($f_editor_admin_panel);
$is_admin = $f_editor_admin_panel
    || (defined("LOGGED_IN") && intval($member_id["user_group"] ?? 99) <= 2);

$editor_placeholder = $f_editor_mode === "full" ? "Mesajinizi yazin..." : "Cevabinizi yazin...";
$lang_code = $lang["language_code"] ? $lang["language_code"] : "tr";

// Upload modal basligi: DLE adminpanel ile ayni (mod=upload)
$_fup_modal_title = isset($lang["bb_t_up"]) ? $lang["bb_t_up"] : "Dosya yukleme";
$_fup_editor_lang_dir = $config["langs"] ? totranslit($config["langs"]) : "Turkish";
$_fup_admin_lng_path = ROOT_DIR . "/language/" . $_fup_editor_lang_dir . "/adminpanel.lng";
if (is_file($_fup_admin_lng_path)) {
    $_fup_lang_save = $lang;
    include $_fup_admin_lng_path;
    if (!empty($lang["bb_t_up"])) {
        $_fup_modal_title = $lang["bb_t_up"];
    } elseif (!empty($lang["images_uptitle"])) {
        $_fup_modal_title = $lang["images_uptitle"];
    }
    $lang = $_fup_lang_save;
}
$editor_type = forum_get_editor_type($forum_cfg, $f_editor_context);

// Konu editörü (admin + önyüz): tam DLE TinyMCE toolbar
$f_editor_full_dle = $f_editor_admin_panel || $f_editor_context === "topic";
if ($f_editor_full_dle) {
    $editor_type = "tinymce";
}
if ($f_editor_admin_panel) {
    $is_admin = true;
}

// Upload modal: plupload + DLE fileuploader CSS (mod=upload ile ayni)
if ($can_upload) {
    global $js_array, $css_array;
    $js_array[] = "public/fileuploader/plupload/plupload.full.min.js";
    $js_array[] = "public/fileuploader/plupload/i18n/{$lang_code}.js";
    $_fup_rtl = (isset($lang["direction"]) && $lang["direction"] == "rtl") ? "_rtl" : "";
    $css_array[] = "public/fileuploader/fileuploader{$_fup_rtl}.css";
}

if ($editor_type == "froala") {
    // -------------------------------------------------------
    // Froala Editor Konfigürasyonu
    // -------------------------------------------------------
    if ($is_admin) {
        $toolbar_buttons = "[
            'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', '|',
            'fontFamily', 'fontSize', 'color', '|',
            'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', 'quote', '|',
            'insertLink', 'insertImage', 'insertVideo', 'insertFile', 'insertTable', 'insertHR', 'emoticons', '|',
            'undo', 'redo', 'clearFormatting', 'html', 'fullscreen'
        ]";
    } else {
        if ($f_editor_mode === "full") {
            $toolbar_buttons = "[
                'bold', 'italic', 'underline', 'strikeThrough', '|',
                'color', '|',
                'paragraphFormat', 'align', 'formatOL', 'formatUL', 'quote', '|',
                'insertLink', 'insertImage', 'insertFile', 'emoticons', '|',
                'undo', 'redo', 'clearFormatting', 'html'
            ]";
        } else {
            $toolbar_buttons = "[
                'bold', 'italic', 'underline', '|',
                'color', '|',
                'quote', '|',
                'insertLink', 'insertImage', 'emoticons', '|',
                'undo', 'redo', 'clearFormatting'
            ]";
        }
    }

    global $onload_scripts;
    if (!is_array($onload_scripts)) {
        $onload_scripts = [];
    }

    $onload_scripts[] = <<<HTML
    if (typeof dle_root === 'undefined' || !dle_root) {
        var dle_root = '{$config['http_home_url']}';
    }
    if (dle_root && !dle_root.endsWith('/')) { dle_root += '/'; }

    if (typeof dle_login_hash === 'undefined' || !dle_login_hash) {
        var dle_login_hash = '{$dle_login_hash}';
    }

    // media_upload override
    window.media_upload = function(area, author, news_id, wysiwyg) {
        var n = area + author + news_id + wysiwyg;
        if (jQuery("#mediaupload").hasClass("ui-dialog-content") && window.media_upload_manager == n) {
            jQuery("#mediaupload").dialog("open");
            if (typeof check_all === "function") { check_all(); }
            return false;
        }
        jQuery("#mediaupload").remove();
        jQuery("body").append("<div id='mediaupload' class='mediaupload-body' title='" + (typeof text_upload !== 'undefined' ? text_upload : 'File Upload') + "' style='display:none'></div>");
        if (typeof ShowLoading === "function") ShowLoading("");
        jQuery.get(dle_root + "index.php?controller=ajax", {
            mod: "forum_upload",
            area: area,
            news_id: news_id,
            author: author,
            wysiwyg: wysiwyg,
            skin: typeof dle_skin !== 'undefined' ? dle_skin : ''
        }, function(data) {
            if (typeof HideLoading === "function") HideLoading("");
            jQuery("#mediaupload").html(data);
            var getBaseSizeFunc = typeof getBaseSize === "function" ? getBaseSize : function() { return 1; };
            var w_width = 900 * getBaseSizeFunc();
            var w_height = 600 * getBaseSizeFunc();
            if (w_width > 0.95 * jQuery(window).width()) { w_width = 0.95 * jQuery(window).width(); }
            if (w_height > 0.95 * jQuery(window).height()) { w_height = 0.95 * jQuery(window).height(); }
            jQuery("#mediaupload").dialog({
                autoOpen: true,
                width: w_width,
                height: w_height,
                resizable: false,
                dialogClass: "modalfixed dle-popup-mediaupload",
                classes: { "ui-dialog": "modalfixed dle-popup-mediaupload" },
                open: function(e, t) {
                    jQuery(".dle-popup-mediaupload").append(jQuery("#mediaupload-buttonpane").html());
                    jQuery("#mediaupload-buttonpane").remove();
                },
                dragStart: function(e, t) {
                    jQuery("#mediaupload").css("opacity", "0");
                    jQuery(".mediaupload-insert-params").css("opacity", "0");
                    jQuery(".modalfixed").css("opacity", "0.8");
                },
                dragStop: function(e, t) {
                    jQuery("#mediaupload").css("opacity", "1");
                    jQuery(".mediaupload-insert-params").css("opacity", "1");
                    jQuery(".modalfixed").css("opacity", "1");
                }
            });
            window.media_upload_manager = n;
            if (jQuery(window).width() > 830 && jQuery(window).height() > 530) {
                jQuery(".modalfixed.ui-dialog").css({ position: "fixed" });
                jQuery("#mediaupload").dialog("option", "position", { my: "center", at: "center", of: window });
            }
        }, "html");
        return false;
    };

    // Froala CSS Dinamik Yükleme
    var isCssLoaded = false;
    var links = document.getElementsByTagName('link');
    for (var i = 0; i < links.length; i++) {
        if (links[i].href && links[i].href.indexOf('public/editor/froala/css/editor.css') !== -1) {
            isCssLoaded = true;
            break;
        }
    }
    if (!isCssLoaded) {
        var link = document.createElement('link');
        link.id = 'froala-css';
        link.rel = 'stylesheet';
        link.href = dle_root + 'public/editor/froala/css/editor.css';
        document.head.appendChild(link);
    }

    // TinyMCE Uyumluluk Köprüsü
    window.tinymce = {
        get: function(id) {
            var el = jQuery('#' + id);
            if (el.length) {
                return {
                    setContent: function(content) {
                        el.froalaEditor('html.set', content);
                    },
                    getContent: function() {
                        return el.froalaEditor('html.get');
                    },
                    insertContent: function(content) {
                        el.froalaEditor('html.insert', content);
                    },
                    focus: function() {
                        el.froalaEditor('events.focus');
                    },
                    scrollIntoView: function() {
                        if (el[0]) {
                            el[0].scrollIntoView({behavior:'smooth',block:'center'});
                        }
                    }
                };
            }
            return null;
        },
        triggerSave: function() {
            // Froala otomatik olarak senkronize tutar
        }
    };
    window.tinyMCE = window.tinymce;

    jQuery(document).ready(function($) {
        var uploadParams = {
            subaction: 'upload',
            user_hash: dle_login_hash,
            news_id: '{$f_editor_news_id}'
        };

        var froalaOptions = {
            language: '{$lang_code}',
            height: {$f_editor_height},
            heightMin: 150,
            heightMax: 600,
            placeholderText: '{$editor_placeholder}',
            toolbarButtons: {$toolbar_buttons},
            quickInsertButtons: ['image', 'video', 'table', 'ul', 'ol'],
            imageUploadURL: dle_root + 'index.php?controller=ajax&mod=forum_upload&froala=1',
            imageUploadParam: 'file',
            imageUploadParams: uploadParams,
            imageUploadMethod: 'POST',
            imageAllowedTypes: ['jpeg', 'jpg', 'png', 'gif', 'webp', 'avif'],
            
            fileUploadURL: dle_root + 'index.php?controller=ajax&mod=forum_upload&froala=1',
            fileUploadParam: 'file',
            fileUploadParams: uploadParams,
            fileUploadMethod: 'POST'
        };

        if (!{$can_upload}) {
            froalaOptions.imageUploadURL = '';
            froalaOptions.fileUploadURL = '';
        }

        $('textarea#{$f_editor_id}').froalaEditor(froalaOptions);
    });
HTML;

} else {
    // -------------------------------------------------------
    // TinyMCE Editor Konfigürasyonu (Orijinal Kod)
    // -------------------------------------------------------
    $image_upload_handler = "";
    $image_upload_config  = "paste_data_images: false,\n";

    if ($can_upload) {
        $image_upload_handler = <<<HTML
        var dle_forum_upload_handler = (blobInfo, progress) => new Promise((resolve, reject) => {
            var xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            xhr.open('POST', dle_root + '{$_fup_endpoint}');
            xhr.upload.onprogress = (e) => { if (e.lengthComputable) progress(e.loaded / e.total * 100); };
            xhr.onload = function () {
                if (xhr.status < 200 || xhr.status >= 300) { reject('HTTP Hatasi: ' + xhr.status); return; }
                var json;
                try { json = JSON.parse(xhr.responseText); } catch (e) { reject('Gecersiz JSON yaniti'); return; }
                if (json && json.error) {
                    reject(json.error || json.message || 'Yukleme basarisiz');
                    return;
                }
                var url = json && (json.url || json.link);
                if (url && (json.status === 'success' || json.success)) {
                    resolve(url);
                    return;
                }
                reject((json && (json.message || json.error)) || 'Yukleme basarisiz');
            };
            xhr.onerror = () => reject('Ag hatasi nedeniyle yukleme basarisiz oldu');
            var fd = new FormData();
            fd.append('file', blobInfo.blob(), blobInfo.filename());
            fd.append('subaction', 'upload');
            fd.append('tinymce', '1');
            fd.append('user_hash', dle_login_hash);
            fd.append('news_id', '{$f_editor_news_id}');
            xhr.send(fd);
        });
HTML;

        $image_upload_config = <<<HTML
        paste_data_images: true,
        automatic_uploads: true,
        images_upload_handler: dle_forum_upload_handler,
        images_reuse_filename: true,
        image_uploadtab: false,
        images_file_types: '{$_fup_img_exts}',
HTML;
    }

    $e_plugins  = "link autolink ";
    $link_icon  = "link unlink dleleech ";

    if (!empty($user_group[$member_id["user_group"]]["allow_image"])) {
        $link_icon .= "| image ";
    }
    if ($can_upload) {
        $link_icon .= "dleupload ";
    }

    $_fup_editor_template_css = "";
    if (@file_exists(ROOT_DIR . "/templates/" . $config["skin"] . "/editor.css")) {
        $_fup_editor_template_css =
            ", dle_root + 'templates/{$config['skin']}/editor.css'";
    }

    $_fup_dlehide_groups_json = "{}";
    if ($f_editor_full_dle) {
        $_fup_groups_list = [];
        foreach ($user_group as $_fup_gid => $_fup_gdata) {
            if ($_fup_gid == 1) {
                continue;
            }
            $_fup_groups_list[$_fup_gid] = $_fup_gdata["group_name"];
        }
        $_fup_dlehide_groups_json = json_encode(
            $_fup_groups_list,
            JSON_UNESCAPED_UNICODE,
        );
    }

    if ($f_editor_full_dle) {
        $editor_plugins = "accordion fullscreen advlist autolink lists dlelink image charmap anchor searchreplace visualblocks visualchars nonbreaking table codemirror dlebutton codesample quickbars autosave pagebreak toc";
        $editor_toolbar = "[
            'bold italic underline strikethrough align bullist numlist link unlink dleleech table dleupload image dlemp dlaudio dletube dleemo dlequote dlehide dlespoiler codesample pastetext code dlemore',
            'mathml fontformatting forecolor backcolor | outdent indent subscript superscript anchor accordion pagebreak dlepage hr charmap searchreplace toc dletypo visualblocks | restoredraft undo redo removeformat fullscreen'
        ]";
        $editor_toolbar_groups = "
        toolbar_groups: {
            fontformatting: {
                icon: 'change-case',
                tooltip: 'Formatting',
                items: 'blocks styles fontfamily fontsize lineheight'
            },
            align: {
                icon: 'align-center',
                tooltip: 'Formatting',
                items: 'alignleft aligncenter alignright alignjustify'
            }
        },
        ";
        $extra_admin_config = "
        verify_html: false,
        nonbreaking_force_tab: true,
        extended_valid_elements: 'dlehide[class|contenteditable|data-allowed-groups],span[style]',
        custom_elements: 'dlehide',
        image_advtab: true,
        image_caption: true,
        draggable_modal: true,
        contextmenu: 'image table lists',
        block_formats: 'Header 1=h1;Header 2=h2;Header 3=h3;Header 4=h4;Header 5=h5;Header 6=h6;Tag (p)=p;Tag (div)=div;',
        style_formats: [
            { title: 'Information Block', block: 'div', wrapper: true, styles: { 'color': '#333333', 'border': 'solid 1px #00897B', 'padding': '0.78em', 'background-color': '#E0F2F1', 'box-shadow': 'rgba(126,142,177,0.2) 0 .35em .7em' } },
            { title: 'Warning Block', block: 'div', wrapper: true, styles: { 'border': 'solid 1px #FF9800', 'padding': '0.78em', 'background-color': '#FFF3E0', 'color': '#aa3510', 'box-shadow': 'rgba(126,142,177,0.2) 0 .35em .7em' } },
            { title: 'Error Block', block: 'div', wrapper: true, styles: { 'border': 'solid .1px #FF5722', 'padding': '0.78em', 'background-color': '#FBE9E7', 'color': '#9c1f1f', 'box-shadow': 'rgba(126,142,177,0.2) 0 .35em .7em' } },
            { title: 'Borders', block: 'div', wrapper: true, styles: { 'border': 'solid .1px #ccc', 'padding': '0.78em' } },
        ],
        image_class_list: [
            { title: 'None', value: '' },
            { title: 'Image Border', value: 'image-bordered' },
            { title: 'Image Shadow', value: 'image-shadows' },
            { title: 'Image Padding', value: 'image-padded' },
        ],
        font_size_formats: '0.8em 0.9em 1em 1.1em 1.2em 1.3em 1.4em 1.5em 2em 2.5em 3em',
        font_size_input_default_unit: 'em',
        quickbars_insert_toolbar: false,
        quickbars_selection_toolbar: 'bold italic underline | quicklink | dlequote dlespoiler dlehide | forecolor backcolor | styles | blocks fontsize',
        setup: function(editor) {
            editor.on('PreInit', function() {
                editor.schema.addCustomElements('dlehide');
                editor.schema.addValidElements('dlehide[class|data-allowed-groups|contenteditable]');
            });
        },
        ";
        $_fup_tinymce_statusbar = "true";
        $_fup_tinymce_elementpath = "true";
        $_fup_tinymce_contextmenu = "'image table lists'";
    } else {
        $editor_plugins = $f_editor_mode === "full"
            ? "{$e_plugins}image lists quickbars dlebutton codesample"
            : "{$e_plugins}lists quickbars dlebutton";
        $toolbar_btns   = $f_editor_mode === "full"
            ? "bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist | dleemo {$link_icon}| dlequote codesample dlespoiler dlehide | removeformat"
            : "bold italic underline | bullist numlist | dleemo link | dlequote | removeformat";
        $editor_toolbar = "'" . $toolbar_btns . "'";
        $extra_admin_config = "
        statusbar: false,
        quickbars_insert_toolbar: '',
        quickbars_selection_toolbar: 'bold italic underline | removeformat',
        ";
        $editor_toolbar_groups = "";
        $_fup_tinymce_statusbar = "false";
        $_fup_tinymce_elementpath = "false";
        $_fup_tinymce_contextmenu = "false";
    }

    if (!isset($editor_toolbar_groups)) {
        $editor_toolbar_groups = "";
    }
    if (!isset($_fup_tinymce_statusbar)) {
        $_fup_tinymce_statusbar = "false";
        $_fup_tinymce_elementpath = "false";
        $_fup_tinymce_contextmenu = "false";
    }

    $_fup_dle_cryptkey = substr(hash("sha256", SECURE_AUTH_KEY), 0, 6);
    $_fup_toolbar_mode_line = $f_editor_full_dle
        ? ""
        : "toolbar_mode: 'floating',";
    $_fup_dle_editor_js = "";
    if ($f_editor_full_dle) {
        $_fup_dle_editor_js = <<<JS
    var fupHeight = {$f_editor_height};
    var fupGetBaseSize = typeof getBaseSize === 'function' ? getBaseSize : function() { return 1; };
    fupHeight = 400 * fupGetBaseSize();
    if (fupHeight > 600) { fupHeight = 600; }
    var fupMaxHeight = jQuery(window).height() * 0.8;
    var fupStatusbar = true;
    var fupAdditionalPlugins = '';
    if (jQuery('body').hasClass('editor-style-light') || jQuery('body').hasClass('editor-autoheight')) {
        fupStatusbar = false;
    } else {
        fupAdditionalPlugins = ' wordcount';
    }
    if (jQuery('body').hasClass('editor-autoheight')) {
        fupAdditionalPlugins += ' autoresize';
    }
JS;
        $_fup_tinymce_height = "fupHeight";
        $_fup_tinymce_min_height = "50";
        $_fup_tinymce_max_height_line = "max_height: fupMaxHeight,";
        $_fup_tinymce_statusbar = "fupStatusbar";
        $_fup_tinymce_plugins = "'{$editor_plugins}' + fupAdditionalPlugins";
        $_fup_tinymce_autoresize = "autoresize_bottom_margin: 1,";
        $_fup_tinymce_autosave = "
        autosave_ask_before_unload: true,
        autosave_interval: '10s',
        autosave_prefix: 'dle-editor-{path}{query}-{id}-',
        autosave_restore_when_empty: false,
        autosave_retention: '10m',";
    } else {
        $_fup_tinymce_height = (string) $f_editor_height;
        $_fup_tinymce_min_height = "100";
        $_fup_tinymce_max_height_line = "";
        $_fup_tinymce_plugins = "'{$editor_plugins}'";
        $_fup_tinymce_autoresize = "";
        $_fup_tinymce_autosave = "";
    }

    global $onload_scripts;
    if (!is_array($onload_scripts)) {
        $onload_scripts = [];
    }

    $onload_scripts[] = <<<HTML
    if (typeof dle_root === 'undefined' || !dle_root) {
        var dle_root = '{$config['http_home_url']}';
    }
    if (dle_root && !dle_root.endsWith('/')) { dle_root += '/'; }

    if (typeof dle_login_hash === 'undefined' || !dle_login_hash) {
        var dle_login_hash = '{$dle_login_hash}';
    }

    // media_upload override
    window.media_upload = function(area, author, news_id, wysiwyg) {
        var n = area + author + news_id + wysiwyg;
        if (jQuery("#mediaupload").hasClass("ui-dialog-content") && window.media_upload_manager == n) {
            jQuery("#mediaupload").dialog("open");
            if (typeof check_all === "function") { check_all(); }
            return false;
        }
        jQuery("#mediaupload").remove();
        jQuery("body").append("<div id='mediaupload' class='mediaupload-body' title='" + (typeof text_upload !== 'undefined' ? text_upload : 'File Upload') + "' style='display:none'></div>");
        if (typeof ShowLoading === "function") ShowLoading("");
        jQuery.get(dle_root + "index.php?controller=ajax", {
            mod: "forum_upload",
            area: area,
            news_id: news_id,
            author: author,
            wysiwyg: wysiwyg,
            skin: typeof dle_skin !== 'undefined' ? dle_skin : ''
        }, function(data) {
            if (typeof HideLoading === "function") HideLoading("");
            jQuery("#mediaupload").html(data);
            var getBaseSizeFunc = typeof getBaseSize === "function" ? getBaseSize : function() { return 1; };
            var w_width = 900 * getBaseSizeFunc();
            var w_height = 600 * getBaseSizeFunc();
            if (w_width > 0.95 * jQuery(window).width()) { w_width = 0.95 * jQuery(window).width(); }
            if (w_height > 0.95 * jQuery(window).height()) { w_height = 0.95 * jQuery(window).height(); }
            jQuery("#mediaupload").dialog({
                autoOpen: true,
                width: w_width,
                height: w_height,
                resizable: false,
                dialogClass: "modalfixed dle-popup-mediaupload",
                classes: { "ui-dialog": "modalfixed dle-popup-mediaupload" },
                open: function(e, t) {
                    jQuery(".dle-popup-mediaupload").append(jQuery("#mediaupload-buttonpane").html());
                    jQuery("#mediaupload-buttonpane").remove();
                },
                dragStart: function(e, t) {
                    jQuery("#mediaupload").css("opacity", "0");
                    jQuery(".mediaupload-insert-params").css("opacity", "0");
                    jQuery(".modalfixed").css("opacity", "0.8");
                },
                dragStop: function(e, t) {
                    jQuery("#mediaupload").css("opacity", "1");
                    jQuery(".mediaupload-insert-params").css("opacity", "1");
                    jQuery(".modalfixed").css("opacity", "1");
                }
            });
            window.media_upload_manager = n;
            if (jQuery(window).width() > 830 && jQuery(window).height() > 530) {
                jQuery(".modalfixed.ui-dialog").css({ position: "fixed" });
                jQuery("#mediaupload").dialog("option", "position", { my: "center", at: "center", of: window });
            }
        }, "html");
        return false;
    };

    // XHR INTERCEPT
    (function () {
        var _open = XMLHttpRequest.prototype.open;
        var _send = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function (method, url) {
            this._forumIntercept = false;
            if (typeof url === 'string'
                    && url.indexOf('mod=upload') > -1
                    && url.indexOf('mod=forum_upload') === -1) {
                
                url = url.replace(/mod=upload(&|$)/, 'mod=forum_upload$1');
                this._forumIntercept = true;
            }
            return _open.call(this, method, url);
        };

        XMLHttpRequest.prototype.send = function (data) {
            if (this._forumIntercept && data instanceof FormData) {
                var h = data.get ? data.get('user_hash') : null;
                if (!h || h === 'undefined') {
                    try { data.set('user_hash', dle_login_hash); } catch(e) {}
                }
                if (data.get && data.get('qqfile') && !data.get('file')) {
                    try {
                        var qf = data.get('qqfile');
                        data.delete('qqfile');
                        data.append('file', qf);
                    } catch(e) {}
                }
            }
            return _send.call(this, data);
        };
    })();

    {$image_upload_handler}

    tinyMCE.baseURL = dle_root + 'public/editor/tiny_mce';
    tinyMCE.suffix  = '.min';
    var dle_theme = '{$dark_theme}'.trim();
    if (dle_theme !== '') { jQuery('body').addClass(dle_theme); }
    else if (jQuery('body').hasClass('dle_theme_dark')) { dle_theme = 'dle_theme_dark'; }

    var body_class = dle_theme;
    if (jQuery('body').hasClass('style-smoothing')) {
        body_class = body_class + ' style-smoothing';
    }

    {$_fup_dle_editor_js}

    tinymce.init({
        selector: 'textarea#{$f_editor_id}',
        license_key: 'gpl',
        language: '{$lang["language_code"]}',
        directionality: '{$lang["direction"]}',
        body_class: body_class,
        skin: dle_theme === 'dle_theme_dark' ? 'oxide-dark' : 'oxide',
        content_css: [dle_root + 'public/editor/css/content.css'{$_fup_editor_template_css}],
        content_style: 'body { --font-size-base: ' + jQuery('body').css('font-size') + '; }',
        element_format: 'html',
        width: '100%',
        height: {$_fup_tinymce_height},
        min_height: {$_fup_tinymce_min_height},
        {$_fup_tinymce_max_height_line}
        {$_fup_tinymce_autoresize}
        menubar: false,
        statusbar: {$_fup_tinymce_statusbar},
        {$_fup_toolbar_mode_line}
        contextmenu: {$_fup_tinymce_contextmenu},
        deprecation_warnings: false,
        promotion: false,
        cache_suffix: '?v={$config["cache_id"]}',
        plugins: {$_fup_tinymce_plugins},
        toolbar: {$editor_toolbar},
        {$editor_toolbar_groups}
        {$extra_admin_config}
        relative_urls: false,
        convert_urls: false,
        remove_script_host: false,
        browser_spellcheck: true,
        formats: {
            bold:          {inline: 'b'},
            italic:        {inline: 'i'},
            underline:     {inline: 'u', exact: true},
            strikethrough: {inline: 's', exact: true}
        },
        paste_as_text: false,
        paste_postprocess: function(editor, args) {
            if (typeof DLEclearPasteText === 'function') {
                args.node.innerHTML = DLEclearPasteText(args.node.innerHTML, editor);
            }
        },
        elementpath: {$_fup_tinymce_elementpath},
        branding: false,
        text_patterns: [],
        link_default_target: '_blank',
        image_dimensions: true,
        a11y_advanced_options: true,
        dle_root: dle_root,
        dle_upload_area: 'short_story',
        dle_upload_user: '{$member_id["name"]}',
        dle_upload_news: {$f_editor_news_id},
        dle_cryptkey: '{$_fup_dle_cryptkey}',
        dle_user_groups: {$_fup_dlehide_groups_json},
        {$_fup_tinymce_autosave}
        {$image_upload_config}
        placeholder: '{$editor_placeholder}',
        mobile: {
            toolbar_mode: 'sliding',
            toolbar: 'bold italic underline | link | removeformat'
        }
    });
HTML;
}

if ($f_editor_full_dle) {
    // DLE addnews (shortsite.php): wseditor + mavi ust serit — editor-panel admin icindir
    $wysiwyg = <<<HTML
<script>
var text_upload     = "{$_fup_modal_title}";
var dle_quote_title = "{$lang["i_quote"]}";
</script>
<div class="wseditor{$dark_theme}"><textarea id="{$f_editor_id}" name="text" class="wysiwygeditor" style="width:100%;height:{$f_editor_height}px;"></textarea></div>
HTML;
} else {
    $wysiwyg = <<<HTML
<script>
var text_upload     = "{$_fup_modal_title}";
var dle_quote_title = "{$lang["i_quote"]}";
</script>
<div class="dleaddcomments-editor wseditor dlecomments-editor{$dark_theme}">
    <textarea id="{$f_editor_id}" name="text" style="width:100%;height:{$f_editor_height}px;"></textarea>
</div>
HTML;
}
