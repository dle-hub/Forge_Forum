<?php
/*
=====================================================
 Forge Forum Engine — Konu Gösterimi
-----------------------------------------------------
 File: engine/modules/forum/actions/topic.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

// -------------------------------------------------
// 1. KONUYU BUL
// -------------------------------------------------
$topic = null;
if ($f_topic_id > 0) {
    $topic = $db->super_query(
        "SELECT * FROM " . PREFIX . "_forum_topics WHERE id = '{$f_topic_id}'",
    );
} elseif (isset($_GET["alt_name"])) {
    $alt = $db->safesql(totranslit($_GET["alt_name"]));
    $topic = $db->super_query(
        "SELECT * FROM " . PREFIX . "_forum_topics WHERE alt_name = '{$alt}'",
    );
}

if (!$topic || !$topic["id"]) {
    msgbox($lang['forum_err_title'], $lang['forum_reply_topic_not_found']);
    return;
}

// Erişim Yetki Kontrolü (Access Permission Check)
if ($topic && !empty($topic['access'])) {
    $access = [];
    $rules = explode("||", $topic['access']);
    foreach ($rules as $rule) {
        $parts = explode(":", $rule);
        if (count($parts) == 2) {
            $access[intval($parts[0])] = intval($parts[1]);
        }
    }
    
    $user_group_id = intval($member_id['user_group']);
    if (isset($access[$user_group_id])) {
        if ($access[$user_group_id] == 3) {
            msgbox($lang['forum_access_denied'], $lang['forum_topic_no_view_perm']);
            return;
        }
    }
}
if ($topic["is_deleted"]) {
    msgbox($lang['forum_err_title'], $lang['forum_topic_deleted']);
    return;
}
if (!$topic["is_approved"] && $member_id["user_group"] > 2) {
    msgbox($lang['forum_err_title'], $lang['forum_topic_unapproved']);
    return;
}

$tid = intval($topic["id"]);
$cid = intval($topic["cat_id"]);

// -------------------------------------------------
// 2. KATEGORİ BİLGİSİ
// -------------------------------------------------
$category = $db->super_query(
    "SELECT name, alt_name FROM " . PREFIX . "_forum_cats WHERE id = '{$cid}'",
);
$cat_name = $category["name"] ? stripslashes($category["name"]) : "";
$cat_alt = $category["alt_name"] ?: "";

// -------------------------------------------------
// 3. GÖRÜNTÜLENME SAYACI (session bazlı)
// -------------------------------------------------
$view_key = "forum_viewed_" . $tid;
if (empty($_SESSION[$view_key])) {
    $db->query(
        "UPDATE " .
            PREFIX .
            "_forum_topics SET views = views + 1 WHERE id = '{$tid}'",
    );
    $_SESSION[$view_key] = 1;
    $topic["views"]++;
}
// Konuyu okundu olarak işaretle (son mesaj tarihini sakla)
$_SESSION["forum_read_" . $tid] = isset($topic["last_post_date"]) ? $topic["last_post_date"] : time();

// -------------------------------------------------
// 4. SEO META & SPEEDBAR
// -------------------------------------------------
$prefix_html = "";
if (isset($topic["prefix_id"]) && $topic["prefix_id"] > 0) {
    $pfx = $db->super_query("SELECT name, color, icon FROM " . PREFIX . "_forum_prefixes WHERE id = '{$topic["prefix_id"]}'");
    if ($pfx && !empty($pfx['name'])) {
        $prefix_html = '<span class="mybb-prefix-badge" style="background:' . htmlspecialchars($pfx["color"], ENT_QUOTES, "UTF-8") . '; color:#fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold; margin-right: 5px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa ' . htmlspecialchars($pfx["icon"], ENT_QUOTES, "UTF-8") . '"></i>' . htmlspecialchars($pfx["name"], ENT_QUOTES, "UTF-8") . '</span>';
    }
}

$plain_title = forum_word_filter(stripslashes($topic["title"]));
$topic_title = $prefix_html . htmlspecialchars($plain_title, ENT_QUOTES, "UTF-8");
$metatags["title"] = $plain_title . " — " . $forum_title;
$metatags["description"] = mb_substr(strip_tags($plain_title), 0, 160);

$forum_speedbar[
    $config["http_home_url"] . "forum/" . $cat_alt . "/"
] = $cat_name;
$forum_speedbar[""] = $plain_title;

// Sabit / Kilitli blokları — HTML topic.tpl'de, PHP sadece göster/gizle
$topic_is_pinned = isset($topic["is_pinned"]) && $topic["is_pinned"] == 1;
$topic_is_locked = isset($topic["is_locked"]) && $topic["is_locked"] == 1;

// Custom Breadcrumb HTML
$breadcrumb_html = "";
if (is_array($forum_speedbar) && count($forum_speedbar) > 0) {
    $breadcrumb_html .=
        '<nav class="flex mb-5 px-4 py-3 text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm" aria-label="Breadcrumb">';
    $breadcrumb_html .=
        '<ol class="inline-flex items-center space-x-3 list-none p-0 m-0">';

    $final_crumbs = [
        $config["http_home_url"] => $lang['forum_breadcrumb_home'],
        $config["http_home_url"] . "forum/" => $lang['forum_breadcrumb_forum'],
        $config["http_home_url"] . "forum/" . $cat_alt . "/" => $cat_name,
        "" => $plain_title,
    ];

    $counter = 0;
    $total = count($final_crumbs);

    foreach ($final_crumbs as $url => $title) {
        $counter++;
        $title = htmlspecialchars(
            strip_tags(stripslashes($title)),
            ENT_QUOTES,
            "UTF-8",
        );

        $breadcrumb_html .= '<li class="inline-flex items-center">';
        if ($counter > 1) {
            $breadcrumb_html .=
                '<svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
        }

        if ($url !== "" && $counter < $total) {
            if ($counter == 1) {
                $breadcrumb_html .=
                    '<a href="' .
                    $url .
                    '" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition">';
                $breadcrumb_html .=
                    '<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h2a1 1 0 001-1v-6.586l.707.707a1 1 0 001.414-1.414l-7-7z"></path></svg>';
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

// -------------------------------------------------
// 5. KONU SAHİBİ
// -------------------------------------------------
$author = $db->super_query(
    "SELECT name, user_id, forum_points, forum_rank_id, forum_post_count
    FROM " .
        PREFIX .
        "_users WHERE user_id = '{$topic["user_id"]}'",
);
$author_name = $author["name"] ?: $lang['forum_ajax_quote_guest'];

// -------------------------------------------------
// 6. SAYFALAMA AYARLARI
// -------------------------------------------------
$perpage = max(1, intval($forum_cfg["posts_per_page"]));

$total_posts = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " .
        PREFIX .
        "_forum_posts WHERE topic_id = '{$tid}' AND is_deleted = 0 AND is_approved = 1",
);
$total_posts = intval($total_posts["cnt"]);
$total_pages = max(1, (int) ceil($total_posts / $perpage));

if (!empty($f_page_is_last)) {
    $last_pid = intval($topic["last_post_id"] ?? 0);
    header(
        "Location: " . forum_topic_post_url($tid, $topic["alt_name"], $last_pid),
        true,
        301,
    );
    exit();
}

$offset = ($f_page - 1) * $perpage;

// -------------------------------------------------
// 7. MESAJLARI ÇEK
// -------------------------------------------------
$posts = [];
$db->query(
    "SELECT p.*, u.name AS user_name, u.user_id AS uid, u.foto, u.email,
            u.forum_points, u.forum_rank_id, u.forum_post_count, u.user_group
     FROM " .
        PREFIX .
        "_forum_posts p
     LEFT JOIN " .
        PREFIX .
        "_users u ON u.user_id = p.user_id
     WHERE p.topic_id = '{$tid}' AND p.is_deleted = 0 AND p.is_approved = 1
     ORDER BY p.date ASC
     LIMIT {$offset}, {$perpage}",
);
while ($row = $db->get_row()) {
    $posts[] = $row;
}

// -------------------------------------------------
// 8. BEĞENİ SAYILARI
// -------------------------------------------------
$like_counts = [];
if ($forum_cfg["enable_likes"] && count($posts)) {
    $post_ids = [];
    foreach ($posts as $p) {
        $post_ids[] = intval($p["id"]);
    }
    $in = implode(",", $post_ids);
    if ($in) {
        $db->query(
            "SELECT post_id, type, COUNT(*) AS cnt FROM " .
                PREFIX .
                "_forum_likes WHERE post_id IN ({$in}) GROUP BY post_id, type",
        );
        while ($lr = $db->get_row()) {
            $like_counts[$lr["post_id"]][$lr["type"]] = intval($lr["cnt"]);
        }
    }
}

// Konu Etiketlerini Çek (Fetch Topic Tags)
$tags_html = "";
$db->query("SELECT t.name, t.alt_name FROM " . PREFIX . "_forum_tags t 
            INNER JOIN " . PREFIX . "_forum_topic_tags tt ON tt.tag_id = t.id 
            WHERE tt.topic_id = '{$tid}'");
$tag_links = [];
while ($trow = $db->get_row()) {
    $tag_links[] = '<a href="' . $config['http_home_url'] . 'forum/search/?q=' . urlencode($trow['name']) . '" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-2.5 py-1 rounded border transition flex items-center gap-1"><i class="fa fa-tag text-gray-400"></i>' . htmlspecialchars($trow['name'], ENT_QUOTES, "UTF-8") . '</a>';
}
if (count($tag_links) > 0) {
    $tags_html = '<div class="mt-3 mb-5 p-4 border border-gray-200 bg-white rounded shadow-sm flex flex-wrap items-center gap-2">
        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mr-1"><i class="fa fa-tags text-blue-500 mr-1"></i> ' . $lang['forum_topic_tags_label'] . '</span>' . implode('', $tag_links) . '</div>';
}

// -------------------------------------------------
// 9. MESAJ SATIRLARINI NATIVE TPL'DE DERLE
// -------------------------------------------------
$post_html = "";

if (!class_exists('ParseFilter')) {
    include_once (ENGINE_DIR . '/classes/parse.class.php');
}
$parse = new ParseFilter();

foreach ($posts as $idx => $p) {
    $tpl->load_template("forum/post.tpl");

    $p_name = htmlspecialchars(
        $p["user_name"] ?: $lang['forum_ajax_quote_guest'],
        ENT_QUOTES,
        "UTF-8",
    );
    $p_posts = intval($p["forum_post_count"]);
    $p_pts = intval($p["forum_points"]);
    
    $p_text = forum_word_filter(stripslashes($p["text"]));
    
    // 1. Tehlikeli taglari temizle (XSS korumasi)
    $p_text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $p_text);
    $p_text = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $p_text);
    
    // 2. BBCode + HTML birlikte isle
    // BB_Parse(true) = wysiwyg modu: HTML'i escape ETMEZ, BBCode'u HTML'e cevirir
    // Bu sekilde hem TinyMCE HTML icerigi hem [quote=...] gibi BBCode dogru calisir
    if (method_exists($parse, 'BB_Parse')) {
        $p_text = $parse->BB_Parse($p_text, true);
    } else {
        // Fallback: temel BBCode parser
        $p_text = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $p_text);
        $p_text = preg_replace('/\[i\](.*?)\[\/i\]/is', '<em>$1</em>', $p_text);
        $p_text = preg_replace('/\[u\](.*?)\[\/u\]/is', '<span style="text-decoration:underline;">$1</span>', $p_text);
        $p_text = preg_replace('/\[s\](.*?)\[\/s\]/is', '<del>$1</del>', $p_text);
        $p_text = preg_replace('/\[quote=([^\]]+)\](.*?)\[\/quote\]/is', '<blockquote class="border-l-4 border-blue-500 pl-4 italic my-2 text-gray-600 bg-gray-50 p-3 rounded"><strong>$1:</strong> $2</blockquote>', $p_text);
        $p_text = preg_replace('/\[quote\](.*?)\[\/quote\]/is', '<blockquote class="border-l-4 border-blue-500 pl-4 italic my-2 text-gray-600 bg-gray-50 p-3 rounded">$1</blockquote>', $p_text);
        $p_text = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/is', '<a href="$1" target="_blank" rel="nofollow">$2</a>', $p_text);
        $p_text = preg_replace('/\[url\](.*?)\[\/url\]/is', '<a href="$1" target="_blank" rel="nofollow">$1</a>', $p_text);
        $p_text = preg_replace('/\[img\](.*?)\[\/img\]/is', '<img src="$1" class="max-w-full rounded my-2" alt="">', $p_text);
        $p_text = preg_replace('/\[thumb\](.*?)\[\/thumb\]/is', '<img src="$1" class="max-w-full rounded my-2" alt="">', $p_text);
        $p_text = preg_replace('/\[code\](.*?)\[\/code\]/is', '<pre class="bg-gray-50 border rounded p-3 text-xs overflow-x-auto my-2 font-mono">$1</pre>', $p_text);
        if (!preg_match('/<[a-z]/i', $p_text)) {
            $p_text = nl2br($p_text);
        }
    }

    $p_date = $p["date"];
    $p_id = intval($p["id"]);
    $p_num = $offset + $idx + 1;

    global $user_group;

    // Düşen / Seçilen Grup Rozeti — SADECE VERİ (HTML post.tpl'de)
    $g_id = intval($p["user_group"]);
    $g_name = "";
    $g_style = "color: #4b5563; border: 1px solid #d1d5db; background-color: #f9fafb;";
    if (isset($user_group[$g_id])) {
        $g_name = htmlspecialchars($user_group[$g_id]['group_name'], ENT_QUOTES, "UTF-8");
        if ($g_id == 1) {
            $g_style = 'color: #e11d48; border: 1px solid #fca5a5; background-color: #fff1f2;';
        } elseif ($g_id == 2) {
            $g_style = 'color: #2563eb; border: 1px solid #93c5fd; background-color: #eff6ff;';
        } elseif ($g_id == 3) {
            $g_style = 'color: #059669; border: 1px solid #6ee7b7; background-color: #ecfdf5;';
        }
    }

    // Forum rütbesi — admin panelindeki puana göre hesaplanır
    $rank = forum_get_user_rank(intval($p["uid"]), $p_pts);
    $r_name = "";
    $r_color = "#9ca3af";
    if ($rank && !empty($rank["title"])) {
        $r_name = htmlspecialchars($rank["title"], ENT_QUOTES, "UTF-8");
        $r_color = htmlspecialchars($rank["color"], ENT_QUOTES, "UTF-8");
    }

    // Yıldız — SADECE VERİ (sayı + renk, HTML post.tpl'de)
    $star_count = 1;
    $star_color = "#9ca3af";
    if ($p_pts >= 1000)     { $star_count = 5; $star_color = "#ec4899"; }
    elseif ($p_pts >= 500)  { $star_count = 5; $star_color = "#3b82f6"; }
    elseif ($p_pts >= 250)  { $star_count = 4; $star_color = "#10b981"; }
    elseif ($p_pts >= 100)  { $star_count = 3; $star_color = "#f59e0b"; }
    elseif ($p_pts >= 30)   { $star_count = 2; $star_color = "#8b5cf6"; }

    // Beğeni sayıları
    $likes    = intval($like_counts[$p_id]["like"]    ?? 0);
    $dislikes = intval($like_counts[$p_id]["dislike"] ?? 0);

    // Etiket Atamaları
    $tpl->set("{post_id}", $p_id);
    $tpl->set("{p_name}",  $p_name);
    $tpl->set("{p_posts}", $p_posts);
    $tpl->set("{p_pts}",   $p_pts);
    $tpl->set("{p_text}",  $p_text);
    $tpl->set("{p_date}",  $p_date);
    $tpl->set("{p_num}",   $p_num);
    $tpl->set("{likes}",    $likes);
    $tpl->set("{dislikes}", $dislikes);
    // IP and GeoIP lookup
    $p_ip_val = trim($p["ip"] ?? "");
    $p_ip_html = $lang['forum_topic_not_specified'];
    
    if ($p_ip_val) {
        $p_ip_html = htmlspecialchars($p_ip_val, ENT_QUOTES, "UTF-8");
        
        // GeoIP Country Lookup
        $country_code = 'UNKNOWN';
        if (class_exists('DLECountry')) {
            $country_code = DLECountry::Get($p_ip_val);
        } else {
            $geo_class = ENGINE_DIR . '/classes/geoip/geo.class.php';
            if (file_exists($geo_class)) {
                include_once DLEPlugins::Check($geo_class);
                if (class_exists('DLECountry')) {
                    $country_code = DLECountry::Get($p_ip_val);
                }
            }
        }
        
        $flag_html = '';
        if ($country_code && $country_code !== 'UNKNOWN' && $country_code !== '-') {
            $country_lower = strtolower($country_code);
            $flag_path = 'public/flags/' . $country_lower . '.png';
            if (file_exists(ROOT_DIR . '/' . $flag_path)) {
                $flag_html = '<img src="' . $config['http_home_url'] . $flag_path . '" alt="' . $country_code . '" title="' . $country_code . '" style="vertical-align: middle; margin-right: 5px; width: 16px; height: 11px; border: 1px solid #e5e7eb; border-radius: 1px; display: inline-block;" /> ';
            }
        }
        
        // Check if user is Admin or Moderator to see interactive DLE IP dropdown menu
        if ($is_logged && ($member_id['user_group'] == 1 || $member_id['user_group'] == 2)) {
            // Check translation keys, fall back to default Turkish if keys are missing
            $ip_info_lang = $lang['ip_info'] ?? 'IP Adres Bilgisi';
            $ip_tools_lang = $lang['ip_tools'] ?? 'Kullanıcı Ara';
            $ip_ban_lang = $lang['ip_ban'] ?? 'IP Adresini Yasakla';
            
            $p_ip_html = $flag_html . "<a onclick=\"return dropdownmenu(this, event, IPMenu('" . $p_ip_val . "', '" . $ip_info_lang . "', '" . $ip_tools_lang . "', '" . $ip_ban_lang . "'), '190px')\" href=\"https://www.nic.ru/whois/?searchWord={$p_ip_val}\" target=\"_blank\" class=\"mybb-ip-link\">{$p_ip_val}</a>";
        } else {
            // For guests or regular users, just show flag + raw IP (though they won't see it anyway due to post.tpl group tags)
            $p_ip_html = $flag_html . $p_ip_val;
        }
    }
    
    $tpl->set("{p_ip}", $p_ip_html);
    $tpl->set("{http_home_url}", $config["http_home_url"]);

    // Grup rozet verisi (HTML post.tpl'de)
    $tpl->set("{p_group_id}",    $g_id);
    $tpl->set("{p_group_name}",  $g_name);
    $tpl->set("{p_group_style}", $g_style);

    // Rütbe verisi (HTML post.tpl'de)
    $tpl->set("{p_rank_name}",  $r_name);
    $tpl->set("{p_rank_color}", $r_color);
    $tpl->set(
        "{p_rank_html}",
        $rank ? forum_rank_render_badge_html($rank, "post") : "",
    );

    // Yıldız verisi (HTML post.tpl'de)
    $tpl->set("{p_star_count}", $star_count);
    $tpl->set("{p_star_color}", $star_color);

    // -------------------------------------------------
    // Hızlı Bağlantılar — E-POSTA, ÖZEL MESAJ, BUL
    // -------------------------------------------------
    $p_uid_val      = intval($p["uid"]);
    $p_name_enc     = urlencode($p["user_name"] ?: "");
    $home           = $config["http_home_url"];

    // E-Posta: Yalnızca kayıtlı üye ve e-postası varsa göster
    $p_has_email = ($p_uid_val > 0 && !empty($p["email"]));
    $p_email_href = $p_has_email
        ? $home . "index.php?do=feedback&user=" . $p_uid_val
        : "#";

    // Özel Mesaj: Giriş yapılmış, hedef kayıtlı üye, kendine değil
    $p_can_pm = ($is_logged
                 && $p_uid_val > 0
                 && $p_uid_val !== intval($member_id["user_id"]));
    $p_pm_href = $p_can_pm
        ? $home . "index.php?do=pm&doaction=newpm&username=" . $p_name_enc
        : "#";
    $p_pm_onclick = $p_can_pm
        ? "DLESendPM('" . addslashes($p["user_name"]) . "'); return false;"
        : "return false;";

    // BUL: Forumda bu kullanıcının gönderilerini bul (forum profil sayfası)
    $p_find_href = ($p_uid_val > 0)
        ? $home . "index.php?do=forum&action=profile&user=" . $p_name_enc
        : "#";

    $tpl->set("{p_uid}",        $p_uid_val);
    $tpl->set("{p_email_href}", $p_email_href);
    $tpl->set("{p_pm_href}",    $p_pm_href);
    $tpl->set("{p_pm_onclick}", $p_pm_onclick);
    $tpl->set("{p_find_href}",  $p_find_href);

    // Blok etiketleri
    $tpl->set_block("'\\[has-email\\](.*?)\\[/has-email\\]'si",  $p_has_email ? "\\1" : "");
    $tpl->set_block("'\\[no-email\\](.*?)\\[/no-email\\]'si",    $p_has_email ? ""    : "\\1");
    $tpl->set_block("'\\[can-pm\\](.*?)\\[/can-pm\\]'si",        $p_can_pm    ? "\\1" : "");
    $tpl->set_block("'\\[no-pm\\](.*?)\\[/no-pm\\]'si",          $p_can_pm    ? ""    : "\\1");
    $tpl->set_block("'\\[has-profile\\](.*?)\\[/has-profile\\]'si", ($p_uid_val > 0) ? "\\1" : "");

    // Rütbe bloğu — [p-has-rank] yalnızca rütbe varsa açılır
    if ($r_name !== "") {
        $tpl->set("[p-has-rank]",  "");
        $tpl->set("[/p-has-rank]", "");
    } else {
        $tpl->set_block("'\\[p-has-rank\\](.*?)\\[/p-has-rank\\]'si", "");
    }

    // Avatar (HTML post.tpl'de — sadece URL gönder) — avatar kabı post.tpl'de

    $avatar_url = forum_user_avatar_url($p["foto"] ?? "", 100);
    if ($avatar_url !== "") {
        $tpl->set(
            "{avatar}",
            '<img class="w-full h-full object-cover rounded" src="' .
                htmlspecialchars($avatar_url, ENT_QUOTES, "UTF-8") .
                '" alt="' .
                $p_name .
                '">',
        );
    } else {
        // Pastel tonlarda harika bir varsayılan profil kutusu (Inline style ile her temada kusursuz)
        $tpl->set("{avatar}", '<div style="width:100%; height:100%; background:#f0f9ff; display:flex; align-items:center; justify-center:center; display:-webkit-flex; -webkit-align-items:center; -webkit-justify-content:center; justify-content:center; border-radius:4px;"><i class="fa fa-user" style="font-size:42px; color:#93c5fd;"></i></div>');
    }

    // "Konu Sahibi" rozeti sadece 1. sayfa ve 1. mesajda (#1) görünür.
    if ($f_page == 1 && $idx == 0) {
        $tpl->set_block("'\\[owner\\](.*?)\\[/owner\\]'si", "\\1");
    } else {
        $tpl->set_block("'\\[owner\\](.*?)\\[/owner\\]'si", "");
    }

    // Düzenleme yetki kontrolü (Edit permission check)
    $can_edit = false;
    $is_post_owner = ($is_logged && intval($p['user_id']) === intval($member_id['user_id']));
    $is_mod_user = ($is_logged && $member_id['user_group'] <= 3); // Admin (1), Mod (2), Editör (3)
    
    if ($is_logged) {
        if ($is_mod_user) {
            $can_edit = true;
        } elseif ($is_post_owner) {
            if ($f_page == 1 && $idx == 0) {
                // Konuyu açan kişi kendi ana konusunu her zaman düzenleyebilir!
                $can_edit = true;
            } else {
                // Cevaplarını sadece ilk 15 dakikada düzenleyebilir!
                $post_time = strtotime($p['date']);
                $elapsed = (time() - $post_time) / 60;
                if ($elapsed <= 15) {
                    $can_edit = true;
                }
            }
        }
    }
    
    if ($can_edit) {
        $tpl->set_block("'\\[edit\\](.*?)\\[/edit\\]'si", "\\1");
    } else {
        $tpl->set_block("'\\[edit\\](.*?)\\[/edit\\]'si", "");
    }

    // Şikayet Et yetki kontrolü (Complaint permission check)
    if ($is_logged && $member_id['name'] != $p_name) {
        $tpl->set_block("'\\[complaint\\](.*?)\\[/complaint\\]'si", "\\1");
    } else {
        $tpl->set_block("'\\[complaint\\](.*?)\\[/complaint\\]'si", "");
    }

    $tpl->compile("forum_post_compiled");
    $post_html .= $tpl->result["forum_post_compiled"];
    $tpl->result["forum_post_compiled"] = "";
    
    // Eğer 1. sayfa ve 1. mesaj bittiyse etiketleri hemen ardından ekle
    if ($f_page == 1 && $idx == 0 && !empty($tags_html)) {
        $post_html .= $tags_html;
    }
}

// -------------------------------------------------
// 9.5 ANKET VE ETİKET SİSTEMİ (POLL & TAGS)
// -------------------------------------------------
$poll_html = "";
$poll = $db->super_query("SELECT * FROM " . PREFIX . "_forum_polls WHERE topic_id = '{$tid}'");
if ($poll && isset($poll['id'])) {
    $pid = intval($poll['id']);
    
    // Kullanıcı oy kullandı mı kontrolü
    $uid = intval($member_id['user_id']);
    $has_voted = 0;
    if ($uid > 0) {
        $vote_check = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_poll_votes WHERE poll_id = '{$pid}' AND user_id = '{$uid}'");
        $has_voted = intval($vote_check['cnt']) > 0 ? 1 : 0;
    } else {
        $has_voted = 1; // Misafirler varsayılan olarak oylayamaz
    }
    
    // Süre kontrolü
    $is_expired = 0;
    if ($poll['end_date'] && strtotime($poll['end_date']) < time()) {
        $is_expired = 1;
    }
    $is_closed = intval($poll['is_closed']) || $is_expired;
    
    // Oy verme işlemi
    if (isset($_POST['submit_poll_vote']) && !$has_voted && !$is_closed) {
        $selected_opts = $_POST['poll_options'] ?? [];
        if (!is_array($selected_opts)) {
            $selected_opts = [intval($selected_opts)];
        }
        
        $voted_cnt = 0;
        foreach ($selected_opts as $opt_id) {
            $opt_id = intval($opt_id);
            if ($opt_id > 0) {
                $db->query("INSERT IGNORE INTO " . PREFIX . "_forum_poll_votes (poll_id, option_id, user_id, date) 
                            VALUES ('{$pid}', '{$opt_id}', '{$uid}', NOW())");
                $db->query("UPDATE " . PREFIX . "_forum_poll_options SET votes = votes + 1 WHERE id = '{$opt_id}'");
                $voted_cnt++;
            }
        }
        if ($voted_cnt > 0) {
            $has_voted = 1;
            header("Location: " . $config['http_home_url'] . "forum/topic/" . $tid . "-" . $topic['alt_name'] . ".html");
            exit;
        }
    }
    
    // Seçenekleri çek
    $options = [];
    $total_votes = 0;
    $db->query("SELECT * FROM " . PREFIX . "_forum_poll_options WHERE poll_id = '{$pid}' ORDER BY posi ASC, id ASC");
    while ($opt_row = $db->get_row()) {
        $options[] = $opt_row;
        $total_votes += intval($opt_row['votes']);
    }
    
    // Anket Arayüzünü Oluştur (Premium MyBB Tasarımı)
    $poll_html .= '<div class="mybb-category-block mybb-poll-block">';
    $poll_html .= '  <div class="mybb-cat-header"><span class="mybb-cat-title"><i class="fa fa-bar-chart" style="margin-right:6px;"></i> ' . $lang['forum_topic_poll_label'] . ' ' . htmlspecialchars($poll['question'], ENT_QUOTES, "UTF-8") . '</span></div>';
    $poll_html .= '  <div class="mybb-poll-body">';
    
    $view_results = isset($_GET['view_poll_results']) ? 1 : 0;
    if ($has_voted || $is_closed || $view_results) {
        // Sonuçları Göster
        $poll_html .= '    <div class="mybb-poll-results">';
        $rank = 0;
        foreach ($options as $opt) {
            $rank++;
            $votes = intval($opt['votes']);
            $pct = $total_votes > 0 ? round(($votes / $total_votes) * 100) : 0;
            
            // En yüksek oy alan seçenek farklı renk alsın
            $bar_class = 'mybb-poll-bar-fill';
            if ($pct >= 50) $bar_class .= ' mybb-poll-bar-high';
            elseif ($pct >= 25) $bar_class .= ' mybb-poll-bar-mid';
            
            $poll_html .= '      <div class="mybb-poll-option">';
            $poll_html .= '        <div class="mybb-poll-option-head"><span class="mybb-poll-option-text">' . htmlspecialchars($opt['text'], ENT_QUOTES, "UTF-8") . '</span><span class="mybb-poll-option-stat">' . sprintf($lang['forum_topic_votes_pct'], $votes, $pct) . '</span></div>';
            $poll_html .= '        <div class="mybb-poll-bar"><div class="' . $bar_class . '" style="width:' . $pct . '%"></div></div>';
            $poll_html .= '      </div>';
        }
        $poll_html .= '      <div class="mybb-poll-footer"><i class="fa fa-info-circle"></i> ' . $lang['forum_topic_total_votes'] . ' <strong>' . $total_votes . '</strong>';
        if ($is_closed) {
            $poll_html .= ' <span class="mybb-poll-closed-badge">' . $lang['forum_topic_poll_closed'] . '</span>';
        }
        $poll_html .= '      </div>';
        $poll_html .= '    </div>';
    } else {
        // Oylama Formunu Göster
        $poll_html .= '    <form method="post" action="" class="mybb-poll-form">';
        $input_type = $poll['multiple'] ? 'checkbox' : 'radio';
        $max_lbl = $poll['multiple'] && $poll['max_choices'] > 1 ? ' <span class="mybb-poll-max-hint">(' . sprintf($lang['forum_topic_poll_max_choices'], intval($poll['max_choices'])) . ')</span>' : '';
        
        $poll_html .= '      <div class="mybb-poll-vote-hint">' . $lang['forum_topic_poll_vote_hint'] . $max_lbl . '</div>';
        $poll_html .= '      <div class="mybb-poll-options-list">';
        foreach ($options as $opt) {
            $poll_html .= '        <label class="mybb-poll-vote-label">';
            $poll_html .= '          <input type="' . $input_type . '" name="poll_options[]" value="' . $opt['id'] . '" class="mybb-poll-input">';
            $poll_html .= '          <span>' . htmlspecialchars($opt['text'], ENT_QUOTES, "UTF-8") . '</span>';
            $poll_html .= '        </label>';
        }
        $poll_html .= '      </div>';
        $poll_html .= '      <div class="mybb-poll-actions">';
        $poll_html .= '        <button type="submit" name="submit_poll_vote" class="mybb-btn-action mybb-poll-submit-btn"><i class="fa fa-check"></i> ' . $lang['forum_topic_poll_submit'] . '</button>';
        if ($is_logged) {
            $poll_html .= '        <a href="?view_poll_results=1" class="mybb-poll-results-link"><i class="fa fa-eye"></i> ' . $lang['forum_topic_poll_view_results'] . '</a>';
        } else {
            $poll_html .= '        <span class="mybb-poll-guest-hint">' . $lang['forum_topic_poll_login_required'] . '</span>';
        }
        $poll_html .= '      </div>';
        $poll_html .= '    </form>';
    }
    $poll_html .= '  </div>';
    $poll_html .= '</div>';
}

// -------------------------------------------------
// 10. ANA ŞABLONA BASMA (TOPIC.TPL)
// -------------------------------------------------
// Forum editorunu yukle (varsa)
$allow_post_reply = true;
if ($topic && !empty($topic['access'])) {
    $access = [];
    $rules = explode("||", $topic['access']);
    foreach ($rules as $rule) {
        $parts = explode(":", $rule);
        if (count($parts) == 2) {
            $access[intval($parts[0])] = intval($parts[1]);
        }
    }
    $user_group_id = intval($member_id['user_group']);
    if (isset($access[$user_group_id]) && $access[$user_group_id] == 1) {
        $allow_post_reply = false;
    }
}

if (!$allow_post_reply) {
    $wysiwyg = '<div class="alert alert-warning text-sm p-4 rounded-lg" style="background:#fffbeb; border:1px solid #fef3c7; color:#b45309;"><i class="fa fa-exclamation-triangle mr-2"></i> ' . $lang['forum_topic_reply_limited'] . '</div>';
} elseif ($forum_cfg["editor_enabled"]) {
    include_once DLEPlugins::Check(ENGINE_DIR . "/modules/forum/editor_helpers.php");
    $editor_type = forum_get_editor_type($forum_cfg, "reply");
    forum_register_editor_assets($editor_type, $js_array, $css_array, $lang);
    $f_editor_id = "forum-reply-textarea";
    $f_editor_height = 250;
    $f_editor_mode = "reply";
    $f_editor_context = "reply";
    include_once DLEPlugins::Check(ENGINE_DIR . "/editor/forum.php");
} else {
    $wysiwyg = '<textarea name="text" id="forum-reply-textarea" rows="4" class="w-full border border-gray-300 rounded-lg p-3 text-sm" placeholder="' . $lang['forum_topic_placeholder'] . '"></textarea>';
}

$tpl->load_template("forum/topic.tpl");
$tpl->set("{poll_block}", $poll_html);
$tpl->set("{tags_block}", "");

$tpl->set("{forum_breadcrumb}", $breadcrumb_html);
$tpl->set("{topic_title}",     $topic_title);
$tpl->set("{topic_id}",        $tid);
$tpl->set("{topic_views}",     intval($topic["views"]));
$tpl->set("{topic_replies}",   intval($topic["replies"]));
$tpl->set("{topic_date}",      $topic["date"]);
$tpl->set("{topic_is_locked}", $topic["is_locked"]);
$tpl->set("{topic_is_pinned}", $topic["is_pinned"]);
$tpl->set(
    "{pin_label}",
    $topic["is_pinned"] ? $lang['forum_topic_unpin'] : $lang['forum_topic_pin'],
);
$mod_visible = $member_id["user_group"] <= 2 ? "block" : "none";
$tpl->set("{mod_display}", $mod_visible);
$tpl->set("{lock_label}", $topic["is_locked"] ? $lang['forum_topic_unlock'] : $lang['forum_topic_lock']);

$allow_bump = $is_logged && ($member_id["user_group"] <= 2 || intval($topic["user_id"]) === intval($member_id["user_id"]));
if ($allow_bump) {
    $tpl->set("[allow-bump]", "");
    $tpl->set("[/allow-bump]", "");
} else {
    $tpl->set_block("'\\[allow-bump\\](.*?)\\[/allow-bump\\]'si", "");
}

// Sabit / Kilitli blokları — HTML tamamen topic.tpl'de
if ($topic_is_pinned) {
    $tpl->set("[topic-pinned]",  "");
    $tpl->set("[/topic-pinned]", "");
} else {
    $tpl->set_block("'\\[topic-pinned\\](.*?)\\[/topic-pinned\\]'si", "");
}
if ($topic_is_locked) {
    $tpl->set("[topic-locked]",  "");
    $tpl->set("[/topic-locked]", "");
} else {
    $tpl->set_block("'\\[topic-locked\\](.*?)\\[/topic-locked\\]'si", "");
}
$tpl->set("{author_name}", htmlspecialchars($author_name, ENT_QUOTES, "UTF-8"));
$tpl->set("{http_home_url}", $config["http_home_url"]);
$tpl->set("{dle_login_hash}", $dle_login_hash);
$tpl->set("{lang_code}", $lang["language_code"]);
$tpl->set("{lang_dir}", $lang["direction"]);

$tpl->set("{reply_editor}", $wysiwyg);

$tpl->set("{post_rows}", $post_html);

// Sayfalama
$pagination = "";
if ($total_pages > 1) {
    $pagination .= '<div class="flex justify-center gap-1 py-4">';
    $base =
        $config["http_home_url"] .
        "forum/topic/" .
        $tid .
        "-" .
        $topic["alt_name"] .
        "/page/";
    for ($i = 1; $i <= $total_pages; $i++) {
        $active =
            $i == $f_page
                ? "bg-blue-500 text-white"
                : "bg-white text-gray-700 hover:bg-gray-100";
        $pagination .=
            '<a href="' .
            $base .
            $i .
            '/" class="px-3 py-1.5 text-sm rounded border ' .
            $active .
            '">' .
            $i .
            "</a>";
    }
    $pagination .= "</div>";
}
$tpl->set("{pagination}", $pagination);

$tpl->compile("content");
$tpl->clear();
?>
