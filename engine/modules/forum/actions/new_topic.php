<?php
/*
=====================================================
 Forge Forum Engine — Yeni Konu Açma
-----------------------------------------------------
 File: engine/modules/forum/actions/new_topic.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

// -------------------------------------------------
// 1. YETKİ KONTROLÜ
// -------------------------------------------------
if (!$is_logged) {
    msgbox($lang['forum_access_denied'], $lang['forum_new_login_required']);
    return;
}
if (!$forum_cfg["allow_guest_post"] && !$is_logged) {
    msgbox($lang['forum_access_denied'], $lang['forum_new_guest_no_post']);
    return;
}

// -------------------------------------------------
// 2. KATEGORİYİ BUL
// -------------------------------------------------
$cat_id = 0;
$cat_row = null;
if (!empty($f_cat)) {
    $cat_row = $db->super_query(
        "SELECT * FROM " . PREFIX . "_forum_cats WHERE alt_name = '{$f_cat}'",
    );
    $cat_id = $cat_row["id"] ? intval($cat_row["id"]) : 0;
}
if (isset($_POST["cat_id"])) {
    $cat_id = intval($_POST["cat_id"]);
    $cat_row = $db->super_query(
        "SELECT * FROM " . PREFIX . "_forum_cats WHERE id = '{$cat_id}'",
    );
}

// Kategorileri listele (form için - Alt forum ağacı dahil)
$categories = [];
$db->query(
    "SELECT id, name, alt_name, permissions, parent_id FROM " .
        PREFIX .
        "_forum_cats ORDER BY parent_id ASC, posi ASC",
);
while ($row = $db->get_row()) {
    $categories[] = $row;
}

// -------------------------------------------------
// 3. POST İŞLEMİ
// -------------------------------------------------
if (isset($_POST["submit_topic"])) {
    if ($_POST["user_hash"] !== $dle_login_hash) {
        msgbox($lang['forum_err_title'], $lang['forum_edit_csrf']);
        return;
    }

    $post_cat_id = intval($_POST["cat_id"]);
    $post_title = trim($_POST["title"]);
    $post_text = trim($_POST["text"]);

    if (!$post_cat_id) {
        msgbox($lang['forum_err_title'], $lang['forum_edit_invalid_cat']);
        return;
    }
    if (empty($post_title)) {
        msgbox($lang['forum_err_title'], $lang['forum_edit_empty_title']);
        return;
    }
    if (empty($post_text)) {
        msgbox($lang['forum_err_title'], $lang['forum_edit_empty_text']);
        return;
    }

    // Flood kontrolü
    $flood_sec = intval($forum_cfg["flood_control_sec"]);
    if ($flood_sec > 0) {
        $last = $db->super_query(
            "SELECT date FROM " .
                PREFIX .
                "_forum_posts
            WHERE user_id = '{$member_id["user_id"]}' ORDER BY date DESC LIMIT 1",
        );
        if ($last["date"]) {
            $elapsed = time() - strtotime($last["date"]);
            if ($elapsed < $flood_sec) {
                $wait = $flood_sec - $elapsed;
                msgbox(
                    $lang['forum_err_title'],
                    sprintf($lang['forum_flood_wait'], $wait),
                );
                return;
            }
        }
    }

    // Kategori yetki kontrolü
    $sel_cat = $db->super_query(
        "SELECT permissions FROM " .
            PREFIX .
            "_forum_cats WHERE id = '{$post_cat_id}'",
    );
    if ($sel_cat["permissions"] != "all") {
        $allowed = explode(",", $sel_cat["permissions"]);
        if (!in_array($member_id["user_group"], $allowed)) {
            msgbox(
                $lang['forum_access_denied'],
                $lang['forum_new_no_perm'],
            );
            return;
        }
    }

    // Güvenlik filtreleme
    if ($forum_cfg["editor_enabled"]) {
        // TinyMCE HTML icerigini HTMLPurifier ile temizle
        if (!class_exists("HTMLPurifier_Config")) {
            require_once ENGINE_DIR .
                "/classes/htmlpurifier/HTMLPurifier.standalone.php";
        }
        $purify_config = HTMLPurifier_Config::createDefault();
        $purify_config->set("Core.Encoding", "UTF-8");
        $purify_config->set(
            "HTML.Allowed",
            "p,b,i,u,s,strong,em,span[style],div[style],br,ul,ol,li,a[href|target|rel],img[src|alt|width|height|style],pre,code,blockquote,h1,h2,h3,h4,h5,h6,hr,table[style],tr,td[style],th[style],sub,sup",
        );
        $purify_config->set(
            "CSS.AllowedProperties",
            "color,background-color,font-size,font-weight,font-style,text-align,text-decoration,margin,padding,border,width,max-width,height,float",
        );
        $purifier = new HTMLPurifier($purify_config);
        $post_text = $purifier->purify($post_text);
    } else {
        // BBCode modu - BBCode etiketlerini temizle
        $post_text = strip_tags($post_text);
    }
    $prefix_id  = intval($_POST['prefix_id']);
    $safe_title = $db->safesql($post_title);
    $safe_text = $db->safesql($post_text);
    $alt_name = $db->safesql(totranslit($post_title));
    $uid = intval($member_id["user_id"]);
    $ip = $db->safesql($_SERVER["REMOTE_ADDR"]);
    $now = date("Y-m-d H:i:s");
    $approved = $forum_cfg["require_approval"] ? 0 : 1;

    // ZİNCİR: 1. Konuyu ekle (including prefix_id)
    $db->query(
        "INSERT INTO " .
            PREFIX .
            "_forum_topics
        (cat_id, prefix_id, title, alt_name, user_id, is_approved, date, last_bump_date)
        VALUES ('{$post_cat_id}','{$prefix_id}','{$safe_title}','{$alt_name}','{$uid}','{$approved}','{$now}','{$now}')",
    );

    $new_topic_id = $db->insert_id();
    if (!$new_topic_id) {
        msgbox($lang['forum_err_title'], $lang['forum_new_create_failed']);
        return;
    }

    // 2. İlk mesajı ekle
    $db->query(
        "INSERT INTO " .
            PREFIX .
            "_forum_posts
        (topic_id, user_id, text, is_approved, ip, date)
        VALUES ('{$new_topic_id}','{$uid}','{$safe_text}','{$approved}','{$ip}','{$now}')",
    );

    $new_post_id = $db->insert_id();

    // Link uploads made during topic creation to the newly created post
    forum_link_pending_uploads($new_post_id, $uid);

    // Mention etiketlerini işle ve bildirim gönder
    forum_parse_mentions($post_text, $new_topic_id, $new_post_id, $uid);

    // 2.1 EKLENTİ: Konu Etiketlerini Kaydet
    $tags_str = trim($_POST['tags']);
    if (!empty($tags_str)) {
        $tags_arr = explode(',', $tags_str);
        foreach ($tags_arr as $tname) {
            $tname = trim($tname);
            if (empty($tname)) continue;
            $talt = $db->safesql(totranslit($tname));
            $tname_safe = $db->safesql($tname);
            
            // Etiketi ekle veya güncelle
            $db->query("INSERT INTO " . PREFIX . "_forum_tags (name, alt_name, topic_count) 
                        VALUES ('{$tname_safe}', '{$talt}', 1) 
                        ON DUPLICATE KEY UPDATE topic_count = topic_count + 1");
            
            $tag_id = $db->insert_id();
            if (!$tag_id) {
                $existing_tag = $db->super_query("SELECT id FROM " . PREFIX . "_forum_tags WHERE alt_name = '{$talt}'");
                $tag_id = intval($existing_tag['id']);
            }
            
            if ($tag_id > 0) {
                $db->query("INSERT IGNORE INTO " . PREFIX . "_forum_topic_tags (tag_id, topic_id) 
                            VALUES ('{$tag_id}', '{$new_topic_id}')");
            }
        }
    }

    // 2.2 EKLENTİ: Konu Anketini Kaydet
    $poll_question = trim($_POST['poll_question']);
    $poll_options_str = trim($_POST['poll_options']);
    if (!empty($poll_question) && !empty($poll_options_str)) {
        $poll_multiple = isset($_POST['poll_multiple']) ? 1 : 0;
        $poll_max_choices = intval($_POST['poll_max_choices']);
        $poll_days = intval($_POST['poll_days']);
        $end_date = "NULL";
        if ($poll_days > 0) {
            $end_date = "'" . date('Y-m-d H:i:s', time() + $poll_days * 86400) . "'";
        }
        
        $safe_question = $db->safesql($poll_question);
        
        $db->query("INSERT INTO " . PREFIX . "_forum_polls (topic_id, question, multiple, max_choices, end_date) 
                    VALUES ('{$new_topic_id}', '{$safe_question}', '{$poll_multiple}', '{$poll_max_choices}', {$end_date})");
        $poll_id = $db->insert_id();
        
        if ($poll_id) {
            $options_arr = explode("\n", $poll_options_str);
            $posi = 0;
            foreach ($options_arr as $opt) {
                $opt = trim($opt);
                if (empty($opt)) continue;
                $safe_opt = $db->safesql($opt);
                $db->query("INSERT INTO " . PREFIX . "_forum_poll_options (poll_id, text, votes, posi) 
                            VALUES ('{$poll_id}', '{$safe_opt}', 0, '{$posi}')");
                $posi++;
            }
        }
    }

    // 3. Kullanıcı sayaçları
    $post_cnt = $db->super_query(
        "SELECT COUNT(*) AS cnt FROM " .
            PREFIX .
            "_forum_posts
        WHERE user_id = '{$uid}' AND is_deleted = 0 AND is_approved = 1",
    );
    $topic_cnt = $db->super_query(
        "SELECT COUNT(*) AS cnt FROM " .
            PREFIX .
            "_forum_topics
        WHERE user_id = '{$uid}' AND is_deleted = 0 AND is_approved = 1",
    );
    $pts =
        intval($topic_cnt["cnt"]) * intval($forum_cfg["points_per_topic"]) +
        intval($post_cnt["cnt"]) * intval($forum_cfg["points_per_post"]);

    $db->query(
        "UPDATE " .
            PREFIX .
            "_users SET
        forum_post_count = '" .
            intval($post_cnt["cnt"]) .
            "',
        forum_points = '{$pts}'
        WHERE user_id = '{$uid}'",
    );
    forum_sync_user_rank($uid, $pts);

    // 4. Kategori istatistikleri
    $cat_topic_cnt = $db->super_query(
        "SELECT COUNT(*) AS cnt FROM " .
            PREFIX .
            "_forum_topics
        WHERE cat_id = '{$post_cat_id}' AND is_deleted = 0 AND is_approved = 1",
    );
    $cat_post_cnt = $db->super_query(
        "SELECT COUNT(*) AS cnt FROM " .
            PREFIX .
            "_forum_posts p
        JOIN " .
            PREFIX .
            "_forum_topics t ON t.id = p.topic_id
        WHERE t.cat_id = '{$post_cat_id}' AND p.is_deleted = 0 AND p.is_approved = 1",
    );

    $db->query(
        "UPDATE " .
            PREFIX .
            "_forum_cats SET
        topic_count = '" .
            intval($cat_topic_cnt["cnt"]) .
            "',
        post_count  = '" .
            intval($cat_post_cnt["cnt"]) .
            "',
        last_post_id = '{$new_post_id}',
        last_topic_id = '{$new_topic_id}'
        WHERE id = '{$post_cat_id}'",
    );

    // 5. Konuya ait son mesaj bilgisi
    $db->query(
        "UPDATE " .
            PREFIX .
            "_forum_topics SET
        last_post_id = '{$new_post_id}',
        last_user_id = '{$uid}',
        last_post_date = '{$now}'
        WHERE id = '{$new_topic_id}'",
    );

    // Başarılı → yönlendir
    $redirect_url =
        $config["http_home_url"] .
        "forum/topic/" .
        $new_topic_id .
        "-" .
        $alt_name .
        ".html";
    header("Location: {$redirect_url}");
    exit();
}

// -------------------------------------------------
// 4. FORMU GÖSTER
// -------------------------------------------------
$cat_name = $cat_row ? stripslashes($cat_row["name"]) : "";
$metatags["title"] = sprintf($lang['forum_new_title'], $forum_title);

$forum_speedbar[""] = $lang['forum_new_breadcrumb'];

if ($forum_cfg["editor_enabled"]) {
    include_once DLEPlugins::Check(ENGINE_DIR . "/modules/forum/editor_helpers.php");
    forum_register_editor_assets("tinymce", $js_array, $css_array, $lang);
    $f_editor_id = "forum-editor";
    $f_editor_height = 350;
    $f_editor_mode = "full";
    $f_editor_context = "topic";
    include_once DLEPlugins::Check(ENGINE_DIR . "/editor/forum.php");
    $tpl->set("{forum_editor}", $wysiwyg);
} else {
    $tpl->set(
        "{forum_editor}",
        '<textarea name="text" id="forum-editor" rows="10" class="w-full border border-gray-300 rounded-lg p-3 text-sm" placeholder="' . $lang['forum_new_placeholder'] . '"></textarea>',
    );
}

$tpl->load_template("forum/new_topic.tpl");
$tpl->set("{cat_id}", $cat_id);
$tpl->set("{cat_name}", htmlspecialchars($cat_name, ENT_QUOTES, "UTF-8"));
$tpl->set("{http_home_url}", $config["http_home_url"]);
$tpl->set("{dle_login_hash}", $dle_login_hash);
$tpl->set("{lang_code}", $lang["language_code"]);
$tpl->set("{lang_dir}", $lang["direction"]);

// Kategori dropdown (Tüm kategori ve alt forum ağacını hiyerarşik şekilde ekliyoruz)
$cat_tree = [];
foreach ($categories as $cat) {
    $cat_tree[$cat['parent_id']][] = $cat;
}

$cat_opts = '<option value="">' . $lang['forum_edit_cat_select'] . '</option>';
if (isset($cat_tree[0])) {
    foreach ($cat_tree[0] as $parent) {
        $p_name = htmlspecialchars(stripslashes($parent["name"]), ENT_QUOTES, "UTF-8");
        $selected = ($cat_id && $parent["id"] == $cat_id) ? " selected" : "";
        $cat_opts .= '<option value="' . $parent["id"] . '"' . $selected . ' style="font-weight: bold;">' . $p_name . "</option>";
        
        if (isset($cat_tree[$parent["id"]])) {
            foreach ($cat_tree[$parent["id"]] as $sub) {
                $sub_name = htmlspecialchars(stripslashes($sub["name"]), ENT_QUOTES, "UTF-8");
                $selected_sub = ($cat_id && $sub["id"] == $cat_id) ? " selected" : "";
                $cat_opts .= '<option value="' . $sub["id"] . '"' . $selected_sub . '>&nbsp;&nbsp;&nbsp;&nbsp;— ' . $sub_name . "</option>";
            }
        }
    }
}
$tpl->set(
    "{category_selector}",
    '<select name="cat_id" class="mybb-select" required>' .
        $cat_opts .
        "</select>",
);

// Konu Önekleri (Prefix Selector)
$prefix_opts = '<option value="0">' . $lang['forum_edit_pfx_select'] . '</option>';
$db->query("SELECT id, name FROM " . PREFIX . "_forum_prefixes ORDER BY posi ASC, id ASC");
while ($prow = $db->get_row()) {
    $prefix_opts .= '<option value="' . $prow['id'] . '">' . htmlspecialchars($prow['name'], ENT_QUOTES, "UTF-8") . '</option>';
}
$tpl->set(
    "{prefix_selector}",
    '<select name="prefix_id" class="mybb-select" style="max-width: 150px; margin-right: 10px;">' .
        $prefix_opts .
        "</select>",
);

$tpl->compile("content");
$tpl->clear();
