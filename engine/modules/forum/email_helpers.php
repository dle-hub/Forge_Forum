<?php
/*
=====================================================
 Forge Forum Engine — E-posta Bildirim Yardımcıları
-----------------------------------------------------
 File: engine/modules/forum/email_helpers.php
 Use: Abonelere DLE mail API'si (dle_mail) ile
      forum bildirim e-postası gönderir.
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

// forum_topic_post_url / forum_plain_text_snippet için (init.php zaten dahil eder;
// AJAX bağlamında çağrılırsa güvence altına al).
if (!function_exists("forum_topic_post_url")) {
    include_once DLEPlugins::Check(ENGINE_DIR . "/modules/forum/editor_helpers.php");
}

if (!function_exists("forum_default_email_template")) {
    /**
     * Kurulumda _email tablosuna yazılacak varsayılan şablon.
     * Desteklenen yer tutucular: {$url}, {%username_to%}, {%username%},
     * {%title%}, {%date%}, {%link%}, {%text%}, {%unsubscribe%}
     */
    function forum_default_email_template()
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum bildirimi</title>
</head>

<body style="margin:0; padding:0; background-color:#f0f0f0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; font-size:14px; color:#222f3e;">
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-family: system-ui, sans-serif; font-size:14px;">
        <tr>
            <td align="center" style="padding:20px; font-family: system-ui, sans-serif; font-size:14px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: separate; border-spacing: 0; max-width:640px; background-color:#ffffff; border-radius:10px; box-shadow:0 16px 16px -10px rgba(34,47,62,.15),0 0 40px 1px rgba(34,47,62,.10); font-family: system-ui, sans-serif; font-size:14px;">
                    <tr>
                        <td style="background-color:#4C6891; color:#ffffff; padding-left:20px; padding-right:20px; padding-top:12px; padding-bottom:12px; vertical-align:middle; border-top-left-radius:6px; border-top-right-radius:6px; font-family: system-ui, sans-serif; font-size:15px; text-shadow:1px 1px 2px rgba(51, 51, 51, .5);">
                            {$url} &mdash; Forum bildirimi
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff; color:#000000; text-align:left; padding:20px; border-bottom-left-radius:6px; border-bottom-right-radius:6px; font-family: system-ui, sans-serif; font-size:14px; line-height:1.6;">
                            Sayın {%username_to%},<br><br>
                            Abone olduğunuz <b>{%title%}</b> konusuna yeni bir cevap yazıldı.<br><br>
                            -------------------------------------------------<br>
                            Cevaba ilişkin kısa bilgi<br>
                            -------------------------------------------------<br><br>
                            Yazar: {%username%}<br>
                            Eklenme tarihi: {%date%}<br>
                            Konu bağlantısı: <a href="{%link%}" target="_blank" style="color:#4C6891; text-decoration:none;">{%link%}</a><br><br>
                            -------------------------------------------------<br>
                            Mesaj<br>
                            -------------------------------------------------<br><br>
                            <div style="background-color:#f6f8fa; border-left:3px solid #4C6891; padding:10px 14px;">{%text%}</div><br>
                            -------------------------------------------------<br><br>
                            Bu konuya ilişkin yeni cevap bildirimlerini artık almak istemiyorsanız <a href="{%unsubscribe%}" target="_blank" style="color:#4C6891; text-decoration:none;">aboneliklerinizi yönetin</a>.<br><br>
                            Saygılarımızla,<br>{$url} site yönetimi
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
HTML;
    }
}

