<?php
/*
=====================================================
 Forge Forum Engine — AJAX @Mention Kullanıcı Arama
-----------------------------------------------------
 URL: index.php?controller=ajax&mod=forum_mention_search
 Kullanım: editörde "@" yazılınca öneri listesi için
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

header('Content-Type: application/json; charset=utf-8');

if (!$is_logged) {
    echo json_encode(['success' => false, 'users' => []]);
    exit;
}
if (!isset($_POST['user_hash']) || $_POST['user_hash'] !== $dle_login_hash) {
    echo json_encode(['success' => false, 'users' => []]);
    exit;
}

// Forum ayarları
$forum_cfg = [];
$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
while ($row = $db->get_row()) {
    $forum_cfg[$row["name"]] = stripslashes($row["value"]);
}
if (isset($forum_cfg['enable_mentions']) && !intval($forum_cfg['enable_mentions'])) {
    echo json_encode(['success' => false, 'users' => []]);
    exit;
}

$q = isset($_POST['q']) ? trim((string) $_POST['q']) : '';
if ($q !== '' && $q[0] === '@') {
    $q = substr($q, 1);
}
// Mention deseniyle uyumlu karakterler dışındakileri temizle
$q = preg_replace('/[^a-zA-Z0-9_\-\.ğüşöçİĞÜŞÖÇ]/u', '', $q);
if ($q === '') {
    echo json_encode(['success' => true, 'users' => []]);
    exit;
}

$like = $db->safesql(str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q));

$users = [];
$db->query(
    "SELECT user_id, name, foto FROM " .
        USERPREFIX .
        "_users WHERE name LIKE '{$like}%' ORDER BY name ASC LIMIT 8",
);
while ($r = $db->get_row()) {
    $name = stripslashes((string) $r['name']);
    $foto = stripslashes((string) ($r['foto'] ?? ''));
    $avatar = '';
    if ($foto !== '') {
        if (filter_var($foto, FILTER_VALIDATE_EMAIL)) {
            $avatar = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($foto))) . '?s=40';
        } elseif (strpos($foto, '//') === 0 || strpos($foto, 'http') === 0) {
            $avatar = $foto;
        } else {
            $avatar = $config['http_home_url'] . 'uploads/fotos/' . $foto;
        }
    }
    $users[] = [
        'id'     => intval($r['user_id']),
        'name'   => $name,
        'avatar' => $avatar,
    ];
}

echo json_encode(['success' => true, 'users' => $users]);
exit;
