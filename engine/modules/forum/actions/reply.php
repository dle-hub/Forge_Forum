<?php
/*
=====================================================
 Forge Forum Engine — Cevap Yazma
-----------------------------------------------------
 File: engine/modules/forum/actions/reply.php
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
    msgbox($lang['forum_access_denied'], $lang['forum_reply_login_required']);
    return;
}

// -------------------------------------------------
// 2. KONUYU BUL VE KONTROL ET
// -------------------------------------------------
$tid = $f_topic_id;
if (!$tid && isset($_POST["topic_id"])) {
    $tid = intval($_POST["topic_id"]);
}
if (!$tid) {
    msgbox($lang['forum_err_title'], $lang['forum_reply_invalid_topic']);
    return;
}

$topic = $db->super_query(
    "SELECT * FROM " . PREFIX . "_forum_topics WHERE id = '{$tid}'",
);
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
        if ($access[$user_group_id] == 1) {
            msgbox($lang['forum_access_denied'], $lang['forum_reply_no_perm_read']);
            return;
        } elseif ($access[$user_group_id] == 3) {
            msgbox($lang['forum_access_denied'], $lang['forum_reply_no_perm_view']);
            return;
        }
    }
}

if ($topic["is_deleted"]) {
    msgbox($lang['forum_err_title'], $lang['forum_reply_topic_deleted']);
    return;
}
if ($topic["is_locked"] && $member_id["user_group"] > 2) {
    msgbox($lang['forum_err_title'], $lang['forum_reply_topic_locked']);
    return;
}
if (!$topic["is_approved"] && $member_id["user_group"] > 2) {
    msgbox($lang['forum_err_title'], $lang['forum_reply_topic_unapproved']);
    return;
}

$cid = intval($topic["cat_id"]);

// -------------------------------------------------
// 3. POST İŞLEMİ
// -------------------------------------------------
if (isset($_POST["submit_reply"])) {
    if ($_POST["user_hash"] !== $dle_login_hash) {
        msgbox($lang['forum_err_title'], $lang['forum_edit_csrf']);
        return;
    }

    $post_text = trim($_POST["text"]);
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

    // Min/max uzunluk
    $min_len = intval($forum_cfg["min_post_length"]);
    $max_len = intval($forum_cfg["max_post_length"]);
    if ($min_len > 0 && mb_strlen($post_text) < $min_len) {
        msgbox($lang['forum_err_title'], sprintf($lang['forum_reply_min_len'], $min_len));
        return;
    }
    if ($max_len > 0 && mb_strlen($post_text) > $max_len) {
        msgbox($lang['forum_err_title'], sprintf($lang['forum_reply_max_len'], $max_len));
        return;
    }

    // HTMLPurifier ile guvenlik temizligi (editor aciksa)
    if ($forum_cfg["editor_enabled"]) {
        if (!class_exists("HTMLPurifier_Config")) {
            require_once ENGINE_DIR .
                "/classes/htmlpurifier/HTMLPurifier.standalone.php";
        }
        $purify_config = HTMLPurifier_Config::createDefault();
        $purify_config->set("Core.Encoding", "UTF-8");
        $purify_config->set(
            "HTML.Allowed",
            "p,b,i,u,s,strong,em,span[style],div[style],br,ul,ol,li,a[href|target|rel],img[src|alt|width|height|style],pre,code,blockquote,hr,sub,sup",
        );
        $purify_config->set(
            "CSS.AllowedProperties",
            "color,background-color,font-size,font-weight,font-style,text-align,text-decoration,margin,padding,border,width,max-width,height,float",
        );
        $purifier = new HTMLPurifier($purify_config);
        $post_text = $purifier->purify($post_text);
    } else {
        $post_text = strip_tags($post_text);
    }

    $safe_text = $db->safesql($post_text);
    $uid = intval($member_id["user_id"]);
    $ip = $db->safesql($_SERVER["REMOTE_ADDR"]);
    $now = date("Y-m-d H:i:s");
    $approved = $forum_cfg["require_approval"] ? 0 : 1;

    // ZİNCİR: 1. Mesajı ekle
    $db->query(
        "INSERT INTO " .
            PREFIX .
            "_forum_posts
        (topic_id, user_id, text, is_approved, ip, date)
        VALUES ('{$tid}','{$uid}','{$safe_text}','{$approved}','{$ip}','{$now}')",
    );

    $new_post_id = $db->insert_id();
    if (!$new_post_id) {
        msgbox($lang['forum_err_title'], $lang['forum_reply_failed']);
        return;
    }

    // Link uploads made during reply creation to the newly created post
    $db->query("UPDATE " . PREFIX . "_forum_uploads SET post_id = '{$new_post_id}' WHERE user_id = '{$uid}' AND post_id = 0 AND date >= NOW() - INTERVAL 2 HOUR");

    // Mention etiketlerini işle ve bildirim gönder
    forum_parse_mentions($post_text, $tid, $new_post_id, $uid);

    // 2. Konu istatistiklerini güncelle
    $replies = $db->super_query(
        "SELECT COUNT(*) AS cnt FROM " .
            PREFIX .
            "_forum_posts
        WHERE topic_id = '{$tid}' AND is_deleted = 0 AND is_approved = 1",
    );
    $replies = max(0, intval($replies["cnt"]) - 1);

    $db->query(
        "UPDATE " .
            PREFIX .
            "_forum_topics SET
        replies = '{$replies}',
        last_post_id = '{$new_post_id}',
        last_user_id = '{$uid}',
        last_post_date = '{$now}',
        last_bump_date = '{$now}'
        WHERE id = '{$tid}'",
    );

    // 3. Kullanıcı sayacı
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
    $cat_post_cnt = $db->super_query(
        "SELECT COUNT(*) AS cnt FROM " .
            PREFIX .
            "_forum_posts p
        JOIN " .
            PREFIX .
            "_forum_topics t ON t.id = p.topic_id
        WHERE t.cat_id = '{$cid}' AND p.is_deleted = 0 AND p.is_approved = 1",
    );
    $db->query(
        "UPDATE " .
            PREFIX .
            "_forum_cats SET
        post_count = '" .
            intval($cat_post_cnt["cnt"]) .
            "',
        last_post_id = '{$new_post_id}'
        WHERE id = '{$cid}'",
    );

    // 5. Abonelere bildirim (varsa)
    if ($forum_cfg["enable_notifications"]) {
        $subs = [];
        $db->query(
            "SELECT user_id FROM " .
                PREFIX .
                "_forum_subs
            WHERE topic_id = '{$tid}' AND user_id != '{$uid}'",
        );
        while ($s = $db->get_row()) {
            $db->query(
                "INSERT INTO " .
                    PREFIX .
                    "_forum_notifs
                (user_id, from_user, type, topic_id, post_id, date)
                VALUES ('{$s["user_id"]}','{$uid}','reply','{$tid}','{$new_post_id}',NOW())",
            );
        }
    }

    // 6. Konuyu açana bildirim
    if ($forum_cfg["enable_notifications"] && $topic["user_id"] != $uid) {
        $db->query(
            "INSERT INTO " .
                PREFIX .
                "_forum_notifs
            (user_id, from_user, type, topic_id, post_id, date)
            VALUES ('{$topic["user_id"]}','{$uid}','reply','{$tid}','{$new_post_id}',NOW())",
        );
    }

    // Yönlendir: son sayfa + anchor
    $total_posts = $db->super_query(
        "SELECT COUNT(*) AS cnt FROM " .
            PREFIX .
            "_forum_posts
        WHERE topic_id = '{$tid}' AND is_deleted = 0 AND is_approved = 1",
    );
    $total_posts = intval($total_posts["cnt"]);
    $perpage = intval($forum_cfg["posts_per_page"]);
    $last_page = ceil($total_posts / $perpage);

    if ($config["allow_alt_url"]) {
        if ($last_page > 1) {
            $redirect =
                $config["http_home_url"] .
                "forum/topic/" .
                $tid .
                "-" .
                $topic["alt_name"] .
                "/page/" .
                $last_page .
                "/";
        } else {
            $redirect =
                $config["http_home_url"] .
                "forum/topic/" .
                $tid .
                "-" .
                $topic["alt_name"] .
                ".html";
        }
    } else {
        $redirect =
            $config["http_home_url"] .
            "index.php?do=forum&action=topic&id=" .
            $tid;
        if ($last_page > 1) {
            $redirect .= "&page=" . $last_page;
        }
    }

    $redirect .= "#post-" . $new_post_id;

    header("Location: {$redirect}");
    exit();
}

// -------------------------------------------------
// 4. GET ise konu sayfasına yönlendir (form topic.tpl'de)
// -------------------------------------------------
if ($config["allow_alt_url"]) {
    $redirect =
        $config["http_home_url"] .
        "forum/topic/" .
        $tid .
        "-" .
        $topic["alt_name"] .
        ".html";
} else {
    $redirect =
        $config["http_home_url"] . "index.php?do=forum&action=topic&id=" . $tid;
}

header("Location: {$redirect}");
exit();
?>