if (!function_exists("forum_default_cat_email_template")) {
    /**
     * Kategori abonelerine "yeni konu açıldı" bildirimi için varsayılan e-posta şablonu.
     * Yer tutucular: {$url}, {%username_to%}, {%username%}, {%category%},
     * {%title%}, {%date%}, {%link%}, {%text%}, {%unsubscribe%}
     */
    function forum_default_cat_email_template()
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum bildirimi</title>
</head>

<body style="margin:0; padding:0; background-color:#f0f0f0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; font-size:14px; color:#222f3e;">
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-family: system-ui, sans-serif; font-size:14px;">
        <tr>
            <td align="center" style="padding:20px; font-family: system-ui, sans-serif; font-size:14px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: separate; border-spacing: 0; max-width:640px; background-color:#ffffff; border-radius:10px; box-shadow:0 16px 16px -10px rgba(34,47,62,.15),0 0 40px 1px rgba(34,47,62,.10); font-family: system-ui, sans-serif; font-size:14px;">
                    <tr>
                        <td style="background-color:#4C6891; color:#ffffff; padding-left:20px; padding-right:20px; padding-top:12px; padding-bottom:12px; vertical-align:middle; border-top-left-radius:6px; border-top-right-radius:6px; font-family: system-ui, sans-serif; font-size:15px; text-shadow:1px 1px 2px rgba(51, 51, 51, .5);">
                            {$url} &mdash; Forum bildirimi
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff; color:#000000; text-align:left; padding:20px; border-bottom-left-radius:6px; border-bottom-right-radius:6px; font-family: system-ui, sans-serif; font-size:14px; line-height:1.6;">
                            Sayın {%username_to%},<br><br>
                            Abone olduğunuz <b>{%category%}</b> kategorisinde yeni bir konu açıldı.<br><br>
                            -------------------------------------------------<br>
                            Konuya ilişkin kısa bilgi<br>
                            -------------------------------------------------<br><br>
                            Konu: {%title%}<br>
                            Açan: {%username%}<br>
                            Açılış tarihi: {%date%}<br>
                            Konu bağlantısı: <a href="{%link%}" target="_blank" style="color:#4C6891; text-decoration:none;">{%link%}</a><br><br>
                            -------------------------------------------------<br>
                            Mesaj
                            -------------------------------------------------<br><br>
                            <div style="background-color:#f6f8fa; border-left:3px solid #4C6891; padding:10px 14px;">{%text%}</div><br>
                            -------------------------------------------------<br><br>
                            Bu kategorideki yeni konu bildirimlerini artık almak istemiyorsanız <a href="{%unsubscribe%}" target="_blank" style="color:#4C6891; text-decoration:none;">aboneliklerinizi yönetin</a>.<br><br>
                            Saygılarımızla,<br>{$url} site yönetimi
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
HTML;
    }
}

if (!function_exists("forum_email_template_row")) {
    /**
     * _email tablosundaki şablonu (bir kez) okur.
     * Kayıt yoksa varsayılan şablona düşer. Varsayılan ad: 'forum'.
     */
    function forum_email_template_row($name = "forum")
    {
        global $db;
        static $cache = [];

        $name = (string) $name;
        if ($name === "") {
            $name = "forum";
        }
        if (isset($cache[$name])) {
            return $cache[$name];
        }

        $r = $db->super_query(
            "SELECT template, use_html FROM " .
                PREFIX .
                "_email WHERE name='" .
                $db->safesql($name) .
                "' LIMIT 0,1",
        );
        if ($r && isset($r["template"]) && trim((string) $r["template"]) !== "") {
            $row = [
                "template" => stripslashes((string) $r["template"]),
                "use_html" => intval($r["use_html"]),
            ];
        } else {
            $default_fn = $name === "forum_cat" ? "forum_default_cat_email_template" : "forum_default_email_template";
            $row = [
                "template" => function_exists($default_fn) ? $default_fn() : forum_default_email_template(),
                "use_html" => 1,
            ];
        }

        $cache[$name] = $row;
        return $row;
    }
}

if (!function_exists("forum_site_url")) {
    /**
     * Yönlendirme/şablon için tam site adresini döndürür.
     */
    function forum_site_url()
    {
        global $config;

        $home = (string) ($config["http_home_url"] ?? "");
        $ssl = function_exists("isSSL") && isSSL();

        if (strpos($home, "//") === 0) {
            $home = ($ssl ? "https:" : "http:") . $home;
        } elseif (strpos($home, "/") === 0) {
            $home = ($ssl ? "https://" : "http://") . ($_SERVER["HTTP_HOST"] ?? "") . $home;
        }

        return $home;
    }
}

