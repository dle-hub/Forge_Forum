<?php
/*
=====================================================
 Forge Forum Engine — Etiket Yönetimi
-----------------------------------------------------
 File: engine/inc/forum/tags.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

include_once DLEPlugins::Check(
    ENGINE_DIR . "/inc/forum/admin_ui_helper.php",
);

$tag_id    = isset($_REQUEST['tag_id']) ? intval($_REQUEST['tag_id']) : 0;
$subaction = isset($_REQUEST['subaction']) ? totranslit($_REQUEST['subaction']) : '';

// SIL
if ( $subaction == 'delete' && $tag_id > 0 ) {
    if ( $_REQUEST['dle_post_hash'] !== $dle_login_hash ) { msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']); return; }
    $db->query( "DELETE FROM " . PREFIX . "_forum_topic_tags WHERE tag_id='{$tag_id}'" );
    $db->query( "DELETE FROM " . PREFIX . "_forum_tags WHERE id='{$tag_id}'" );
    msg("success", $lang['forum_set_success_title'], $lang['forum_tag_deleted'], "?mod=forum&action=tags"); return;
}

// GÜNCELLE
if ( isset($_POST['save_tag']) && $tag_id > 0 ) {
    if ( $_POST['dle_post_hash'] !== $dle_login_hash ) { msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']); return; }
    $name = $db->safesql(trim($_POST['tag_name']));
    $alt  = $db->safesql(totranslit(trim($_POST['tag_name'])));
    if(empty($name)){ msg("error", $lang['forum_error_title'], $lang['forum_tag_err_name']); return; }
    $db->query( "UPDATE " . PREFIX . "_forum_tags SET name='{$name}', alt_name='{$alt}' WHERE id='{$tag_id}'" );
    msg("success", $lang['forum_set_success_title'], $lang['forum_tag_updated'], "?mod=forum&action=tags"); return;
}

// DÜZENLE
$edit_tag = null;
if ( $tag_id > 0 ) {
    $edit_tag = $db->super_query( "SELECT * FROM " . PREFIX . "_forum_tags WHERE id='{$tag_id}'" );
}

$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$perpage = 50; $offset = ($page-1)*$perpage;
$total = $db->super_query( "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_tags" );
$total = intval($total['cnt']);

$tags = array();
$db->query( "SELECT * FROM " . PREFIX . "_forum_tags ORDER BY topic_count DESC, name ASC LIMIT {$offset},{$perpage}" );
while($row=$db->get_row()) $tags[] = $row;

$pages = ceil($total/$perpage);
$pagination = '';
if($pages>1){
    $pagination .= '<ul class="pagination pagination-sm no-margin">';
    for($i=1;$i<=$pages;$i++){ $act=($i==$page)?'active':''; $pagination.='<li class="'.$act.'"><a href="?mod=forum&action=tags&page='.$i.'">'.$i.'</a></li>'; }
    $pagination .= '</ul>';
}
?>

<?php if($edit_tag): ?>
<div class="panel panel-default">
  <div class="panel-heading"><?php echo $lang['forum_tag_edit']; ?> <strong><?php echo htmlspecialchars($edit_tag['name'],ENT_QUOTES,'UTF-8'); ?></strong></div>
  <div class="panel-body">
    <form method="post" action="?mod=forum&action=tags&tag_id=<?php echo $tag_id; ?>" class="form-horizontal">
      <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">
      <div class="form-group">
        <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_tag_name']; ?></label>
        <div class="col-md-10 col-sm-9">
          <input class="form-control width-350" value="<?php echo htmlspecialchars($edit_tag['name'],ENT_QUOTES,'UTF-8'); ?>" type="text" name="tag_name" required>
        </div>
      </div>
  </div>
  <div class="panel-footer">
    <button type="submit" name="save_tag" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-floppy-o position-left"></i><?php echo $lang['forum_btn_update']; ?></button>
    <a href="?mod=forum&action=tags" class="btn bg-grey-400 btn-sm btn-raised"><?php echo $lang['forum_btn_cancel']; ?></a>
  </div>
  </form>
</div>
<?php endif; ?>

<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_tag_title']; ?>
    <span class="label label-primary position-right"><?php echo $total; ?> <?php echo $lang['forum_promo_count']; ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>#</th><th><?php echo $lang['forum_tag_th_name']; ?></th><th><?php echo $lang['forum_tag_th_seo']; ?></th><th><?php echo $lang['forum_tag_th_topics']; ?></th><th width="80"><?php echo $lang['forum_poll_th_action']; ?></th></tr></thead>
      <tbody>
        <?php if(count($tags)): foreach($tags as $t): ?>
        <tr>
          <td><?php echo $t['id']; ?></td>
          <td><span class="label label-primary"><?php echo htmlspecialchars($t['name'],ENT_QUOTES,'UTF-8'); ?></span></td>
          <td><code><?php echo htmlspecialchars($t['alt_name'],ENT_QUOTES,'UTF-8'); ?></code></td>
          <td><?php echo intval($t['topic_count']); ?></td>
          <td>
            <?php echo forum_admin_action_dropdown([
                [
                    "href" => "?mod=forum&action=tags&tag_id=" . intval($t["id"]),
                    "label" => $lang["forum_btn_edit"],
                    "icon" => "fa fa-pencil-square-o",
                ],
                [
                    "href" =>
                        "?mod=forum&action=tags&tag_id=" .
                        intval($t["id"]) .
                        "&subaction=delete&dle_post_hash=" .
                        $dle_login_hash,
                    "label" => $lang["forum_btn_delete"],
                    "icon" => "fa fa-trash-o",
                    "danger" => true,
                    "divider_before" => true,
                    "onclick" =>
                        "return confirm('" .
                        addslashes($lang["forum_tag_del_conf"]) .
                        "');",
                ],
            ]); ?>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5" class="text-center text-muted"><?php echo $lang['forum_tag_empty']; ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel-footer"><?php echo $pagination; ?></div>
</div>
