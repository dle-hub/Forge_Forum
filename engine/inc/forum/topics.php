<?php
/*
=====================================================
 Forge Forum Engine — Konu Yönetimi
-----------------------------------------------------
 File: engine/inc/forum/topics.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/

if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/editor_helpers.php",
);
$forum_cfg = forum_load_cfg();

$id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : 0;
$subaction = isset($_REQUEST["subaction"]) ? totranslit($_REQUEST["subaction"]) : "";

// -------------------------------------------------
// POST: TOPLU İŞLEMLER (MASS ACTIONS)
// -------------------------------------------------
if (isset($_POST["mass_action_submit"])) {
    if ($_POST["dle_post_hash"] !== $dle_login_hash) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }

    $selected_topics = $_POST["selected_topics"];
    $mass_action = totranslit($_POST["mass_action"]);

    if (empty($selected_topics) || !is_array($selected_topics)) {
        msg("error", $lang['forum_error_title'], $lang['forum_topic_mass_no_select']);
        return;
    }

    $safe_ids = array_map("intval", $selected_topics);
    $ids_string = implode(",", $safe_ids);

    if ($mass_action == "approve") {
        $db->query("UPDATE " . PREFIX . "_forum_topics SET is_approved = 1 WHERE id IN ($ids_string)");
        $db->query("UPDATE " . PREFIX . "_forum_posts SET is_approved = 1 WHERE topic_id IN ($ids_string)");
        msg("success", $lang['forum_set_success_title'], $lang['forum_topic_mass_approved'], "?mod=forum&action=topics");
    } elseif ($mass_action == "unapprove") {
        $db->query("UPDATE " . PREFIX . "_forum_topics SET is_approved = 0 WHERE id IN ($ids_string)");
        $db->query("UPDATE " . PREFIX . "_forum_posts SET is_approved = 0 WHERE topic_id IN ($ids_string)");
        msg("success", $lang['forum_set_success_title'], $lang['forum_topic_mass_unapproved'], "?mod=forum&action=topics");
    } elseif ($mass_action == "pin") {
        $db->query("UPDATE " . PREFIX . "_forum_topics SET is_pinned = 1 WHERE id IN ($ids_string)");
        msg("success", $lang['forum_set_success_title'], $lang['forum_topic_mass_pinned'], "?mod=forum&action=topics");
    } elseif ($mass_action == "unpin") {
        $db->query("UPDATE " . PREFIX . "_forum_topics SET is_pinned = 0 WHERE id IN ($ids_string)");
        msg("success", $lang['forum_set_success_title'], $lang['forum_topic_mass_unpinned'], "?mod=forum&action=topics");
    } elseif ($mass_action == "lock") {
        $db->query("UPDATE " . PREFIX . "_forum_topics SET is_locked = 1 WHERE id IN ($ids_string)");
        msg("success", $lang['forum_set_success_title'], $lang['forum_topic_mass_locked'], "?mod=forum&action=topics");
    } elseif ($mass_action == "unlock") {
        $db->query("UPDATE " . PREFIX . "_forum_topics SET is_locked = 0 WHERE id IN ($ids_string)");
        msg("success", $lang['forum_set_success_title'], $lang['forum_topic_mass_unlocked'], "?mod=forum&action=topics");
    } elseif ($mass_action == "trash") {
        $db->query("UPDATE " . PREFIX . "_forum_topics SET is_deleted = 1 WHERE id IN ($ids_string)");
        $db->query("UPDATE " . PREFIX . "_forum_posts SET is_deleted = 1 WHERE topic_id IN ($ids_string)");
        msg("success", $lang['forum_set_success_title'], $lang['forum_topic_mass_trashed'], "?mod=forum&action=topics");
    } elseif ($mass_action == "restore") {
        $db->query("UPDATE " . PREFIX . "_forum_topics SET is_deleted = 0 WHERE id IN ($ids_string)");
        $db->query("UPDATE " . PREFIX . "_forum_posts SET is_deleted = 0 WHERE topic_id IN ($ids_string)");
        msg("success", $lang['forum_set_success_title'], $lang['forum_topic_mass_restored'], "?mod=forum&action=topics");
    } elseif ($mass_action == "move") {
        $target_cat = intval($_POST["target_cat_id"]);
        if ($target_cat <= 0) {
            msg("error", $lang['forum_error_title'], $lang['forum_topic_mass_no_cat']);
            return;
        }
        $db->query("UPDATE " . PREFIX . "_forum_topics SET cat_id = '{$target_cat}' WHERE id IN ($ids_string)");
        msg("success", $lang['forum_set_success_title'], $lang['forum_topic_mass_moved'], "?mod=forum&action=topics");
    } elseif ($mass_action == "delete") {
        $db->query("DELETE FROM " . PREFIX . "_forum_topics WHERE id IN ($ids_string)");
        $db->query("DELETE FROM " . PREFIX . "_forum_posts WHERE topic_id IN ($ids_string)");
        msg("success", $lang['forum_set_success_title'], $lang['forum_topic_mass_deleted'], "?mod=forum&action=topics");
    }
    return;
}

// -------------------------------------------------
// POST: TEKİL DÜZENLEME KAYDET (SAVE SINGLE TOPIC)
// -------------------------------------------------
if (isset($_POST["save_topic"]) && $id > 0) {
    if ($_POST["dle_post_hash"] !== $dle_login_hash) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }

    $title = $db->safesql(trim(htmlspecialchars($_POST["title"], ENT_QUOTES, "UTF-8")));
    $alt_name = trim($_POST["alt_name"]);
    if (empty($alt_name)) {
        $alt_name = totranslit($_POST["title"]);
    } else {
        $alt_name = totranslit($alt_name);
    }
    $alt_name = $db->safesql($alt_name);

    $cat_id = intval($_POST["cat_id"]);
    $prefix_id = intval($_POST["prefix_id"]);
    $is_pinned = isset($_POST["is_pinned"]) ? 1 : 0;
    $is_locked = isset($_POST["is_locked"]) ? 1 : 0;
    $is_approved = isset($_POST["is_approved"]) ? 1 : 0;
    $is_deleted = isset($_POST["is_deleted"]) ? 1 : 0;

    $author_name = $db->safesql(trim($_POST["author"]));
    $user_row = $db->super_query("SELECT user_id FROM " . PREFIX . "_users WHERE name='{$author_name}'");
    $user_id = $user_row["user_id"] ? intval($user_row["user_id"]) : 0;

    if (empty($title)) {
        msg("error", $lang['forum_error_title'], $lang['forum_topic_empty_title']);
        return;
    }

    // Erişim İzinleri (Access Permissions)
    $group_regel = [];
    if (isset($_POST['group_extra']) && is_array($_POST['group_extra'])) {
        foreach ($_POST['group_extra'] as $g_id => $g_val) {
            $g_val = intval($g_val);
            if ($g_val > 0) {
                $group_regel[] = intval($g_id) . ':' . $g_val;
            }
        }
    }
    $safe_access = count($group_regel) ? implode("||", $group_regel) : '';
    $safe_access = $db->safesql($safe_access);

    // Konuyu güncelle
    $db->query("UPDATE " . PREFIX . "_forum_topics SET 
        title='{$title}', 
        alt_name='{$alt_name}', 
        cat_id='{$cat_id}', 
        prefix_id='{$prefix_id}', 
        is_pinned='{$is_pinned}', 
        is_locked='{$is_locked}', 
        is_approved='{$is_approved}', 
        is_deleted='{$is_deleted}', 
        user_id='{$user_id}',
        access='{$safe_access}'
        WHERE id='{$id}'");

    // İlk mesajı bul ve metnini güncelle
    $first_post = $db->super_query("SELECT id FROM " . PREFIX . "_forum_posts WHERE topic_id='{$id}' ORDER BY id ASC LIMIT 1");
    if ($first_post["id"]) {
        $post_text = isset($_POST["text"]) ? $_POST["text"] : (isset($_POST["full_story"]) ? $_POST["full_story"] : $_POST["post_text"]);
        $post_text = $db->safesql(trim($post_text));
        $db->query("UPDATE " . PREFIX . "_forum_posts SET text='{$post_text}', is_approved='{$is_approved}', is_deleted='{$is_deleted}', user_id='{$user_id}' WHERE id='{$first_post["id"]}'");
    }

    // Anket işlemleri (Poll save/update/delete)
    $poll_question = isset($_POST['poll_question']) ? trim($_POST['poll_question']) : '';
    $poll_options_str = isset($_POST['poll_options']) ? trim($_POST['poll_options']) : '';
    $delete_poll = isset($_POST['delete_poll']) ? 1 : 0;
    
    $existing_poll = $db->super_query("SELECT id FROM " . PREFIX . "_forum_polls WHERE topic_id = '{$id}'");
    
    if ($delete_poll && $existing_poll['id']) {
        $poll_id = intval($existing_poll['id']);
        $db->query("DELETE FROM " . PREFIX . "_forum_polls WHERE id = '{$poll_id}'");
        $db->query("DELETE FROM " . PREFIX . "_forum_poll_options WHERE poll_id = '{$poll_id}'");
        $db->query("DELETE FROM " . PREFIX . "_forum_poll_votes WHERE poll_id = '{$poll_id}'");
    } elseif (!empty($poll_question) && !empty($poll_options_str)) {
        $poll_multiple = isset($_POST['poll_multiple']) ? 1 : 0;
        $poll_is_closed = isset($_POST['poll_is_closed']) ? 1 : 0;
        $poll_max_choices = intval($_POST['poll_max_choices']);
        if ($poll_max_choices < 1) $poll_max_choices = 1;
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
                end_date = {$end_date},
                is_closed = '{$poll_is_closed}'
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
            $db->query("INSERT INTO " . PREFIX . "_forum_polls (topic_id, question, multiple, max_choices, end_date, is_closed) 
                        VALUES ('{$id}', '{$safe_question}', '{$poll_multiple}', '{$poll_max_choices}', {$end_date}, '{$poll_is_closed}')");
            $poll_id = $db->insert_id();
            if (!$poll_id) {
                $get_poll = $db->super_query("SELECT id FROM " . PREFIX . "_forum_polls WHERE topic_id = '{$id}'");
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
    }

    msg("success", $lang['forum_set_success_title'], $lang['forum_topic_updated'], "?mod=forum&action=topics");
    return;
}

// -------------------------------------------------
// YARDIMCI VERİLER: KATEGORİ VE ÖNEKLER
// -------------------------------------------------
$categories = [];
$db->query("SELECT id, name FROM " . PREFIX . "_forum_cats ORDER BY posi ASC");
while ($row = $db->get_row()) {
    $categories[] = $row;
}

function catDropdown($cats, $selected = 0) {
    $opts = "";
    foreach ($cats as $c) {
        $sel = $c["id"] == $selected ? " selected" : "";
        $opts .= '<option value="' . $c["id"] . '"' . $sel . '>' . htmlspecialchars($c["name"], ENT_QUOTES, "UTF-8") . '</option>';
    }
    return $opts;
}

// -------------------------------------------------
// GÖRÜNÜM: DÜZENLEME EKRANI (EDIT FORM)
// -------------------------------------------------
if ($subaction == "edit" && $id > 0) {
    forum_register_editor_assets("tinymce", $js_array, $css_array, $lang);
    $js_array[] = "public/js/sortable.js";
    $js_array[] = "public/fileuploader/plupload/plupload.full.min.js";
    $js_array[] = "public/fileuploader/plupload/i18n/{$lang['language_code']}.js";

    if($lang['direction'] == 'rtl') $rtl_prefix ='_rtl'; else $rtl_prefix = '';
    $css_array[] = "public/fileuploader/fileuploader{$rtl_prefix}.css";

    // access sütununu güvenli bir şekilde ekleyelim
    $db->query("SHOW COLUMNS FROM " . PREFIX . "_forum_topics LIKE 'access'");
    if (!$db->num_rows()) {
        $db->query("ALTER TABLE " . PREFIX . "_forum_topics ADD COLUMN access VARCHAR(255) NOT NULL DEFAULT ''");
    }

    $topic = $db->super_query("SELECT t.*, u.name as author_name FROM " . PREFIX . "_forum_topics t LEFT JOIN " . PREFIX . "_users u ON u.user_id=t.user_id WHERE t.id='{$id}'");
    if (!$topic["id"]) {
        msg("error", $lang['forum_error_title'], $lang['forum_topic_not_found']);
        return;
    }

    // İlk mesajı çek
    $first_post = $db->super_query("SELECT * FROM " . PREFIX . "_forum_posts WHERE topic_id='{$id}' ORDER BY id ASC LIMIT 1");

    // Anket verilerini yükle (Load poll data)
    $poll = $db->super_query("SELECT * FROM " . PREFIX . "_forum_polls WHERE topic_id = '{$id}'");
    $poll_options_str = "";
    $poll_days = 0;
    if ($poll && $poll['id']) {
        $options_arr = [];
        $db->query("SELECT text FROM " . PREFIX . "_forum_poll_options WHERE poll_id = '" . intval($poll['id']) . "' ORDER BY posi ASC");
        while ($orow = $db->get_row()) {
            $options_arr[] = stripslashes($orow['text']);
        }
        $poll_options_str = implode("\n", $options_arr);
        
        if (!empty($poll['end_date'])) {
            $diff = strtotime($poll['end_date']) - time();
            if ($diff > 0) {
                $poll_days = ceil($diff / 86400);
            }
        }
    }

    // Tüm önekleri çek
    $prefixes = [];
    $db->query("SELECT id, name FROM " . PREFIX . "_forum_prefixes ORDER BY posi ASC");
    while ($prow = $db->get_row()) {
        $prefixes[] = $prow;
    }
    ?>
    <script>
        function gen_alt_name() {
            var title = $('#title').val();
            if (title) {
                $.post('engine/ajax/controller.php?mod=translit', { title: title }, function(data){
                    $('#alt_name').val(data);
                });
            }
        }
    </script>
    <div class="panel panel-default">
        <div class="panel-heading">
            <ul class="nav nav-tabs nav-tabs-solid">
                <li class="active"><a href="#tabhome" data-toggle="tab"><i class="fa fa-home position-left"></i> <?php echo $lang['forum_topic_tab_general']; ?></a></li>
                <li><a href="#tabvote" data-toggle="tab"><i class="fa fa-bar-chart position-left"></i> <?php echo $lang['forum_topic_tab_poll']; ?></a></li>
                <li><a href="#tabaccess" data-toggle="tab"><i class="fa fa-lock position-left"></i> <?php echo $lang['forum_topic_tab_access']; ?></a></li>
            </ul>
            <div class="heading-elements not-collapsible">
                <a href="?mod=forum&action=topics" class="btn btn-default btn-xs"><i class="fa fa-arrow-left position-left"></i> <?php echo $lang['forum_topic_btn_back']; ?></a>
            </div>
        </div>
        
        <form method="post" action="?mod=forum&action=topics&subaction=edit&id=<?php echo $id; ?>" autocomplete="off" class="form-horizontal" onsubmit="if(typeof tinyMCE !== 'undefined') { tinyMCE.triggerSave(); }">
            <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">

            <div class="panel-tab-content tab-content">
                <div class="tab-pane active" id="tabhome">
                    <div class="panel-body">

                <div class="form-group">
                    <label class="control-label col-sm-2"><?php echo $lang['forum_topic_title_label']; ?> <span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <input type="text" name="title" id="title" class="form-control" required value="<?php echo htmlspecialchars($topic["title"], ENT_QUOTES, "UTF-8"); ?>" onblur="gen_alt_name();">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-sm-2"><?php echo $lang['forum_topic_seo_label']; ?></label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <input type="text" name="alt_name" id="alt_name" class="form-control" value="<?php echo htmlspecialchars($topic["alt_name"], ENT_QUOTES, "UTF-8"); ?>">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="gen_alt_name(); return false;"><i class="fa fa-refresh"></i> <?php echo $lang['forum_topic_btn_generate']; ?></button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-sm-2"><?php echo $lang['forum_stat_cats']; ?></label>
                    <div class="col-sm-4">
                        <select name="cat_id" class="uniform" style="width:100%;">
                            <?php echo catDropdown($categories, $topic["cat_id"]); ?>
                        </select>
                    </div>
                    <label class="control-label col-sm-2"><?php echo $lang['forum_topic_prefix_label']; ?></label>
                    <div class="col-sm-4">
                        <select name="prefix_id" class="uniform" style="width:100%;">
                            <option value="0"><?php echo $lang['forum_no_prefix_option']; ?></option>
                            <?php foreach ($prefixes as $p): ?>
                                <option value="<?php echo $p["id"]; ?>"<?php echo $p["id"] == $topic["prefix_id"] ? " selected" : ""; ?>><?php echo htmlspecialchars($p["name"], ENT_QUOTES, "UTF-8"); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-sm-2"><?php echo $lang['forum_topic_author_label']; ?></label>
                    <div class="col-sm-10">
                        <input type="text" name="author" class="form-control" style="width: 250px;" value="<?php echo htmlspecialchars($topic["author_name"] ?: $lang["forum_topic_guest"], ENT_QUOTES, "UTF-8"); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-sm-2"><?php echo $lang['forum_topic_opts_label']; ?></label>
                    <div class="col-sm-10">
                        <label class="checkbox-inline" style="padding-top: 0;">
                            <input type="checkbox" class="icheck" name="is_pinned" value="1"<?php echo $topic["is_pinned"] ? " checked" : ""; ?>> <?php echo $lang['forum_topic_opt_pin']; ?>
                        </label>
                        <label class="checkbox-inline" style="padding-top: 0; margin-left: 20px;">
                            <input type="checkbox" class="icheck" name="is_locked" value="1"<?php echo $topic["is_locked"] ? " checked" : ""; ?>> <?php echo $lang['forum_topic_opt_lock']; ?>
                        </label>
                        <label class="checkbox-inline" style="padding-top: 0; margin-left: 20px;">
                            <input type="checkbox" class="icheck" name="is_approved" value="1"<?php echo $topic["is_approved"] ? " checked" : ""; ?>> <?php echo $lang['forum_topic_opt_approve']; ?>
                        </label>
                        <label class="checkbox-inline" style="padding-top: 0; margin-left: 20px;">
                            <input type="checkbox" class="icheck" name="is_deleted" value="1"<?php echo $topic["is_deleted"] ? " checked" : ""; ?>> <?php echo $lang['forum_topic_opt_trash']; ?>
                        </label>
                    </div>
                </div>

                <div class="form-group editor-group" style="margin-top: 20px;">
                    <label class="control-label col-sm-2"><?php echo $lang['forum_topic_first_post']; ?> <span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <script>
                        if (typeof dle_root === 'undefined' || !dle_root) {
                            var dle_root = '<?php echo $config['http_home_url']; ?>';
                        }
                        if (dle_root && !dle_root.endsWith('/')) {
                            dle_root += '/';
                        }
                        if (typeof dle_login_hash === 'undefined') {
                            var dle_login_hash = '<?php echo $dle_login_hash; ?>';
                        }
                        </script>
                        <?php
                        @ini_set('display_errors', 1);
                        @error_reporting(E_ALL);
                        
                        global $lang, $member_id, $user_group, $config, $forum_cfg, $wysiwyg, $onload_scripts;
                        
                        if (!is_array($lang)) $lang = [];
                        if (!isset($lang["language_code"])) $lang["language_code"] = "tr";
                        if (!isset($lang["direction"])) $lang["direction"] = "ltr";
                        if (!isset($lang["bb_t_up"])) $lang["bb_t_up"] = $lang["forum_bb_up"];
                        if (!isset($lang["i_quote"])) $lang["i_quote"] = $lang["forum_bb_quote"];
                        $f_editor_id = "forum-editor";
                        $f_editor_height = 350;
                        $f_editor_mode = "full";
                        $f_editor_context = "topic";
                        $f_editor_admin_panel = true;
                        $onload_scripts = [];

                        $first_post_html = forum_prepare_editor_content(
                            $first_post["text"] ?? "",
                        );
                        
                        if (file_exists(ENGINE_DIR . "/editor/forum.php")) {
                            include ENGINE_DIR . "/editor/forum.php";
                        } else {
                            include DLEPlugins::Check(ENGINE_DIR . "/editor/forum.php");
                        }
                        
                        // Fallback kontrolü (Defensive Fallback)
                        if (empty($wysiwyg)) {
                            echo '<textarea id="forum-editor" name="text" style="width:100%;height:350px;" class="form-control">' . $first_post_html . '</textarea>';
                        } else {
                            if (strpos($wysiwyg, '></textarea>') !== false) {
                                $wysiwyg_with_val = str_replace('></textarea>', '>' . $first_post_html . '</textarea>', $wysiwyg);
                                echo $wysiwyg_with_val;
                            } else {
                                echo $wysiwyg;
                                ?>
                                <script>
                                document.getElementById("forum-editor").value = <?php echo json_encode($first_post_html); ?>;
                                </script>
                                <?php
                            }
                        }
                        
                        // Admin panelinde $onload_scripts otomatik yazdırılmadığı için güvenli bir şekilde jQuery hazır olduğunda yazdırıyoruz
                        if (!empty($onload_scripts)) {
                            echo "\n<script>\njQuery(function($){\n" . implode("\n", $onload_scripts) . "\n});\n</script>\n";
                        }
                        ?>
                    </div>
                </div>

                    </div>
                </div>

                <div class="tab-pane" id="tabvote">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label col-sm-2"><?php echo $lang['forum_topic_poll_q']; ?></label>
                            <div class="col-sm-10">
                                <input type="text" name="poll_question" class="form-control" value="<?php echo htmlspecialchars($poll['question'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo $lang['forum_topic_poll_q_ph']; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-2"><?php echo $lang['forum_topic_poll_opts']; ?> <br><span class="text-muted text-size-small">(<?php echo $lang['forum_topic_poll_opts_desc']; ?>)</span></label>
                            <div class="col-sm-10">
                                <textarea name="poll_options" rows="7" class="form-control" placeholder="<?php echo htmlspecialchars($lang['forum_topic_poll_opts_ph'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($poll_options_str, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-2"><?php echo $lang['forum_topic_poll_max']; ?></label>
                            <div class="col-sm-4">
                                <input type="number" name="poll_max_choices" class="form-control" style="width: 120px;" value="<?php echo intval($poll['max_choices'] ?? 1); ?>" min="1">
                                <span class="help-block text-muted text-size-small"><?php echo $lang['forum_topic_poll_max_desc']; ?></span>
                            </div>
                            <label class="control-label col-sm-2"><?php echo $lang['forum_topic_poll_days']; ?></label>
                            <div class="col-sm-4">
                                <input type="number" name="poll_days" class="form-control" style="width: 120px;" value="<?php echo intval($poll_days); ?>" min="0">
                                <span class="help-block text-muted text-size-small"><?php echo $lang['forum_topic_poll_days_desc']; ?></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-2"><?php echo $lang['forum_topic_poll_status']; ?></label>
                            <div class="col-sm-10">
                                <label class="checkbox-inline" style="padding-top: 0;">
                                    <input type="checkbox" class="icheck" name="poll_multiple" value="1"<?php echo ($poll && $poll['multiple']) ? ' checked' : ''; ?>> <?php echo $lang['forum_topic_poll_multiple']; ?>
                                </label>
                                <label class="checkbox-inline" style="padding-top: 0; margin-left: 20px;">
                                    <input type="checkbox" class="icheck" name="poll_is_closed" value="1"<?php echo ($poll && $poll['is_closed']) ? ' checked' : ''; ?>> <?php echo $lang['forum_topic_poll_closed']; ?>
                                </label>
                                <?php if ($poll && $poll['id']): ?>
                                <label class="checkbox-inline" style="padding-top: 0; margin-left: 20px; color: #dc2626;">
                                    <input type="checkbox" class="icheck" name="delete_poll" value="1"> <?php echo $lang['forum_topic_poll_delete']; ?>
                                </label>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="tabaccess">
                    <div class="panel-body">
                        <div class="alert alert-info alert-styled-left alert-arrow-left alert-bordered mb-20">
                            <button type="button" class="close" data-dismiss="alert"><span>×</span><span class="sr-only">Kapat</span></button>
                            <span class="text-semibold"><?php echo $lang['forum_topic_info']; ?></span> <?php echo $lang['forum_topic_access_desc']; ?>
                        </div>

                        <?php
                        $access = [];
                        if (!empty($topic['access'])) {
                            $rules = explode("||", $topic['access']);
                            foreach ($rules as $rule) {
                                $parts = explode(":", $rule);
                                if (count($parts) == 2) {
                                    $access[intval($parts[0])] = intval($parts[1]);
                                }
                            }
                        }

                        foreach ($user_group as $group):
                            $val = isset($access[$group['id']]) ? $access[$group['id']] : 0;
                        ?>
                        <div class="form-group">
                            <label class="control-label col-sm-2"><?php echo htmlspecialchars($group['group_name'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <div class="col-sm-10">
                                <select name="group_extra[<?php echo $group['id']; ?>]" class="uniform" style="width: 250px;">
                                    <option value="0"<?php echo $val == 0 ? ' selected' : ''; ?>><?php echo $lang['forum_topic_acc_def']; ?></option>
                                    <option value="1"<?php echo $val == 1 ? ' selected' : ''; ?>><?php echo $lang['forum_topic_acc_read']; ?></option>
                                    <option value="2"<?php echo $val == 2 ? ' selected' : ''; ?>><?php echo $lang['forum_topic_acc_write']; ?></option>
                                    <option value="3"<?php echo $val == 3 ? ' selected' : ''; ?>><?php echo $lang['forum_topic_acc_deny']; ?></option>
                                </select>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="panel-footer" style="padding: 15px; background: #fafafa; border-top: 1px solid #e5e7eb;">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" name="save_topic" class="btn bg-teal btn-raised btn-sm"><i class="fa fa-check position-left"></i> <?php echo $lang['forum_btn_save']; ?></button>
                    <a href="?mod=forum&action=topics" class="btn btn-default btn-raised btn-sm" style="margin-left: 10px;"><i class="fa fa-times position-left"></i> <?php echo $lang['forum_btn_cancel']; ?></a>
                </div>
            </div>
        </form>
    </div>
    <?php
    return;
}

// -------------------------------------------------
// GÖRÜNÜM: LİSTELEME EKRANI (DEFAULT TABLE LIST)
// -------------------------------------------------
$_SESSION["admin_referrer"] = htmlspecialchars($_SERVER["REQUEST_URI"], ENT_QUOTES, "UTF-8");

$_REQUEST["search_title"] = isset($_REQUEST["search_title"]) ? $_REQUEST["search_title"] : "";
$_REQUEST["search_author"] = isset($_REQUEST["search_author"]) ? $_REQUEST["search_author"] : "";
$_REQUEST["search_cat"] = isset($_REQUEST["search_cat"]) ? intval($_REQUEST["search_cat"]) : 0;
$_REQUEST["status"] = isset($_REQUEST["status"]) ? intval($_REQUEST["status"]) : 0;
$_REQUEST["from_date"] = isset($_REQUEST["from_date"]) ? $_REQUEST["from_date"] : "";
$_REQUEST["to_date"] = isset($_REQUEST["to_date"]) ? $_REQUEST["to_date"] : "";
$_REQUEST["sort"] = isset($_REQUEST["sort"]) ? totranslit($_REQUEST["sort"]) : "date_desc";

$search_title = $db->safesql(trim($_REQUEST["search_title"]));
$search_author = $db->safesql(trim($_REQUEST["search_author"]));
$search_cat = intval($_REQUEST["search_cat"]);
$status = intval($_REQUEST["status"]);
$from_date = $db->safesql(trim($_REQUEST["from_date"]));
$to_date = $db->safesql(trim($_REQUEST["to_date"]));
$sort = totranslit($_REQUEST["sort"]);

$start_from = isset($_REQUEST["start_from"]) ? intval($_REQUEST["start_from"]) : 0;
$per_page = isset($_REQUEST["per_page"]) ? intval($_REQUEST["per_page"]) : 50;
if ($per_page < 1) $per_page = 50;

$where = [];
if (!empty($search_title)) {
    $where[] = "t.title LIKE '%{$search_title}%'";
}
if (!empty($search_author)) {
    $where[] = "u.name LIKE '{$search_author}%'";
}
if ($search_cat > 0) {
    $where[] = "t.cat_id = '{$search_cat}'";
}
if (!empty($from_date)) {
    $where[] = "t.date >= '{$from_date}'";
}
if (!empty($to_date)) {
    $where[] = "t.date <= '{$to_date}'";
}

if ($status == 1) {
    $where[] = "t.is_approved = 1 AND t.is_deleted = 0";
} elseif ($status == 2) {
    $where[] = "t.is_approved = 0 AND t.is_deleted = 0";
} elseif ($status == 3) {
    $where[] = "t.is_pinned = 1 AND t.is_deleted = 0";
} elseif ($status == 4) {
    $where[] = "t.is_locked = 1 AND t.is_deleted = 0";
} elseif ($status == 5) {
    $where[] = "t.is_deleted = 1";
} else {
    // Varsayılan olarak çöp kutusunu listeleme, is_deleted = 0 olanları getir
    if ($status != 5) {
        $where[] = "t.is_deleted = 0";
    }
}

$where_clause = count($where) ? " WHERE " . implode(" AND ", $where) : "";

// Sıralama
$order_by = "t.is_pinned DESC, t.last_bump_date DESC";
if ($sort == "date_asc") {
    $order_by = "t.date ASC";
} elseif ($sort == "replies_desc") {
    $order_by = "t.replies DESC";
} elseif ($sort == "views_desc") {
    $order_by = "t.views DESC";
} elseif ($sort == "title_asc") {
    $order_by = "t.title ASC";
}

// Konuları çek
$db->query("SELECT t.*, u.name as author_name, c.name as cat_name 
    FROM " . PREFIX . "_forum_topics t 
    LEFT JOIN " . PREFIX . "_users u ON u.user_id = t.user_id 
    LEFT JOIN " . PREFIX . "_forum_cats c ON c.id = t.cat_id
    " . $where_clause . " 
    ORDER BY " . $order_by . " 
    LIMIT {$start_from}, {$per_page}");

$entries_showed = 0;
$entries = "";

while ($row = $db->get_row()) {
    $entries_showed++;
    $itemdate = date("d.m.Y H:i", strtotime($row["date"]));
    $title = htmlspecialchars(stripslashes($row["title"]), ENT_QUOTES, "UTF-8");

    $edit_url = "?mod=forum&action=topics&subaction=edit&id=" . $row["id"];
    $frontend_url = $config["http_home_url"] . "forum/topic/" . $row["id"] . "-" . $row["alt_name"] . ".html";

    // Badges / Durum ikonları
    $badges = "";
    if ($row["is_pinned"]) {
        $badges .= '<span class="badge badge-danger position-left" style="background:#dc2626;">' . $lang['forum_topic_badge_pin'] . '</span> ';
    }
    if ($row["is_locked"]) {
        $badges .= '<i class="fa fa-lock text-muted position-left" title="' . $lang['forum_topic_badge_lock'] . '"></i> ';
    }
    if ($row["is_deleted"]) {
        $badges .= '<span class="badge badge-warning position-left" style="background:#d97706;">' . $lang['forum_topic_badge_trash'] . '</span> ';
    }

    // Onay durumu
    if ($row["is_approved"]) {
        $approve_icon = '<span class="text-success"><i class="fa fa-check-circle" style="font-size: 15px;"></i></span>';
    } else {
        $approve_icon = '<span class="text-danger"><i class="fa fa-exclamation-circle" style="font-size: 15px;"></i></span>';
    }

    $entries .= '<tr>';
    $entries .= '<td class="hidden-xs hidden-sm text-nowrap cursor-pointer" onclick="document.location=\'' . $edit_url . '\';">' . $itemdate . '</td>';
    $entries .= '<td class="cursor-pointer" onclick="document.location=\'' . $edit_url . '\';">';
    $entries .= $badges . '<a href="' . $edit_url . '" style="font-weight:600; color:#1e293b;">' . $title . '</a>';
    $entries .= '</td>';
    $entries .= '<td class="hidden-xs text-center"><a href="' . $frontend_url . '" target="_blank" class="tip" title="' . $lang['forum_topic_view_site'] . '">' . number_format($row["views"]) . '</a></td>';
    $entries .= '<td class="hidden-xs text-center">' . number_format($row["replies"]) . '</td>';
    $entries .= '<td class="text-center">' . $approve_icon . '</td>';
    $entries .= '<td class="hidden-xs text-center">' . htmlspecialchars($row["cat_name"] ?: $lang['forum_topic_unknown'], ENT_QUOTES, "UTF-8") . '</td>';
    $entries .= '<td class="hidden-xs hidden-sm"><a href="?mod=editusers&action=edituser&user=' . urlencode($row["author_name"]) . '" target="_blank">' . htmlspecialchars($row["author_name"] ?: $lang['forum_topic_guest'], ENT_QUOTES, "UTF-8") . '</a></td>';
    $entries .= '<td style="text-align: center"><input name="selected_topics[]" value="' . $row["id"] . '" type="checkbox" class="icheck"></td>';
    $entries .= '</tr>';
}

// Toplam sayı
$total_row = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_forum_topics t LEFT JOIN " . PREFIX . "_users u ON u.user_id = t.user_id " . $where_clause);
$all_topics_count = intval($total_row["count"]);

// Arama modali ve şablonu
?>
<script>
    function search_submit(prm){
        document.optionsbar.start_from.value=prm;
        document.optionsbar.submit();
        return false;
    }
    function check_uncheck_all() {
        var frm = document.topics_form;
        for (var i=0; i<frm.elements.length; i++) {
            var elmnt = frm.elements[i];
            if (elmnt.type=='checkbox' && elmnt.name !== 'master_box') {
                if (frm.master_box.checked == true) {
                    elmnt.checked = false;
                    $(elmnt).parents('tr').removeClass('warning');
                } else {
                    elmnt.checked = true;
                    $(elmnt).parents('tr').addClass('warning');
                }
            }
        }
        if (frm.master_box.checked == true) {
            frm.master_box.checked = false;
        } else {
            frm.master_box.checked = true;
        }
    }
    $(function() {
        $('.table').find('tr > td:last-child').find('input[type=checkbox]').on('change', function() {
            if ($(this).is(':checked')) {
                $(this).parents('tr').addClass('warning');
            } else {
                $(this).parents('tr').removeClass('warning');
            }
        });
        
        $('select[name="mass_action"]').on('change', function() {
            if ($(this).val() == 'move') {
                $('#target_cat_select_container').show();
            } else {
                $('#target_cat_select_container').hide();
            }
        });
    });
</script>

<div class="modal fade" id="advancedsearch" tabindex="-1" role="dialog" aria-labelledby="advancedsearchLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="?mod=forum&action=topics" method="GET" name="optionsbar" id="optionsbar">
                <input type="hidden" name="mod" value="forum">
                <input type="hidden" name="action" value="topics">
                <input type="hidden" name="start_from" id="start_from" value="<?php echo $start_from; ?>">
                
                <div class="modal-header ui-dialog-titlebar">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <span class="ui-dialog-title"><?php echo $lang['forum_topic_search_title']; ?></span>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <div class="row">
                            <div class="col-sm-6">
                                <label><?php echo $lang['forum_topic_search_keyword']; ?></label>
                                <input name="search_title" value="<?php echo htmlspecialchars($_REQUEST["search_title"], ENT_QUOTES, "UTF-8"); ?>" type="text" class="form-control" placeholder="<?php echo $lang['forum_topic_search_keyword_ph']; ?>">
                            </div>
                            <div class="col-sm-6">
                                <label><?php echo $lang['forum_topic_search_author']; ?></label>
                                <input name="search_author" value="<?php echo htmlspecialchars($_REQUEST["search_author"], ENT_QUOTES, "UTF-8"); ?>" type="text" class="form-control" placeholder="<?php echo $lang['forum_topic_search_author_ph']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-sm-6">
                                <label><?php echo $lang['forum_stat_cats']; ?></label>
                                <select name="search_cat" class="uniform" style="width:100%;">
                                    <option value="0"><?php echo $lang['forum_topic_all_cats']; ?></option>
                                    <?php echo catDropdown($categories, $search_cat); ?>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label><?php echo $lang['forum_topic_status']; ?></label>
                                <select class="uniform" style="width:100%" name="status">
                                    <option value="0"<?php echo $status == 0 ? " selected" : ""; ?>><?php echo $lang['forum_topic_st_all']; ?></option>
                                    <option value="1"<?php echo $status == 1 ? " selected" : ""; ?>><?php echo $lang['forum_topic_st_appr']; ?></option>
                                    <option value="2"<?php echo $status == 2 ? " selected" : ""; ?>><?php echo $lang['forum_topic_st_unappr']; ?></option>
                                    <option value="3"<?php echo $status == 3 ? " selected" : ""; ?>><?php echo $lang['forum_topic_st_pin']; ?></option>
                                    <option value="4"<?php echo $status == 4 ? " selected" : ""; ?>><?php echo $lang['forum_topic_st_lock']; ?></option>
                                    <option value="5"<?php echo $status == 5 ? " selected" : ""; ?>><?php echo $lang['forum_topic_st_trash']; ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <div class="col-sm-6">
                                <label><?php echo $lang['forum_topic_date_start']; ?></label>
                                <input data-rel="calendar" class="form-control" type="text" name="from_date" value="<?php echo htmlspecialchars($_REQUEST["from_date"], ENT_QUOTES, "UTF-8"); ?>" autocomplete="off">
                            </div>
                            <div class="col-sm-6">
                                <label><?php echo $lang['forum_topic_date_end']; ?></label>
                                <input data-rel="calendar" class="form-control" type="text" name="to_date" value="<?php echo htmlspecialchars($_REQUEST["to_date"], ENT_QUOTES, "UTF-8"); ?>" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <div class="col-sm-6">
                                <label><?php echo $lang['forum_topic_sort']; ?></label>
                                <select class="uniform" style="width:100%" name="sort">
                                    <option value="date_desc"<?php echo $sort == "date_desc" ? " selected" : ""; ?>><?php echo $lang['forum_topic_sort_date_desc']; ?></option>
                                    <option value="date_asc"<?php echo $sort == "date_asc" ? " selected" : ""; ?>><?php echo $lang['forum_topic_sort_date_asc']; ?></option>
                                    <option value="replies_desc"<?php echo $sort == "replies_desc" ? " selected" : ""; ?>><?php echo $lang['forum_topic_sort_rep_desc']; ?></option>
                                    <option value="views_desc"<?php echo $sort == "views_desc" ? " selected" : ""; ?>><?php echo $lang['forum_topic_sort_view_desc']; ?></option>
                                    <option value="title_asc"<?php echo $sort == "title_asc" ? " selected" : ""; ?>><?php echo $lang['forum_topic_sort_title_asc']; ?></option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label><?php echo $lang['forum_topic_per_page']; ?></label>
                                <input class="form-control text-center" name="per_page" value="<?php echo $per_page; ?>" type="number">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button onclick="search_submit(0); return(false);" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-search position-left"></i> <?php echo $lang['forum_topic_btn_search']; ?></button>
                    <button onclick="document.location='?mod=forum&action=topics'; return(false);" class="btn bg-danger btn-sm btn-raised"><i class="fa fa-eraser position-left"></i> <?php echo $lang['forum_topic_btn_reset']; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($entries_showed == 0): ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <?php echo $lang['forum_topic_list']; ?>
            <div class="heading-elements not-collapsible">
                <ul class="icons-list">
                    <li><a data-toggle="modal" data-target="#advancedsearch" href="#"><i class="fa fa-search position-left"></i><span><?php echo $lang['forum_topic_adv_search']; ?></span></a></li>
                </ul>
            </div>
        </div>
        <div class="panel-body">
            <div style="display: table; min-height: 150px; width: 100%;">
                <div class="text-center" style="display: table-cell; vertical-align: middle; color: #94a3b8; font-style: italic;">
                    <i class="fa fa-info-circle fa-2x mb-10" style="display: block;"></i> <?php echo $lang['forum_topic_not_found_list']; ?>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <form action="?mod=forum&action=topics" method="post" name="topics_form">
        <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $lang['forum_topic_list']; ?> (<?php echo $lang['forum_topic_showing']; ?> <?php echo $entries_showed; ?> — <?php echo $lang['forum_topic_total_found']; ?> <?php echo $all_topics_count; ?>)
                <div class="heading-elements not-collapsible">
                    <ul class="icons-list">
                        <li><a data-toggle="modal" data-target="#advancedsearch" href="#"><i class="fa fa-search position-left"></i><span><?php echo $lang['forum_topic_adv_search']; ?></span></a></li>
                    </ul>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-xs table-hover">
                    <thead>
                        <tr>
                            <th class="hidden-xs hidden-sm" style="width: 130px;"><?php echo $lang['forum_topic_col_date']; ?></th>
                            <th><?php echo $lang['forum_topic_title_label']; ?></th>
                            <th class="hidden-xs text-center" style="width: 80px;"><i class="fa fa-eye tip" title="<?php echo $lang['forum_topic_col_views']; ?>"></i></th>
                            <th class="hidden-xs text-center" style="width: 80px;"><i class="fa fa-comment-o tip" title="<?php echo $lang['forum_topic_col_replies']; ?>"></i></th>
                            <th style="width: 50px; text-align: center;"><?php echo $lang['forum_topic_col_appr']; ?></th>
                            <th class="hidden-xs text-center"><?php echo $lang['forum_stat_cats']; ?></th>
                            <th class="hidden-xs hidden-sm" style="width: 150px;"><?php echo $lang['forum_topic_col_author']; ?></th>
                            <th style="width: 40px; text-align: center;"><input type="checkbox" name="master_box" title="<?php echo $lang['forum_topic_select_all']; ?>" onclick="javascript:check_uncheck_all();" class="icheck"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $entries; ?>
                    </tbody>
                </table>
            </div>

            <div class="panel-footer" style="padding: 15px;">
                <div class="pull-right">
                    <span id="target_cat_select_container" style="display: none; margin-right: 10px;">
                        <select name="target_cat_id" class="uniform" style="width: 200px;">
                            <option value="0"><?php echo $lang['forum_topic_sel_target']; ?></option>
                            <?php echo catDropdown($categories, 0); ?>
                        </select>
                    </span>
                    <select name="mass_action" class="uniform position-left" style="width: 180px;">
                        <option value=""><?php echo $lang['forum_topic_sel_mass']; ?></option>
                        <option value="approve"><?php echo $lang['forum_topic_mass_approve']; ?></option>
                        <option value="unapprove"><?php echo $lang['forum_topic_mass_unapprove_opt']; ?></option>
                        <option value="pin"><?php echo $lang['forum_topic_mass_pin_opt']; ?></option>
                        <option value="unpin"><?php echo $lang['forum_topic_mass_unpin_opt']; ?></option>
                        <option value="lock"><?php echo $lang['forum_topic_mass_lock_opt']; ?></option>
                        <option value="unlock"><?php echo $lang['forum_topic_mass_unlock_opt']; ?></option>
                        <option value="move"><?php echo $lang['forum_topic_mass_move_opt']; ?></option>
                        <option value="trash"><?php echo $lang['forum_topic_mass_trash_opt']; ?></option>
                        <option value="restore"><?php echo $lang['forum_topic_mass_restore_opt']; ?></option>
                        <option value="delete"><?php echo $lang['forum_topic_mass_del_opt']; ?></option>
                    </select>
                    <input class="btn bg-teal btn-sm btn-raised" type="submit" name="mass_action_submit" value="<?php echo $lang['forum_topic_btn_start']; ?>">
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </form>

    <?php
    // Sayfalama (Pagination)
    if ($all_topics_count > $per_page) {
        $npp_nav = "";
        
        if ($start_from > 0) {
            $previous = $start_from - $per_page;
            $npp_nav .= '<li><a onclick="javascript:search_submit(' . $previous . '); return(false);" href="#" title="' . $lang['forum_topic_prev'] . '"><i class="fa fa-backward"></i></a></li>';
        }
        
        $enpages_count = ceil($all_topics_count / $per_page);
        $enpages_start_from = 0;
        $enpages = "";

        for ($j = 1; $j <= $enpages_count; $j++) {
            if ($enpages_start_from != $start_from) {
                $enpages .= '<li><a onclick="javascript:search_submit(' . $enpages_start_from . '); return(false);" href="#">' . $j . '</a></li>';
            } else {
                $enpages .= '<li class="active"><span>' . $j . '</span></li>';
            }
            $enpages_start_from += $per_page;
        }

        $npp_nav .= $enpages;

        if ($start_from + $per_page < $all_topics_count) {
            $next = $start_from + $per_page;
            $npp_nav .= '<li><a onclick="javascript:search_submit(' . $next . '); return(false);" href="#" title="' . $lang['forum_topic_next'] . '"><i class="fa fa-forward"></i></a></li>';
        }

        echo '<div class="text-center mt-20 mb-20"><ul class="pagination pagination-sm">' . $npp_nav . '</ul></div>';
    }
endif;