if (!function_exists("forum_send_forum_email")) {
    /**
     * Tek bir alıcıya forum bildirim e-postası gönderir.
     */
    function forum_send_forum_email($to_email, $to_name, $subject, $vars = [], $template_name = "forum")
    {
        global $config;

        $to_email = trim((string) $to_email);
        if ($to_email === "" || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (!class_exists("dle_mail")) {
            return false;
        }

        $tpl = forum_email_template_row($template_name);
        $body = (string) $tpl["template"];

        $body = str_replace('{$url}', forum_site_url(), $body);
        $body = str_replace(
            "{%username_to%}",
            htmlspecialchars((string) $to_name, ENT_QUOTES, "UTF-8"),
            $body,
        );

        if (!empty($vars)) {
            foreach ($vars as $key => $value) {
                $body = str_replace("{%" . $key . "%}", (string) $value, $body);
            }
        }

        try {
            $mail = new dle_mail($config, !empty($tpl["use_html"]));
            $mail->send($to_email, (string) $subject, $body);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists("forum_notify_reply_email")) {
    /**
     * Bir konuya yeni cevap yazıldığında abonelere ve konu yazarına
     * e-posta bildirimi gönderir (gönderen hariç, tekilleştirilmiş).
     */
    function forum_notify_reply_email($topic, $post_id, $poster_uid, $post_text)
    {
        global $db, $forum_cfg, $lang;

        if (empty($forum_cfg["enable_email_notify"])) {
            return 0;
        }
        if (empty($topic) || empty($topic["id"])) {
            return 0;
        }

        $tid = intval($topic["id"]);
        $post_id = intval($post_id);
        $poster_uid = intval($poster_uid);

        // Alıcı listesi: aboneler + konu yazarı
        $recipients = [];
        $sent = 0;

        $db->query(
            "SELECT u.user_id, u.name, u.email
             FROM " . PREFIX . "_forum_subs s
             INNER JOIN " . USERPREFIX . "_users u ON u.user_id = s.user_id
             WHERE s.topic_id = '{$tid}' AND s.user_id != '{$poster_uid}'",
        );
        while ($r = $db->get_row()) {
            $uid = intval($r["user_id"]);
            if ($uid > 0 && !empty($r["email"])) {
                $recipients[$uid] = $r;
            }
        }

        $author_id = intval($topic["user_id"]);
        if ($author_id > 0 && $author_id != $poster_uid && !isset($recipients[$author_id])) {
            $author = $db->super_query(
                "SELECT user_id, name, email FROM " . USERPREFIX . "_users WHERE user_id = '{$author_id}'",
            );
            if ($author && !empty($author["email"])) {
                $recipients[$author_id] = $author;
            }
        }

        if (empty($recipients)) {
            return 0;
        }

        // Gönderen adı
        $poster = $db->super_query(
            "SELECT name FROM " . USERPREFIX . "_users WHERE user_id = '{$poster_uid}'",
        );
        $poster_name =
            $poster && $poster["name"] !== ""
                ? stripslashes($poster["name"])
                : ($lang["forum_guest"] ?? "Misafir");

        $title = stripslashes((string) $topic["title"]);
        $link = forum_topic_post_url($tid, $topic["alt_name"] ?? "", $post_id);
        $snippet = forum_plain_text_snippet($post_text, 400);
        $unsubscribe = forum_site_url() . "index.php?do=forum&action=subscriptions";

        $subject = str_replace(
            "{title}",
            $title,
            $lang["forum_mail_reply_subject"] ?? "Yeni cevap: {title}",
        );

        foreach ($recipients as $rec) {
            $to_name = stripslashes((string) $rec["name"]);
            $sent_ok = forum_send_forum_email($rec["email"], $to_name, $subject, [
                "username" => htmlspecialchars($poster_name, ENT_QUOTES, "UTF-8"),
                "title" => htmlspecialchars($title, ENT_QUOTES, "UTF-8"),
                "link" => htmlspecialchars($link, ENT_QUOTES, "UTF-8"),
                "text" => nl2br(htmlspecialchars($snippet, ENT_QUOTES, "UTF-8")),
                "unsubscribe" => htmlspecialchars($unsubscribe, ENT_QUOTES, "UTF-8"),
                "date" => langdate("d.m.Y H:i", time(), true),
            ]);
            if ($sent_ok) {
                $sent++;
            }
        }

        return $sent;
    }
}

if (!function_exists("forum_notify_category_new_topic")) {
    /**
     * Bir kategoriye yeni konu açıldığında o kategorinin abonelerine
     * bildirim gönderir: site içi bildirim (enable_notifications) ve
     * e-posta (enable_email_notify). Gönderen hariç tutulur.
     *
     * @param array    $topic       Konu satırı (id, title, alt_name, cat_id)
     * @param int      $post_id     İlk mesaj ID'si
     * @param int      $poster_uid  Konuyu açan kullanıcı ID'si
     * @param string   $post_text   İlk mesaj metni (e-posta özeti için)
     * @param int[]|null $recipient_uids  Boş bırakılırsa aboneler DB'den çekilir.
     * @return int Gönderilen bildirim + e-posta sayısı
     */
    function forum_notify_category_new_topic($topic, $post_id, $poster_uid, $post_text, $recipient_uids = null)
    {
        global $db, $forum_cfg, $lang;

        if (empty($forum_cfg["enable_cat_subscribe"])) {
            return 0;
        }
        if (empty($topic) || empty($topic["id"])) {
            return 0;
        }

        $tid        = intval($topic["id"]);
        $post_id    = intval($post_id);
        $poster_uid = intval($poster_uid);
        $cat_id     = intval($topic["cat_id"] ?? 0);

        if ($cat_id <= 0) {
            return 0;
        }

        // Alıcı listesi (gönderen hariç)
        $recipients = [];

        if (is_array($recipient_uids)) {
            $ids = [];
            foreach ($recipient_uids as $u) {
                $u = intval($u);
                if ($u > 0 && $u !== $poster_uid) {
                    $ids[$u] = $u;
                }
            }
            if (!empty($ids)) {
                $in = implode(",", $ids);
                $db->query(
                    "SELECT user_id, name, email FROM " . USERPREFIX . "_users WHERE user_id IN ({$in})",
                );
                while ($r = $db->get_row()) {
                    $recipients[intval($r["user_id"])] = $r;
                }
            }
        } else {
            $db->query(
                "SELECT u.user_id, u.name, u.email
                 FROM " .
                    PREFIX .
                    "_forum_cat_subs s
                 INNER JOIN " .
                    USERPREFIX .
                    "_users u ON u.user_id = s.user_id
                 WHERE s.cat_id = '{$cat_id}' AND s.user_id != '{$poster_uid}'",
            );
            while ($r = $db->get_row()) {
                $recipients[intval($r["user_id"])] = $r;
            }
        }

        if (empty($recipients)) {
            return 0;
        }

        // Site içi bildirimler
        $notif_count = 0;
        if (!empty($forum_cfg["enable_notifications"])) {
            foreach ($recipients as $rec_uid => $rec) {
                $db->query(
                    "INSERT INTO " .
                        PREFIX .
                        "_forum_notifs (user_id, from_user, type, topic_id, post_id, is_read, date)
                     VALUES ('{$rec_uid}','{$poster_uid}','cat_topic','{$tid}','{$post_id}',0,NOW())",
                );
                $notif_count++;
            }
        }

        // E-posta bildirimi
        if (empty($forum_cfg["enable_email_notify"])) {
            return $notif_count;
        }

        $poster = $db->super_query(
            "SELECT name FROM " . USERPREFIX . "_users WHERE user_id = '{$poster_uid}'",
        );
        $poster_name =
            $poster && $poster["name"] !== ""
                ? stripslashes($poster["name"])
                : ($lang["forum_guest"] ?? "Misafir");

        $cat = $db->super_query(
            "SELECT name FROM " . PREFIX . "_forum_cats WHERE id = '{$cat_id}'",
        );
        $cat_name = $cat && isset($cat["name"]) ? stripslashes((string) $cat["name"]) : "";

        $title      = stripslashes((string) ($topic["title"] ?? ""));
        $link       = forum_topic_post_url($tid, $topic["alt_name"] ?? "", $post_id);
        $snippet    = forum_plain_text_snippet($post_text, 400);
        $unsubscribe = forum_site_url() . "index.php?do=forum&action=subscriptions";

        $subject = str_replace(
            "{title}",
            $title,
            $lang["forum_mail_topic_subject"] ?? "Yeni konu: {title}",
        );

        $sent = 0;
        foreach ($recipients as $rec) {
            $to_name = stripslashes((string) $rec["name"]);
            $ok = forum_send_forum_email(
                $rec["email"],
                $to_name,
                $subject,
                [
                    "username" => htmlspecialchars($poster_name, ENT_QUOTES, "UTF-8"),
                    "title" => htmlspecialchars($title, ENT_QUOTES, "UTF-8"),
                    "category" => htmlspecialchars($cat_name, ENT_QUOTES, "UTF-8"),
                    "link" => htmlspecialchars($link, ENT_QUOTES, "UTF-8"),
                    "text" => nl2br(htmlspecialchars($snippet, ENT_QUOTES, "UTF-8")),
                    "unsubscribe" => htmlspecialchars($unsubscribe, ENT_QUOTES, "UTF-8"),
                    "date" => langdate("d.m.Y H:i", time(), true),
                ],
                "forum_cat",
            );
            if ($ok) {
                $sent++;
            }
        }

        return $notif_count + $sent;
    }
}
