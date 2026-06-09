<?php
/*
=====================================================
 Forge Forum Engine — Şikayet ve Rapor Yönetimi
-----------------------------------------------------
 File: engine/inc/forum/reports.php
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

if ($member_id["user_group"] > 2) {
    msg("error", $lang['forum_access_denied'], $lang['forum_no_perm']);
    return;
}

$tab = isset($_GET["tab"]) ? totranslit($_GET["tab"]) : "pending";
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$perpage = 25;
$offset = ($page - 1) * $perpage;

// Bekleyenler (status=0)
$totalPending = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_reports WHERE status=0",
);
$totalPending = intval($totalPending["cnt"]);

// Çözülenler (status>0)
$totalResolved = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_reports WHERE status>0",
);
$totalResolved = intval($totalResolved["cnt"]);

$whereStatus = $tab == "pending" ? "r.status=0" : "r.status>0";

$reports = [];
$db->query(
    "SELECT r.*,
        rep.name AS reporter_name,
        `mod`.name AS mod_name,
        t.title AS topic_title, t.id AS tid,
        p.text AS post_text, p.topic_id AS post_topic_id
    FROM " .
        PREFIX .
        "_forum_reports r
    LEFT JOIN " .
        PREFIX .
        "_users rep ON rep.user_id = r.reporter_id
    LEFT JOIN " .
        PREFIX .
        "_users `mod` ON mod.user_id = r.mod_id
    LEFT JOIN " .
        PREFIX .
        "_forum_topics t ON t.id = r.target_id AND r.target_type='topic'
    LEFT JOIN " .
        PREFIX .
        "_forum_posts p ON p.id = r.target_id AND r.target_type='post'
    WHERE {$whereStatus}
    ORDER BY r.date DESC LIMIT {$offset},{$perpage}",
);
while ($row = $db->get_row()) {
    $reports[] = $row;
}

$total = $tab == "pending" ? $totalPending : $totalResolved;

function rptPagination($total, $page, $perpage, $tab)
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
            '"><a href="?mod=forum&action=reports&tab=' .
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
function reportAction(action, id, extra) {
    var msgs = {
        resolve1: '<?php echo $lang['forum_rep_resolve1']; ?>',
        resolve2: '<?php echo $lang['forum_rep_resolve2']; ?>',
        trash_content: '<?php echo $lang['forum_rep_trash']; ?>'
    };
    var key = action + (extra||'');
    var msg = msgs[key] || '<?php echo $lang['forum_rep_def_conf']; ?>';

    DLEconfirm(msg, '<?php echo $lang['forum_trash_confirm']; ?>', function(){
        var data = { action: action, id: id, user_hash: '<?php echo $dle_login_hash; ?>' };
        if(extra) data.status = extra;

        $.post(dle_root + 'index.php?controller=ajax&mod=forum_reports', data, function(res){
            if(res.success){ DLEPush.info(res.message); setTimeout(function(){ location.reload(); },800); }
            else DLEPush.error(res.error||'<?php echo $lang['forum_trash_err']; ?>');
        },'json');
    });
}
</script>

<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_rep_title']; ?>
  </div>

  <ul class="nav nav-tabs nav-tabs-solid">
    <li class="<?php echo $tab == "pending" ? "active" : ""; ?>">
      <a href="?mod=forum&action=reports&tab=pending">
        <i class="fa fa-exclamation-triangle text-danger position-left"></i> <?php echo $lang['forum_rep_pending']; ?>
        <span class="label label-danger position-right"><?php echo $totalPending; ?></span>
      </a>
    </li>
    <li class="<?php echo $tab == "resolved" ? "active" : ""; ?>">
      <a href="?mod=forum&action=reports&tab=resolved">
        <i class="fa fa-check-circle text-success position-left"></i> <?php echo $lang['forum_rep_resolved']; ?>
        <span class="label label-success position-right"><?php echo $totalResolved; ?></span>
      </a>
    </li>
  </ul>

  <div class="table-responsive">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th><?php echo $lang['forum_rep_th_target']; ?></th>
          <th><?php echo $lang['forum_rep_th_reporter']; ?></th>
          <th><?php echo $lang['forum_rep_th_reason']; ?></th>
          <th><?php echo $lang['forum_rep_th_date']; ?></th>
          <?php if (
              $tab == "resolved"
          ): ?><th><?php echo $lang['forum_rep_th_mod']; ?></th><th><?php echo $lang['forum_rep_th_status']; ?></th><?php endif; ?>
          <th width="<?php echo $tab == "pending"
              ? "200"
              : "100"; ?>"><?php echo $lang['forum_poll_th_action']; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($reports)):
            foreach ($reports as $r):

                $isTopic = $r["target_type"] == "topic";
                $targetLabel = $isTopic
                    ? $lang['forum_rep_lbl_topic'] . " #" . $r["target_id"]
                    : $lang['forum_rep_lbl_post'] . " #" . $r["target_id"];
                $targetText = $isTopic
                    ? $r["topic_title"]
                    : strip_tags($r["post_text"]);
                $targetUrl = $isTopic
                    ? $config["http_home_url"] .
                        "forum/" .
                        $r["target_id"] .
                        "-topic.html"
                    : $config["http_home_url"] .
                        "forum/topic/" .
                        $r["post_topic_id"] .
                        "/#post-" .
                        $r["target_id"];
                $statusLabel =
                    $r["status"] == 1
                        ? '<span class="label label-success">' . $lang['forum_rep_solved'] . '</span>'
                        : '<span class="label label-default">' . $lang['forum_rep_rejected'] . '</span>';
                ?>
        <tr class="<?php echo $tab == "pending" ? "" : "text-muted"; ?>">
          <td><?php echo $r["id"]; ?></td>
          <td>
            <span class="label label-<?php echo $isTopic
                ? "primary"
                : "warning"; ?> position-left"><?php echo $isTopic
     ? $lang['forum_rep_lbl_topic']
     : $lang['forum_rep_lbl_post']; ?></span>
            <a href="<?php echo $targetUrl; ?>" target="_blank">
              <?php echo htmlspecialchars(
                  mb_substr($targetText, 0, 60),
                  ENT_QUOTES,
                  "UTF-8",
              ); ?>
            </a>
          </td>
          <td><?php echo htmlspecialchars(
              $r["reporter_name"],
              ENT_QUOTES,
              "UTF-8",
          ); ?></td>
          <td><small><?php echo htmlspecialchars(
              mb_substr($r["reason"], 0, 80),
              ENT_QUOTES,
              "UTF-8",
          ); ?></small></td>
          <td><small class="text-muted"><?php echo $r["date"]; ?></small></td>
          <?php if ($tab == "resolved"): ?>
          <td><?php echo htmlspecialchars(
              $r["mod_name"] ?: "—",
              ENT_QUOTES,
              "UTF-8",
          ); ?></td>
          <td><?php echo $statusLabel; ?></td>
          <?php endif; ?>
          <td>
            <?php if ($tab == "pending"): ?>
            <?php echo forum_admin_action_dropdown([
                [
                    "href" => "#",
                    "label" => $lang["forum_rep_btn_solve"],
                    "icon" => "fa fa-check",
                    "onclick" =>
                        "reportAction('resolve'," .
                        intval($r["id"]) .
                        ",1); return false;",
                ],
                [
                    "href" => "#",
                    "label" => $lang["forum_rep_btn_reject"],
                    "icon" => "fa fa-ban",
                    "onclick" =>
                        "reportAction('resolve'," .
                        intval($r["id"]) .
                        ",2); return false;",
                ],
                [
                    "href" => "#",
                    "label" => $lang["forum_rep_btn_trash"],
                    "icon" => "fa fa-trash-o",
                    "danger" => true,
                    "divider_before" => true,
                    "onclick" =>
                        "reportAction('trash_content'," .
                        intval($r["id"]) .
                        "); return false;",
                ],
            ]); ?>
            <?php else: ?>
            <small class="text-muted"><?php echo $r["resolved_date"]; ?></small>
            <?php endif; ?>
          </td>
        </tr>
        <?php
            endforeach;
        else:
             ?>
        <tr><td colspan="<?php echo $tab == "pending"
            ? 7
            : 9; ?>" class="text-center text-muted">
          <?php echo $tab == "pending"
              ? $lang['forum_rep_empty_wait']
              : $lang['forum_rep_empty_solved']; ?>
        </td></tr>
        <?php
        endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel-footer"><?php echo rptPagination(
      $total,
      $page,
      $perpage,
      $tab,
  ); ?></div>
</div>
