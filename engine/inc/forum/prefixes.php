<?php
/*
=====================================================
 Forge Forum Engine — Konu Önekleri
-----------------------------------------------------
 File: engine/inc/forum/prefixes.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/

if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

include_once DLEPlugins::Check(
    ENGINE_DIR . "/inc/forum/admin_ui_helper.php",
);

$prefix_id = isset($_REQUEST["prefix_id"]) ? intval($_REQUEST["prefix_id"]) : 0;
$subaction = isset($_REQUEST["subaction"])
    ? totranslit($_REQUEST["subaction"])
    : "";

// -------------------------------------------------
// POST: SİL
// -------------------------------------------------
if ($subaction == "delete" && $prefix_id > 0) {
    if ($_REQUEST["dle_post_hash"] !== $dle_login_hash) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }
    $db->query(
        "DELETE FROM " . PREFIX . "_forum_prefixes WHERE id='{$prefix_id}'",
    );
    msg("success", $lang['forum_set_success_title'], $lang['forum_prefix_deleted'], "?mod=forum&action=prefixes");
    return;
}

// -------------------------------------------------
// POST: KAYDET / GÜNCELLE
// -------------------------------------------------
if (isset($_POST["save_prefix"])) {
    if ($_POST["dle_post_hash"] !== $dle_login_hash) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }

    $pfx_name = $db->safesql(trim($_POST["prefix_name"]));
    $pfx_color = $db->safesql(trim($_POST["prefix_color"]));
    $pfx_icon = $db->safesql(trim($_POST["prefix_icon"]));
    $pfx_cat_id = intval($_POST["prefix_cat_id"]);
    $pfx_is_required = isset($_POST["prefix_is_required"]) ? 1 : 0;
    $pfx_posi = intval($_POST["prefix_posi"]);

    if (empty($pfx_name)) {
        msg("error", $lang['forum_error_title'], $lang['forum_prefix_empty_name']);
        return;
    }
    if (empty($pfx_color)) {
        $pfx_color = "#3498db";
    }
    if (empty($pfx_icon)) {
        $pfx_icon = "fa-tag";
    }

    if ($prefix_id > 0) {
        $db->query(
            "UPDATE " .
                PREFIX .
                "_forum_prefixes SET
            name='{$pfx_name}', color='{$pfx_color}', icon='{$pfx_icon}',
            cat_id='{$pfx_cat_id}', is_required='{$pfx_is_required}', posi='{$pfx_posi}'
            WHERE id='{$prefix_id}'",
        );
        msg(
            "success",
            $lang['forum_set_success_title'],
            $lang['forum_prefix_updated'],
            "?mod=forum&action=prefixes",
        );
    } else {
        $db->query(
            "INSERT INTO " .
                PREFIX .
                "_forum_prefixes
            (name, color, icon, cat_id, is_required, posi)
            VALUES ('{$pfx_name}','{$pfx_color}','{$pfx_icon}','{$pfx_cat_id}','{$pfx_is_required}','{$pfx_posi}')",
        );
        msg(
            "success",
            $lang['forum_set_success_title'],
            $lang['forum_prefix_added'],
            "?mod=forum&action=prefixes",
        );
    }
    return;
}

// -------------------------------------------------
// KATEGORİ LİSTESİ (dropdown için)
// -------------------------------------------------
$categories = [];
$db->query("SELECT id, name FROM " . PREFIX . "_forum_cats ORDER BY posi ASC");
while ($row = $db->get_row()) {
    $categories[] = $row;
}

function catDropdown($cats, $selected = 0)
{
    $opts = '<option value="0">' . $GLOBALS['lang']['forum_topic_all_cats'] . '</option>';
    foreach ($cats as $c) {
        $sel = $c["id"] == $selected ? " selected" : "";
        $opts .=
            '<option value="' .
            $c["id"] .
            '"' .
            $sel .
            ">" .
            htmlspecialchars($c["name"], ENT_QUOTES, "UTF-8") .
            "</option>";
    }
    return $opts;
}

// -------------------------------------------------
// DÜZENLEME VERİSİ
// -------------------------------------------------
$edit_prefix = null;
if ($prefix_id > 0 && $subaction != "delete") {
    $edit_prefix = $db->super_query(
        "SELECT * FROM " . PREFIX . "_forum_prefixes WHERE id='{$prefix_id}'",
    );
    if (!$edit_prefix["id"]) {
        $prefix_id = 0;
    }
}

// -------------------------------------------------
// TÜM ÖNEKLER
// -------------------------------------------------
$all_prefixes = [];
$db->query(
    "SELECT p.*, c.name AS cat_name FROM " .
        PREFIX .
        "_forum_prefixes p
    LEFT JOIN " .
        PREFIX .
        "_forum_cats c ON c.id = p.cat_id
    ORDER BY p.posi ASC, p.id ASC",
);
while ($row = $db->get_row()) {
    $all_prefixes[] = $row;
}
?>

<!-- ============================================================ -->
<!-- YENİ ÖNEK MODAL -->
<!-- ============================================================ -->
<div class="modal fade" id="newPrefix" tabindex="-1" role="dialog" aria-labelledby="newPrefixLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form method="post" action="?mod=forum&action=prefixes" autocomplete="off">
        <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">
        <div class="modal-header ui-dialog-titlebar">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <span class="ui-dialog-title" id="newPrefixLabel"><?php echo $lang['forum_prefix_new']; ?></span>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <div class="row">
              <div class="col-sm-6">
                <label><?php echo $lang['forum_prefix_name']; ?> <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input name="prefix_name" type="text" dir="auto" class="form-control" maxlength="100" required placeholder="<?php echo $lang['forum_prefix_name_ph']; ?>">
                  <span class="input-group-addon"><i class="fa fa-question-circle text-primary-600" data-rel="popover" data-trigger="hover" data-placement="auto right" data-content="<?php echo $lang['forum_prefix_name_help']; ?>"></i></span>
                </div>
              </div>
              <div class="col-sm-6">
                <label><?php echo $lang['forum_prefix_color']; ?></label>
                <div class="input-group">
                  <input name="prefix_color" type="text" dir="auto" class="form-control" maxlength="20" value="#3498db">
                  <span class="input-group-addon"><i class="fa fa-question-circle text-primary-600" data-rel="popover" data-trigger="hover" data-placement="auto right" data-content="<?php echo $lang['forum_prefix_color_help']; ?>"></i></span>
                </div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">
              <div class="col-sm-6">
                <label><?php echo $lang['forum_prefix_icon']; ?></label>
                <div class="input-group">
                  <input name="prefix_icon" type="text" dir="auto" class="form-control" maxlength="100" value="fa-tag">
                  <span class="input-group-addon"><i class="fa fa-question-circle text-primary-600" data-rel="popover" data-trigger="hover" data-placement="auto right" data-content="<?php echo $lang['forum_prefix_icon_help']; ?>"></i></span>
                </div>
              </div>
              <div class="col-sm-6">
                <label><?php echo $lang['forum_stat_cats']; ?></label>
                <select class="uniform" name="prefix_cat_id" data-width="100%">
                  <?php echo catDropdown($categories, 0); ?>
                </select>
                <span class="help-block text-muted text-size-small"><?php echo $lang['forum_prefix_cat_help']; ?></span>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">
              <div class="col-sm-6">
                <label><?php echo $lang['forum_prefix_posi']; ?></label>
                <input name="prefix_posi" type="number" class="form-control" value="0">
              </div>
              <div class="col-sm-6">
                <div class="checkbox pt-20">
                  <label><input type="checkbox" name="prefix_is_required" value="1" class="icheck"> <?php echo $lang['forum_prefix_req']; ?></label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="margin-top:-1.39em;">
          <button type="button" class="btn bg-grey-400 btn-sm btn-raised" data-dismiss="modal"><?php echo $lang['forum_btn_cancel']; ?></button>
          <button type="submit" name="save_prefix" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-floppy-o position-left"></i><?php echo $lang['forum_btn_save']; ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- DÜZENLEME PANELİ -->
<!-- ============================================================ -->
<?php if ($prefix_id > 0 && $edit_prefix): ?>
<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_prefix_edit']; ?> <strong style="color:<?php echo htmlspecialchars(
        $edit_prefix["color"],
        ENT_QUOTES,
        "UTF-8",
    ); ?>;"><i class="fa <?php echo htmlspecialchars(
    $edit_prefix["icon"],
    ENT_QUOTES,
    "UTF-8",
); ?> position-left"></i><?php echo htmlspecialchars(
     $edit_prefix["name"],
     ENT_QUOTES,
     "UTF-8",
 ); ?></strong>
    <div class="heading-elements">
      <ul class="icons-list">
        <li><a href="?mod=forum&action=prefixes"><i class="fa fa-times"></i></a></li>
      </ul>
    </div>
  </div>
  <div class="panel-body">
    <form method="post" action="?mod=forum&action=prefixes&prefix_id=<?php echo $prefix_id; ?>" class="form-horizontal" autocomplete="off">
      <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_prefix_name']; ?> <span class="text-danger">*</span></label>
        <div class="col-md-10 col-sm-9">
          <input class="form-control width-350" value="<?php echo htmlspecialchars(
              $edit_prefix["name"],
              ENT_QUOTES,
              "UTF-8",
          ); ?>" maxlength="100" type="text" dir="auto" name="prefix_name" required>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_prefix_color']; ?></label>
        <div class="col-md-10 col-sm-9">
          <input class="form-control width-150" value="<?php echo htmlspecialchars(
              $edit_prefix["color"],
              ENT_QUOTES,
              "UTF-8",
          ); ?>" maxlength="20" type="text" dir="auto" name="prefix_color">
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_prefix_icon']; ?></label>
        <div class="col-md-10 col-sm-9">
          <input class="form-control width-350" value="<?php echo htmlspecialchars(
              $edit_prefix["icon"],
              ENT_QUOTES,
              "UTF-8",
          ); ?>" maxlength="100" type="text" dir="auto" name="prefix_icon">
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_stat_cats']; ?></label>
        <div class="col-md-10 col-sm-9">
          <select class="uniform" name="prefix_cat_id" data-width="350">
            <?php echo catDropdown(
                $categories,
                intval($edit_prefix["cat_id"]),
            ); ?>
          </select>
          <span class="help-block text-muted text-size-small"><?php echo $lang['forum_prefix_cat_help2']; ?></span>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_prefix_posi']; ?></label>
        <div class="col-md-10 col-sm-9">
          <input class="form-control width-100" value="<?php echo intval(
              $edit_prefix["posi"],
          ); ?>" type="number" name="prefix_posi">
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"></label>
        <div class="col-md-10 col-sm-9">
          <div class="checkbox"><label><input class="icheck" type="checkbox" name="prefix_is_required" value="1" <?php if (
              $edit_prefix["is_required"]
          ) {
              echo "checked";
          } ?>> <?php echo $lang['forum_prefix_req']; ?></label></div>
        </div>
      </div>
  </div>
  <div class="panel-footer">
    <button type="submit" name="save_prefix" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-floppy-o position-left"></i><?php echo $lang['forum_btn_update']; ?></button>
    <a href="?mod=forum&action=prefixes" class="btn bg-grey-400 btn-sm btn-raised"><?php echo $lang['forum_btn_cancel']; ?></a>
  </div>
  </form>
</div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- ÖNEK LİSTESİ -->
<!-- ============================================================ -->
<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_prefix_title']; ?>
    <div class="heading-elements">
      <ul class="icons-list">
        <li><a href="#" data-toggle="modal" data-target="#newPrefix"><i class="fa fa-plus-circle position-left"></i><?php echo $lang['forum_prefix_btn_add']; ?></a></li>
      </ul>
    </div>
  </div>

  <?php if (count($all_prefixes) > 0): ?>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th><?php echo $lang['forum_prefix_th_name']; ?></th>
          <th><?php echo $lang['forum_prefix_th_color']; ?></th>
          <th><?php echo $lang['forum_prefix_th_icon']; ?></th>
          <th><?php echo $lang['forum_stat_cats']; ?></th>
          <th><?php echo $lang['forum_prefix_th_req']; ?></th>
          <th><?php echo $lang['forum_prefix_th_posi']; ?></th>
          <th width="100"><?php echo $lang['forum_poll_th_action']; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($all_prefixes as $p): ?>
        <tr>
          <td><?php echo $p["id"]; ?></td>
          <td>
            <span class="label" style="background:<?php echo htmlspecialchars(
                $p["color"],
                ENT_QUOTES,
                "UTF-8",
            ); ?>;">
              <i class="fa <?php echo htmlspecialchars(
                  $p["icon"],
                  ENT_QUOTES,
                  "UTF-8",
              ); ?> position-left"></i>
              <?php echo htmlspecialchars($p["name"], ENT_QUOTES, "UTF-8"); ?>
            </span>
          </td>
          <td><code><?php echo htmlspecialchars(
              $p["color"],
              ENT_QUOTES,
              "UTF-8",
          ); ?></code></td>
          <td><i class="fa <?php echo htmlspecialchars(
              $p["icon"],
              ENT_QUOTES,
              "UTF-8",
          ); ?>"></i></td>
          <td><?php echo $p["cat_id"] > 0
              ? htmlspecialchars($p["cat_name"], ENT_QUOTES, "UTF-8")
              : "<em>" . $lang['forum_prefix_all'] . "</em>"; ?></td>
          <td><?php echo $p["is_required"]
              ? '<span class="label label-danger">' . $lang['forum_prefix_yes'] . '</span>'
              : '<span class="label label-default">' . $lang['forum_prefix_no'] . '</span>'; ?></td>
          <td><?php echo intval($p["posi"]); ?></td>
          <td>
            <?php echo forum_admin_action_dropdown([
                [
                    "href" => "?mod=forum&action=prefixes&prefix_id=" . intval($p["id"]),
                    "label" => $lang["forum_btn_edit"],
                    "icon" => "fa fa-pencil-square-o",
                ],
                [
                    "href" =>
                        "?mod=forum&action=prefixes&prefix_id=" .
                        intval($p["id"]) .
                        "&subaction=delete&dle_post_hash=" .
                        $dle_login_hash,
                    "label" => $lang["forum_btn_delete"],
                    "icon" => "fa fa-trash-o",
                    "danger" => true,
                    "divider_before" => true,
                    "onclick" =>
                        "return confirm('" .
                        addslashes($lang["forum_prefix_del_conf"]) .
                        "');",
                ],
            ]); ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="panel-footer">
    <button class="btn bg-teal btn-sm btn-raised position-left" onclick="$('#newPrefix').modal({backdrop: 'static',keyboard: false}); return false;"><i class="fa fa-plus-circle position-left"></i><?php echo $lang['forum_prefix_btn_add']; ?></button>
  </div>
  <?php else: ?>
  <div class="panel-body">
    <div class="alert alert-info no-margin"><?php echo $lang['forum_prefix_empty']; ?></div>
  </div>
  <div class="panel-footer">
    <button class="btn bg-teal btn-sm btn-raised position-left" onclick="$('#newPrefix').modal({backdrop: 'static',keyboard: false}); return false;"><i class="fa fa-plus-circle position-left"></i><?php echo $lang['forum_prefix_btn_add']; ?></button>
  </div>
  <?php endif; ?>
</div>
