<?php
/*
=====================================================
 Forge Forum Engine — Anket Yönetimi
-----------------------------------------------------
 File: engine/inc/forum/polls.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

include_once DLEPlugins::Check(
    ENGINE_DIR . "/inc/forum/admin_ui_helper.php",
);

$poll_id   = isset($_REQUEST['poll_id']) ? intval($_REQUEST['poll_id']) : 0;
$subaction = isset($_REQUEST['subaction']) ? totranslit($_REQUEST['subaction']) : '';

// SIL
if ( $subaction == 'delete' && $poll_id > 0 ) {
    if ( $_REQUEST['dle_post_hash'] !== $dle_login_hash ) { msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']); return; }
    $db->query( "DELETE FROM " . PREFIX . "_forum_poll_votes WHERE poll_id='{$poll_id}'" );
    $db->query( "DELETE FROM " . PREFIX . "_forum_poll_options WHERE poll_id='{$poll_id}'" );
    $db->query( "DELETE FROM " . PREFIX . "_forum_polls WHERE id='{$poll_id}'" );
    msg("success", $lang['forum_set_success_title'], $lang['forum_poll_deleted'], "?mod=forum&action=polls"); return;
}

// KAPAT/AC
if ( $subaction == 'toggle' && $poll_id > 0 ) {
    if ( $_REQUEST['dle_post_hash'] !== $dle_login_hash ) { msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']); return; }
    $row = $db->super_query( "SELECT is_closed FROM " . PREFIX . "_forum_polls WHERE id='{$poll_id}'" );
    $new = $row['is_closed'] ? 0 : 1;
    $db->query( "UPDATE " . PREFIX . "_forum_polls SET is_closed='{$new}' WHERE id='{$poll_id}'" );
    $txt = $new ? $lang['forum_poll_closed_msg'] : $lang['forum_poll_opened_msg'];
    msg("success", $lang['forum_set_success_title'], $txt, "?mod=forum&action=polls"); return;
}

$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$perpage = 30;
$offset = ($page-1)*$perpage;

$total = $db->super_query( "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_polls" );
$total = intval($total['cnt']);

$polls = array();
$db->query( "SELECT p.*, t.title AS topic_title FROM " . PREFIX . "_forum_polls p
    LEFT JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
    ORDER BY p.id DESC LIMIT {$offset},{$perpage}" );
while($row=$db->get_row()) $polls[] = $row;

// Sayfalama
$pages = ceil($total/$perpage);
$pagination = '';
if($pages>1){
    $pagination .= '<ul class="pagination pagination-sm no-margin">';
    for($i=1;$i<=$pages;$i++){
        $act = ($i==$page)?'active':'';
        $pagination .= '<li class="'.$act.'"><a href="?mod=forum&action=polls&page='.$i.'">'.$i.'</a></li>';
    }
    $pagination .= '</ul>';
}
?>

<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_poll_title']; ?>
    <span class="label label-primary position-right"><?php echo $total; ?> <?php echo $lang['forum_poll_count']; ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>#</th><th><?php echo $lang['forum_poll_th_q']; ?></th><th><?php echo $lang['forum_poll_th_topic']; ?></th><th><?php echo $lang['forum_poll_th_opts']; ?></th><th><?php echo $lang['forum_poll_th_votes']; ?></th><th><?php echo $lang['forum_poll_th_end']; ?></th><th><?php echo $lang['forum_poll_th_status']; ?></th><th width="100"><?php echo $lang['forum_poll_th_action']; ?></th></tr></thead>
      <tbody>
        <?php if(count($polls)): foreach($polls as $p):
            $opts = $db->super_query( "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_poll_options WHERE poll_id='{$p['id']}'" );
            $votes= $db->super_query( "SELECT SUM(votes) AS total FROM " . PREFIX . "_forum_poll_options WHERE poll_id='{$p['id']}'" );
            $closed = $p['is_closed'] ? '<span class="label label-danger">' . $lang['forum_poll_lbl_closed'] . '</span>' : '<span class="label label-success">' . $lang['forum_poll_lbl_open'] . '</span>';
        ?>
        <tr>
          <td><?php echo $p['id']; ?></td>
          <td><strong><?php echo htmlspecialchars($p['question'],ENT_QUOTES,'UTF-8'); ?></strong></td>
          <td><?php echo htmlspecialchars($p['topic_title']?mb_substr($p['topic_title'],0,40):'—',ENT_QUOTES,'UTF-8'); ?></td>
          <td><?php echo intval($opts['cnt']); ?></td>
          <td><?php echo intval($votes['total']); ?></td>
          <td><?php echo $p['end_date']?$p['end_date']:'<em>' . $lang['forum_poll_indefinite'] . '</em>'; ?></td>
          <td><?php echo $closed; ?></td>
          <td>
            <?php echo forum_admin_action_dropdown([
                [
                    "href" =>
                        "?mod=forum&action=polls&poll_id=" .
                        intval($p["id"]) .
                        "&subaction=toggle&dle_post_hash=" .
                        $dle_login_hash,
                    "label" => $lang["forum_poll_btn_toggle"],
                    "icon" => $p["is_closed"] ? "fa fa-unlock" : "fa fa-lock",
                ],
                [
                    "href" =>
                        "?mod=forum&action=polls&poll_id=" .
                        intval($p["id"]) .
                        "&subaction=delete&dle_post_hash=" .
                        $dle_login_hash,
                    "label" => $lang["forum_btn_delete"],
                    "icon" => "fa fa-trash-o",
                    "danger" => true,
                    "divider_before" => true,
                    "onclick" =>
                        "return confirm('" .
                        addslashes($lang["forum_poll_confirm_del"]) .
                        "');",
                ],
            ]); ?>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="8" class="text-center text-muted"><?php echo $lang['forum_poll_empty']; ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel-footer"><?php echo $pagination; ?></div>
</div>
