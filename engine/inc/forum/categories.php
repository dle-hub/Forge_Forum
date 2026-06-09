<?php
/*
=====================================================
 Forge Forum Engine — Kategori Yönetimi
-----------------------------------------------------
 File: engine/inc/forum/categories.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/

if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

include_once DLEPlugins::Check(ENGINE_DIR . "/inc/forum/cat_icon_helper.php");

$cat_id = isset($_REQUEST["cat_id"]) ? intval($_REQUEST["cat_id"]) : 0;
$subaction = isset($_REQUEST["subaction"])
    ? totranslit($_REQUEST["subaction"])
    : "";

function forum_getCatTree($cats, $parent_id = 0)
{
    $result = [];
    foreach ($cats as $cat) {
        if (intval($cat["parent_id"]) == $parent_id) {
            $result[] = $cat;
            $children = forum_getCatTree($cats, $cat["id"]);
            $result = array_merge($result, $children);
        }
    }
    return $result;
}

function forum_parentDropdown($cats, $selected = 0, $exclude_id = 0)
{
    global $lang;
    $opts = '<option value="0">' . $lang['forum_cat_main_cat'] . '</option>';
    $tree = forum_getCatTree($cats);
    foreach ($tree as $c) {
        if ($c["id"] == $exclude_id) {
            continue;
        }
        $sel = $c["id"] == $selected ? " selected" : "";
        $indent = str_repeat(
            "&nbsp;&nbsp;",
            isset($c["depth"]) ? $c["depth"] : 0,
        );
        $opts .=
            '<option value="' .
            $c["id"] .
            '"' .
            $sel .
            ">" .
            $indent .
            htmlspecialchars($c["name"], ENT_QUOTES, "UTF-8") .
            "</option>";
    }
    return $opts;
}

// =================================================================
// POST: SİL
// =================================================================
if ($subaction == "delete" && $cat_id > 0) {
    if ($_REQUEST["dle_post_hash"] !== $dle_login_hash) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }

    function forum_deleteCatRecursive($cat_id)
    {
        global $db;
        $db->query(
            "SELECT id FROM " .
                PREFIX .
                "_forum_cats WHERE parent_id='{$cat_id}'",
        );
        while ($row = $db->get_row()) {
            forum_deleteCatRecursive($row["id"]);
        }
        $db->query(
            "UPDATE " .
                PREFIX .
                "_forum_topics SET cat_id=0 WHERE cat_id='{$cat_id}'",
        );
        $db->query(
            "DELETE FROM " . PREFIX . "_forum_cats WHERE id='{$cat_id}'",
        );
    }
    forum_deleteCatRecursive($cat_id);
    msg(
        "success",
        $lang['forum_set_success_title'],
        $lang['forum_cat_deleted'],
        "?mod=forum&action=categories",
    );
    return;
}

// =================================================================
// POST: KAYDET / GÜNCELLE
// =================================================================
if (isset($_POST["save_cat"])) {
    if ($_POST["dle_post_hash"] !== $dle_login_hash) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }

    $cat_name = $db->safesql(trim($_POST["cat_name"]));
    $cat_alt_name = $db->safesql(trim($_POST["cat_alt_name"]));
    $cat_description = $db->safesql(trim($_POST["cat_description"]));
    $cat_icon_raw = trim((string) ($_POST["cat_icon"] ?? ""));
    $cat_icon = $db->safesql(
        $cat_icon_raw !== ""
            ? forum_normalize_cat_icon($cat_icon_raw)
            : forum_cat_icon_suggest($cat_name, $cat_parent),
    );
    $cat_parent = intval($_POST["cat_parent"]);
    $cat_permissions = $db->safesql(trim($_POST["cat_permissions"]));
    $cat_posi = intval($_POST["cat_posi"]);

    if (empty($cat_name)) {
        msg("error", $lang['forum_error_title'], $lang['forum_cat_empty_name']);
        return;
    }
    if (empty($cat_alt_name)) {
        $cat_alt_name = $db->safesql(totranslit($cat_name));
    }
    if (empty($cat_permissions)) {
        $cat_permissions = "all";
    }
    if ($cat_id > 0) {
        $db->query(
            "UPDATE " .
                PREFIX .
                "_forum_cats SET
            parent_id='{$cat_parent}', name='{$cat_name}', alt_name='{$cat_alt_name}',
            description='{$cat_description}', icon='{$cat_icon}', posi='{$cat_posi}',
            permissions='{$cat_permissions}' WHERE id='{$cat_id}'",
        );
        msg(
            "success",
            $lang['forum_set_success_title'],
            $lang['forum_cat_updated'],
            "?mod=forum&action=categories",
        );
    } else {
        $db->query(
            "INSERT INTO " .
                PREFIX .
                "_forum_cats
            (parent_id, name, alt_name, description, icon, posi, permissions)
            VALUES ('{$cat_parent}','{$cat_name}','{$cat_alt_name}','{$cat_description}','{$cat_icon}','{$cat_posi}','{$cat_permissions}')",
        );
        msg(
            "success",
            $lang['forum_set_success_title'],
            $lang['forum_cat_added'],
            "?mod=forum&action=categories",
        );
    }
    return;
}

// =================================================================
// TÜM KATEGORİLERİ ÇEK
// =================================================================
$all_cats = [];
$db->query("SELECT * FROM " . PREFIX . "_forum_cats ORDER BY posi ASC, id ASC");
while ($row = $db->get_row()) {
    $all_cats[$row["id"]] = $row;
}
foreach ($all_cats as $id => $cat) {
    $depth = 0;
    $pid = $cat["parent_id"];
    while ($pid > 0 && isset($all_cats[$pid])) {
        $depth++;
        $pid = $all_cats[$pid]["parent_id"];
    }
    $all_cats[$id]["depth"] = $depth;
}

// =================================================================
// DÜZENLEME VERİSİ
// =================================================================
$edit_cat = null;
if ($cat_id > 0 && $subaction != "delete") {
    $edit_cat = $db->super_query(
        "SELECT * FROM " . PREFIX . "_forum_cats WHERE id='{$cat_id}'",
    );
    if (!$edit_cat["id"]) {
        $cat_id = 0;
    }
}

// =================================================================
// DLE NATIVE: DisplayForumCategories
// =================================================================
function DisplayForumCategories($parentid = 0, $sublevelmarker = false)
{
    global $all_cats, $dle_login_hash, $lang;
    $out = "";
    $roots = [];
    foreach ($all_cats as $c) {
        if (intval($c["parent_id"]) == $parentid) {
            $roots[] = $c;
        }
    }
    if (count($roots)) {
        foreach ($roots as $cat) {
            $name = htmlspecialchars($cat["name"], ENT_QUOTES, "UTF-8");
            $icon_html = forum_cat_icon_render_html(
                $cat["icon"],
                "position-left",
            );
            $tcount = intval($cat["topic_count"]);
            $pcount = intval($cat["post_count"]);
            $counts = "<div class=\"visible-md-inline-block visible-lg-inline-block mr-20\" style=\"display:inline-block;\">{$lang['forum_stat_topics']}: {$tcount} | {$lang['forum_stat_posts']}: {$pcount}</div>";

            $out .= "<li class=\"dd-item\" data-id=\"{$cat["id"]}\">";
            $out .= "<div class=\"dd-handle\"></div>";
            $out .= "<div class=\"dd-content\">";
            $out .= "<b>ID:{$cat["id"]}</b> {$icon_html} {$name}";
            $out .= "<div class=\"pull-right\">{$counts}";
            $out .= "<a href=\"?mod=forum&action=categories&cat_id={$cat["id"]}\"><i title=\"{$lang['forum_btn_edit']}\" class=\"fa fa-pencil-square-o\"></i></a>&nbsp;&nbsp;";
            $out .= "<a onclick=\"return confirm('{$lang['forum_cat_del_confirm']}');\" href=\"?mod=forum&action=categories&cat_id={$cat["id"]}&subaction=delete&dle_post_hash={$dle_login_hash}\"><i title=\"{$lang['forum_btn_delete']}\" class=\"fa fa-trash-o text-danger\"></i></a>";
            $out .= "</div></div>";
            $children = DisplayForumCategories($cat["id"], true);
            $out .= $children;
            $out .= "</li>";
        }
        if ($sublevelmarker) {
            return "<ol class=\"dd-list\">" . $out . "</ol>";
        } else {
            return $out;
        }
    }
    return "";
}
?>

<!-- ============================================================ -->
<!-- YENİ KATEGORİ MODAL -->
<!-- ============================================================ -->
<div class="modal fade" id="newForumCat" tabindex="-1" role="dialog" aria-labelledby="newForumCatLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form method="post" action="?mod=forum&action=categories" autocomplete="off">
        <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">
        <div class="modal-header ui-dialog-titlebar">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <span class="ui-dialog-title" id="newForumCatLabel"><?php echo $lang['forum_cat_new_title']; ?></span>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <div class="row">
              <div class="col-sm-6">
                <label><?php echo $lang['forum_cat_name']; ?> <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input name="cat_name" type="text" dir="auto" class="form-control" maxlength="100" required>
                  <span class="input-group-addon"><i class="fa fa-question-circle text-primary-600" data-rel="popover" data-trigger="hover" data-placement="auto right" data-content="<?php echo $lang['forum_cat_name_desc']; ?>"></i></span>
                </div>
              </div>
              <div class="col-sm-6">
                <label><?php echo $lang['forum_cat_alt_name']; ?></label>
                <div class="input-group">
                  <input name="cat_alt_name" type="text" dir="auto" class="form-control" maxlength="100">
                  <span class="input-group-addon"><i class="fa fa-question-circle text-primary-600" data-rel="popover" data-trigger="hover" data-placement="auto right" data-content="<?php echo $lang['forum_cat_alt_name_desc']; ?>"></i></span>
                </div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">
              <div class="col-sm-12">
                <label><?php echo $lang['forum_cat_desc']; ?></label>
                <textarea name="cat_description" dir="auto" class="form-control" style="width:100%;" rows="3"></textarea>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">
              <div class="col-sm-6">
                <label><?php echo $lang['forum_cat_icon']; ?></label>
                <?php echo forum_cat_icon_picker("fa-folder-open", "cat_icon_new"); ?>
              </div>
              <div class="col-sm-6">
                <label><?php echo $lang['forum_cat_parent']; ?></label>
                <select class="uniform" name="cat_parent" data-width="100%" data-live-search="true" data-none-results-text="<?php echo $lang['forum_cat_not_found']; ?>">
                  <?php echo forum_parentDropdown($all_cats, 0, 0); ?>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">
              <div class="col-sm-6">
                <label><?php echo $lang['forum_cat_posi']; ?></label>
                <input name="cat_posi" type="number" class="form-control" value="0">
              </div>
              <div class="col-sm-6">
                <label><?php echo $lang['forum_cat_perms']; ?></label>
                <input name="cat_permissions" type="text" class="form-control" value="all" maxlength="100">
                <span class="help-block text-muted text-size-small"><?php echo $lang['forum_cat_perms_desc']; ?></span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="margin-top:-1.39em;">
          <button type="button" class="btn bg-grey-400 btn-sm btn-raised" data-dismiss="modal"><?php echo $lang['forum_btn_cancel']; ?></button>
          <button type="submit" name="save_cat" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-floppy-o position-left"></i><?php echo $lang['forum_btn_save']; ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- DÜZENLEME PANELİ -->
<!-- ============================================================ -->
<?php if ($cat_id > 0 && $edit_cat): ?>
<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_cat_edit_title']; ?> <strong><?php echo htmlspecialchars(
        $edit_cat["name"],
        ENT_QUOTES,
        "UTF-8",
    ); ?></strong>
    <div class="heading-elements">
      <ul class="icons-list">
        <li><a href="?mod=forum&action=categories"><i class="fa fa-times"></i></a></li>
      </ul>
    </div>
  </div>
  <div class="panel-body">
    <form method="post" action="?mod=forum&action=categories&cat_id=<?php echo $cat_id; ?>" class="form-horizontal" autocomplete="off">
      <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_cat_name']; ?> <span class="text-danger">*</span></label>
        <div class="col-md-10 col-sm-9">
          <input class="form-control width-350" value="<?php echo htmlspecialchars(
              $edit_cat["name"],
              ENT_QUOTES,
              "UTF-8",
          ); ?>" maxlength="100" type="text" dir="auto" name="cat_name" required>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_cat_alt_name']; ?></label>
        <div class="col-md-10 col-sm-9">
          <input class="form-control width-350" value="<?php echo htmlspecialchars(
              $edit_cat["alt_name"],
              ENT_QUOTES,
              "UTF-8",
          ); ?>" maxlength="100" type="text" dir="auto" name="cat_alt_name">
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_cat_desc']; ?></label>
        <div class="col-md-10 col-sm-9">
          <textarea name="cat_description" dir="auto" class="classic" style="width:100%;max-width:38.19em;" rows="3"><?php echo htmlspecialchars(
              $edit_cat["description"],
              ENT_QUOTES,
              "UTF-8",
          ); ?></textarea>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_cat_icon']; ?></label>
        <div class="col-md-10 col-sm-9">
          <?php echo forum_cat_icon_picker(
              $edit_cat["icon"] ?: "fa-folder-open",
              "cat_icon_edit",
          ); ?>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_cat_parent']; ?></label>
        <div class="col-md-10 col-sm-9">
          <select class="uniform" name="cat_parent" data-width="350" data-live-search="true" data-none-results-text="<?php echo $lang['forum_cat_not_found']; ?>">
            <?php echo forum_parentDropdown(
                $all_cats,
                intval($edit_cat["parent_id"]),
                $cat_id,
            ); ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_cat_posi']; ?></label>
        <div class="col-md-10 col-sm-9">
          <input class="form-control width-100" value="<?php echo intval(
              $edit_cat["posi"],
          ); ?>" type="number" name="cat_posi">
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_cat_perms']; ?></label>
        <div class="col-md-10 col-sm-9">
          <input class="form-control width-350" value="<?php echo htmlspecialchars(
              $edit_cat["permissions"],
              ENT_QUOTES,
              "UTF-8",
          ); ?>" maxlength="100" type="text" dir="auto" name="cat_permissions">
          <span class="help-block text-muted text-size-small"><?php echo $lang['forum_cat_perms_desc2']; ?></span>
        </div>
      </div>
  </div>
  <div class="panel-footer">
    <button type="submit" name="save_cat" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-floppy-o position-left"></i><?php echo $lang['forum_btn_update']; ?></button>
    <a href="?mod=forum&action=categories" class="btn bg-grey-400 btn-sm btn-raised"><?php echo $lang['forum_btn_cancel']; ?></a>
  </div>
  </form>
</div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- KATEGORİ LİSTESİ (DD Nestable) -->
<!-- ============================================================ -->
<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_cat_list_title']; ?>
    <div class="heading-elements">
      <ul class="icons-list">
        <li><a href="#" data-toggle="modal" data-target="#newForumCat"><i class="fa fa-plus-circle position-left"></i><?php echo $lang['forum_cat_add_btn']; ?></a></li>
      </ul>
    </div>
  </div>
  <?php if (count($all_cats) > 0): ?>
  <div class="panel-body">
    <div class="dd" id="forum-nestable">
      <ol class="dd-list">
        <?php echo DisplayForumCategories(); ?>
      </ol>
    </div>
  </div>
  <div class="panel-footer">
    <button class="btn bg-primary-600 btn-sm btn-raised position-left nestable-action" data-action="expand-all"><i class="fa fa-plus-square-o position-left"></i><?php echo $lang['forum_btn_expand']; ?></button>
    <button class="btn bg-primary-600 btn-sm btn-raised position-left nestable-action" data-action="collapse-all"><i class="fa fa-minus-square-o position-left"></i><?php echo $lang['forum_btn_collapse']; ?></button>
    <button class="btn bg-teal btn-sm btn-raised position-left" onclick="$('#newForumCat').modal({backdrop: 'static',keyboard: false}); return false;"><i class="fa fa-plus-circle position-left"></i><?php echo $lang['forum_cat_add_btn']; ?></button>
  </div>
  <?php else: ?>
  <div class="panel-body">
    <div class="alert alert-info no-margin"><?php echo $lang['forum_cat_no_cats']; ?></div>
  </div>
  <div class="panel-footer">
    <button class="btn bg-teal btn-sm btn-raised position-left" onclick="$('#newForumCat').modal({backdrop: 'static',keyboard: false}); return false;"><i class="fa fa-plus-circle position-left"></i><?php echo $lang['forum_cat_add_btn']; ?></button>
  </div>
  <?php endif; ?>
</div>

<?php echo forum_cat_icon_assets(); ?>
<script>
$(function(){
    $('#forum-nestable').nestable({maxDepth: 5});
    $('.nestable-action').on('click', function(e){
        var action = $(this).data('action');
        if (action === 'expand-all') $('#forum-nestable').nestable('expandAll');
        else $('#forum-nestable').nestable('collapseAll');
    });
});
</script>
