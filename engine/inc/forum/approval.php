<?php
/*
=====================================================
 Forge Forum Engine — Onay Kuyruğu
-----------------------------------------------------
 File: engine/inc/forum/approval.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if ($member_id["user_group"] > 2) {
    msg("error", $lang['forum_access_denied'], $lang['forum_no_perm']);
    return;
}

$tab = isset($_GET["tab"]) ? totranslit($_GET["tab"]) : "topics";
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$perpage = 25;
$offset = ($page - 1) * $perpage;

$totalTopics = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " .
        PREFIX .
        "_forum_topics WHERE is_approved=0 AND is_deleted=0",
);
$totalTopics = intval($totalTopics["cnt"]);

$totalPosts = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " .
        PREFIX .
        "_forum_posts WHERE is_approved=0 AND is_deleted=0",
);
$totalPosts = intval($totalPosts["cnt"]);

$items = [];
if ($tab == "topics") {
    $db->query(
        "SELECT t.*, u.name AS author_name, c.name AS cat_name
        FROM " .
            PREFIX .
            "_forum_topics t
        LEFT JOIN " .
            PREFIX .
            "_users u ON u.user_id=t.user_id
        LEFT JOIN " .
            PREFIX .
            "_forum_cats c ON c.id=t.cat_id
        WHERE t.is_approved=0 AND t.is_deleted=0
        ORDER BY t.date ASC LIMIT {$offset},{$perpage}",
    );
} else {
    $db->query(
        "SELECT p.*, u.name AS author_name, t.title AS topic_title
        FROM " .
            PREFIX .
            "_forum_posts p
        LEFT JOIN " .
            PREFIX .
            "_users u ON u.user_id=p.user_id
        LEFT JOIN " .
            PREFIX .
            "_forum_topics t ON t.id=p.topic_id
        WHERE p.is_approved=0 AND p.is_deleted=0
        ORDER BY p.date ASC LIMIT {$offset},{$perpage}",
    );
}
while ($row = $db->get_row()) {
    $items[] = $row;
}
$total = $tab == "topics" ? $totalTopics : $totalPosts;

function appPag($total, $page, $perpage, $tab)
{
    $pages = ceil($total / $perpage);
    if ($pages <= 1) {
        return "";
    }
    $o = '<ul class="pagination pagination-sm no-margin">';
    for ($i = 1; $i <= $pages; $i++) {
        $act = $i == $page ? "active" : "";
        $o .=
            '<li class="' .
            $act .
            '"><a href="?mod=forum&action=approval&tab=' .
            $tab .
            "&page=" .
            $i .
            '">' .
            $i .
            "</a></li>";
    }
    return $o . "</ul>";
}
?>

<script>
var dle_root = '<?php echo $config["http_home_url"]; ?>';

function appAction(action, id, type) {
    var msgs = {
        approve: '<?php echo $lang['forum_app_approve']; ?>',
        reject: '<?php echo $lang['forum_app_reject']; ?>',
        ipban: '<?php echo $lang['forum_app_ipban']; ?>'
    };
    DLEconfirm(msgs[action]||'<?php echo $lang['forum_app_def_conf']; ?>', '<?php echo $lang['forum_trash_confirm']; ?>', function(){
        $.post(dle_root+'index.php?controller=ajax&mod=forum_approval',
            { action:action, id:id, type:type, user_hash:'<?php echo $dle_login_hash; ?>' },
            function(res){
                if(res.success){ DLEPush.info(res.message); setTimeout(function(){ location.reload(); },800); }
                else DLEPush.error(res.error||'<?php echo $lang['forum_trash_err']; ?>');
            },'json');
    });
}
</script>

<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_app_title']; ?>
    <span class="label label-warning position-right"><?php echo $totalTopics +
        $totalPosts; ?> <?php echo $lang['forum_app_wait']; ?></span>
  </div>

  <ul class="nav nav-tabs nav-tabs-solid">
    <li class="<?php echo $tab == "topics" ? "active" : ""; ?>">
      <a href="?mod=forum&action=approval&tab=topics">
        <i class="fa fa-file-text-o position-left"></i> <?php echo $lang['forum_app_wait_topics']; ?>
        <span class="label label-danger position-right"><?php echo $totalTopics; ?></span>
      </a>
    </li>
    <li class="<?php echo $tab == "posts" ? "active" : ""; ?>">
      <a href="?mod=forum&action=approval&tab=posts">
        <i class="fa fa-comment-o position-left"></i> <?php echo $lang['forum_app_wait_posts']; ?>
        <span class="label label-danger position-right"><?php echo $totalPosts; ?></span>
      </a>
    </li>
  </ul>

  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr>
        <th>#</th><th><?php echo $lang['forum_app_th_content']; ?></th><th><?php echo $lang['forum_topic_th_author']; ?></th>
        <?php if (
            $tab == "topics"
        ): ?><th><?php echo $lang['forum_topic_th_cat']; ?></th><?php else: ?><th><?php echo $lang['forum_rep_lbl_topic']; ?></th><?php endif; ?>
        <th><?php echo $lang['forum_app_th_ip']; ?></th><th><?php echo $lang['forum_rep_th_date']; ?></th><th width="210"><?php echo $lang['forum_poll_th_action']; ?></th>
      </tr></thead>
      <tbody>
        <?php if (count($items)):
            foreach ($items as $it):
                $txt = $tab == "topics" ? $it["title"] : $it["text"]; ?>
        <tr>
          <td><?php echo $it["id"]; ?></td>
          <td>
            <strong><?php echo htmlspecialchars(
                mb_substr(strip_tags($txt), 0, 70),
                ENT_QUOTES,
                "UTF-8",
            ); ?></strong>
          </td>
          <td><?php echo htmlspecialchars(
              $it["author_name"] ?: $lang['forum_app_guest'],
              ENT_QUOTES,
              "UTF-8",
          ); ?></td>
          <?php if ($tab == "topics"): ?>
          <td><?php echo htmlspecialchars(
              $it["cat_name"] ?: "—",
              ENT_QUOTES,
              "UTF-8",
          ); ?></td>
          <?php else: ?>
          <td><small><?php echo htmlspecialchars(
              mb_substr($it["topic_title"] ?: "—", 0, 30),
              ENT_QUOTES,
              "UTF-8",
          ); ?></small></td>
          <?php endif; ?>
          <td><code class="text-size-small"><?php echo htmlspecialchars(
              $it["ip"] ?: "—",
              ENT_QUOTES,
              "UTF-8",
          ); ?></code></td>
          <td><small class="text-muted"><?php echo $it["date"]; ?></small></td>
          <td>
            <div class="btn-group btn-group-xs">
              <button class="btn bg-success" onclick="appAction('approve',<?php echo $it[
                  "id"
              ]; ?>,'<?php echo $tab == "topics"
    ? "topic"
    : "post"; ?>')"><i class="fa fa-check"></i> <?php echo $lang['forum_app_btn_approve']; ?></button>
              <button class="btn btn-danger" onclick="appAction('reject',<?php echo $it[
                  "id"
              ]; ?>,'<?php echo $tab == "topics"
    ? "topic"
    : "post"; ?>')"><i class="fa fa-trash"></i> <?php echo $lang['forum_app_btn_del']; ?></button>
              <button class="btn bg-warning" onclick="appAction('ipban',<?php echo $it[
                  "id"
              ]; ?>,'<?php echo $tab == "topics"
    ? "topic"
    : "post"; ?>')"><i class="fa fa-ban"></i> <?php echo $lang['forum_app_btn_ban']; ?></button>
            </div>
          </td>
        </tr>
        <?php
            endforeach;
        else:
             ?>
        <tr><td colspan="<?php echo $tab == "topics"
            ? 7
            : 7; ?>" class="text-center text-muted"><?php echo $lang['forum_app_empty']; ?></td></tr>
        <?php
        endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel-footer"><?php echo appPag(
      $total,
      $page,
      $perpage,
      $tab,
  ); ?></div>
</div>
