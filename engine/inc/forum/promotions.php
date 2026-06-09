<?php
/*
=====================================================
 Forge Forum Engine — Grup Terfi Yönetimi
-----------------------------------------------------
 File: engine/inc/forum/promotions.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

include_once DLEPlugins::Check(
    ENGINE_DIR . "/inc/forum/admin_ui_helper.php",
);

$promo_id  = isset($_REQUEST['promo_id']) ? intval($_REQUEST['promo_id']) : 0;
$subaction = isset($_REQUEST['subaction']) ? totranslit($_REQUEST['subaction']) : '';

// SIL
if ( $subaction == 'delete' && $promo_id > 0 ) {
    if ( $_REQUEST['dle_post_hash'] !== $dle_login_hash ) { msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']); return; }
    $db->query( "DELETE FROM " . PREFIX . "_forum_promotions WHERE id='{$promo_id}'" );
    msg("success", $lang['forum_set_success_title'], $lang['forum_promo_deleted'], "?mod=forum&action=promotions"); return;
}

// KAYDET
if ( isset($_POST['save_promo']) ) {
    if ( $_POST['dle_post_hash'] !== $dle_login_hash ) { msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']); return; }
    $min_pts   = intval($_POST['min_points']);
    $min_posts = intval($_POST['min_posts']);
    $from_grp  = intval($_POST['from_group']);
    $to_grp    = intval($_POST['to_group']);
    if($to_grp <= 0){ msg("error", $lang['forum_error_title'], $lang['forum_promo_err_target']); return; }

    if($promo_id > 0){
        $db->query( "UPDATE " . PREFIX . "_forum_promotions SET min_points='{$min_pts}', min_posts='{$min_posts}', from_group='{$from_grp}', to_group='{$to_grp}' WHERE id='{$promo_id}'" );
        msg("success", $lang['forum_set_success_title'], $lang['forum_promo_updated'], "?mod=forum&action=promotions");
    } else {
        $db->query( "INSERT INTO " . PREFIX . "_forum_promotions (min_points, min_posts, from_group, to_group) VALUES ('{$min_pts}','{$min_posts}','{$from_grp}','{$to_grp}')" );
        msg("success", $lang['forum_set_success_title'], $lang['forum_promo_added'], "?mod=forum&action=promotions");
    }
    return;
}

$edit_promo = null;
if($promo_id > 0) $edit_promo = $db->super_query( "SELECT * FROM " . PREFIX . "_forum_promotions WHERE id='{$promo_id}'" );

$promos = array();
$db->query( "SELECT * FROM " . PREFIX . "_forum_promotions ORDER BY min_points ASC" );
while($row=$db->get_row()) $promos[] = $row;

$groups = array();
$db->query( "SELECT id, group_name FROM " . PREFIX . "_usergroups ORDER BY id ASC" );
while($row=$db->get_row()) $groups[] = $row;

function grpDropdown($groups,$sel=0){
    $o='<option value="0">' . $GLOBALS['lang']['forum_promo_select'] . '</option>';
    foreach($groups as $g){ $s=($g['id']==$sel)?' selected':''; $o.='<option value="'.$g['id'].'"'.$s.'>'.$g['group_name'].'</option>'; }
    return $o;
}
?>

<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $promo_id>0 ? $lang['forum_promo_edit'] : $lang['forum_promo_new']; ?>
    <?php if($promo_id>0): ?><div class="heading-elements"><ul class="icons-list"><li><a href="?mod=forum&action=promotions"><i class="fa fa-times"></i></a></li></ul></div><?php endif; ?>
  </div>
  <div class="panel-body">
    <form method="post" action="?mod=forum&action=promotions<?php echo $promo_id>0?'&promo_id='.$promo_id:''; ?>" class="form-horizontal">
      <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_promo_min_pts']; ?></label>
        <div class="col-md-10 col-sm-9"><input class="form-control width-150" type="number" name="min_points" value="<?php echo $edit_promo?intval($edit_promo['min_points']):'0'; ?>"></div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_promo_min_posts']; ?></label>
        <div class="col-md-10 col-sm-9"><input class="form-control width-150" type="number" name="min_posts" value="<?php echo $edit_promo?intval($edit_promo['min_posts']):'0'; ?>"></div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_promo_from']; ?></label>
        <div class="col-md-10 col-sm-9"><select class="uniform" name="from_group" data-width="350"><?php echo grpDropdown($groups,$edit_promo?intval($edit_promo['from_group']):0); ?></select><span class="help-block text-muted text-size-small"><?php echo $lang['forum_promo_all_grp']; ?></span></div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_promo_to']; ?> <span class="text-danger">*</span></label>
        <div class="col-md-10 col-sm-9"><select class="uniform" name="to_group" data-width="350" required><?php echo grpDropdown($groups,$edit_promo?intval($edit_promo['to_group']):0); ?></select></div>
      </div>
  </div>
  <div class="panel-footer">
    <button type="submit" name="save_promo" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-floppy-o position-left"></i><?php echo $promo_id > 0 ? $lang['forum_btn_update'] : $lang['forum_btn_save']; ?></button>
    <?php if($promo_id>0): ?><a href="?mod=forum&action=promotions" class="btn bg-grey-400 btn-sm btn-raised"><?php echo $lang['forum_btn_cancel']; ?></a><?php endif; ?>
  </div>
  </form>
</div>

<div class="panel panel-default mt-20">
  <div class="panel-heading">
    <?php echo $lang['forum_promo_title']; ?>
    <span class="label label-primary position-right"><?php echo count($promos); ?> <?php echo $lang['forum_promo_count']; ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>#</th><th><?php echo $lang['forum_promo_min_pts']; ?></th><th><?php echo $lang['forum_promo_min_posts']; ?></th><th><?php echo $lang['forum_promo_from']; ?></th><th><?php echo $lang['forum_promo_to']; ?></th><th width="80"><?php echo $lang['forum_poll_th_action']; ?></th></tr></thead>
      <tbody>
        <?php if(count($promos)): foreach($promos as $pr):
            $fromName = $lang['forum_promo_all']; $toName = $lang['forum_promo_none'];
            foreach($groups as $g){ if($g['id']==$pr['from_group'])$fromName=$g['group_name']; if($g['id']==$pr['to_group'])$toName=$g['group_name']; }
        ?>
        <tr>
          <td><?php echo $pr['id']; ?></td>
          <td><?php echo intval($pr['min_points']); ?></td>
          <td><?php echo intval($pr['min_posts']); ?></td>
          <td><?php echo htmlspecialchars($fromName,ENT_QUOTES,'UTF-8'); ?></td>
          <td><strong><?php echo htmlspecialchars($toName,ENT_QUOTES,'UTF-8'); ?></strong></td>
          <td>
            <?php echo forum_admin_action_dropdown([
                [
                    "href" =>
                        "?mod=forum&action=promotions&promo_id=" . intval($pr["id"]),
                    "label" => $lang["forum_btn_edit"],
                    "icon" => "fa fa-pencil-square-o",
                ],
                [
                    "href" =>
                        "?mod=forum&action=promotions&promo_id=" .
                        intval($pr["id"]) .
                        "&subaction=delete&dle_post_hash=" .
                        $dle_login_hash,
                    "label" => $lang["forum_btn_delete"],
                    "icon" => "fa fa-trash-o",
                    "danger" => true,
                    "divider_before" => true,
                    "onclick" =>
                        "return confirm('" .
                        addslashes($lang["forum_promo_del_conf"]) .
                        "');",
                ],
            ]); ?>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" class="text-center text-muted"><?php echo $lang['forum_promo_empty']; ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
