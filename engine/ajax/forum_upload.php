<?php
/*
=====================================================
 Forge Forum Engine — AJAX Hızlı Cevap Dosya Yükleme Kontrolcüsü
=====================================================
 URL: index.php?controller=ajax&mod=forum_upload
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

// Dil dosyasını yükle (AJAX isteklerinde forum.lng bootstrap'ta yüklenmeyebilir)
if (!is_array($lang)) {
    $lang = [];
}
$forum_lang_dir = $config['langs'] ? totranslit($config['langs']) : 'Turkish';
$_fup_forum_lng = ROOT_DIR . '/language/' . $forum_lang_dir . '/forum.lng';
if (!file_exists($_fup_forum_lng)) {
    $_fup_forum_lng = ROOT_DIR . '/language/Turkish/forum.lng';
}
if (file_exists($_fup_forum_lng)) {
    include $_fup_forum_lng;
}
unset($_fup_forum_lng);

// Upload modalı DLE adminpanel.lng anahtarlarını kullanır (mod=upload admin modülünde yüklenir)
$forum_upload_lang_keys = [
    'images_ath', 'images_amh', 'images_water', 'hidpi_upl', 'public_file_upl',
    'upload_t_seite_1', 'upload_t_seite_2', 'upload_t_seite_3',
    'storage_upload', 'storage_default', 'opt_sys_imfs_1',
    'media_upload_st', 'images_iln', 'edit_selall', 'images_upurl', 'db_load_a',
    'files_max_info_1', 'media_upload_title', 'images_align', 'images_left',
    'images_right', 'images_center', 'opt_sys_no',
    'media_upload_b1', 'media_upload_b2', 'media_upload_ip2', 'media_upload_ip3',
    'media_upload_ip4', 'media_upload_ip5', 'media_upload_ip6', 'media_upload_ip7',
    'p_cancel', 'images_all_insert', 'images_del',
    'up_im_expand', 'up_im_copy', 'up_im_copy1',
    'error_max_queue', 'remote_error', 'remote_error_1', 'tinyapi_error',
    'ajax_info', 'file_delete', 'p_info', 'files_del_error', 'delete_selected',
    'plugins_a_3', 'media_upload_st6', 'media_upload_st9', 'media_upload_st10',
    'media_upload_st4', 'media_upload_st5', 'media_upload_st11', 'media_upload_st12', 'media_upload_st13',
    'images_uptitle', 'bb_t_up',
];
$forum_admin_lng = ROOT_DIR . '/language/' . $forum_lang_dir . '/adminpanel.lng';
if (file_exists($forum_admin_lng)) {
    $lang_before_upload = $lang;
    include $forum_admin_lng;
    $lang_admin_upload = $lang;
    $lang = $lang_before_upload;
    foreach ($forum_upload_lang_keys as $_fup_key) {
        if (!empty($lang_admin_upload[$_fup_key])) {
            $lang[$_fup_key] = $lang_admin_upload[$_fup_key];
        }
    }
    unset($lang_before_upload, $lang_admin_upload, $_fup_key);
}

$forum_upload_lang_fallbacks = [
    'images_ath' => 'Şu boyutta küçük kopya oluştur:',
    'images_amh' => 'Şu boyutta orta kopya oluştur:',
    'images_water' => 'Filigran ekle',
    'hidpi_upl' => 'HiDPI (Retina) desteği',
    'public_file_upl' => 'Dosyayı herkese açık yap',
    'upload_t_seite_1' => 'En büyük kenara göre',
    'upload_t_seite_2' => 'Genişliğe göre',
    'upload_t_seite_3' => 'Yüksekliğe göre',
    'storage_upload' => 'Depolama:',
    'storage_default' => 'Varsayılan depolama',
    'opt_sys_imfs_1' => 'Yerel sunucu',
    'files_max_info' => 'Sunucuya yüklenen dosyanın azami boyutu',
    'files_max_info_1' => 'Sunucuya yüklenen görselin azami boyutu',
    'images_upurl' => 'Siteden yükle (URL):',
    'db_load_a' => 'Yükle',
    'bb_t_up' => 'Dosya ve görselleri sunucuya yükleme',
    'images_uptitle' => 'Resim Yükle',
];
foreach ($forum_upload_lang_fallbacks as $_fup_fb_key => $_fup_fb_val) {
    if (empty($lang[$_fup_fb_key])) {
        $lang[$_fup_fb_key] = $_fup_fb_val;
    }
}
unset($forum_upload_lang_fallbacks, $_fup_fb_key, $_fup_fb_val);

$forum_select_none = isset($lang['forum_upload_select_none']) ? $lang['forum_upload_select_none'] : 'Seçim yapılmadı';
$forum_select_no_results = isset($lang['forum_upload_select_no_results']) ? $lang['forum_upload_select_no_results'] : 'Sonuç bulunamadı';

// 1. Oturum Kontrolü
$_fup_logged = !empty($member_id['user_id']) && intval($member_id['user_id']) > 0;
if (!$_fup_logged) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $lang['forum_ajax_login'], 'status' => 'error', 'message' => $lang['forum_ajax_login']]);
    exit;
}
$is_logged = $_fup_logged;

// 2. Veritabanı tablosu (kurulumda da oluşturulur; dosya yüklenmeden mesaj atılırsa failsafe)
include_once DLEPlugins::Check(ENGINE_DIR . "/modules/forum/editor_helpers.php");
forum_ensure_uploads_table();

// 3. Forum Ayarlarını Çek
$forum_cfg = [];
$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
while ($row = $db->get_row()) {
    $forum_cfg[$row["name"]] = stripslashes($row["value"]);
}

$forum_cfg = array_merge([
    "max_attachment_mb" => "5",
    "allowed_filetypes" => "jpg,jpeg,png,gif,webp,pdf,zip,rar",
    "upload_groups" => "1,2,3,4",
    "upload_min_posts" => "0",
    "upload_max_per_day" => "5",
], $forum_cfg);

// 4. Üye Grubu Yetki Kontrolü
$upload_groups = explode(',', $forum_cfg['upload_groups']);
$user_group_id = intval($member_id['user_group']);
if( !in_array($user_group_id, $upload_groups) ) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>'error','message'=>$lang['forum_ajax_upload_group_denied'], 'error' => $lang['forum_ajax_upload_group_denied']]);
    exit;
}

// 5. Minimum Mesaj Sayısı Kontrolü
$min_posts = intval($forum_cfg['upload_min_posts']);
if( $min_posts > 0 ) {
    $user_posts = intval($member_id['forum_post_count']);
    if( $user_posts < $min_posts ) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status'=>'error','message'=>sprintf($lang['forum_ajax_upload_min_posts'], $min_posts, $user_posts), 'error' => sprintf($lang['forum_ajax_upload_min_posts'], $min_posts, $user_posts)]);
        exit;
    }
}

// 6. Günlük Yükleme Limiti Kontrolü
$max_per_day = intval($forum_cfg['upload_max_per_day']);
if( $max_per_day > 0 ) {
    $uid = intval($member_id['user_id']);
    $today_start = date('Y-m-d 00:00:00');
    $today_end = date('Y-m-d 23:59:59');
    $count_row = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_forum_uploads WHERE user_id = '{$uid}' AND date BETWEEN '{$today_start}' AND '{$today_end}'");
    if( intval($count_row['count']) >= $max_per_day ) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status'=>'error','message'=>sprintf($lang['forum_ajax_upload_limit_reached'], $max_per_day), 'error' => sprintf($lang['forum_ajax_upload_limit_reached'], $max_per_day)]);
        exit;
    }
}

$news_id = isset($_REQUEST['news_id']) ? intval($_REQUEST['news_id']) : 0;
$area = isset($_REQUEST['area']) ? totranslit($_REQUEST['area']) : 'comments';
$author = isset($_REQUEST['author']) ? strip_tags(urldecode((string)$_REQUEST['author'])) : '';
$subaction = isset($_REQUEST['subaction']) ? trim($_REQUEST['subaction']) : '';

function forum_upload_format_quick_response($res) {
	global $config;

	if (!is_array($res)) {
		return ['status' => 'error', 'message' => 'Invalid response', 'error' => 'Invalid response'];
	}

	if (!empty($res['error'])) {
		return ['status' => 'error', 'message' => $res['error'], 'error' => $res['error']];
	}

	$url = !empty($res['url']) ? $res['url'] : (!empty($res['link']) ? $res['link'] : '');

	if (!$url && !empty($res['uploaded_filename'])) {
		$url = $config['http_home_url'] . 'uploads/forum/' . date('Y-m') . '/' . $res['uploaded_filename'];
	}

	$is_image = !empty($res['is_image']);
	$name = !empty($res['display_name']) ? $res['display_name'] : 'dosya';
	$bbcode = $is_image ? '[img]' . $url . '[/img]' : '[url=' . $url . ']' . $name . '[/url]';

	return [
		'status' => 'success',
		'success' => true,
		'url' => $url,
		'link' => $url,
		'bbcode' => $bbcode,
		'is_image' => $is_image,
		'filename' => $name,
	];
}

// 7. Yükleme İşlemi (Subaction == upload)
if ($subaction === 'upload') {
    if( !isset($_REQUEST['user_hash']) OR !$_REQUEST['user_hash'] OR $_REQUEST['user_hash'] != $dle_login_hash ) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $lang['sess_error']]);
        exit;
    }

    include_once (ENGINE_DIR . '/classes/uploads/forum_upload.class.php');

    $make_thumb = isset($_REQUEST['make_thumb']) ? intval($_REQUEST['make_thumb']) : 1;
    $make_watermark = isset($_REQUEST['make_watermark']) ? intval($_REQUEST['make_watermark']) : 1;
    $make_medium = isset($_REQUEST['make_medium']) ? intval($_REQUEST['make_medium']) : 0;
    $hidpi = isset($_REQUEST['hidpi']) ? intval($_REQUEST['hidpi']) : 0;
    $t_size = isset($_REQUEST['t_size']) ? $_REQUEST['t_size'] : $config['max_image'];
    $t_seite = isset($_REQUEST['t_seite']) ? intval($_REQUEST['t_seite']) : intval($config['t_seite']);
    $m_size = isset($_REQUEST['m_size']) ? $_REQUEST['m_size'] : $config['medium_image'];
    $m_seite = isset($_REQUEST['m_seite']) ? intval($_REQUEST['m_seite']) : intval($config['t_seite']);

    $uploader = new ForumFileUploader($area, $news_id, $author, $t_size, $t_seite, $make_thumb, $make_watermark, $m_size, $m_seite, $make_medium, $hidpi);

    $upload_raw = $uploader->FileUpload();
    $upload_res = json_decode($upload_raw, true);

    if (isset($_REQUEST['froala'])) {
        header('Content-Type: application/json; charset=utf-8');
        if (!empty($upload_res['error'])) {
            echo json_encode(['error' => $upload_res['error']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $froala_link = !empty($upload_res['url']) ? $upload_res['url'] : ($upload_res['link'] ?? '');
            echo json_encode(['link' => $froala_link], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    if (!empty($_REQUEST['tinymce']) || !empty($_REQUEST['quick'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(forum_upload_format_quick_response($upload_res), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo $upload_raw;
    exit;
}

// 8. Silme İşlemi (Subaction == deluploads)
if ($subaction === 'deluploads') {
    if( !isset($_REQUEST['user_hash']) OR !$_REQUEST['user_hash'] OR $_REQUEST['user_hash'] != $dle_login_hash ) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $delete_ids = [];
    $check_arrays = ['images', 'files', 'static_files', 'comments_files'];
    foreach ($check_arrays as $arr_name) {
        if (isset($_POST[$arr_name]) && is_array($_POST[$arr_name])) {
            foreach ($_POST[$arr_name] as $val) {
                if (is_numeric($val)) {
                    $delete_ids[] = intval($val);
                } else {
                    $val_safe = $db->safesql(trim($val));
                    $row = $db->super_query("SELECT id FROM " . PREFIX . "_forum_uploads WHERE filepath = '{$val_safe}' OR filepath = '/{$val_safe}'");
                    if ($row['id']) {
                        $delete_ids[] = intval($row['id']);
                    }
                }
            }
        }
    }

    if (count($delete_ids) > 0) {
        $ids_str = implode(',', $delete_ids);
        $db->query("SELECT * FROM " . PREFIX . "_forum_uploads WHERE id IN ({$ids_str})");
        $files_to_delete = [];
        while ($row = $db->get_row()) {
            $files_to_delete[] = $row;
        }

        // DLEFiles modülünü başlat
        DLEFiles::init();

        foreach ($files_to_delete as $file) {
            $file_id = intval($file['id']);
            $file_user = intval($file['user_id']);
            $is_admin = ($member_id['user_group'] <= 2);

            if ($is_admin || $file_user === intval($member_id['user_id'])) {
                $db->query("DELETE FROM " . PREFIX . "_forum_uploads WHERE id = '{$file_id}'");

                $filepath = ltrim($file['filepath'], '/');
                DLEFiles::Delete($filepath, $file['driver']);

                // Thumb & Medium temizle
                $file_parts = pathinfo($filepath);
                $thumb_path = $file_parts['dirname'] . '/thumbs/' . $file_parts['basename'];
                $medium_path = $file_parts['dirname'] . '/medium/' . $file_parts['basename'];

                DLEFiles::Delete($thumb_path, $file['driver']);
                DLEFiles::Delete($medium_path, $file['driver']);

                // HiDPI temizle
                $hidpi_name = $file_parts['filename'] . '@x2.' . $file_parts['extension'];
                $hidpi_path = $file_parts['dirname'] . '/' . $hidpi_name;
                $hidpi_thumb_path = $file_parts['dirname'] . '/thumbs/' . $hidpi_name;
                $hidpi_medium_path = $file_parts['dirname'] . '/medium/' . $hidpi_name;

                DLEFiles::Delete($hidpi_path, $file['driver']);
                DLEFiles::Delete($hidpi_thumb_path, $file['driver']);
                DLEFiles::Delete($hidpi_medium_path, $file['driver']);
            }
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success']);
    exit;
}

// 9. Listeleme ve Arayüz Raporlama (Subaction boş ise)
if (empty($subaction)) {
    $uploaded_list = [];
    $uid = intval($member_id['user_id']);

    if ($news_id > 0) {
        $db->query("SELECT * FROM " . PREFIX . "_forum_uploads WHERE post_id = '{$news_id}' ORDER BY id DESC");
    } else {
        $two_hours_ago = date('Y-m-d H:i:s', time() - 7200);
        $db->query("SELECT * FROM " . PREFIX . "_forum_uploads WHERE user_id = '{$uid}' AND post_id = 0 AND date >= '{$two_hours_ago}' ORDER BY id DESC");
    }

    $images_count = 0;
    $files_count = 0;
    $images_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif', 'heic'];
    $allowed_video = ["mp4", "mp3", "m4v", "m4a", "mov", "webm", "m3u8", "mkv", "flac", "aac", "ogg"];

    while ($row = $db->get_row()) {
        $file_ext = strtolower(pathinfo($row['filename'], PATHINFO_EXTENSION));
        if (!$file_ext) {
            $file_ext = strtolower(pathinfo($row['filepath'], PATHINFO_EXTENSION));
        }

        if (in_array($file_ext, $images_exts)) {
            $images_count++;

            // Yüklenen resmin detaylarını get_uploaded_image_info yardımıyla çöz
            $clean_path = str_ireplace('/uploads/forum/', '', $row['filepath']);
            $clean_path = ltrim($clean_path, '/');
            $image = get_uploaded_image_info($clean_path, 'forum', true);

            $img_url = $image->url;
            $size = $image->size;
            $dimension = $image->dimension;
            if ($size) $size = "({$size})";

            if ($image->medium) {
                $img_url = $image->medium;
                $medium_data = "yes";
            } else {
                $medium_data = "no";
            }

            if ($image->thumb) {
                $img_url = $image->thumb;
                $thumb_data = "yes";
            } else {
                $thumb_data = "no";
            }

            if ($image->hidpi) {
                $hidpi_data = " data-hidpi=\"{$image->hidpi}\"";
                $hidpi_url = str_replace($image->name, $image->hidpi, $image->url);
                $hidpi_url = " data-srcset=\"{$hidpi_url} 2x\"";
            } else {
                $hidpi_data = '';
                $hidpi_url = '';
            }

            $file_name = explode("_", $image->name);
            if (count($file_name) > 1 && strlen($file_name[0]) == 10) unset($file_name[0]);
            $file_name = implode("_", $file_name);
            $base_name = pathinfo($file_name, PATHINFO_FILENAME);
            $file_type = explode(".", $file_name);
            $file_type = totranslit(end($file_type));

            $uploaded_list[] = <<<HTML
<div class="file-preview-card" data-type="image" data-area="images" data-deleteid="{$row['id']}" data-url="{$image->url}" data-path="{$image->path}" data-thumb="{$thumb_data}" data-medium="{$medium_data}"{$hidpi_data}>
	<div class="active-ribbon"><span><i class="mediaupload-icon mediaupload-icon-ok"></i></span></div>
	<div class="file-content">
		<div class="file-ext">{$file_type}</div>
		<img src="{$img_url}" class="file-preview-image">
	</div>
	<div class="file-footer">
		<div class="file-footer-caption">
			<div class="file-caption-info" rel="tooltip" title="{$image->name}">{$base_name}</div>
			<div class="file-size-info">{$dimension} {$size}</div>
		</div>
		<div class="file-footer-bottom">
			<div class="file-preview">
				<a href="{$image->url}"{$hidpi_url} data-highslide="single" target="_blank" rel="tooltip" title="{$lang['up_im_expand']}"><i class="mediaupload-icon mediaupload-icon-zoom"></i></a>
				<a class="clipboard-copy-link" href="#" rel="tooltip" title="{$lang['up_im_copy']}"><i class="mediaupload-icon mediaupload-icon-copy"></i></a>
			</div>
			<div class="file-delete"><a class="file-delete-link" href="#"><i class="mediaupload-icon mediaupload-icon-trash"></i></a></div>
		</div>
	</div>
</div>
HTML;
        } else {
            $files_count++;

            $data_url = $download_url = $config['http_home_url'] . ltrim($row['filepath'], '/');
            $size = formatsize($row['filesize']);
            $file_type = explode(".", $row['filename']);
            $file_type = totranslit(end($file_type));
            $base_name = pathinfo($row['filename'], PATHINFO_FILENAME);
            $file_play = "";

            if (in_array($file_type, $allowed_video)) {
                if (in_array($file_type, ['mp3', 'flac', 'aac', 'ogg'])) {
                    $file_play = "audio";
                } else {
                    $file_play = "video";
                }
            }

            $file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-43.755 -32.246)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#f9f9f9" stroke="#cecece" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#cecece"></path></g></svg>';
            $b_color = 'transparent';

            if (in_array($file_type, ['doc', 'docx'])) {
                $file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-588.829 -297.644)"><g transform="translate(545.074 265.397)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#2a60ae" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#2a60ae"></path></g><g transform="translate(596.025 337.278)"><rect width="17.063" height="3.707" rx="1.853" transform="translate(0 5.926)" fill="#2a60ae"></rect><rect width="11.25" height="3.707" rx="1.853" transform="translate(0 11.851)" fill="#2a60ae"></rect><rect width="30.474" height="3.707" rx="1.853" fill="#2a60ae"></rect></g><path d="M3.42,0A.749.749,0,0,1,2.9-.181.723.723,0,0,1,2.66-.627L.627-12.787A.265.265,0,0,1,.608-12.9a.381.381,0,0,1,.124-.276.381.381,0,0,1,.275-.124H3.5q.551,0,.608.437L5.263-5.681,6.574-9.842a.62.62,0,0,1,.627-.513H8.626a.62.62,0,0,1,.627.513L10.564-5.7l1.178-7.163a.51.51,0,0,1,.171-.332.676.676,0,0,1,.418-.1H14.82a.372.372,0,0,1,.285.124.4.4,0,0,1,.114.276v.114L13.186-.627a.705.705,0,0,1-.247.446A.757.757,0,0,1,12.426,0H10.507a.69.69,0,0,1-.475-.152A.742.742,0,0,1,9.8-.494L7.923-5.757,6.042-.494A.908.908,0,0,1,5.8-.152.69.69,0,0,1,5.32,0Z" transform="translate(597 323)" fill="#2a60ae"></path></g></svg>';
                $b_color = '#e9eff7';
            }
            if (in_array($file_type, ['ppt', 'pptx'])) {
                $file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-236.224 -502.325)"><g transform="translate(192.469 470.079)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#c64122" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#c64122"></path></g><path d="M1.767,0a.456.456,0,0,1-.332-.143.456.456,0,0,1-.143-.332V-12.806a.5.5,0,0,1,.133-.352.447.447,0,0,1,.342-.142H7.144a6.059,6.059,0,0,1,3.876,1.121,3.953,3.953,0,0,1,1.406,3.287,3.818,3.818,0,0,1-1.406,3.24A6.25,6.25,0,0,1,7.144-4.579H4.978v4.1a.472.472,0,0,1-.133.332A.447.447,0,0,1,4.5,0ZM7.049-7.3A1.747,1.747,0,0,0,8.275-7.7a1.554,1.554,0,0,0,.446-1.206,1.726,1.726,0,0,0-.408-1.2,1.613,1.613,0,0,0-1.264-.456H4.921V-7.3Z" transform="translate(245 527)" fill="#c64122"></path><g transform="translate(4 9)"><rect width="21.546" height="4.463" rx="2.232" transform="translate(249.483 542.098)" fill="#c64122"></rect><path d="M0,10V.03C.245.01.491,0,.74,0A9.443,9.443,0,0,1,10,9.615q0,.193-.008.385Z" transform="translate(261.791 518.347)" fill="#c64122" opacity="0.42"></path><path d="M10.5,21A10.519,10.519,0,0,1,2.8,3.33,10.461,10.461,0,0,1,9.664,0V10.9H21A10.51,10.51,0,0,1,10.5,21Z" transform="translate(250 519.053)" fill="#c64122"></path></g></g></svg>';
                $b_color = '#f9ebe8';
            }
            if (in_array($file_type, ['xls', 'xlsx'])) {
                $file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-410.326 -502.325)"><g transform="translate(366.571 470.079)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#209c61" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#209c61"></path></g><path d="M.589,0A.381.381,0,0,1,.313-.124.381.381,0,0,1,.19-.4.506.506,0,0,1,.247-.627L4.389-6.783.57-12.673A.506.506,0,0,1,.513-12.9a.381.381,0,0,1,.123-.276A.381.381,0,0,1,.912-13.3H3.819a.79.79,0,0,1,.684.418L6.65-9.576l2.223-3.306a.776.776,0,0,1,.665-.418h2.774a.382.382,0,0,1,.276.124.381.381,0,0,1,.123.276.506.506,0,0,1-.057.228L8.8-6.821l4.18,6.194a.506.506,0,0,1,.057.228.382.382,0,0,1-.124.276A.381.381,0,0,1,12.635,0h-3a.759.759,0,0,1-.665-.38L6.536-3.914,4.161-.38A.759.759,0,0,1,3.5,0Z" transform="translate(419 527)" fill="#209c61"></path><g transform="translate(-2.695 2.152)"><g transform="translate(421.695 536.07)"><rect width="6.546" height="4.463" rx="2" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(7.851)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(15.701)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(15.701 6)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(15.701 12)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(7.851 6)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(7.851 12)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(0 6)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(0 12)" fill="#209c61"></rect></g></g></g></svg>';
                $b_color = '#e8f5ef';
            }
            if (in_array($file_type, ['txt'])) {
                $file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-934.326 -297.644)"><g transform="translate(890.571 265.397)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#c6c8db" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#c6c8db"></path></g><g transform="translate(945.655 324.912)"><rect width="32.783" height="4.463" rx="2.232" transform="translate(0 14.27)" fill="#c6c8db"></rect><rect width="32.783" height="4.463" rx="2.232" transform="translate(0 7.135)" fill="#c6c8db"></rect><rect width="32.783" height="4.463" rx="2.232" fill="#c6c8db"></rect></g></g></svg>';
                $b_color = '#f8f8fb';
            }
            if (in_array($file_type, ['pdf'])) {
                $file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-760.79 -297.644)"><g transform="translate(717.035 265.397)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#fa3225" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#fa3225"></path></g><g transform="translate(768.517 337.278)"><rect width="17.063" height="3.707" rx="1.853" transform="translate(0 5.926)" fill="#fa3225"></rect><rect width="11.25" height="3.707" rx="1.853" transform="translate(0 11.851)" fill="#fa3225"></rect><rect width="30.474" height="3.707" rx="1.853" fill="#fa3225"></rect></g><g transform="translate(762.773 294.187)"><path d="M49.9-138.9a7.264,7.264,0,0,1-3.09-3.893c.326-1.339.84-3.372.449-4.646a1.812,1.812,0,0,0-3.459-.492c-.362,1.324-.029,3.191.586,5.572a67.964,67.964,0,0,1-2.953,6.209c-.007,0-.007.007-.014.007-1.961,1.006-5.326,3.22-3.944,4.921a2.249,2.249,0,0,0,1.556.724c1.3,0,2.584-1.3,4.422-4.472a41.242,41.242,0,0,1,5.717-1.679,10.968,10.968,0,0,0,4.632,1.411,1.873,1.873,0,0,0,1.426-3.141C54.216-139.367,51.292-139.085,49.9-138.9Zm-11.137,6.969a10,10,0,0,1,2.526-2.909C39.713-132.326,38.758-131.877,38.758-131.935Zm6.788-15.841c.608,0,.55,2.67.145,3.394C45.329-145.54,45.336-147.776,45.546-147.776Zm-2.034,11.347a33.39,33.39,0,0,0,2.055-4.537,9.373,9.373,0,0,0,2.5,2.953A26.647,26.647,0,0,0,43.513-136.429Zm10.935-.413s-.413.492-3.1-.651C54.266-137.7,54.744-137.037,54.447-136.842Z" transform="translate(-29.503 163.391)" fill="#fa3225"></path></g></g></svg>';
                $b_color = '#ffeae8';
            }

            $uploaded_list[] = <<<HTML
<div class="file-preview-card" data-type="file" data-area="files" data-deleteid="{$row['id']}" data-url="{$data_url}" data-path="{$row['filepath']}" data-play="{$file_play}" data-public="1">
	<div class="active-ribbon"><span><i class="mediaupload-icon mediaupload-icon-ok"></i></span></div>
	<div class="file-content" style="background-color: {$b_color};">
		<div class="file-ext">{$file_type}</div>
		{$file_icon}
	</div>
	<div class="file-footer">
		<div class="file-footer-caption">
			<div class="file-caption-info" rel="tooltip" title="ID: {$row['id']}, {$row['filename']}">{$base_name}</div>
			<div class="file-size-info">({$size})</div>
		</div>
		<div class="file-footer-bottom">
			<div class="file-preview">
				<a href="{$download_url}" class="position-left" rel="tooltip" title="{$lang['plugins_a_3']}" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M7.646 10.854a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 9.293V5.5a.5.5 0 0 0-1 0v3.793L6.354 8.146a.5.5 0 1 0-.708.708z"/><path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/></svg></a>
				<a class="clipboard-copy-link" href="#" rel="tooltip" title="{$lang['up_im_copy']}"><i class="mediaupload-icon mediaupload-icon-copy"></i></a>
			</div>
			<div class="file-delete"><a class="file-delete-link" href="#"><i class="mediaupload-icon mediaupload-icon-trash"></i></a></div>
		</div>
	</div>
</div>
HTML;
        }
    }

    $uploaded_list_str = implode("\n", $uploaded_list);

    $max_file_size = intval($forum_cfg['max_attachment_mb']) * 1024 * 1024;
    $forum_max_size_text = formatsize($max_file_size);
    if (!empty($lang['files_max_info'])) {
        $lang['files_max_info_1'] = $lang['files_max_info'] . '<br>' . $lang['files_max_info_1'] . ' ' . $forum_max_size_text;
    } else {
        $lang['files_max_info_1'] = $lang['files_max_info_1'] . ' ' . $forum_max_size_text;
    }
    $image_ext = "jpg,jpeg,png,gif,webp,bmp,avif,heic";
    $allowed_filetypes = explode(',', strtolower($forum_cfg['allowed_filetypes']));
    $allowed_files = [];
    foreach ($allowed_filetypes as $ext) {
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif', 'heic'])) {
            $allowed_files[] = $ext;
        }
    }
    $file_ext = count($allowed_files) > 0 ? ',{title : "Another files", extensions : "' . implode(",", $allowed_files) . '"}' : '';

    $max_images_allowed = -1;
    $max_files_allowed = -1;

    $upload_param = "";
    $ftp_input = "";
    $storage_input = "";

    if ($user_group[$member_id['user_group']]['allow_image_size']) {
        $t_seite_selected = ['0' => '', '1' => '', '2' => ''];
        $t_seite_selected[intval($config['t_seite'])] = "selected";

        if ($config['max_image']) {
            $upload_param .= <<<HTML
<div class="checkbox"><label class="checkbox-inline margin-left form-check-label"><input class="icheck form-check-input" type="checkbox" name="make_thumb" id="make_thumb" value="1" checked="checked"><span>{$lang['images_ath']}</span></label><input class="classic margin-left" type="text" name="t_size" id="t_size" style="width:6.25rem;" autocomplete="off" value="{$config['max_image']}"><select name="t_seite" id="t_seite" class="uniform"><option value="0" {$t_seite_selected[0]}>{$lang['upload_t_seite_1']}</option><option value="1" {$t_seite_selected[1]}>{$lang['upload_t_seite_2']}</option><option value="2" {$t_seite_selected[2]}>{$lang['upload_t_seite_3']}</option></select></div>
HTML;
        }

        if ($config['medium_image']) {
            $upload_param .= <<<HTML
<div class="checkbox"><label class="checkbox-inline margin-left form-check-label"><input class="icheck form-check-input" type="checkbox" name="make_medium" id="make_medium" value="1" checked="checked"><span>{$lang['images_amh']}</span></label><input class="classic margin-left" type="text" name="m_size" id="m_size" style="width:6.25rem;" autocomplete="off" value="{$config['medium_image']}"><select name="m_seite" id="m_seite" class="uniform"><option value="0" {$t_seite_selected[0]}>{$lang['upload_t_seite_1']}</option><option value="1" {$t_seite_selected[1]}>{$lang['upload_t_seite_2']}</option><option value="2" {$t_seite_selected[2]}>{$lang['upload_t_seite_3']}</option></select></div>
HTML;
        }

        if ($config['allow_watermark']) {
            $upload_param .= "<div class=\"checkbox\"><label class=\"checkbox-inline margin-left form-check-label\"><input class=\"icheck form-check-input\" type=\"checkbox\" name=\"make_watermark\" value=\"yes\" id=\"make_watermark\" checked=\"checked\"><span>{$lang['images_water']}</span></label></div>";
        }

        $upload_param .= "<div class=\"checkbox\"><label class=\"checkbox-inline margin-left form-check-label\"><input class=\"icheck form-check-input\" type=\"checkbox\" name=\"hidpi\" value=\"1\" id=\"hidpi\"><span>{$lang['hidpi_upl']}</span></label></div>";
    }

    if ($user_group[$member_id['user_group']]['allow_public_file_upload']) {
        $upload_param .= "<div class=\"checkbox\"><label class=\"checkbox-inline margin-left form-check-label\"><input class=\"icheck form-check-input\" type=\"checkbox\" name=\"public_file\" value=\"1\" id=\"public_file\"><span>{$lang['public_file_upl']}</span></label></div>";
    }

    if (intval($member_id['user_group']) === 1) {
        $locate = "FTP /uploads/forum/";
        if (DLEFiles::getDefaultStorage()) {
            $locate = "Remote /forum/";
        }

        $ftp_input = <<<HTML
	<div class="mediaupload-row">
		<div class="mediaupload-col1">
			{$locate}
		</div>
		<div class="mediaupload-col2">
			<input class="classic" type="text" id="ftpurl" name="ftpurl" autocomplete="off" style="width:100%;">
		</div>
		<div class="mediaupload-col3">
			<button onclick="upload_from_url('ftp'); return false;">{$lang['db_load_a']}</button>
		</div>
	</div>
	<div id="upload-viaftp-status"></div>
HTML;
    }

    if ($user_group[$member_id['user_group']]['allow_change_storage']) {
        $storages_list = DLEFiles::getStorages();

        if (count($storages_list)) {
            $storages_list = ['-1' => $lang['storage_default'], '0' => $lang['opt_sys_imfs_1']] + $storages_list;
            $storages_select = "<select class=\"uniform\" name=\"upload_driver\" id=\"upload_driver\">\r\n";

            foreach ($storages_list as $value => $description) {
                $storages_select .= "<option value=\"{$value}\"";
                if ($value == '-1') {
                    $storages_select .= " selected ";
                }
                $storages_select .= ">{$description}</option>\n";
            }

            $storages_select .= "</select>";

            $storage_input = <<<HTML
	<div class="mediaupload-row">
		<div class="mediaupload-col1">
			<div class="margin-left">{$lang['storage_upload']}</div>
		</div>
		<div class="mediaupload-col2">
			{$storages_select}
		</div>
	</div>
HTML;
        }
    }

    $chunk_size_mb = (isset($config['file_chunk_size']) && floatval($config['file_chunk_size']) > 0)
        ? floatval($config['file_chunk_size'])
        : 1.5;

    $im_show = !empty($uploaded_list_str) ? "tabClick(0);" : "";
    $rtl_prefix = ($lang['direction'] == 'rtl') ? '_rtl' : '';

    header('Content-Type: text/html; charset=utf-8');

    echo <<<HTML
<div class="tabs">
	<div class="tabsitems">
	  <ul>
		<li><a href='#' id="link1" onclick="tabClick(2); return false;" title='{$lang['media_upload_st']}' class="current" ><span>{$lang['media_upload_st']}</span></a></li>
		<li><a href='#' id="link2" onclick="tabClick(0); return false;" title='{$lang['images_iln']}'><span>{$lang['images_iln']}</span></a></li>
	  </ul>
	</div>
	<div id="check-all-box">
	  <label class="form-check-label"><input class="icheck form-check-input" type="checkbox" name="check_all" id="check_all" value="1"  onchange="check_all(this); return false;"><span class="position-right">{$lang['edit_selall']}</span></label>
	</div>
</div>
<div style="clear: both;"></div>
<div class="mediaupload-box">
<div id="stmode" class="file-upload-box" >
	<div class="media-upload-button-area">
		<div id="file-uploader"></div>
	</div>
	<div class="mediaupload-row">
		<div class="mediaupload-col1">
			{$lang['images_upurl']}
		</div>
		<div class="mediaupload-col2">
			<input class="classic" type="text" id="copyurl" name="copyurl" autocomplete="off" style="width:100%;">
		</div>
		<div class="mediaupload-col3">
			<button onclick="upload_from_url('url'); return false;">{$lang['db_load_a']}</button>
		</div>
	</div>
	<div id="upload-viaurl-status"></div>
	{$ftp_input}
	{$storage_input}
	<div class="upload-options">{$upload_param}</div>
	<div class="upload-restriction">{$lang['files_max_info_1']}</div>
</div>
<div id="cont1" class="file-preview-box file-can-all-selected" style="display:none;">{$uploaded_list_str}</div>
<div id="cont2" style="display:none;"></div>

<div id="mediaupload-buttonpane" style="display:none;">
	<div class="mediaupload-insert-params" style="display:none;">
		<div class="mediaupload-image-title" style="display:none;">
			<div class="insert-imagetitle"><input id="imagetitle" name="imagetitle" type="text" value="" placeholder="{$lang['media_upload_title']}" class="classic" autocomplete="off" style="width:100%;"></div>
			<div class="insert-properties"><span class="margin-left">{$lang['images_align']}</span><select id="imagealign" name="imagealign" class="dropup uniform" data-width="auto" data-dropdown-align-right="true" data-dropup-auto="false">
				  <option value="none">{$lang['opt_sys_no']}</option>
				  <option value="left">{$lang['images_left']}</option>
				  <option value="right">{$lang['images_right']}</option>
				  <option value="center">{$lang['images_center']}</option>
				</select>
		</div>
		</div>
		<div class="mediaupload-thumbs-params" style="display:none;"><span class="mediaupload-insert-descr">{$lang['media_upload_b1']}</span>
			<label id="mediaupload-thumb" class="radio-inline form-check-label" style="display:none;"><input class="icheck form-check-input" type="radio" name="thumbimg" id="thumbimg" value="1"><span>{$lang['media_upload_ip2']}</span></label>
			<label id="mediaupload-medium" class="radio-inline form-check-label" style="display:none;"><input class="icheck form-check-input" type="radio" name="thumbimg" id="thumbimg1" value="2"><span>{$lang['media_upload_ip6']}</span></label>
			<label id="mediaupload-original" class="radio-inline margin-left form-check-label" style="display:none;"><input class="icheck form-check-input" type="radio" name="thumbimg" id="thumbimg2" value="0"><span>{$lang['media_upload_ip3']}</span></label>
			<label id="mediaupload-enlarge" class="checkbox-inline form-check-label" style="display:none;"><input class="icheck form-check-input" type="checkbox" name="insertoriginal" id="insertoriginal" value="1" checked="checked"><span>{$lang['media_upload_ip7']}</span></label>
		</div>
		
		<div class="mediaupload-file-params" style="display:none;"><span class="mediaupload-insert-descr">{$lang['media_upload_b2']}</span>
			<label class="radio-inline form-check-label"><input id="attachfordownload" class="icheck form-check-input" type="radio" name="filemode" value="1"><span>{$lang['media_upload_ip4']}</span></label>
			<label class="radio-inline form-check-label"><input id="attachforplayer" class="icheck form-check-input" type="radio" name="filemode" value="0" checked="checked"><span>{$lang['media_upload_ip5']}</span></label>
		</div>
		
	</div>
	<div class="mediaupload-footer ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
		<div class="ui-dialog-buttonset">
		<button type="button" class="ui-button" onclick="$('#mediaupload').dialog('close'); return false;">{$lang['p_cancel']}</button>
		<button id='mediaupload-insert' type="button" onclick="media_insert_selected(); return false;" class="ui-button bg-teal" style="display:none;">{$lang['images_all_insert']}</button>
		<button id='mediaupload-delete' type="button" onclick="media_delete_selected(); return false;" class="ui-button" style="display:none;">{$lang['images_del']}</button>
		</div>
	</div>
</div>
</div>

<script>
if (typeof jQuery !== 'undefined' && typeof jQuery.getCachedScript !== 'function') {
	jQuery.getCachedScript = function(url, options) {
		options = jQuery.extend(options || {}, { dataType: 'script', cache: true, url: url });
		return jQuery.ajax(options);
	};
}

jQuery(function($){
	setTimeout(function() {
		initmediauploadpopup();
	}, 1);
});

var plupoad_ui_plugin_loaded = true;
var max_images_allowed = {$max_images_allowed};
var max_files_allowed = {$max_files_allowed};
var allways_bbimages = '0';

function initmediauploadpopup() {
	LoadDLEFont();
	RestoreDefaultUploadOptions();

	if (typeof $.fn.selectpicker === "function") {
		$('.dle-popup-mediaupload select.uniform').selectpicker({
			noneSelectedText: '{$forum_select_none}',
			noneResultsText: '{$forum_select_no_results}',
			countSelectedText: '{0} seçildi'
		});
		$('.dle-popup-mediaupload select.uniform').selectpicker('refresh');
	}
	if (typeof $.fn.tooltip === "function") {
		$('[rel=tooltip]').tooltip({ container: 'body' });
	}

	$(document).off("click", '.file-preview-card .clipboard-copy-link');
	$(document).off("click", '.file-preview-card .file-delete-link');
	$(document).on("click", '.file-preview-card .file-delete-link',	function(e){
		e.preventDefault();
		media_delete_file( $(this).closest('.file-preview-card') );
		return false;
	});

	$(document).on("click", '.file-preview-card .clipboard-copy-link',	function(e){
		e.preventDefault();
		document.activeElement.blur();
		var box = $(this).closest('.file-preview-card');
		var copytext = box.data('url');
		DLEcopyToClipboard(copytext);
		return false;
	});	

	$(document).off("click", '.file-preview-card .file-content:not(.select-disable)');
	$(document).on("click", '.file-preview-card .file-content:not(.select-disable)', function(e){
		e.preventDefault();
		$(this).parent().toggleClass("active");
		insert_props_panel();
		return false;
	});

	if (typeof $.fn.plupload !== "function" ) {
		$.getCachedScript(dle_root + 'public/fileuploader/plupload/plupload.full.min.js?v={$config['cache_id']}').done(function() {
			$.getCachedScript(dle_root + 'public/fileuploader/plupload/i18n/{$lang['language_code']}.js?v={$config['cache_id']}').done(function() {
				loadmediauploader();
			});
		});
	} else {
		loadmediauploader();
	}

	if (typeof Fancybox == "undefined" ) {
		$.getCachedScript( dle_root + 'public/fancybox/fancybox.js?v={$config['cache_id']}' );
	}
};

function RestoreDefaultUploadOptions() {
	LoadUploadOptions();
	$('#mediaupload .upload-options select, #mediaupload .upload-options input[type="checkbox"], #mediaupload .upload-options input[type="text"], #upload_driver').off('change.forumupload').on('change.forumupload', function() {
		SaveUploadOptions( $(this) );
	});
	if (typeof $.fn.selectpicker === "function") {
		$('.dle-popup-mediaupload select.uniform').selectpicker('refresh');
	}
};

function LoadUploadOptions() {
	try {
		var savedData = localStorage.getItem('dle_upload_options');
		if (savedData) {
			try {
				var SavedEL = JSON.parse(savedData);
				for (var key in SavedEL) {
					if (SavedEL.hasOwnProperty(key)) {
						if (SavedEL[key][0] == 'checkbox') {
							$('#' + key).prop('checked', SavedEL[key][1]);
						} else {
							$('#' + key).val(SavedEL[key][1]);
						}
					}
				}
			} catch (e) {}
		}
	} catch (e) {}
};

function SaveUploadOptions(el) {
	var value = null;
	var SavedEL = {};
	try {
		var savedData = localStorage.getItem('dle_upload_options');
		if (savedData) {
			try {
				SavedEL = JSON.parse(savedData);
			} catch (e) {
				SavedEL = {};
			}
		}
	} catch (e) {}
	if (el.attr('type') === 'checkbox') {
		value = el.is(':checked');
	} else {
		value = el.val();
	}
	var key = el.attr('id');
	SavedEL[key] = [el.attr('type'), value];
	try {
		localStorage.setItem('dle_upload_options', JSON.stringify(SavedEL));
	} catch (e) {}
};

function LoadDLEFont() {
	var cssHref = dle_root + 'public/fileuploader/fileuploader{$rtl_prefix}.css?v={$config['cache_id']}';
	if (!$('link[href*="fileuploader{$rtl_prefix}.css"]').length) {
		$('head').append('<link rel="stylesheet" type="text/css" href="' + cssHref + '">');
	}
	const elem = document.createElement('i');
	elem.className = 'mediaupload-icon';
	elem.style.position = 'absolute';
	elem.style.left = '-9999px';
	document.body.appendChild(elem);
	document.body.removeChild(elem);
};

function DLEcopyToClipboard(text) {
   try {
		const elem = document.createElement('textarea');
		elem.value = text;
		elem.setAttribute('readonly', '');
		elem.style.position = 'absolute';
		elem.style.left = '-9999px';
		document.body.appendChild(elem);
		elem.select();
		document.execCommand('copy');
		document.body.removeChild(elem);
		DLEPush.info('{$lang['up_im_copy1']}', '', 2000);
  } catch (err) {
	console.log('Unable to copy');
  }
};

var totaluploaded = 0;

function forum_sync_upload_params(uploader) {
	uploader.settings.multipart_params['t_size'] = $('#t_size').val();
	uploader.settings.multipart_params['t_seite'] = $('#t_seite').val();
	uploader.settings.multipart_params['make_thumb'] = $("#make_thumb").is(":checked") ? 1 : 0;
	uploader.settings.multipart_params['m_size'] = $('#m_size').val();
	uploader.settings.multipart_params['m_seite'] = $('#m_seite').val();
	uploader.settings.multipart_params['make_medium'] = $("#make_medium").is(":checked") ? 1 : 0;
	uploader.settings.multipart_params['make_watermark'] = $("#make_watermark").is(":checked") ? 1 : 0;
	uploader.settings.multipart_params['public_file'] = $("#public_file").is(":checked") ? 1 : 0;
	uploader.settings.multipart_params['hidpi'] = $("#hidpi").is(":checked") ? 1 : 0;
	if ($('#upload_driver').length) {
		uploader.settings.multipart_params['upload_driver'] = $('#upload_driver').val();
	}
}

function loadmediauploader() {
	$("#file-uploader").plupload({
		runtimes: 'html5',
		url: dle_root + "index.php?controller=ajax&mod=forum_upload",
		file_data_name: "qqfile",
		max_file_size: '{$max_file_size}',
		chunk_size: '{$chunk_size_mb}mb',
		filters: [
			{title : "Allowed files", extensions : "{$image_ext}"}{$file_ext}
		],
		rename: true,
		sortable: true,
		dragdrop: true,
		views: {
			list: true,
			thumbs: true,
			remember: true,
			active: 'list'
		},
		multipart_params: {"subaction" : "upload", "news_id" : "{$news_id}", "area" : "{$area}", "author" : "{$author}", "user_hash" : dle_login_hash},
		ready: function(event, args) {
			{$im_show}
		},
		started: function(event, args) {
			forum_sync_upload_params(args.up);
		},
		selected: function(event, args) {
			var uploader = args.up;
			var image_extensions = ["gif", "jpg", "png", "jpeg", "webp", "bmp", "avif", "heic"];
			var images_each_count = 0;
			var files_each_count = 0;
			var count_errors = false;

			forum_sync_upload_params(uploader);
			$('.plupload_container').addClass('plupload_files_selected');

			plupload.each(uploader.files, function(file) {
				var fileext = file.name.split('.').pop().toLowerCase();
				if (jQuery.inArray(fileext, image_extensions) >= 0) {
					images_each_count++;
					if (max_images_allowed > -1 && images_each_count > max_images_allowed) {
						count_errors = true;
						setTimeout(function() { uploader.removeFile(file); }, 100);
					}
				} else {
					files_each_count++;
					if (max_files_allowed > -1 && files_each_count > max_files_allowed) {
						count_errors = true;
						setTimeout(function() { uploader.removeFile(file); }, 100);
					}
				}
			});

			if (count_errors) {
				$('#file-uploader').plupload('notify', 'error', "{$lang['error_max_queue']}");
			}
			$('#file-uploader').plupload('refresh');
		},
		removed: function(event, args) {
			if (args.up.files.length) {
				$('.plupload_container').addClass('plupload_files_selected');
			} else {
				$('.plupload_container').removeClass('plupload_files_selected');
			}
			$('#file-uploader').plupload('refresh');
		},
		uploaded: function(event, args) {
			var response = '';
			try {
				response = JSON.parse(args.result.response);
			} catch (e) {}

			var status = args.result.status;
			var file = args.file;

			if (status == 200) {
				if (response.success) {
					var returnbox = response.returnbox;
					returnbox = returnbox.replace(/&lt;/g, "<");
					returnbox = returnbox.replace(/&gt;/g, ">");
					returnbox = returnbox.replace(/&amp;/g, "&");

					if (response.remote_error) {
						$('#file-uploader').plupload('notify', 'info', "{$lang['media_upload_st6']} <b>" + file.name + "</b> {$lang['media_upload_st9']} <br><span style=\"color:red;\">{$lang['remote_error']}<br>" + response.remote_error + "</span><br>{$lang['remote_error_1']}");
					}
					if (response.tinypng_error) {
						$('#file-uploader').plupload('notify', 'info', "{$lang['media_upload_st6']} <b>" + file.name + "</b> {$lang['media_upload_st9']} <br><span style=\"color:red;\">{$lang['tinyapi_error']}<br>" + response.tinypng_error + "</span>");
					}

					$('#cont1').append(returnbox);
					setTimeout(function() { $('#' + file.id).fadeOut("slow"); }, 500);
					totaluploaded++;
				} else if (response.error) {
					$('#file-uploader').plupload('notify', 'error', "{$lang['media_upload_st6']} <b>" + file.name + "</b> {$lang['media_upload_st10']} <br><span style=\"color:red;\">" + response.error + "</span>");
				} else {
					args.result.response = args.result.response.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
					$('#file-uploader').plupload('notify', 'error', "{$lang['media_upload_st6']} <b>" + file.name + "</b> {$lang['media_upload_st10']} <br><span style=\"color:red;\">" + args.result.response + "</span>");
				}
			} else {
				$('#file-uploader').plupload('notify', 'error', "{$lang['media_upload_st6']} <b>" + file.name + "</b> {$lang['media_upload_st10']} <br><span style=\"color:red;\">HTTP: " + status + "</span>");
			}
		},
		complete: function() {
			$('.plupload_container').removeClass('plupload_files_selected');
			$('#file-uploader').plupload('refresh');
			$('#file-uploader').plupload('clearQueue');
			if (totaluploaded) {
				if (typeof $.fn.tooltip === "function") {
					$('[rel=tooltip]').tooltip({ container: 'body' });
				}
				tabClick(0);
				totaluploaded = 0;
			}
		}
	});
};

function check_all( obj ) {
	if(obj && obj.checked) {
		$('.file-can-all-selected .file-preview-card').addClass("active");
	} else {
		$('.file-preview-card').removeClass("active");
		$("#check_all").prop('checked', false);
	}
	insert_props_panel();
	return false;
}

function insert_props_panel() {
	if( $('.file-preview-card.active').length ) {
		var backup_state = $('.mediaupload-insert-params').outerHeight();
		$('#mediaupload-insert').show();
		$('#mediaupload-delete').show();
		var show = false;
		$('.mediaupload-image-title').hide();
		$('.mediaupload-thumbs-params').hide();
		$('#mediaupload-thumb').hide();
		$('#mediaupload-medium').hide();
		$('#mediaupload-original').hide();
		$('#mediaupload-enlarge').hide();
		$('.mediaupload-file-params').hide();

		$('.file-preview-card.active').each(function(){
			if($(this).data('type') == 'image'){
				show = true;
				$('.mediaupload-image-title').show();
				if( $(this).data('thumb') == 'yes' || $(this).data('medium') == 'yes' ) {
					$('.mediaupload-thumbs-params').show();
					$('#mediaupload-original').show();
					$('#mediaupload-enlarge').show();
				}
				if( $(this).data('thumb') == 'yes' ) {
					$('#mediaupload-thumb').show();
					$('#thumbimg').prop('checked', true);
				}
				if( $(this).data('medium') == 'yes' ) {
					$('#mediaupload-medium').show();
					if( !$('#thumbimg').prop('checked') || ($(this).data('thumb') != 'yes' && !$('#mediaupload-thumb').is(':visible')) ) {
						$('#thumbimg1').prop('checked', true);
					}
				}
			} else {
				if ( $(this).data('play') == "video" || $(this).data('play') == "audio" ) {
					show = true;
					$('.mediaupload-file-params').show();
				}
			}
		});
			
		if( $('.mediaupload-insert-params').is(':visible') ) {
			var current_state = $('.mediaupload-insert-params').outerHeight();
			if(current_state != backup_state) {
				current_state = current_state - backup_state;
				$('.mediaupload-body').height( $('.mediaupload-body').height() - current_state );
			}
		} else {
			if( show ) {
				$('.mediaupload-insert-params').show();
				$('.mediaupload-body').height( $('.mediaupload-body').height() - $('.mediaupload-insert-params').outerHeight() );				
			}
		}
	} else {
		$('#mediaupload-insert').hide();
		$('#mediaupload-delete').hide();
		if( $('.mediaupload-insert-params').is(':visible') ) {		
				$('.mediaupload-body').height( $('.mediaupload-body').height() + $('.mediaupload-insert-params').outerHeight() );
				$('.mediaupload-insert-params').hide();
		}
	}
	return false;
}

function tabClick(n) {
	if (n == 0) {
		$("#cont2").hide();
		$("#stmode").hide();
		$("#linkbox").hide();
		$("#cont1").fadeTo('slow', 1);
		$("#link2").addClass("current");
		$("#link1").removeClass("current");
		$("#link3").removeClass("current");
		$("#check-all-box").show();
	}
	if (n == 1) {
		$("#stmode").hide();
		$("#cont1").hide();
		$("#linkbox").hide();
		$("#cont2").fadeTo('slow', 1);
		$("#link3").addClass("current");
		$("#link1").removeClass("current");
		$("#link2").removeClass("current");
		$("#check-all-box").hide();
	}
	if (n == 2) {
		$("#cont2").hide();
		$("#cont1").hide();
		$("#linkbox").hide();
		$("#stmode").fadeTo('slow', 1);
		$("#link1").addClass("current");
		$("#link2").removeClass("current");
		$("#link3").removeClass("current");
		$("#check-all-box").hide();
	}
}

function media_insert_selected() {
    var frm = document.delimages;
	var allways_bbimages = '{$config['bbimages_in_wysiwyg']}';
	var links = new Array();
	var align = $('#imagealign').val();
	var content = '';
	var t = 0;
	var url = '';
	var hidpi_name = '';
	var have_images = false;

	if( $('.file-preview-card.active').length ) {
		$('.file-preview-card.active').each(function() {
			if($(this).data('type') == 'image'){
				have_images = true;
				url = $(this).data('url');
				hidpi_name = '';

				if( $(this).data('hidpi') ) {
					hidpi_name = $(this).data('hidpi');
				}

				if ( !$('#insertoriginal').prop('checked') ) {
					if( $('#thumbimg').prop('checked') || $('#thumbimg1').prop('checked') ) {
						var folder='';
						if($(this).data('thumb') == "yes") {
							folder='thumbs';
						}
						if( $('#thumbimg').prop('checked') ) {
							folder="thumbs";
						} 
						if( $('#thumbimg1').prop('checked') && $(this).data('medium') == "yes" ) {
							folder="medium";
						}
						if(folder != '') {
							url = url.split('/');
							var filename = url.pop();
							url.push(folder);
							url.push(filename);
							url = url.join('/');
						}
					}
					links[t] = buildimage (url, hidpi_name);
				} else {
					if ( $(this).data('thumb') == "yes" || $(this).data('medium') == "yes" ) {
						if( $('#thumbimg').prop('checked') ) {
							if($(this).data('thumb') == "yes") {
								links[t] = buildthumb (url, 'thumb', hidpi_name);
							} else {
								links[t] = buildthumb (url, 'medium', hidpi_name);
							}
						} else if( $('#thumbimg1').prop('checked') ) {
							if($(this).data('medium') == "yes" ) {
								links[t] = buildthumb (url, 'medium', hidpi_name);
							} else {
								links[t] = buildthumb (url, 'thumb', hidpi_name);
							}
						} else {
							links[t] = buildimage ( url, hidpi_name );
						}
					} else {
						links[t] = buildimage ( url, hidpi_name );
					}
				}
			} else {
				if ( ($(this).data('play') == "video" || $(this).data('play') == "audio") && $('#attachforplayer').prop('checked') ) {
					links[t] = '['+$(this).data('play')+'='+$(this).data('url')+']';
				} else {
					if( $(this).data('public') == "1" ) {
						links[t] = '<a href="'+$(this).data('url')+'">'+$(this).data('url')+'</a>';
					} else {
						links[t] = '[attachment='+$(this).data('path')+']';
					}
				}
			}
			t++;
		});
	}
	
	if( $('.file-preview-card.active').length > 1 ) {
		if( !have_images ) {
			content = links.join(' ');
		} else if (align == 'center') {
		    var lastElement = links[links.length - 1];
			var lastCaret = '';
			if (lastElement.indexOf('<img') > -1 ) {
				lastCaret = '<br>';
			}
			if(allways_bbimages == '1') {
				content = links.join('</p><p style="text-align: center;">');
				content = '<p style="text-align: center;">'+ content +'</p>';
			} else {
				content = links.join('</p><p>');
				content = '<p>'+ content +'</p>' + lastCaret;
			}
		} else {
			content = links.join(' ');
		}
	} else { 
		content = links.join(''); 
		if (align == 'center' && content && have_images && allways_bbimages == '1' ) { content = '<p style="text-align: center;">'+ content +'</p>'; }
	}

	insertcontent( content );
}

function buildthumb( image, tag, hidpi_name ) {
	var align = $('#imagealign').val();
	var imagealt = $('#imagetitle').val();
	var content = '';
	var url = '';
	var hidpi_url = '';
	var allways_bbimages = '{$config['bbimages_in_wysiwyg']}';

	if( allways_bbimages != '1') {
		if( tag == 'thumb' ) {
			var folder="thumbs";
		} else {
			var folder="medium";
		}
		if(hidpi_name) {
			url = image.split('/');
			url.pop();
			url.push(hidpi_name);
			url = url.join('/');
			hidpi_url = ' data-srcset="' + url + ' 2x" ';
		} else {
			hidpi_url = '';
		}
		url = image.split('/');
		var filename = url.pop();
		url.push(folder);
		url.push(filename);
		url = url.join('/');
		content = '<a href="'+image+'" class="highslide" target="_blank"'+ hidpi_url +'>';
		content += buildimage( url, hidpi_name );
		content += '</a>';
	} else {
		var imgoption = "";
		if (imagealt != "") { 
			imgoption = "|"+imagealt;
		}
		if (align != "none" && align != "center") { 
			imgoption = align+imgoption;
		}
		if (imgoption != "" ) {
			imgoption = "="+imgoption;
		}
		content = '['+tag+''+imgoption+']'+ image +'[/'+tag+']';
	}
	return content;
}

function buildimage( image, hidpi_name ) {
	var content = '';
	var url = '';
	var align = $('#imagealign').val();
	var imagealt = $('#imagetitle').val();
	var allways_bbimages = '{$config['bbimages_in_wysiwyg']}';
	imagealt = escapeHtml(imagealt);

	if(hidpi_name) {
		url = image.split('/');
		url.pop();
		url.push(hidpi_name);
		url = url.join('/');
		hidpi_name = 'srcset="' + url + ' 2x" ';
	} else {
		hidpi_name = '';
	}
	
	if (allways_bbimages != '1') {
		if (align == 'center' || align == 'none') {
			if(align == 'center') {
				img_opt = " style=\"display: block; margin-left: auto; margin-right: auto;\"";
			} else {
				img_opt = "";
			}
			content = '<img '+ hidpi_name +'src="'+ image +'" alt="'+ imagealt +'"'+ img_opt +'>';
		} else {
			content = '<img '+ hidpi_name +'src="'+ image +'" style="float:' + align+ ';" alt="'+ imagealt +'">';
		}
	} else {
		var imgoption = "";
		var imagealt = $('#imagetitle').val();
		if (imagealt != "") { 
			imgoption = "|"+imagealt;
		}
		if (align != "none" && align != "center") { 
			imgoption = align+imgoption;
		}
		if (imgoption != "" ) {
			imgoption = "="+imgoption;
		}
		content = '[img'+imgoption+']'+ image +'[/img]';
	}
	return content;
}

function insertcontent( content ) {
	var allways_bbimages = '{$config['bbimages_in_wysiwyg']}';
	
	// Froala Editor Destek Kontrolü
	var froalaTextarea = jQuery('textarea#forum-editor');
	if (froalaTextarea.length && typeof froalaTextarea.froalaEditor === 'function' && froalaTextarea.data('froala.editor')) {
		froalaTextarea.froalaEditor('html.insert', content);
		jQuery('#mediaupload').dialog('close');
		return false;
	}
	
	// TinyMCE normal akışı
	var editor = typeof tinymce !== 'undefined' ? tinymce.activeEditor : null;
	if (editor) {
		var dom = editor.dom;
		var node = editor.selection.getNode();
		var newline = '<br>';
		var hasText = (node && node.innerText) ? node.innerText.trim() : '';

		if(content.indexOf('<p>') > -1 || allways_bbimages == '1' || hasText.length ) {
			newline = '';
		}

		editor.insertContent( content + newline );
		
		if (content.indexOf('[video=') > -1 || content.indexOf('[audio=') > -1) {
			var node = editor.selection.getNode();
			if (node && node.nodeName == 'P') {
				var stylenode = dom.getAttrib(node, 'style');
				var classnode = dom.getAttrib(node, 'class');
				if (stylenode) {
					stylenode = ' style="' + stylenode + '"';
				}
				if (classnode) {
					classnode = ' class="' + classnode + '"';
				}
				var newnode = '<div' + stylenode + classnode + '>' + editor.selection.select(node).innerHTML + '</div>';
				editor.selection.select(node);
				editor.insertContent(newnode);
			}
		}
	} else {
		// Düz textarea fallback
		var textarea = jQuery('textarea#forum-editor');
		if (textarea.length) {
			var text = textarea.val();
			var pos = textarea.prop('selectionStart') || 0;
			textarea.val(text.substring(0, pos) + content + text.substring(pos));
		}
	}

	jQuery('#mediaupload').dialog('close');
	return false;
}

function escapeHtml( string ) {
	var entityMap = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#39;',
		'/': '&#x2F;',
		'`': '&#x60;',
		'=': '&#x3D;',
		'?': '&#x3F'
	};
	return String(string).replace(/[&<>"'`=\/\?]/g, function (match) {
		return entityMap[match];
	});
}

function upload_from_url( url ) {
	var t_size = $('#t_size').val();
	var upload_driver = $('#upload_driver').val();
	var t_seite = $('#t_seite').val();
	var m_size = $('#m_size').val();
	var m_seite = $('#m_seite').val();
	var make_thumb = $("#make_thumb").is(":checked") ? 1 : 0;
	var make_medium = $("#make_medium").is(":checked") ? 1 : 0;
	var make_watermark = $("#make_watermark").is(":checked") ? 1 : 0;
	var public_file = $("#public_file").is(":checked") ? 1 : 0;
	var hidpi = $("#hidpi").is(":checked") ? 1 : 0;

	if (url == 'url' ) {
		var copyurl = $('#copyurl').val();
		var ftpurl = '';
		var error_id = 'upload-viaurl-status';		
	} else {
		var ftpurl = $('#ftpurl').val();
		var copyurl = '';
		var error_id = 'upload-viaftp-status';
	}

	$('#'+error_id).html( '<span style="color:green;">{$lang['ajax_info']}</span>' );

	$.post( dle_root + "index.php?controller=ajax&mod=forum_upload", { news_id: "{$news_id}", imageurl: copyurl, ftpurl: ftpurl, t_size: t_size, upload_driver: upload_driver, hidpi: hidpi, t_seite: t_seite, make_thumb: make_thumb, m_size: m_size, m_seite: m_seite, make_medium: make_medium, make_watermark: make_watermark, public_file: public_file, area: "{$area}", author: "{$author}", subaction: "upload", user_hash : "{$dle_login_hash}" }, function(data){
		if ( data.success ) {
			var returnbox = data.returnbox;
			returnbox = returnbox.replace(/&lt;/g, "<");
			returnbox = returnbox.replace(/&gt;/g, ">");
			returnbox = returnbox.replace(/&amp;/g, "&");
			$('#cont1').append( returnbox );
			$('#'+error_id).html('');
			if (url == 'url' ) {
				$('#copyurl').val('');
			} else {
				$('#ftpurl').val('');
			}
			tabClick(0);
		} else {
			if( data.error ) $('#'+error_id).html( '<span style="color:red;">' + data.error + '</span>' );
		}
	}, "json");
	return false;
}

function media_delete_file( file ) {
	DLEconfirmDelete( '{$lang['file_delete']}', '{$lang['p_info']}', function () {
		var formData = new FormData();
		formData.append('subaction', 'deluploads');
		formData.append('user_hash', '{$dle_login_hash}');
		formData.append('area', '{$area}');
		formData.append('news_id', '{$news_id}');
		formData.append('author', '{$author}');
		formData.append( file.data('area')+'[]', file.data('deleteid') );

		if( $( '#imagesallowmore' ).length ) {
			if ( file.data('area') == "images" ) {
				var allow_more = parseInt( $('#imagesallowmore').text() );
				var images_uploaded = parseInt( $('#imagesuploaded').text() );
				allow_more ++;
				images_uploaded --;
				if( allow_more < 0 ) allow_more = 0;
				max_images_allowed = allow_more;
				$('#imagesallowmore').text(allow_more);
				$('#imagesuploaded').text(images_uploaded);
			}
		}
		if( $( '#filesallowmore' ).length ) {
			if ( file.data('area') == "files" ) {
				var allow_more = parseInt( $('#filesallowmore').text() );
				var files_uploaded = parseInt( $('#filesuploaded').text() );
				allow_more ++;
				files_uploaded --;
				if( allow_more < 0 ) allow_more = 0;
				max_files_allowed = allow_more;
				$('#filesallowmore').text(allow_more);
				$('#filesuploaded').text(files_uploaded);
			}
		}
		ShowLoading('');
		$.ajax({
			url: dle_root + "index.php?controller=ajax&mod=forum_upload",
			data: formData,
			processData: false,
			contentType: false,
			type: 'POST',
			dataType: 'json',
			success: function(data) {
				HideLoading('');
				if (data.status) {
					file.fadeOut("slow", function() {
						file.remove();
					});
				} else {
					DLEPush.error('{$lang['files_del_error']}');
				}
			}
		});
		return false;
	} );
	return false;
}

function media_delete_selected() {
	if( $('.file-preview-card.active').length ) {
		DLEconfirmDelete( '{$lang['delete_selected']}', '{$lang['p_info']}', function () {
			var allow_del = true;
			var formData = new FormData();
			formData.append('subaction', 'deluploads');
			formData.append('user_hash', '{$dle_login_hash}');
			formData.append('area', '{$area}');
			formData.append('news_id', '{$news_id}');
			formData.append('author', '{$author}');
			
			$('.file-preview-card.active').each(function(){
				if( $(this).data('area') == 'shared' ) {
					allow_del = false;
					check_all();
					return false;
				} else if( $(this).data('deleteid') ) {
					formData.append( $(this).data('area')+'[]', $(this).data('deleteid') );
					if( $( '#imagesallowmore' ).length ) {
						if ( $(this).data('area') == "images" ) {
							var allow_more = parseInt( $('#imagesallowmore').text() );
							var images_uploaded = parseInt( $('#imagesuploaded').text() );
							allow_more ++;
							images_uploaded --;
							if( allow_more < 0 ) allow_more = 0;
							max_images_allowed = allow_more;
							$('#imagesallowmore').text(allow_more);
							$('#imagesuploaded').text(images_uploaded);
						}
					}
					if( $( '#filesallowmore' ).length ) {
						if ( $(this).data('area') == "files" ) {
							var allow_more = parseInt( $('#filesallowmore').text() );
							var files_uploaded = parseInt( $('#filesuploaded').text() );
							allow_more ++;
							files_uploaded --;
							if( allow_more < 0 ) allow_more = 0;
							max_files_allowed = allow_more;
							$('#filesallowmore').text(allow_more);
							$('#filesuploaded').text(files_uploaded);
						}
					}
				}
			});
			if(!allow_del) {
				return false;
			}
			ShowLoading('');
			$.ajax({
				url: dle_root + "index.php?controller=ajax&mod=forum_upload",
				data: formData,
				processData: false,
				contentType: false,
				type: 'POST',
				dataType: 'json',
				success: function(data) {
					HideLoading('');
					if (data.status) {
						$('.file-preview-card.active').fadeOut("slow", function() {
							$('.file-preview-card.active').remove();
							check_all();
						});
					} else {
						DLEPush.error('{$lang['files_del_error']}');
					}
				}
			});
			return false;
		} );
	}
	return false;
}
</script>
HTML;
    exit;
}
