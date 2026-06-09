<?php
/*
=====================================================
 Forge Forum Engine — Dashboard / Özet
-----------------------------------------------------
 File: engine/inc/forum/main.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/

if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (!function_exists("forum_plain_text_snippet")) {
    function forum_plain_text_snippet($text, $limit = 85)
    {
        $text = html_entity_decode(stripslashes((string) $text), ENT_QUOTES, "UTF-8");
        $text = strip_tags($text);

        for ($i = 0; $i < 5; $i++) {
            $next = preg_replace(
                '/\[quote(?:=[^\]]+)?\](.*?)\[\/quote\]/isu',
                '$1 ',
                $text,
            );
            if ($next === $text) {
                break;
            }
            $text = $next;
        }

        $paired = [
            "b",
            "i",
            "u",
            "s",
            "code",
            "hide",
            "spoiler",
            "left",
            "right",
            "center",
            "color",
            "size",
        ];
        foreach ($paired as $tag) {
            $text = preg_replace(
                '/\[' . $tag . '(?:=[^\]]+)?\](.*?)\[\/' . $tag . '\]/isu',
                '$1 ',
                $text,
            );
        }

        $text = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/isu', '$2 ', $text);
        $text = preg_replace('/\[url\](.*?)\[\/url\]/isu', '$1 ', $text);
        $text = preg_replace('/\[email=(.*?)\](.*?)\[\/email\]/isu', '$2 ', $text);
        $text = preg_replace('/\[email\](.*?)\[\/email\]/isu', '$1 ', $text);
        $text = preg_replace(
            '/\[(?:img|thumb|video|audio|media|attach|upload)[^\]]*\]/iu',
            '',
            $text,
        );
        $text = preg_replace('/\[\/[^\]]+\]/iu', '', $text);
        $text = preg_replace('/\[[^\]]+\]/iu', '', $text);
        $text = preg_replace('/\s+/u', ' ', trim($text));

        if ($limit > 0 && mb_strlen($text) > $limit) {
            $text = mb_substr($text, 0, $limit) . '...';
        }

        return $text;
    }
}

// SÜRÜM KONTROLÜ (Sistem Bilgisi)
// -------------------------------------------------
if (!function_exists("forum_normalize_upgrade_url")) {
    function forum_normalize_upgrade_url($url)
    {
        $url = trim(str_replace("&amp;", "&", (string) $url));
        if ($url === "") {
            return "";
        }

        // github.com/.../blob/branch/file.json → raw.githubusercontent.com
        if (preg_match(
            '#^https?://github\.com/([^/]+)/([^/]+)/blob/([^/]+)/(.+)$#i',
            $url,
            $m,
        )) {
            return "https://raw.githubusercontent.com/{$m[1]}/{$m[2]}/{$m[3]}/{$m[4]}";
        }

        return $url;
    }
}

if (!function_exists("forum_http_get")) {
    function forum_http_get($url)
    {
        if (function_exists("curl_init")) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt(
                $ch,
                CURLOPT_USERAGENT,
                "Forge-Forum-Update-Check/1.0",
            );
            $data = curl_exec($ch);
            curl_close($ch);

            if ($data !== false && $data !== "") {
                return $data;
            }
        }

        if (preg_match("/1|yes|on|true/i", ini_get("allow_url_fopen"))) {
            $ctx = stream_context_create([
                "http" => [
                    "timeout" => 15,
                    "header" => "User-Agent: Forge-Forum-Update-Check/1.0\r\n",
                ],
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
            ]);
            $data = @file_get_contents($url, false, $ctx);
            if ($data !== false && $data !== "") {
                return $data;
            }
        }

        return false;
    }
}

if (!function_exists("forum_release_mirror_urls")) {
    function forum_release_mirror_urls($url)
    {
        $url = forum_normalize_upgrade_url($url);
        $urls = [];

        if ($url !== "") {
            $urls[] = $url;
        }

        if (
            preg_match(
                '#raw\.githubusercontent\.com/([^/]+)/([^/]+)/([^/]+)/(.+)$#i',
                $url,
                $m,
            )
        ) {
            $urls[] =
                "https://cdn.jsdelivr.net/gh/{$m[1]}/{$m[2]}@{$m[3]}/{$m[4]}";
        }

        return array_values(array_unique($urls));
    }
}

if (!function_exists("forum_fetch_release_json")) {
    function forum_fetch_release_json($url)
    {
        $cache_bust = "t=" . time();
        foreach (forum_release_mirror_urls($url) as $mirror) {
            $fetch_url =
                $mirror .
                (strpos($mirror, "?") === false ? "?" : "&") .
                $cache_bust;
            $data = forum_http_get($fetch_url);
            if ($data !== false && $data !== "") {
                return $data;
            }
        }

        return false;
    }
}

$forum_version = "1.0.0";
$forum_plugin_name = "";
$forum_upgrade_url =
    "https://raw.githubusercontent.com/dle-hub/Forge_Forum/main/release.json";
$forum_update = [
    "available" => false,
    "version" => "",
    "remote_version" => "",
    "url" => "",
    "checked" => false,
    "error" => "",
];

$plugin_info = $db->super_query(
    "SELECT name, version, upgradeurl FROM " .
        PREFIX .
        "_plugins WHERE name='Forge Forum' LIMIT 1",
);
if (!$plugin_info) {
    $plugin_info = $db->super_query(
        "SELECT name, version, upgradeurl FROM " .
            PREFIX .
            "_plugins WHERE upgradeurl LIKE '%Forge_Forum%release.json%' LIMIT 1",
    );
}
if ($plugin_info) {
    $forum_plugin_name = trim($plugin_info["name"] ?? "");
    if (!empty($plugin_info["version"])) {
        $forum_version = trim($plugin_info["version"]);
    }
    if (!empty($plugin_info["upgradeurl"])) {
        $forum_upgrade_url = forum_normalize_upgrade_url(
            $plugin_info["upgradeurl"],
        );
    }
}

$remote_data = forum_fetch_release_json($forum_upgrade_url);
if ($remote_data) {
    $remote = json_decode(trim($remote_data), true);
    if (is_array($remote) && !empty($remote["version"])) {
        $forum_update["checked"] = true;
        $forum_update["remote_version"] = trim($remote["version"]);
        if (
            version_compare(
                $forum_update["remote_version"],
                $forum_version,
                ">",
            )
        ) {
            $forum_update["available"] = true;
            $forum_update["version"] = $forum_update["remote_version"];
            $forum_update["url"] = trim($remote["url"] ?? "");
        }
    } else {
        $forum_update["error"] = "invalid_json";
    }
} else {
    $forum_update["error"] = "fetch_failed";
}

// İSTATİSTİKLERİ ÇEK
// -------------------------------------------------
$stats = [];

// Toplam kategori
$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_cats",
);
$stats["cats"] = intval($row["cnt"]);

$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_cats WHERE parent_id = 0",
);
$stats["root_cats"] = intval($row["cnt"]);

// Toplam konu
$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " .
        PREFIX .
        "_forum_topics WHERE is_deleted = 0",
);
$stats["topics"] = intval($row["cnt"]);

// Toplam mesaj
$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " .
        PREFIX .
        "_forum_posts WHERE is_deleted = 0",
);
$stats["posts"] = intval($row["cnt"]);

// Toplam kullanıcı (DLE users tablosundan)
$row = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_users");
$stats["users"] = intval($row["cnt"]);

// Son 24 saatte açılan konu
$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " .
        PREFIX .
        "_forum_topics WHERE is_deleted = 0 AND date >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
);
$stats["topics_24h"] = intval($row["cnt"]);

// Son 24 saatte atılan mesaj
$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " .
        PREFIX .
        "_forum_posts WHERE is_deleted = 0 AND date >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
);
$stats["posts_24h"] = intval($row["cnt"]);

// Onay bekleyen mesaj
$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " .
        PREFIX .
        "_forum_posts WHERE is_approved = 0 AND is_deleted = 0",
);
$stats["pending"] = intval($row["cnt"]);

// Son kayıt olan üye
$row = $db->super_query(
    "SELECT name, user_id FROM " .
        PREFIX .
        "_users ORDER BY user_id DESC LIMIT 1",
);
$stats["last_user"] = $row;

// Son konu
$row = $db->super_query(
    "SELECT t.id, t.title, t.date, u.name FROM " .
        PREFIX .
        "_forum_topics t
    LEFT JOIN " .
        PREFIX .
        "_users u ON u.user_id = t.user_id
    WHERE t.is_deleted = 0
    ORDER BY t.date DESC LIMIT 1",
);
$stats["last_topic"] = $row;

$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_ranks",
);
$stats["ranks"] = intval($row["cnt"]);

// Bekleyen şikayet sayısı
$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_reports WHERE status = 0",
);
$stats["pending_reports"] = intval($row["cnt"]);

// Onay bekleyen konular
$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_topics WHERE is_approved = 0 AND is_deleted = 0",
);
$stats["pending_topics"] = intval($row["cnt"]);

// Onay bekleyen mesajlar
$row = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_posts WHERE is_approved = 0 AND is_deleted = 0",
);
$stats["pending_posts"] = intval($row["cnt"]);

// Son konuları çek (5 adet)
$last_topics = [];
$db->query("SELECT t.id, t.title, t.date, t.views, t.replies, t.alt_name, u.name as author 
            FROM " . PREFIX . "_forum_topics t
            LEFT JOIN " . USERPREFIX . "_users u ON u.user_id = t.user_id
            WHERE t.is_deleted = 0 AND t.is_approved = 1
            ORDER BY t.date DESC LIMIT 5");
while ($row = $db->get_row()) {
    $last_topics[] = $row;
}

// Son mesajları çek (5 adet)
$last_posts = [];
$db->query("SELECT p.id, p.topic_id, p.date, u.name as author, t.title as topic_title, t.alt_name as topic_alt_name, p.text 
            FROM " . PREFIX . "_forum_posts p
            LEFT JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
            LEFT JOIN " . USERPREFIX . "_users u ON u.user_id = p.user_id
            WHERE p.is_deleted = 0 AND p.is_approved = 1 AND t.is_deleted = 0 AND t.is_approved = 1
            ORDER BY p.date DESC LIMIT 5");
while ($row = $db->get_row()) {
    $last_posts[] = $row;
}
?>

<?php
$forum_stat_card_style =
    "position:relative; overflow:hidden; border-radius:4px; padding:20px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1); min-height:108px;";
$forum_stat_sub_style = "font-size:10px; margin-top:4px; opacity:0.75;";
?>

<!-- 1. ÜST SIRA: 4'lü İstatistik Kartları -->
<div class="row" style="display:flex; flex-wrap:wrap;">
    <div class="col-sm-6 col-md-3" style="display:flex;">
        <div class="panel panel-body bg-blue-600 has-bg-image text-white" style="<?php echo $forum_stat_card_style; ?> flex:1;">
            <div class="media no-margin" style="display:flex; align-items:center; justify-content:space-between;">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="font-size:26px; margin:0; font-weight:600;"><?php echo $stats["cats"]; ?></h3>
                    <span class="text-uppercase text-size-mini text-semibold" style="font-size:11px; opacity:0.85; letter-spacing:0.5px;"><?php echo $lang['forum_stat_cats']; ?></span>
                    <div class="text-size-small" style="<?php echo $forum_stat_sub_style; ?>"><i class="fa fa-folder-open"></i> <?php echo sprintf($lang['forum_stat_cats_sub'], $stats["root_cats"]); ?></div>
                </div>
                <div class="media-right media-middle">
                    <i class="fa fa-sitemap fa-3x" style="opacity:0.4; font-size:36px;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-md-3" style="display:flex;">
        <div class="panel panel-body bg-success-600 has-bg-image text-white" style="<?php echo $forum_stat_card_style; ?> flex:1;">
            <div class="media no-margin" style="display:flex; align-items:center; justify-content:space-between;">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="font-size:26px; margin:0; font-weight:600;"><?php echo $stats["topics"]; ?></h3>
                    <span class="text-uppercase text-size-mini text-semibold" style="font-size:11px; opacity:0.85; letter-spacing:0.5px;"><?php echo $lang['forum_stat_topics']; ?></span>
                    <div class="text-size-small" style="<?php echo $forum_stat_sub_style; ?>"><i class="fa fa-clock-o"></i> 24s: <?php echo $stats["topics_24h"]; ?> yeni</div>
                </div>
                <div class="media-right media-middle">
                    <i class="fa fa-comments fa-3x" style="opacity:0.4; font-size:36px;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-md-3" style="display:flex;">
        <div class="panel panel-body bg-indigo-600 has-bg-image text-white" style="<?php echo $forum_stat_card_style; ?> flex:1;">
            <div class="media no-margin" style="display:flex; align-items:center; justify-content:space-between;">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="font-size:26px; margin:0; font-weight:600;"><?php echo $stats["posts"]; ?></h3>
                    <span class="text-uppercase text-size-mini text-semibold" style="font-size:11px; opacity:0.85; letter-spacing:0.5px;"><?php echo $lang['forum_stat_posts']; ?></span>
                    <div class="text-size-small" style="<?php echo $forum_stat_sub_style; ?>"><i class="fa fa-clock-o"></i> 24s: <?php echo $stats["posts_24h"]; ?> yeni</div>
                </div>
                <div class="media-right media-middle">
                    <i class="fa fa-commenting fa-3x" style="opacity:0.4; font-size:36px;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-md-3" style="display:flex;">
        <div class="panel panel-body bg-warning-600 has-bg-image text-white" style="<?php echo $forum_stat_card_style; ?> flex:1;">
            <div class="media no-margin" style="display:flex; align-items:center; justify-content:space-between;">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="font-size:26px; margin:0; font-weight:600;"><?php echo $stats["users"]; ?></h3>
                    <span class="text-uppercase text-size-mini text-semibold" style="font-size:11px; opacity:0.85; letter-spacing:0.5px;"><?php echo $lang['forum_stat_users']; ?></span>
                    <div class="text-size-small" style="<?php echo $forum_stat_sub_style; ?> white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:140px;">
                        <i class="fa fa-user-plus"></i> <?php echo htmlspecialchars($stats["last_user"]["name"] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
                <div class="media-right media-middle">
                    <i class="fa fa-users fa-3x" style="opacity:0.4; font-size:36px;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. ALT SIRA: Sol ve Sağ Sütunlar -->
<div class="row">
    <!-- SOL SÜTUN: Bildirimler ve Son Hareketler -->
    <div class="col-lg-8">

        <!-- Onay Bekleyen İçerik Uyarısı -->
        <?php if ($stats['pending_topics'] > 0 || $stats['pending_posts'] > 0): ?>
        <div class="alert alert-warning alert-styled-left alert-arrow-left alert-component" style="background:#fffbeb; border-color:#fef3c7; color:#b45309; padding: 15px 20px; border-radius: 4px; margin-bottom: 20px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <div>
                <i class="fa fa-exclamation-triangle mr-10" style="font-size:16px; vertical-align:middle; margin-right:8px;"></i>
                <?php echo sprintf($lang['forum_admin_pending_alert'], $stats['pending_topics'], $stats['pending_posts']); ?>
            </div>
            <a href="?mod=forum&action=approval" class="btn btn-warning btn-xs btn-raised" style="font-size: 11px; padding: 4px 12px; font-weight:600; background-color:#d97706; border:none; color:#fff; border-radius:3px;"><?php echo $lang['forum_menu_approval']; ?></a>
        </div>
        <?php endif; ?>

        <!-- Bekleyen Şikayet Uyarısı -->
        <?php if ($stats['pending_reports'] > 0): ?>
        <div class="alert alert-danger alert-styled-left alert-arrow-left alert-component" style="background:#fef2f2; border-color:#fee2e2; color:#b91c1c; padding: 15px 20px; border-radius: 4px; margin-bottom: 20px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <div>
                <i class="fa fa-flag mr-10" style="font-size:16px; vertical-align:middle; margin-right:8px;"></i>
                <?php echo sprintf($lang['forum_admin_reports_alert'], $stats['pending_reports']); ?>
            </div>
            <a href="?mod=forum&action=reports" class="btn btn-danger btn-xs btn-raised" style="font-size: 11px; padding: 4px 12px; font-weight:600; background-color:#dc2626; border:none; color:#fff; border-radius:3px;"><?php echo $lang['forum_menu_reports']; ?></a>
        </div>
        <?php endif; ?>

        <!-- Son Forum Hareketleri (Tabs) -->
        <div class="panel panel-default">
            <div class="panel-heading" style="padding:0; border-bottom:1px solid #e5e7eb;">
                <ul class="nav nav-tabs nav-tabs-solid" style="margin:0; border:none;">
                    <li class="active"><a href="#tab-last-topics" data-toggle="tab" style="border-radius:0; border:none; padding:15px 20px;"><i class="fa fa-comments position-left" style="margin-right:6px;"></i> <?php echo $lang['forum_admin_latest_topics']; ?></a></li>
                    <li><a href="#tab-last-posts" data-toggle="tab" style="border-radius:0; border:none; padding:15px 20px;"><i class="fa fa-pencil-square-o position-left" style="margin-right:6px;"></i> <?php echo $lang['forum_admin_latest_posts']; ?></a></li>
                </ul>
            </div>
            <div class="panel-tab-content tab-content" style="padding:0;">
                <!-- Tab 1: Son Konular -->
                <div class="tab-pane active" id="tab-last-topics">
                    <?php if (count($last_topics) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                                    <th style="padding:12px 15px; font-weight:600; color:#374151;"><?php echo $lang['forum_menu_topics']; ?></th>
                                    <th class="text-center" style="width:100px; padding:12px 15px; font-weight:600; color:#374151;"><?php echo $lang['forum_admin_views']; ?></th>
                                    <th class="text-center" style="width:100px; padding:12px 15px; font-weight:600; color:#374151;"><?php echo $lang['forum_admin_replies']; ?></th>
                                    <th class="text-right" style="width:180px; padding:12px 15px; font-weight:600; color:#374151;">Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($last_topics as $t): ?>
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:12px 15px; vertical-align:middle;">
                                        <a href="<?php echo $config['http_home_url']; ?>forum/<?php echo $t['id']; ?>-<?php echo $t['alt_name'] ?: 'topic'; ?>.html" target="_blank" class="text-semibold text-blue-700" style="font-size:13px; font-weight:600;">
                                            <?php echo htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <div class="text-muted" style="font-size:11px; margin-top:2px;">
                                            Açan: <strong><?php echo htmlspecialchars($t['author'] ?: 'Misafir', ENT_QUOTES, 'UTF-8'); ?></strong>
                                        </div>
                                    </td>
                                    <td class="text-center" style="vertical-align:middle; padding:12px 15px;"><span class="badge badge-default" style="background:#e5e7eb; color:#374151; font-weight:500; padding:4px 8px; border-radius:3px;"><?php echo $t['views']; ?></span></td>
                                    <td class="text-center" style="vertical-align:middle; padding:12px 15px;"><span class="badge bg-blue-600" style="font-weight:500; padding:4px 8px; border-radius:3px;"><?php echo $t['replies']; ?></span></td>
                                    <td class="text-right text-muted" style="font-size:11px; vertical-align:middle; padding:12px 15px;"><?php echo $t['date']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="panel-body text-center text-muted italic" style="padding:30px;">
                        <i class="fa fa-info-circle mr-5" style="font-size:16px; margin-right:6px; vertical-align:middle;"></i> <?php echo $lang['forum_admin_no_activity']; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab 2: Son Mesajlar -->
                <div class="tab-pane" id="tab-last-posts">
                    <?php if (count($last_posts) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                                    <th style="padding:12px 15px; font-weight:600; color:#374151;">Mesaj / Konu</th>
                                    <th class="text-right" style="width:180px; padding:12px 15px; font-weight:600; color:#374151;">Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($last_posts as $p):
                                    $snippet = forum_plain_text_snippet($p['text'], 85);
                                ?>
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:12px 15px; vertical-align:middle;">
                                        <div style="font-size:13px; color:#1f2937; margin-bottom:4px; line-height:1.4;">
                                            "<?php echo htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'); ?>"
                                        </div>
                                        <div class="text-muted" style="font-size:11px;">
                                            Yazan: <strong><?php echo htmlspecialchars($p['author'] ?: 'Misafir', ENT_QUOTES, 'UTF-8'); ?></strong> — Konu: 
                                            <a href="<?php echo $config['http_home_url']; ?>forum/<?php echo $p['topic_id']; ?>-<?php echo $p['topic_alt_name'] ?: 'topic'; ?>.html" target="_blank" class="text-blue-700" style="font-weight:500;">
                                                <?php echo htmlspecialchars($p['topic_title'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-right text-muted" style="font-size:11px; vertical-align:middle; padding:12px 15px;"><?php echo $p['date']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="panel-body text-center text-muted italic" style="padding:30px;">
                        <i class="fa fa-info-circle mr-5" style="font-size:16px; margin-right:6px; vertical-align:middle;"></i> <?php echo $lang['forum_admin_no_activity']; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- SAĞ SÜTUN: Hızlı Linkler ve Sistem Bilgisi -->
    <div class="col-lg-4">

        <!-- HIZLI İŞLEMLER -->
        <div class="panel panel-default">
            <div class="panel-heading" style="border-bottom:1px solid #e5e7eb; padding:15px 20px;">
                <i class="fa fa-bolt position-left" style="margin-right:6px;"></i> <?php echo $lang['forum_main_quick_actions']; ?>
            </div>
            <div class="list-bordered">
                <div class="row box-section" style="border-bottom:1px solid #f3f4f6; margin:0;">
                    <div class="col-sm-12 media-list media-list-linked" style="padding:0;">
                        <a class="media-link" href="?mod=forum&action=topics" style="padding:15px 20px; display:flex; align-items:center;">
                            <div class="media-left" style="margin-right:15px;"><i class="fa fa-list fa-2x text-muted" style="width:36px; text-align:center; color:#6b7280;"></i></div>
                            <div class="media-body">
                                <h6 class="media-heading text-semibold" style="margin:0 0 3px 0; font-size:13px; font-weight:600; color:#1f2937;"><?php echo $lang['forum_main_manage_topics']; ?></h6>
                                <span class="text-muted" style="font-size:11px;"><?php echo $lang['forum_main_manage_topics_desc']; ?></span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row box-section" style="border-bottom:1px solid #f3f4f6; margin:0;">
                    <div class="col-sm-12 media-list media-list-linked" style="padding:0;">
                        <a class="media-link" href="?mod=forum&action=categories" style="padding:15px 20px; display:flex; align-items:center;">
                            <div class="media-left" style="margin-right:15px;"><i class="fa fa-sitemap fa-2x text-muted" style="width:36px; text-align:center; color:#6b7280;"></i></div>
                            <div class="media-body">
                                <h6 class="media-heading text-semibold" style="margin:0 0 3px 0; font-size:13px; font-weight:600; color:#1f2937;"><?php echo $lang['forum_main_manage_cats']; ?></h6>
                                <span class="text-muted" style="font-size:11px;"><?php echo $lang['forum_main_manage_cats_desc']; ?></span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row box-section" style="border-bottom:1px solid #f3f4f6; margin:0;">
                    <div class="col-sm-12 media-list media-list-linked" style="padding:0;">
                        <a class="media-link" href="?mod=forum&action=permissions" style="padding:15px 20px; display:flex; align-items:center;">
                            <div class="media-left" style="margin-right:15px;"><i class="fa fa-lock fa-2x text-muted" style="width:36px; text-align:center; color:#6b7280;"></i></div>
                            <div class="media-body">
                                <h6 class="media-heading text-semibold" style="margin:0 0 3px 0; font-size:13px; font-weight:600; color:#1f2937;"><?php echo $lang['forum_menu_permissions']; ?></h6>
                                <span class="text-muted" style="font-size:11px;"><?php echo $lang['forum_main_permissions_desc']; ?></span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row box-section" style="border-bottom:1px solid #f3f4f6; margin:0;">
                    <div class="col-sm-12 media-list media-list-linked" style="padding:0;">
                        <a class="media-link" href="?mod=forum&action=ranks" style="padding:15px 20px; display:flex; align-items:center;">
                            <div class="media-left" style="margin-right:15px;"><i class="fa fa-trophy fa-2x text-muted" style="width:36px; text-align:center; color:#6b7280;"></i></div>
                            <div class="media-body">
                                <h6 class="media-heading text-semibold" style="margin:0 0 3px 0; font-size:13px; font-weight:600; color:#1f2937;"><?php echo $lang['forum_menu_ranks']; ?></h6>
                                <span class="text-muted" style="font-size:11px;"><?php echo $lang['forum_main_ranks_desc']; ?></span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row box-section" style="border-bottom:1px solid #f3f4f6; margin:0;">
                    <div class="col-sm-12 media-list media-list-linked" style="padding:0;">
                        <a class="media-link" href="?mod=forum&action=wordfilter" style="padding:15px 20px; display:flex; align-items:center;">
                            <div class="media-left" style="margin-right:15px;"><i class="fa fa-filter fa-2x text-muted" style="width:36px; text-align:center; color:#6b7280;"></i></div>
                            <div class="media-body">
                                <h6 class="media-heading text-semibold" style="margin:0 0 3px 0; font-size:13px; font-weight:600; color:#1f2937;"><?php echo $lang['forum_menu_wordfilter']; ?></h6>
                                <span class="text-muted" style="font-size:11px;"><?php echo $lang['forum_main_wordfilter_desc']; ?></span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row box-section" style="margin:0;">
                    <div class="col-sm-12 media-list media-list-linked" style="padding:0;">
                        <a class="media-link" href="?mod=forum&action=settings" style="padding:15px 20px; display:flex; align-items:center;">
                            <div class="media-left" style="margin-right:15px;"><i class="fa fa-gears fa-2x text-muted" style="width:36px; text-align:center; color:#6b7280;"></i></div>
                            <div class="media-body">
                                <h6 class="media-heading text-semibold" style="margin:0 0 3px 0; font-size:13px; font-weight:600; color:#1f2937;"><?php echo $lang['forum_menu_settings']; ?></h6>
                                <span class="text-muted" style="font-size:11px;"><?php echo $lang['forum_main_settings_desc']; ?></span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- SİSTEM BİLGİSİ -->
        <div class="panel panel-default mt-20" style="margin-top:20px;">
            <div class="panel-heading" style="border-bottom:1px solid #e5e7eb; padding:15px 20px;">
                <i class="fa fa-info-circle position-left" style="margin-right:6px;"></i> <?php echo $lang['forum_main_sys_info']; ?>
            </div>
            <div class="panel-body" style="padding:0;">
                <table class="table table-striped" style="margin-bottom:0; font-size:12px;">
                    <tbody>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 20px;"><strong><?php echo $lang['forum_main_plugin_ver']; ?></strong></td>
                            <td class="text-right" style="padding:12px 20px;">
                                <span class="label label-info" style="font-weight:600; padding:3px 8px; border-radius:3px; background-color:#3b82f6; color:#fff;">v<?php echo htmlspecialchars($forum_version, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($forum_plugin_name): ?>
                                    <span class="text-muted" style="font-size:10px; display:block; margin-top:4px;"><?php echo sprintf($lang['forum_main_update_db_note'], htmlspecialchars($forum_plugin_name, ENT_QUOTES, 'UTF-8')); ?></span>
                                <?php else: ?>
                                    <span class="text-danger" style="font-size:10px; display:block; margin-top:4px;"><?php echo $lang['forum_main_update_db_missing']; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 20px;"><strong><?php echo $lang['forum_main_update_remote_label']; ?></strong></td>
                            <td class="text-right" style="padding:12px 20px;">
                                <?php if ($forum_update['checked'] && !empty($forum_update['remote_version'])): ?>
                                    <span class="label label-default" style="font-weight:600; padding:3px 8px; border-radius:3px;">v<?php echo htmlspecialchars($forum_update['remote_version'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 20px;"><strong><?php echo $lang['forum_main_update_status']; ?></strong></td>
                            <td class="text-right" style="padding:12px 20px;">
                                <?php if ($forum_update['available']): ?>
                                    <span class="label label-warning" style="font-weight:600; padding:3px 8px; border-radius:3px;">v<?php echo htmlspecialchars($forum_update['version'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if (!empty($forum_update['url'])): ?>
                                        <a href="<?php echo htmlspecialchars($forum_update['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="text-primary" style="margin-left:8px; font-size:11px;"><?php echo $lang['forum_main_update_download']; ?></a>
                                    <?php endif; ?>
                                <?php elseif ($forum_update['checked']): ?>
                                    <span class="text-success" style="font-size:12px;"><i class="fa fa-check-circle"></i> <?php echo $lang['forum_main_update_current']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px;"><?php echo $lang['forum_main_update_check_failed']; ?></span>
                                    <span class="text-muted" style="font-size:10px; display:block; margin-top:4px;"><?php echo $lang['forum_main_update_check_hint']; ?></span>
                                <?php endif; ?>
                                <a href="?mod=forum&amp;action=main&amp;recheck=1" class="text-primary" style="font-size:10px; display:block; margin-top:6px;"><?php echo $lang['forum_main_update_recheck']; ?></a>
                            </td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 20px;"><strong><?php echo $lang['forum_main_dle_ver']; ?></strong></td>
                            <td class="text-right text-muted" style="padding:12px 20px;"><?php echo $config['version_id']; ?></td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 20px;"><strong><?php echo $lang['forum_main_table_prefix']; ?></strong></td>
                            <td class="text-right text-muted" style="padding:12px 20px;"><code><?php echo PREFIX; ?></code></td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 20px;"><strong>PHP Versiyonu</strong></td>
                            <td class="text-right text-muted" style="padding:12px 20px;"><?php echo phpversion(); ?></td>
                        </tr>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 20px;"><strong>MySQL Versiyonu</strong></td>
                            <td class="text-right text-muted" style="padding:12px 20px;"><?php echo $db->mysql_version; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
