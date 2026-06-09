<?php
/*
=====================================================
 Forge Forum Engine — Bakım ve Senkronizasyon
-----------------------------------------------------
 File: engine/inc/forum/tools.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if ($member_id["user_group"] != 1) {
    msg("error", $lang['forum_access_denied'], $lang['forum_super_admin_only']);
    return;
}
?>

<script>
var dle_root = '<?php echo $config["http_home_url"]; ?>';
var _forumToolRunning = false;
var _forumToolTimer   = null;

function forumToolStart(action, label) {
    if(_forumToolRunning) {
        DLEPush.warning('<?php echo $lang['forum_tools_running']; ?>');
        return;
    }
    _forumToolRunning = true;

    var $card  = $('#tool-'+action);
    var $prog  = $card.find('.progress-bar');
    var $txt   = $card.find('.tool-status');
    var $btn   = $card.find('.tool-btn');

    $btn.prop('disabled', true);
    $prog.css('width','0%').text('0%');
    $txt.text('<?php echo $lang['forum_tools_starting']; ?>');
    $card.find('.progress').show();

    var step = 0;
    function nextStep() {
        $.post(
            dle_root + 'index.php?controller=ajax&mod=forum_tools',
            { action: action, step: step, user_hash: '<?php echo $dle_login_hash; ?>' },
            function(res) {
                if(!res.success) {
                    $txt.text('<?php echo $lang['forum_tools_err']; ?>' + (res.error || '<?php echo $lang['forum_tools_err_unknown']; ?>'));
                    $btn.prop('disabled', false);
                    _forumToolRunning = false;
                    DLEPush.error(res.error || '<?php echo $lang['forum_tools_err_fail']; ?>');
                    return;
                }
                var pct = res.total > 0 ? Math.round((res.processed / res.total) * 100) : 0;
                $prog.css('width', pct+'%').text(pct+'%');
                $txt.text(res.message);

                if(res.done) {
                    $prog.css('width','100%').text('100%');
                    $txt.text(res.message);
                    $btn.prop('disabled', false);
                    _forumToolRunning = false;
                    DLEPush.info(res.message);
                } else {
                    step = res.step;
                    nextStep();
                }
            },
            'json'
        ).fail(function(){
            $txt.text('<?php echo $lang['forum_tools_err_conn']; ?>');
            $btn.prop('disabled', false);
            _forumToolRunning = false;
            DLEPush.error('<?php echo $lang['forum_tools_err_timeout']; ?>');
        });
    }
    nextStep();
}

$(function(){
    // Sayfa yüklendi
});
</script>

<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_tools_title']; ?>
  </div>
  <div class="panel-body">
    <div class="alert alert-warning">
      <i class="fa fa-exclamation-triangle position-left"></i>
      <strong><?php echo $lang['forum_tools_warn_title']; ?></strong> <?php echo $lang['forum_tools_warn_desc']; ?>
    </div>
  </div>
</div>

<div class="row">
  <?php
  $tools = [
      [
          "id" => "repair_users",
          "icon" => "fa-users",
          "color" => "bg-teal",
          "title" => $lang['forum_tools_t1_title'],
          "desc" => $lang['forum_tools_t1_desc'],
      ],
      [
          "id" => "repair_cats",
          "icon" => "fa-folder-open",
          "color" => "bg-primary",
          "title" => $lang['forum_tools_t2_title'],
          "desc" => $lang['forum_tools_t2_desc'],
      ],
      [
          "id" => "repair_topics",
          "icon" => "fa-comments",
          "color" => "bg-warning",
          "title" => $lang['forum_tools_t3_title'],
          "desc" => $lang['forum_tools_t3_desc'],
      ],
      [
          "id" => "clear_cache",
          "icon" => "fa-bolt",
          "color" => "bg-danger",
          "title" => $lang['forum_tools_t4_title'],
          "desc" => $lang['forum_tools_t4_desc'],
      ],
  ];
  foreach ($tools as $t): ?>
  <div class="col-sm-6">
    <div class="panel panel-default" id="tool-<?php echo $t["id"]; ?>">
      <div class="panel-body">
        <div class="media">
          <div class="media-left">
            <i class="fa <?php echo $t["icon"]; ?> fa-3x text-muted"></i>
          </div>
          <div class="media-body">
            <h5 class="media-heading text-semibold"><?php echo $t[
                "title"
            ]; ?></h5>
            <p class="text-muted text-size-small"><?php echo $t["desc"]; ?></p>
          </div>
        </div>
        <div class="progress mt-15" style="display:none;">
          <div class="progress-bar progress-bar-striped active" style="width:0%">0%</div>
        </div>
        <div class="tool-status text-muted text-size-small mt-5"></div>
        <div class="text-right mt-10">
          <button type="button" class="btn <?php echo $t[
              "color"
          ]; ?> btn-sm btn-raised tool-btn" onclick="forumToolStart('<?php echo $t[
     "id"
 ]; ?>','<?php echo $t["title"]; ?>')">
            <i class="fa fa-play position-left"></i><?php echo $lang['forum_tools_btn_start']; ?>
          </button>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach;
  ?>
</div>

<div class="panel panel-default mt-20">
  <div class="panel-heading"><?php echo $lang['forum_tools_history']; ?></div>
  <div class="panel-body">
    <table class="table table-striped">
      <thead><tr><th><?php echo $lang['forum_tools_th_tool']; ?></th><th><?php echo $lang['forum_tools_th_desc']; ?></th><th><?php echo $lang['forum_tools_th_time']; ?></th><th><?php echo $lang['forum_tools_th_load']; ?></th></tr></thead>
      <tbody>
        <tr>
          <td><i class="fa fa-users text-teal"></i> <?php echo $lang['forum_tools_h1_t']; ?></td>
          <td><?php echo $lang['forum_tools_h1_d']; ?></td>
          <td><?php echo $lang['forum_tools_h1_time']; ?></td>
          <td><span class="label label-success"><?php echo $lang['forum_tools_load_low']; ?></span></td>
        </tr>
        <tr>
          <td><i class="fa fa-folder-open text-primary"></i> <?php echo $lang['forum_tools_h2_t']; ?></td>
          <td><?php echo $lang['forum_tools_h2_d']; ?></td>
          <td><?php echo $lang['forum_tools_h2_time']; ?></td>
          <td><span class="label label-success"><?php echo $lang['forum_tools_load_low']; ?></span></td>
        </tr>
        <tr>
          <td><i class="fa fa-comments text-warning"></i> <?php echo $lang['forum_tools_h3_t']; ?></td>
          <td><?php echo $lang['forum_tools_h3_d']; ?></td>
          <td><?php echo $lang['forum_tools_h3_time']; ?></td>
          <td><span class="label label-warning"><?php echo $lang['forum_tools_load_mid']; ?></span></td>
        </tr>
        <tr>
          <td><i class="fa fa-bolt text-danger"></i> <?php echo $lang['forum_tools_h4_t']; ?></td>
          <td><?php echo $lang['forum_tools_h4_d']; ?></td>
          <td><?php echo $lang['forum_tools_h4_time']; ?></td>
          <td><span class="label label-success"><?php echo $lang['forum_tools_load_low']; ?></span></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
