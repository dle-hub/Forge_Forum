<?php
/*
=====================================================
 Forge Forum — Kategori ikon yardımcıları
=====================================================
 Font Awesome 4.7 (DLE admin) + img: göreli/yol veya URL
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (!function_exists("forum_cat_icon_list")) {
    function forum_cat_icon_list()
    {
        return [
            "fa-folder", "fa-folder-open", "fa-folder-o", "fa-sitemap",
            "fa-comments", "fa-comments-o", "fa-comment", "fa-comment-o",
            "fa-newspaper-o", "fa-file-text-o", "fa-file-o", "fa-pencil-square-o",
            "fa-bullhorn", "fa-bell", "fa-bell-o", "fa-question-circle",
            "fa-info-circle", "fa-life-ring", "fa-lightbulb-o", "fa-book",
            "fa-graduation-cap", "fa-gamepad", "fa-film", "fa-music",
            "fa-headphones", "fa-camera", "fa-picture-o", "fa-video-camera",
            "fa-desktop", "fa-laptop", "fa-mobile", "fa-tablet",
            "fa-code", "fa-terminal", "fa-database", "fa-server", "fa-cloud",
            "fa-cog", "fa-wrench", "fa-cogs", "fa-shopping-cart",
            "fa-credit-card", "fa-money", "fa-heart", "fa-heart-o",
            "fa-star", "fa-star-o", "fa-trophy", "fa-bolt", "fa-fire",
            "fa-users", "fa-user", "fa-group", "fa-globe", "fa-map-marker",
            "fa-flag", "fa-android", "fa-apple", "fa-windows", "fa-linux",
            "fa-shield", "fa-lock", "fa-unlock", "fa-download", "fa-upload",
            "fa-puzzle-piece", "fa-cubes", "fa-th-large", "fa-th-list",
            "fa-car", "fa-plane", "fa-truck", "fa-cutlery", "fa-coffee",
            "fa-beer", "fa-paw", "fa-leaf", "fa-tree", "fa-briefcase",
            "fa-building", "fa-futbol-o", "fa-bookmark", "fa-bookmark-o",
            "fa-tags", "fa-tag", "fa-rss", "fa-envelope-o", "fa-paper-plane-o",
        ];
    }
}

if (!function_exists("forum_cat_icon_is_image")) {
    function forum_cat_icon_is_image($icon)
    {
        $icon = trim((string) $icon);
        if ($icon === "") {
            return false;
        }
        if (stripos($icon, "img:") === 0) {
            return true;
        }
        if (preg_match("#^(https?:)?//#i", $icon)) {
            return true;
        }
        if (preg_match("#^uploads/#i", $icon)) {
            return true;
        }
        return (bool) preg_match(
            "#\.(jpe?g|png|gif|webp|svg|avif|bmp)(\?.*)?$#i",
            $icon,
        );
    }
}

if (!function_exists("forum_cat_icon_strip_image")) {
    function forum_cat_icon_strip_image($icon)
    {
        $icon = trim((string) $icon);
        if (stripos($icon, "img:") === 0) {
            return trim(substr($icon, 4));
        }
        return $icon;
    }
}

if (!function_exists("forum_cat_icon_to_relative")) {
    function forum_cat_icon_to_relative($url)
    {
        global $config;

        $url = trim((string) $url);
        if ($url === "") {
            return "";
        }

        $home = rtrim($config["http_home_url"], "/") . "/";
        if (stripos($url, $home) === 0) {
            return ltrim(substr($url, strlen($home)), "/");
        }

        return $url;
    }
}

if (!function_exists("forum_cat_icon_image_url")) {
    function forum_cat_icon_image_url($icon)
    {
        global $config;

        $path = forum_cat_icon_strip_image($icon);
        if ($path === "") {
            return "";
        }
        if (preg_match("#^https?://#i", $path) || strpos($path, "//") === 0) {
            return $path;
        }

        return rtrim($config["http_home_url"], "/") . "/" . ltrim($path, "/");
    }
}

if (!function_exists("forum_normalize_cat_icon_fa")) {
    function forum_normalize_cat_icon_fa($icon)
    {
        $icon = strtolower(trim((string) $icon));
        $icon = preg_replace("/\s+/", " ", $icon);
        $icon = preg_replace("/\bfa\s+fa-/", "fa-", $icon);
        $icon = str_replace(" ", "", $icon);

        if ($icon !== "" && strpos($icon, "fa-") !== 0) {
            $icon = "fa-" . ltrim($icon, "fa");
        }

        if (!preg_match("/^fa-[a-z0-9\-]+$/", $icon)) {
            return "fa-folder";
        }

        return $icon;
    }
}

if (!function_exists("forum_normalize_cat_icon")) {
    function forum_normalize_cat_icon($icon)
    {
        $icon = trim((string) $icon);
        if ($icon === "") {
            return "";
        }

        if (stripos($icon, "img:") === 0) {
            $path = forum_cat_icon_to_relative(
                forum_cat_icon_strip_image($icon),
            );
            if ($path === "" || preg_match("#\.\.#", $path)) {
                return "fa-folder";
            }
            if (
                !preg_match("#\.(jpe?g|png|gif|webp|svg|avif|bmp)(\?.*)?$#i", $path) &&
                !preg_match("#^https?://#i", $path) &&
                strpos($path, "//") !== 0
            ) {
                return "fa-folder";
            }
            return "img:" . $path;
        }

        if (forum_cat_icon_is_image($icon)) {
            $path = forum_cat_icon_to_relative($icon);
            if ($path === "" || preg_match("#\.\.#", $path)) {
                return "fa-folder";
            }
            return "img:" . $path;
        }

        return forum_normalize_cat_icon_fa($icon);
    }
}

if (!function_exists("forum_cat_icon_fa_class")) {
    function forum_cat_icon_fa_class($icon, $extra_classes = "")
    {
        if (forum_cat_icon_is_image($icon)) {
            return trim($extra_classes);
        }
        $icon = forum_normalize_cat_icon_fa($icon ?: "fa-folder-open");
        $class = "fa " . $icon;
        if (trim($extra_classes) !== "") {
            $class .= " " . trim($extra_classes);
        }
        return $class;
    }
}

if (!function_exists("forum_cat_icon_render_html")) {
    function forum_cat_icon_render_html(
        $icon,
        $extra_class = "",
        $inline_style = "",
    ) {
        $icon = trim((string) $icon);
        if ($icon === "") {
            return "";
        }

        $class = htmlspecialchars(trim($extra_class), ENT_QUOTES, "UTF-8");
        $style = htmlspecialchars(trim($inline_style), ENT_QUOTES, "UTF-8");
        $class_attr = $class !== "" ? ' class="' . $class . '"' : "";
        $style_attr = $style !== "" ? ' style="' . $style . '"' : "";

        if (forum_cat_icon_is_image($icon)) {
            $url = forum_cat_icon_image_url($icon);
            if ($url === "") {
                return "";
            }
            $img_style =
                $style !== ""
                    ? $style
                    : "width:16px;height:16px;object-fit:contain;vertical-align:middle;";
            return '<img src="' .
                htmlspecialchars($url, ENT_QUOTES, "UTF-8") .
                '" alt=""' .
                $class_attr .
                ' style="' .
                htmlspecialchars($img_style, ENT_QUOTES, "UTF-8") .
                '">';
        }

        $fa_class = forum_cat_icon_fa_class($icon, $extra_class);
        return '<i class="' .
            htmlspecialchars($fa_class, ENT_QUOTES, "UTF-8") .
            '"' .
            $style_attr .
            "></i>";
    }
}

if (!function_exists("forum_cat_icon_suggest")) {
    function forum_cat_icon_suggest($name, $parent_id = 0)
    {
        $name = mb_strtolower(trim((string) $name), "UTF-8");
        if ($name === "") {
            return intval($parent_id) > 0 ? "fa-folder-o" : "fa-folder-open";
        }

        $rules = [
            "oyun|game|gaming|steam" => "fa-gamepad",
            "film|sinema|dizi|video|netflix" => "fa-film",
            "müzik|muzik|music|ses|spotify" => "fa-music",
            "foto|görsel|gorsel|resim|image|galeri" => "fa-picture-o",
            "yazılım|yazilim|software|kod|code|program|script|php|python" => "fa-code",
            "haber|news|gündem|gundem|basın|basin" => "fa-newspaper-o",
            "sohbet|chat|konuş|konus|tartış|tartis|forum" => "fa-comments",
            "yardım|yardim|help|destek|support|soru" => "fa-life-ring",
            "duyuru|announce|bilgi|info" => "fa-bullhorn",
            "satış|satis|shop|mağaza|magaza|market|alışveriş|alisveris" => "fa-shopping-cart",
            "teknik|technical|donanım|donanim|hardware|tamir" => "fa-wrench",
            "mobil|phone|telefon|android|iphone" => "fa-mobile",
            "bilgisayar|pc|computer|laptop|notebook" => "fa-laptop",
            "eğitim|egitim|education|ders|okul|üniversite|universite" => "fa-graduation-cap",
            "spor|sport|futbol|basket" => "fa-futbol-o",
            "android" => "fa-android",
            "apple|ios|mac" => "fa-apple",
            "windows|microsoft" => "fa-windows",
            "linux|ubuntu|debian" => "fa-linux",
            "güvenlik|guvenlik|security|hack|antivirus" => "fa-shield",
            "sunucu|server|hosting|vps|cloud|bulut" => "fa-server",
            "veritabanı|veritabani|database|mysql|sql" => "fa-database",
            "modül|modul|eklenti|plugin|extension|addon" => "fa-puzzle-piece",
            "tasarım|tasarim|design|grafik|photoshop" => "fa-picture-o",
            "iş|is|kariyer|career|freelance" => "fa-briefcase",
            "araç|arac|tool|utility|araçlar|araclar" => "fa-cogs",
            "genel|general|misc|çeşitli|cesitli" => "fa-folder-open",
        ];

        foreach ($rules as $pattern => $icon) {
            if (preg_match("/(" . $pattern . ")/iu", $name)) {
                return $icon;
            }
        }

        return intval($parent_id) > 0 ? "fa-folder-o" : "fa-folder-open";
    }
}

if (!function_exists("forum_cat_icon_picker_preview_inner")) {
    function forum_cat_icon_picker_preview_inner($value)
    {
        $value = trim((string) $value);
        if ($value === "") {
            return '<i class="fa fa-folder-open"></i>';
        }
        if (forum_cat_icon_is_image($value)) {
            $url = forum_cat_icon_image_url($value);
            return '<img src="' .
                htmlspecialchars($url, ENT_QUOTES, "UTF-8") .
                '" alt="" style="max-width:18px;max-height:18px;object-fit:contain;">';
        }
        $fa = forum_normalize_cat_icon_fa($value);
        return '<i class="fa ' . htmlspecialchars($fa, ENT_QUOTES, "UTF-8") . '"></i>';
    }
}

if (!function_exists("forum_cat_icon_picker")) {
    function forum_cat_icon_picker($value = "fa-folder-open", $field_id = "cat_icon")
    {
        global $lang, $config;

        $value = trim((string) $value);
        if ($value === "") {
            $value = "fa-folder-open";
        }
        if (!forum_cat_icon_is_image($value)) {
            $value = forum_normalize_cat_icon_fa($value);
        }

        $safe_value = htmlspecialchars($value, ENT_QUOTES, "UTF-8");
        $field_id = preg_replace("/[^a-zA-Z0-9_\-]/", "", $field_id);
        $grid_id = $field_id . "_grid";
        $file_id = $field_id . "_file";
        $auto_label = $lang["forum_cat_icon_auto"] ?? "Otomatik";
        $pick_label = $lang["forum_cat_icon_pick"] ?? "Seç";
        $upload_label = $lang["forum_cat_icon_upload"] ?? "Resim yükle";
        $fa_label = $lang["forum_cat_icon_fa_mode"] ?? "Font Awesome";
        $hint = htmlspecialchars(
            $lang["forum_cat_icon_hint"] ??
                "fa-folder veya uploads/... yolu. Resim yükleyebilirsiniz.",
            ENT_QUOTES,
            "UTF-8",
        );
        $home_url = htmlspecialchars(
            rtrim($config["http_home_url"], "/") . "/",
            ENT_QUOTES,
            "UTF-8",
        );
        $preview = forum_cat_icon_picker_preview_inner($value);

        $buttons = "";
        foreach (forum_cat_icon_list() as $icon) {
            $active =
                !forum_cat_icon_is_image($value) && $icon === $value
                    ? " active"
                    : "";
            $safe = htmlspecialchars($icon, ENT_QUOTES, "UTF-8");
            $buttons .=
                '<button type="button" class="forum-icon-item' .
                $active .
                '" data-icon="' .
                $safe .
                '" title="' .
                $safe .
                '"><i class="fa ' .
                $safe .
                '"></i></button>';
        }

        return <<<HTML
<div class="forum-icon-picker" data-field="{$field_id}" data-home="{$home_url}">
  <div class="input-group" style="max-width:38.19em;">
    <span class="input-group-addon forum-icon-preview" style="min-width:2.4em;text-align:center;">{$preview}</span>
    <input type="text" name="cat_icon" id="{$field_id}" class="form-control forum-icon-input" maxlength="255" dir="auto" value="{$safe_value}" placeholder="fa-folder veya img:uploads/forum/...">
    <span class="input-group-btn">
      <button type="button" class="btn btn-default forum-icon-auto-btn" title="{$auto_label}"><i class="fa fa-magic"></i></button>
      <button type="button" class="btn btn-default forum-icon-toggle-btn" data-target="#{$grid_id}" title="{$pick_label}"><i class="fa fa-th"></i></button>
    </span>
  </div>
  <div class="forum-icon-actions" style="margin-top:6px;max-width:38.19em;">
    <input type="file" id="{$file_id}" class="forum-icon-file-input" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,image/svg+xml" style="display:none;">
    <button type="button" class="btn btn-default btn-sm forum-icon-upload-btn"><i class="fa fa-upload position-left"></i>{$upload_label}</button>
    <button type="button" class="btn btn-default btn-sm forum-icon-fa-mode-btn"><i class="fa fa-font position-left"></i>{$fa_label}</button>
  </div>
  <span class="help-block text-muted text-size-small">{$hint}</span>
  <div id="{$grid_id}" class="forum-icon-grid-wrap" style="display:none;max-width:38.19em;">
    <div class="forum-icon-grid">{$buttons}</div>
  </div>
</div>
HTML;
    }
}

if (!function_exists("forum_cat_icon_assets")) {
    function forum_cat_icon_assets()
    {
        $rules = [];
        $map = [
            "oyun|game|gaming|steam" => "fa-gamepad",
            "film|sinema|dizi|video|netflix" => "fa-film",
            "müzik|muzik|music|ses|spotify" => "fa-music",
            "foto|görsel|gorsel|resim|image|galeri" => "fa-picture-o",
            "yazılım|yazilim|software|kod|code|program|script|php|python" => "fa-code",
            "haber|news|gündem|gundem|basın|basin" => "fa-newspaper-o",
            "sohbet|chat|konuş|konus|tartış|tartis|forum" => "fa-comments",
            "yardım|yardim|help|destek|support|soru" => "fa-life-ring",
            "duyuru|announce|bilgi|info" => "fa-bullhorn",
            "satış|satis|shop|mağaza|magaza|market|alışveriş|alisveris" => "fa-shopping-cart",
            "teknik|technical|donanım|donanim|hardware|tamir" => "fa-wrench",
            "mobil|phone|telefon|android|iphone" => "fa-mobile",
            "bilgisayar|pc|computer|laptop|notebook" => "fa-laptop",
            "eğitim|egitim|education|ders|okul|üniversite|universite" => "fa-graduation-cap",
            "spor|sport|futbol|basket" => "fa-futbol-o",
            "android" => "fa-android",
            "apple|ios|mac" => "fa-apple",
            "windows|microsoft" => "fa-windows",
            "linux|ubuntu|debian" => "fa-linux",
            "güvenlik|guvenlik|security|hack|antivirus" => "fa-shield",
            "sunucu|server|hosting|vps|cloud|bulut" => "fa-server",
            "veritabanı|veritabani|database|mysql|sql" => "fa-database",
            "modül|modul|eklenti|plugin|extension|addon" => "fa-puzzle-piece",
            "tasarım|tasarim|design|grafik|photoshop" => "fa-picture-o",
            "iş|is|kariyer|career|freelance" => "fa-briefcase",
            "araç|arac|tool|utility|araçlar|araclar" => "fa-cogs",
            "genel|general|misc|çeşitli|cesitli" => "fa-folder-open",
        ];
        foreach ($map as $pattern => $icon) {
            $rules[] = ["pattern" => $pattern, "icon" => $icon];
        }
        $rules_json = json_encode($rules, JSON_UNESCAPED_UNICODE);

        return <<<HTML
<style>
.forum-icon-grid{display:flex;flex-wrap:wrap;gap:4px;padding:10px;margin-top:8px;border:1px solid #ddd;background:#fafafa;max-height:220px;overflow-y:auto}
.forum-icon-item{width:36px;height:36px;border:1px solid #ddd;background:#fff;border-radius:3px;cursor:pointer;padding:0;line-height:34px;text-align:center;color:#333}
.forum-icon-item:hover,.forum-icon-item.active{background:#e0f2f1;border-color:#00897b;color:#00695c}
.forum-icon-item i{pointer-events:none;font-size:16px}
.forum-icon-actions .btn{margin-right:4px}
</style>
<script>
(function($){
  function forumIsImageIcon(val) {
    val = (val || '').trim();
    if (!val) return false;
    if (/^img:/i.test(val)) return true;
    if (/^https?:\\/\\//i.test(val) || /^\\/\\//.test(val)) return true;
    if (/^uploads\\//i.test(val)) return true;
    return /\\.(jpe?g|png|gif|webp|svg|avif|bmp)(\\?.*)?$/i.test(val);
  }
  function forumToRelativeUrl(url, home) {
    url = (url || '').trim();
    if (!url) return '';
    if (home && url.indexOf(home) === 0) {
      return url.substring(home.length).replace(/^\\//, '');
    }
    return url.replace(/^\\//, '');
  }
  function forumSuggestIcon(name, parentId) {
    var rules = {$rules_json};
    name = (name || '').toLowerCase();
    for (var i = 0; i < rules.length; i++) {
      try {
        if (new RegExp('(' + rules[i].pattern + ')', 'i').test(name)) {
          return rules[i].icon;
        }
      } catch(e) {}
    }
    return (parseInt(parentId, 10) > 0) ? 'fa-folder-o' : 'fa-folder-open';
  }
  function forumPreviewHtml(\$picker, val) {
    val = (val || '').trim();
    var home = \$picker.data('home') || '';
    if (forumIsImageIcon(val)) {
      var path = val.replace(/^img:/i, '');
      var src = path;
      if (!/^https?:\\/\\//i.test(path) && path.indexOf('//') !== 0) {
        src = home + path.replace(/^\\//, '');
      }
      return '<img src="' + src + '" alt="" style="max-width:18px;max-height:18px;object-fit:contain;">';
    }
    var icon = val.replace(/^fa\\s+/, 'fa-');
    if (icon.indexOf('fa-') !== 0) icon = 'fa-' + icon;
    return '<i class="fa ' + icon + '"></i>';
  }
  function forumSetIcon(\$picker, icon) {
    icon = (icon || '').trim();
    if (!icon) icon = 'fa-folder-open';
    if (!forumIsImageIcon(icon)) {
      icon = icon.replace(/^fa\\s+/, 'fa-');
      if (icon.indexOf('fa-') !== 0) icon = 'fa-' + icon;
    } else if (!/^img:/i.test(icon)) {
      var home = \$picker.data('home') || '';
      icon = 'img:' + forumToRelativeUrl(icon, home);
    }
    \$picker.find('.forum-icon-input').val(icon);
    \$picker.find('.forum-icon-preview').html(forumPreviewHtml(\$picker, icon));
    \$picker.find('.forum-icon-item').removeClass('active');
    if (!forumIsImageIcon(icon)) {
      \$picker.find('.forum-icon-item[data-icon="' + icon + '"]').addClass('active');
    }
  }
  $(function(){
    $(document).on('click', '.forum-icon-item', function(){
      forumSetIcon($(this).closest('.forum-icon-picker'), $(this).data('icon'));
    });
    $(document).on('click', '.forum-icon-toggle-btn', function(){
      $($(this).data('target')).slideToggle(150);
    });
    $(document).on('click', '.forum-icon-auto-btn', function(){
      var \$picker = $(this).closest('.forum-icon-picker');
      var \$form = \$picker.closest('form');
      forumSetIcon(\$picker, forumSuggestIcon(\$form.find('[name="cat_name"]').val(), \$form.find('[name="cat_parent"]').val()));
    });
    $(document).on('input', '.forum-icon-input', function(){
      forumSetIcon($(this).closest('.forum-icon-picker'), $(this).val());
    });
    $(document).on('click', '.forum-icon-fa-mode-btn', function(){
      forumSetIcon($(this).closest('.forum-icon-picker'), 'fa-folder-open');
      var \$grid = $(this).closest('.forum-icon-picker').find('.forum-icon-grid-wrap');
      \$grid.slideDown(150);
    });
    $(document).on('click', '.forum-icon-upload-btn', function(){
      $(this).closest('.forum-icon-picker').find('.forum-icon-file-input').click();
    });
    $(document).on('change', '.forum-icon-file-input', function(){
      var file = this.files && this.files[0];
      if (!file) return;
      var \$picker = $(this).closest('.forum-icon-picker');
      var fd = new FormData();
      fd.append('file', file);
      fd.append('subaction', 'upload');
      fd.append('user_hash', typeof dle_login_hash !== 'undefined' ? dle_login_hash : '');
      fd.append('area', 'cat_icon');
      fd.append('make_thumb', '0');
      fd.append('make_watermark', '0');
      fd.append('make_medium', '0');
      if (typeof ShowLoading === 'function') ShowLoading('');
      $.ajax({
        url: 'index.php?controller=ajax&mod=forum_upload',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
      }).done(function(res){
        if (typeof HideLoading === 'function') HideLoading('');
        var url = res && (res.url || res.link);
        if (url) {
          var home = \$picker.data('home') || '';
          forumSetIcon(\$picker, 'img:' + forumToRelativeUrl(url, home));
        } else {
          alert((res && (res.error || res.message)) || 'Yukleme basarisiz');
        }
      }).fail(function(){
        if (typeof HideLoading === 'function') HideLoading('');
        alert('Yukleme basarisiz');
      });
      this.value = '';
    });
    $('#newForumCat').on('shown.bs.modal', function(){
      forumSetIcon($('#cat_icon_new').closest('.forum-icon-picker'), 'fa-folder-open');
    });
  });
})(jQuery);
</script>
HTML;
    }
}
