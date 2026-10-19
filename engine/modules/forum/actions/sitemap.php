<?php
/*
=====================================================
 Forge Forum Engine — Forum XML Sitemap
-----------------------------------------------------
 File: engine/modules/forum/actions/sitemap.php
 URL: index.php?do=forum&action=sitemap
      ve SEO: /forum/sitemap.xml
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (empty($forum_cfg["enable_sitemap"])) {
    @header("HTTP/1.1 404 Not Found");
    exit();
}

$home = function_exists("forum_site_url")
    ? forum_site_url()
    : $config["http_home_url"];

$limit = 5000;
$urls = [];

// Forum ana sayfası
$urls[] = [
    "loc" => $home . "forum/",
    "lastmod" => date("Y-m-d"),
    "changefreq" => "daily",
    "priority" => "1.0",
];

// Kategoriler — sitemap her zaman misafir (grup 5) bakışıyla üretilir.
$sitemap_access = forum_cat_access_where("c", 5);
$db->query(
    "SELECT c.id, c.alt_name FROM " . PREFIX . "_forum_cats c
     WHERE {$sitemap_access}
     ORDER BY c.posi ASC, c.name ASC",
);
while ($c = $db->get_row()) {
    $urls[] = [
        "loc" => $home . "forum/" . $c["alt_name"] . "/",
        "lastmod" => date("Y-m-d"),
        "changefreq" => "daily",
        "priority" => "0.8",
    ];
}

// Konular
$db->query(
    "SELECT t.id, t.alt_name, t.last_post_date, t.date FROM " . PREFIX . "_forum_topics t
     INNER JOIN " . PREFIX . "_forum_cats c ON c.id = t.cat_id
     WHERE t.is_deleted=0 AND t.is_approved=1 AND {$sitemap_access}
     ORDER BY t.last_post_date DESC
     LIMIT {$limit}",
);
while ($t = $db->get_row()) {
    $lm = !empty($t["last_post_date"]) ? $t["last_post_date"] : $t["date"];
    $ts = $lm ? strtotime((string) $lm) : time();
    $urls[] = [
        "loc" => $home . "forum/topic/" . intval($t["id"]) . "-" . $t["alt_name"] . ".html",
        "lastmod" => date("Y-m-d", $ts),
        "changefreq" => "weekly",
        "priority" => "0.6",
    ];
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    $xml .= "<url>\n";
    $xml .= "<loc>" . htmlspecialchars($u["loc"], ENT_XML1, "UTF-8") . "</loc>\n";
    $xml .= "<lastmod>" . $u["lastmod"] . "</lastmod>\n";
    $xml .= "<changefreq>" . $u["changefreq"] . "</changefreq>\n";
    $xml .= "<priority>" . $u["priority"] . "</priority>\n";
    $xml .= "</url>\n";
}
$xml .= "</urlset>\n";

@header("Content-Type: application/xml; charset=utf-8");
@header("Cache-Control: public, max-age=3600");
echo $xml;
exit();
