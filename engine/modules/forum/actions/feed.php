<?php
/*
=====================================================
 Forge Forum Engine — RSS Akışı
-----------------------------------------------------
 File: engine/modules/forum/actions/feed.php
 URL: index.php?do=forum&action=feed
      index.php?do=forum&action=feed&cat={alt_name}
      ( tip: ?type=topics | ?type=posts )
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (empty($forum_cfg["enable_rss"])) {
    @header("HTTP/1.1 404 Not Found");
    exit();
}

$home = function_exists("forum_site_url")
    ? forum_site_url()
    : $config["http_home_url"];

$feed_type = isset($_REQUEST["type"]) ? totranslit($_REQUEST["type"]) : "topics";
if (!in_array($feed_type, ["topics", "posts"], true)) {
    $feed_type = "topics";
}

$feed_cat_alt = isset($_REQUEST["cat"]) ? totranslit($_REQUEST["cat"]) : "";
$cat = null;
$cat_filter = "";
if ($feed_cat_alt !== "") {
    $alt_sql = $db->safesql($feed_cat_alt);
    $cat = $db->super_query(
        "SELECT id, name, alt_name, permissions FROM " . PREFIX . "_forum_cats WHERE alt_name = '{$alt_sql}'",
    );
    if ($cat && !empty($cat["id"])) {
        if (!forum_cat_access_allowed($cat["permissions"] ?? "")) {
            @header("HTTP/1.1 404 Not Found");
            exit();
        }
        $cat_filter = " AND t.cat_id = '" . intval($cat["id"]) . "'";
    }
}

$cat_filter .= " AND " . forum_cat_access_where("c");

$site_name =
    !empty($config["home_url"])
        ? $config["home_url"]
        : (!empty($config["short_title"])
            ? $config["short_title"]
            : (!empty($config["mail_title"])
                ? $config["mail_title"]
                : $lang["forum_breadcrumb_forum"]));

$is_posts = $feed_type === "posts";
$channel_title =
    ($cat ? stripslashes($cat["name"]) . " - " : "") .
    $lang["forum_breadcrumb_forum"] .
    " - " .
    $site_name;
$channel_link = $home . "forum/";
$channel_desc = $is_posts
    ? $lang["forum_rss_desc_posts"]
    : $lang["forum_rss_desc_topics"];
$self = $home . "forum/rss/" . ($feed_cat_alt !== "" ? $feed_cat_alt . "/" : "");

$lang_code = !empty($lang["language_code"]) ? $lang["language_code"] : "en";
$limit = 30;
$items = [];

if ($is_posts) {
    $db->query(
        "SELECT p.id, p.text, p.date, t.id AS tid, t.title AS topic_title,
                t.alt_name AS topic_alt, u.name AS author
         FROM " . PREFIX . "_forum_posts p
         INNER JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
         INNER JOIN " . PREFIX . "_forum_cats c ON c.id = t.cat_id
         LEFT JOIN " . USERPREFIX . "_users u ON u.user_id = p.user_id
         WHERE p.is_deleted = 0 AND p.is_approved = 1
               AND t.is_deleted = 0 AND t.is_approved = 1 {$cat_filter}
         ORDER BY p.date DESC
         LIMIT {$limit}",
    );
    while ($r = $db->get_row()) {
        $items[] = $r;
    }
} else {
    $db->query(
        "SELECT t.id, t.title, t.alt_name, t.date, t.last_post_date, t.replies,
                t.views, u.name AS author
         FROM " . PREFIX . "_forum_topics t
         INNER JOIN " . PREFIX . "_forum_cats c ON c.id = t.cat_id
         LEFT JOIN " . USERPREFIX . "_users u ON u.user_id = t.user_id
         WHERE t.is_deleted = 0 AND t.is_approved = 1 {$cat_filter}
         ORDER BY t.last_post_date DESC
         LIMIT {$limit}",
    );
    while ($r = $db->get_row()) {
        $items[] = $r;
    }
}

$guest = $lang["forum_guest"] ?? "Misafir";

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
$xml .= "<channel>\n";
$xml .= "<title>" . htmlspecialchars($channel_title, ENT_XML1, "UTF-8") . "</title>\n";
$xml .= "<link>" . htmlspecialchars($channel_link, ENT_XML1, "UTF-8") . "</link>\n";
$xml .= "<description>" . htmlspecialchars($channel_desc, ENT_XML1, "UTF-8") . "</description>\n";
$xml .= "<language>" . htmlspecialchars($lang_code, ENT_XML1, "UTF-8") . "</language>\n";
$xml .= "<lastBuildDate>" . date("D, d M Y H:i:s O") . "</lastBuildDate>\n";
$xml .= '<atom:link href="' . htmlspecialchars($self, ENT_XML1, "UTF-8") .
    '" rel="self" type="application/rss+xml" />' . "\n";
$xml .= "<generator>Forge Forum</generator>\n";

foreach ($items as $it) {
    if ($is_posts) {
        $link = forum_topic_post_url(
            intval($it["tid"]),
            $it["topic_alt"] ?? "",
            intval($it["id"]),
        );
        $title = stripslashes((string) $it["topic_title"]);
        $pub = $it["date"];
        $author = $it["author"] ?: $guest;
        $desc = forum_plain_text_snippet($it["text"], 500);
        $guid = $link;
    } else {
        $link = $home . "forum/topic/" . intval($it["id"]) . "-" . $it["alt_name"] . ".html";
        $title = stripslashes((string) $it["title"]);
        $pub = $it["last_post_date"] ?: $it["date"];
        $author = $it["author"] ?: $guest;
        $desc =
            $title .
            " (" .
            intval($it["replies"]) .
            " " .
            ($lang["forum_rss_replies"] ?? "cevap") .
            ", " .
            intval($it["views"]) .
            " " .
            ($lang["forum_rss_views"] ?? "görüntüleme") .
            ")";
        $guid = $link;
    }

    $ts = strtotime((string) $pub);
    $xml .= "<item>\n";
    $xml .= "<title>" . htmlspecialchars($title, ENT_XML1, "UTF-8") . "</title>\n";
    $xml .= "<link>" . htmlspecialchars($link, ENT_XML1, "UTF-8") . "</link>\n";
    $xml .= '<guid isPermaLink="true">' . htmlspecialchars($guid, ENT_XML1, "UTF-8") . "</guid>\n";
    if ($ts) {
        $xml .= "<pubDate>" . date("D, d M Y H:i:s O", $ts) . "</pubDate>\n";
    }
    $xml .= "<author>" . htmlspecialchars($author, ENT_XML1, "UTF-8") . "</author>\n";
    $xml .= "<description><![CDATA[" .
        str_replace("]]>", "]]&gt;", $desc) .
        "]]></description>\n";
    $xml .= "</item>\n";
}

$xml .= "</channel>\n</rss>\n";

@header("Content-Type: application/rss+xml; charset=utf-8");
@header("Cache-Control: public, max-age=600");
echo $xml;
exit();
