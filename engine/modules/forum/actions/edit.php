<?php
/*
=====================================================
 Forge Forum Engine — Mesaj Düzenleme
-----------------------------------------------------
 File: engine/modules/forum/actions/edit.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

// -------------------------------------------------
// 1. YETKİ
// -------------------------------------------------
if( !$is_logged ) {
    msgbox( $lang['forum_access_denied'], $lang['forum_edit_login_required'] );
    return;
}

// -------------------------------------------------
// 2. MESAJI BUL
// -------------------------------------------------
$post_id = $f_post_id;
if( !$post_id && isset($_GET['post_id']) ) $post_id = intval( $_GET['post_id'] );
if( !$post_id && isset($_POST['post_id']) ) $post_id = intval( $_POST['post_id'] );
if( !$post_id ) { msgbox($lang['forum_err_title'],$lang['forum_edit_invalid_post']); return; }

$post = $db->super_query( "SELECT p.*, t.title AS topic_title, t.alt_name AS topic_alt, t.is_locked, t.cat_id AS topic_cat_id, t.prefix_id AS topic_prefix_id
    FROM " . PREFIX . "_forum_posts p
    LEFT JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
    WHERE p.id = '{$post_id}'" );

if( !$post || !$post['id'] ) { msgbox($lang['forum_err_title'],$lang['forum_edit_post_not_found']); return; }
if( $post['is_deleted'] )  { msgbox($lang['forum_err_title'],$lang['forum_edit_post_deleted']); return; }

$tid = intval( $post['topic_id'] );

// EKLENTİ: Konunun ilk mesajı mı kontrol et (Is this the first post of the topic?)
$first_post = $db->super_query("SELECT id FROM " . PREFIX . "_forum_posts WHERE topic_id = '{$tid}' ORDER BY id ASC LIMIT 1");
$is_first_post = (intval($first_post['id']) === intval($post_id));

// Yetki: mesaj sahibi VEYA admin/mod/editör
$is_owner = ( intval($post['user_id']) === intval($member_id['user_id']) );
$is_mod   = ( $member_id['user_group'] <= 3 ); // Yönetici (1), Moderatör (2) ve Editör (3) tam yetkili

if( !$is_owner && !$is_mod ) {
    msgbox( $lang['forum_access_denied'], $lang['forum_edit_no_perm'] );
    return;
}

// Zaman sınırı (sadece mesaj sahibi için, mod/admin/editör sınırsız, konunun ana mesajı/başlığı için de sınırsız)
if( $is_owner && !$is_mod && !$is_first_post ) {
    $edit_limit = 15; // normal cevaplar için 15 dakika düzenleme sınırı
    $post_time  = strtotime( $post['date'] );
    $elapsed    = ( time() - $post_time ) / 60;
    if( $elapsed > $edit_limit ) {
        msgbox( $lang['forum_err_title'], sprintf($lang['forum_edit_time_limit'], $edit_limit) );
        return;
    }
}

// -------------------------------------------------
// 3. POST İŞLEMİ (Kaydet)
// -------------------------------------------------
if( isset( $_POST['submit_edit'] ) ) {

    if( $_POST['user_hash'] !== $dle_login_hash ) {
        msgbox( $lang['forum_err_title'], $lang['forum_edit_csrf'] );
        return;
    }

    $new_text = trim( $_POST['text'] );
    if( empty( $new_text ) ) {
        msgbox( $lang['forum_err_title'], $lang['forum_edit_empty_text'] );
        return;
    }

    // Guvenlik: DLE'nin kendi strip_tags'i gibi temizle
    // HTMLPurifier'a gerek yok, DLE kendi parse mekanizmasini kullanir
    // HTML icerigi DB'ye kaydedilirken db->safesql() zaten SQL injection'i engeller
    // Sadece fazladan script taglarini temizle
    $new_text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $new_text);
    $new_text = preg_replace('/<\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $new_text);

    $safe_text = $db->safesql( $new_text );
    $uid       = intval( $member_id['user_id'] );
    $now       = date( 'Y-m-d H:i:s' );

    // Eski metni editleme geçmişine kaydet
    $old_text = $db->safesql( $post['text'] );
    $db->query( "INSERT INTO " . PREFIX . "_forum_edit_history
        (post_id, editor_id, old_text, date)
        VALUES ('{$post_id}','{$uid}','{$old_text}','{$now}')" );

    // Mesajı güncelle
    $db->query( "UPDATE " . PREFIX . "_forum_posts SET
        text = '{$safe_text}',
        updated_at = '{$now}'
        WHERE id = '{$post_id}'" );

    // Mention etiketlerini işle ve bildirim gönder
    forum_parse_mentions($new_text, $tid, $post_id, $uid);

    // Eğer konunun ilk mesajı ise, başlık, önek, kategori ve etiketleri de güncelle
    $topic_alt = $post['topic_alt'];
    if ($is_first_post) {
        $new_title = trim($_POST['title']);
        $new_cat_id = intval($_POST['cat_id']);
        $new_prefix_id = intval($_POST['prefix_id']);
        
        if (empty($new_title)) {
            msgbox($lang['forum_err_title'], $lang['forum_edit_empty_title']);
            return;
        }
        if (!$new_cat_id) {
            msgbox($lang['forum_err_title'], $lang['forum_edit_invalid_cat']);
            return;
        }
        
        $safe_title = $db->safesql($new_title);
        $topic_alt = totranslit($new_title);
        $safe_alt = $db->safesql($topic_alt);
        
        // Konuyu güncelle
        $db->query("UPDATE " . PREFIX . "_forum_topics SET
            title = '{$safe_title}',
            alt_name = '{$safe_alt}',
            cat_id = '{$new_cat_id}',
            prefix_id = '{$new_prefix_id}'
            WHERE id = '{$tid}'");
            
        // Etiketleri güncelle
        $db->query("SELECT tag_id FROM " . PREFIX . "_forum_topic_tags WHERE topic_id = '{$tid}'");
        $existing_tag_ids = [];
        while ($trow = $db->get_row()) {
            $existing_tag_ids[] = intval($trow['tag_id']);
        }
        if (count($existing_tag_ids) > 0) {
            $db->query("UPDATE " . PREFIX . "_forum_tags SET topic_count = topic_count - 1 WHERE id IN (" . implode(',', $existing_tag_ids) . ")");
            $db->query("DELETE FROM " . PREFIX . "_forum_topic_tags WHERE topic_id = '{$tid}'");
            $db->query("DELETE FROM " . PREFIX . "_forum_tags WHERE topic_count <= 0");
        }
        
        $tags_str = trim($_POST['tags']);
        if (!empty($tags_str)) {
            $tags_arr = explode(',', $tags_str);
            foreach ($tags_arr as $tname) {
                $tname = trim($tname);
                if (empty($tname)) continue;
                $talt = $db->safesql(totranslit($tname));
                $tname_safe = $db->safesql($tname);
                
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
                                VALUES ('{$tag_id}', '{$tid}')");
                }
            }
        }

        // Anket güncelleme veya ekleme (Poll update or insert)
        $poll_question = trim($_POST['poll_question']);
        $poll_options_str = trim($_POST['poll_options']);
        $existing_poll = $db->super_query("SELECT id FROM " . PREFIX . "_forum_polls WHERE topic_id = '{$tid}'");
        
        if (!empty($poll_question) && !empty($poll_options_str)) {
            $poll_multiple = isset($_POST['poll_multiple']) ? 1 : 0;
            $poll_max_choices = intval($_POST['poll_max_choices']);
            $poll_days = intval($_POST['poll_days']);
            $end_date = "NULL";
            if ($poll_days > 0) {
                $end_date = "'" . date('Y-m-d H:i:s', time() + $poll_days * 86400) . "'";
            }
            $safe_question = $db->safesql($poll_question);
            
            if ($existing_poll['id']) {
                $poll_id = intval($existing_poll['id']);
                $db->query("UPDATE " . PREFIX . "_forum_polls SET
                    question = '{$safe_question}',
                    multiple = '{$poll_multiple}',
                    max_choices = '{$poll_max_choices}',
                    end_date = {$end_date}
                    WHERE id = '{$poll_id}'");
                    
                // Seçenekleri yenile
                $db->query("DELETE FROM " . PREFIX . "_forum_poll_options WHERE poll_id = '{$poll_id}'");
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
            } else {
                // Yeni anket ekle
                $db->query("INSERT INTO " . PREFIX . "_forum_polls (topic_id, question, multiple, max_choices, end_date) 
                            VALUES ('{$tid}', '{$safe_question}', '{$poll_multiple}', '{$poll_max_choices}', {$end_date})");
                $poll_id = $db->insert_id();
                if (!$poll_id) {
                    $get_poll = $db->super_query("SELECT id FROM " . PREFIX . "_forum_polls WHERE topic_id = '{$tid}'");
                    $poll_id = intval($get_poll['id']);
                }
                
                if ($poll_id > 0) {
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
        } elseif ($existing_poll['id']) {
            // Anketi tamamen sil (Eğer alanlar boşaltıldıysa)
            $poll_id = intval($existing_poll['id']);
            $db->query("DELETE FROM " . PREFIX . "_forum_polls WHERE id = '{$poll_id}'");
            $db->query("DELETE FROM " . PREFIX . "_forum_poll_options WHERE poll_id = '{$poll_id}'");
            $db->query("DELETE FROM " . PREFIX . "_forum_poll_votes WHERE poll_id = '{$poll_id}'");
        }
    }

    // Yönlendir
    $redirect = forum_topic_post_url($tid, $topic_alt, intval($post_id));
    header( "Location: {$redirect}" );
    exit;
}

if (!class_exists('ParseFilter')) {
    include_once (ENGINE_DIR . '/classes/parse.class.php');
}
$parse = new ParseFilter();

if ($forum_cfg["editor_enabled"]) {
    include_once DLEPlugins::Check(ENGINE_DIR . "/modules/forum/editor_helpers.php");
    $f_editor_context = $is_first_post ? "topic" : "reply";
    forum_register_editor_assets(
        $is_first_post ? "tinymce" : forum_get_editor_type($forum_cfg, "reply"),
        $js_array,
        $css_array,
        $lang,
    );
    $f_editor_id = "forum-editor";
    $f_editor_height = $is_first_post ? 350 : 250;
    $f_editor_mode = $is_first_post ? "full" : "reply";
    $f_editor_news_id = $post_id;
    include_once DLEPlugins::Check(ENGINE_DIR . "/editor/forum.php");
    
    $post_val = forum_prepare_editor_content($post["text"]);
    
    $wysiwyg = str_replace('></textarea>', '>' . $post_val . '</textarea>', $wysiwyg);
    $tpl->set("{forum_editor}", $wysiwyg);
} else {
    // Editör kapaliysa duz BBCode goster
    $post_val = htmlspecialchars(stripslashes($post['text']), ENT_QUOTES, 'UTF-8');
    $tpl->set(
        "{forum_editor}",
        '<textarea name="text" id="forum-editor" rows="10" class="w-full border border-gray-300 rounded-lg p-3 text-sm">' . $post_val . '</textarea>',
    );
}

$metatags['title'] = sprintf($lang['forum_edit_title'], $forum_title);
$tpl->load_template( 'forum/edit.tpl' );
$tpl->set( '{post_id}', $post_id );
$tpl->set( '{topic_id}', $tid );
$tpl->set( '{post_text}', htmlspecialchars($post['text'], ENT_QUOTES, 'UTF-8') );
$tpl->set( '{topic_title}', htmlspecialchars( stripslashes( $post['topic_title'] ), ENT_QUOTES, 'UTF-8' ) );
$tpl->set( '{dle_login_hash}', $dle_login_hash );
$tpl->set( '{http_home_url}', $config['http_home_url'] );

// İlk mesaj için özel alanlar
if ($is_first_post) {
    $tpl->set_block("'\\[first_post\\](.*?)\\[/first_post\\]'si", "\\1");
    
    // Kategori Seçici (Tüm Kategori ve Alt Forum Ağacı)
    $categories = [];
    $db->query("SELECT id, name, parent_id FROM " . PREFIX . "_forum_cats ORDER BY parent_id ASC, posi ASC");
    while ($row = $db->get_row()) { $categories[] = $row; }
    
    $cat_tree = [];
    foreach ($categories as $cat) {
        $cat_tree[$cat['parent_id']][] = $cat;
    }
    
    $cat_opts = '<option value="">' . $lang['forum_edit_cat_select'] . '</option>';
    if (isset($cat_tree[0])) {
        foreach ($cat_tree[0] as $parent) {
            $selected = ($parent['id'] == $post['topic_cat_id']) ? " selected" : "";
            $cat_opts .= '<option value="' . $parent['id'] . '"' . $selected . ' style="font-weight: bold;">' . htmlspecialchars(stripslashes($parent['name']), ENT_QUOTES, "UTF-8") . '</option>';
            
            if (isset($cat_tree[$parent['id']])) {
                foreach ($cat_tree[$parent['id']] as $sub) {
                    $selected_sub = ($sub['id'] == $post['topic_cat_id']) ? " selected" : "";
                    $cat_opts .= '<option value="' . $sub['id'] . '"' . $selected_sub . '>&nbsp;&nbsp;&nbsp;&nbsp;— ' . htmlspecialchars(stripslashes($sub['name']), ENT_QUOTES, "UTF-8") . '</option>';
                }
            }
        }
    }
    $tpl->set('{category_selector}', '<select name="cat_id" class="mybb-select" required>' . $cat_opts . '</select>');
    
    // Önek Seçici
    $prefix_opts = '<option value="0">' . $lang['forum_edit_pfx_select'] . '</option>';
    $db->query("SELECT id, name FROM " . PREFIX . "_forum_prefixes ORDER BY posi ASC, id ASC");
    while ($prow = $db->get_row()) {
        $selected = ($prow['id'] == $post['topic_prefix_id']) ? " selected" : "";
        $prefix_opts .= '<option value="' . $prow['id'] . '"' . $selected . '>' . htmlspecialchars($prow['name'], ENT_QUOTES, "UTF-8") . '</option>';
    }
    $tpl->set('{prefix_selector}', '<select name="prefix_id" class="mybb-select" style="max-width: 150px; margin-right: 10px;">' . $prefix_opts . '</select>');
    // Etiketler
    $tag_names = [];
    $db->query("SELECT t.name FROM " . PREFIX . "_forum_tags t INNER JOIN " . PREFIX . "_forum_topic_tags tt ON tt.tag_id = t.id WHERE tt.topic_id = '{$tid}'");
    while ($trow = $db->get_row()) { $tag_names[] = $trow['name']; }
    
    $tpl->set('{tags_val}', htmlspecialchars(implode(', ', $tag_names), ENT_QUOTES, 'UTF-8'));
    $tpl->set('{title_val}', htmlspecialchars(stripslashes($post['topic_title']), ENT_QUOTES, 'UTF-8'));

    // Anket verilerini yükle (Load poll data)
    $poll = $db->super_query("SELECT * FROM " . PREFIX . "_forum_polls WHERE topic_id = '{$tid}'");
    if ($poll && $poll['id']) {
        $options_arr = [];
        $db->query("SELECT text FROM " . PREFIX . "_forum_poll_options WHERE poll_id = '" . intval($poll['id']) . "' ORDER BY posi ASC");
        while ($orow = $db->get_row()) {
            $options_arr[] = stripslashes($orow['text']);
        }
        $poll_options_str = implode("\n", $options_arr);
        
        $poll_days = 0;
        if (!empty($poll['end_date'])) {
            $diff = strtotime($poll['end_date']) - time();
            if ($diff > 0) {
                $poll_days = ceil($diff / 86400);
            }
        }
        
        $tpl->set('{poll_question}', htmlspecialchars(stripslashes($poll['question']), ENT_QUOTES, "UTF-8"));
        $tpl->set('{poll_options}', htmlspecialchars($poll_options_str, ENT_QUOTES, "UTF-8"));
        $tpl->set('{poll_max_choices}', intval($poll['max_choices']));
        $tpl->set('{poll_days}', intval($poll_days));
        $tpl->set('{poll_multiple_checked}', $poll['multiple'] ? "checked" : "");
    } else {
        $tpl->set('{poll_question}', '');
        $tpl->set('{poll_options}', '');
        $tpl->set('{poll_max_choices}', '1');
        $tpl->set('{poll_days}', '0');
        $tpl->set('{poll_multiple_checked}', '');
    }
} else {
    $tpl->set_block("'\\[first_post\\](.*?)\\[/first_post\\]'si", "");
    $tpl->set('{category_selector}', '');
    $tpl->set('{prefix_selector}', '');
    $tpl->set('{tags_val}', '');
    $tpl->set('{title_val}', '');
    $tpl->set('{poll_question}', '');
    $tpl->set('{poll_options}', '');
    $tpl->set('{poll_max_choices}', '1');
    $tpl->set('{poll_days}', '0');
    $tpl->set('{poll_multiple_checked}', '');
}

$tpl->compile('content');
$tpl->clear();
