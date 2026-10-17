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
if (!defined('DATALIFEENGINE')) {
    die("Hacking attempt!");
}

include_once DLEPlugins::Check(
    ENGINE_DIR . "/inc/forum/admin_ui_helper.php",
);

$poll_id   = isset($_REQUEST['poll_id']) ? intval($_REQUEST['poll_id']) : 0;
$subaction = isset($_REQUEST['subaction']) ? totranslit($_REQUEST['subaction']) : '';
$poll_csrf_ok = isset($_REQUEST['user_hash']) && $_REQUEST['user_hash'] === $dle_login_hash;

// -----------------------------------------------------------------
// SİL
// -----------------------------------------------------------------
if ($subaction == 'delete' && $poll_id > 0) {
    if (!$poll_csrf_ok) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }
    $db->query("DELETE FROM " . PREFIX . "_forum_poll_votes WHERE poll_id='{$poll_id}'");
    $db->query("DELETE FROM " . PREFIX . "_forum_poll_options WHERE poll_id='{$poll_id}'");
    $db->query("DELETE FROM " . PREFIX . "_forum_polls WHERE id='{$poll_id}'");
    msg("success", $lang['forum_set_success_title'], $lang['forum_poll_deleted'], "?mod=forum&action=polls");
    return;
}

// -----------------------------------------------------------------
// TEMİZLE (oyları sıfırla, anketi koru)
// -----------------------------------------------------------------
if ($subaction == 'clear' && $poll_id > 0) {
    if (!$poll_csrf_ok) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }
    $db->query("DELETE FROM " . PREFIX . "_forum_poll_votes WHERE poll_id='{$poll_id}'");
    $db->query("UPDATE " . PREFIX . "_forum_poll_options SET votes='0' WHERE poll_id='{$poll_id}'");
    msg("success", $lang['forum_set_success_title'], $lang['forum_poll_cleared'], "?mod=forum&action=polls");
    return;
}

// -----------------------------------------------------------------
// DURDUR / DEVAM ETTİR
// -----------------------------------------------------------------
if ($subaction == 'toggle' && $poll_id > 0) {
    if (!$poll_csrf_ok) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }
    $row = $db->super_query("SELECT is_closed FROM " . PREFIX . "_forum_polls WHERE id='{$poll_id}'");
    $new = !empty($row['is_closed']) ? 0 : 1;
    $db->query("UPDATE " . PREFIX . "_forum_polls SET is_closed='{$new}' WHERE id='{$poll_id}'");
    $txt = $new ? $lang['forum_poll_closed_msg'] : $lang['forum_poll_opened_msg'];
    msg("success", $lang['forum_set_success_title'], $txt, "?mod=forum&action=polls");
    return;
}

// -----------------------------------------------------------------
// ANKET SONUÇLARI
// -----------------------------------------------------------------
if ($subaction == 'results' && $poll_id > 0) {
    $poll = $db->super_query(
        "SELECT p.*, t.title AS topic_title
         FROM " . PREFIX . "_forum_polls p
         LEFT JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
         WHERE p.id='{$poll_id}'",
    );
    if (empty($poll['id'])) {
        msg("error", $lang['forum_error_title'], $lang['forum_poll_not_found'], "?mod=forum&action=polls");
        return;
    }

    $options = [];
    $total_votes = 0;
    $db->query(
        "SELECT * FROM " . PREFIX . "_forum_poll_options
         WHERE poll_id='{$poll_id}' ORDER BY posi ASC, id ASC",
    );
    while ($o = $db->get_row()) {
        $options[] = $o;
        $total_votes += intval($o['votes']);
    }

    $voters_row = $db->super_query(
        "SELECT COUNT(DISTINCT user_id) AS cnt FROM " . PREFIX . "_forum_poll_votes WHERE poll_id='{$poll_id}'",
    );
    $voters = intval($voters_row['cnt']);

    $is_closed = intval($poll['is_closed']) || (!empty($poll['end_date']) && strtotime($poll['end_date']) < time());
    $status_lbl = $is_closed
        ? '<span class="label label-danger">' . $lang['forum_poll_lbl_closed'] . '</span>'
        : '<span class="label label-success">' . $lang['forum_poll_lbl_open'] . '</span>';
    $end_lbl = $poll['end_date'] ? $poll['end_date'] : $lang['forum_poll_indefinite'];
    $question = htmlspecialchars($poll['question'], ENT_QUOTES, 'UTF-8');
    $topic_lbl = $poll['topic_title']
        ? htmlspecialchars($poll['topic_title'], ENT_QUOTES, 'UTF-8')
        : '';
    ?>

<div class="panel panel-default">
  <div class="panel-heading">
    <i class="fa fa-bar-chart position-left"></i> <?php echo $lang['forum_poll_results_title']; ?>
    <span class="label label-primary position-right"><?php echo $total_votes; ?> <?php echo $lang['forum_poll_th_votes']; ?></span>
  </div>
  <div class="panel-body">
    <h4 style="margin-top:0"><?php echo $question; ?></h4>
    <p class="text-muted" style="margin-bottom:18px">
      <?php if ($topic_lbl !== ''): ?><i class="fa fa-comments-o"></i> <?php echo $topic_lbl; ?>&nbsp;&nbsp;<?php endif; ?>
      <?php echo $status_lbl; ?>
      &nbsp;&nbsp;<i class="fa fa-clock-o"></i> <?php echo $lang['forum_poll_th_end']; ?>: <?php echo htmlspecialchars($end_lbl, ENT_QUOTES, 'UTF-8'); ?>
      &nbsp;&nbsp;<i class="fa fa-users"></i> <?php echo $lang['forum_poll_total_voters']; ?>: <strong><?php echo $voters; ?></strong>
    </p>

    <?php if (count($options)): ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th width="40">#</th>
          <th><?php echo $lang['forum_poll_th_opts']; ?></th>
          <th width="80"><?php echo $lang['forum_poll_th_votes']; ?></th>
          <th width="80">%</th>
          <th width="38%"><?php echo $lang['forum_poll_th_chart']; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php $rank = 0; foreach ($options as $o):
            $rank++;
            $votes = intval($o['votes']);
            $pct = $total_votes > 0 ? round(($votes / $total_votes) * 100, 1) : 0;
            $bar_color = $pct >= 50 ? '#22c55e' : ($pct >= 25 ? '#3b82f6' : '#94a3b8');
        ?>
        <tr>
          <td><?php echo $rank; ?></td>
          <td><strong><?php echo htmlspecialchars($o['text'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
          <td><?php echo $votes; ?></td>
          <td><?php echo $pct; ?>%</td>
          <td>
            <div style="height:10px;background:#eef1f5;border-radius:5px;overflow:hidden">
              <div style="height:100%;width:<?php echo $pct; ?>%;background:<?php echo $bar_color; ?>;border-radius:5px"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p class="text-muted"><?php echo $lang['forum_poll_no_options']; ?></p>
    <?php endif; ?>
  </div>
  <div class="panel-footer">
    <a href="?mod=forum&action=polls" class="btn bg-grey-400 btn-sm btn-raised"><i class="fa fa-arrow-left position-left"></i><?php echo $lang['forum_poll_back']; ?></a>
    <a href="?mod=forum&action=polls&subaction=edit&poll_id=<?php echo $poll_id; ?>" class="btn bg-teal btn-sm btn-raised"><i class="fa fa-pencil position-left"></i><?php echo $lang['forum_poll_btn_edit']; ?></a>
    <a href="?mod=forum&action=polls&poll_id=<?php echo $poll_id; ?>&subaction=clear&user_hash=<?php echo $dle_login_hash; ?>" onclick="return confirm('<?php echo addslashes($lang['forum_poll_confirm_clear']); ?>');" class="btn bg-orange-400 btn-sm btn-raised"><i class="fa fa-eraser position-left"></i><?php echo $lang['forum_poll_btn_clear']; ?></a>
  </div>
</div>
    <?php
    return;
}

// -----------------------------------------------------------------
// DÜZENLE
// -----------------------------------------------------------------
if ($subaction == 'edit' && $poll_id > 0) {
    // Kaydet
    if (isset($_POST['save_poll'])) {
        if (!$poll_csrf_ok) {
            msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
            return;
        }

        $question = $db->safesql(trim((string) ($_POST['poll_question'] ?? '')));
        $options_raw = trim((string) ($_POST['poll_options'] ?? ''));
        $lines = preg_split('/\r\n|\r|\n/', $options_raw);
        $lines = array_values(array_filter(array_map('trim', $lines), function ($v) {
            return $v !== '';
        }));

        if ($question === '' || count($lines) < 2) {
            msg("error", $lang['forum_error_title'], $lang['forum_poll_edit_required'], "?mod=forum&action=polls&subaction=edit&poll_id={$poll_id}");
            return;
        }

        $multiple = isset($_POST['poll_multiple']) ? 1 : 0;
        $max_choices = max(1, intval($_POST['poll_max_choices'] ?? 1));
        if (!$multiple) {
            $max_choices = 1;
        } elseif ($max_choices > count($lines)) {
            $max_choices = count($lines);
        }
        $is_closed = isset($_POST['poll_closed']) ? 1 : 0;

        $end_raw = trim((string) ($_POST['poll_end_date'] ?? ''));
        $end_sql = 'NULL';
        if ($end_raw !== '') {
            $end_ts = strtotime($end_raw . ' 23:59:59');
            if ($end_ts !== false) {
                $end_sql = "'" . date('Y-m-d H:i:s', $end_ts) . "'";
            }
        }

        $db->query(
            "UPDATE " . PREFIX . "_forum_polls SET
                question='{$question}',
                multiple='{$multiple}',
                max_choices='{$max_choices}',
                end_date={$end_sql},
                is_closed='{$is_closed}'
             WHERE id='{$poll_id}'",
        );

        // Mevcut seçenekleri önce topla (döngü içinde INSERT query_id'yi ezmesin)
        $existing = [];
        $db->query(
            "SELECT id, votes FROM " . PREFIX . "_forum_poll_options
             WHERE poll_id='{$poll_id}' ORDER BY posi ASC, id ASC",
        );
        while ($o = $db->get_row()) {
            $existing[] = $o;
        }

        foreach ($lines as $i => $text) {
            $safe_text = $db->safesql($text);
            if (isset($existing[$i])) {
                $oid = intval($existing[$i]['id']);
                $db->query(
                    "UPDATE " . PREFIX . "_forum_poll_options
                     SET text='{$safe_text}', posi='{$i}' WHERE id='{$oid}'",
                );
            } else {
                $db->query(
                    "INSERT INTO " . PREFIX . "_forum_poll_options (poll_id, text, votes, posi)
                     VALUES ('{$poll_id}', '{$safe_text}', '0', '{$i}')",
                );
            }
        }

        // Fazla seçenekler: yalnızca oy almamış olanlar silinir
        for ($i = count($lines); $i < count($existing); $i++) {
            $oid = intval($existing[$i]['id']);
            if (intval($existing[$i]['votes']) === 0) {
                $db->query(
                    "DELETE FROM " . PREFIX . "_forum_poll_votes
                     WHERE poll_id='{$poll_id}' AND option_id='{$oid}'",
                );
                $db->query("DELETE FROM " . PREFIX . "_forum_poll_options WHERE id='{$oid}'");
            } else {
                $db->query(
                    "UPDATE " . PREFIX . "_forum_poll_options SET posi='{$i}' WHERE id='{$oid}'",
                );
            }
        }

        msg("success", $lang['forum_set_success_title'], $lang['forum_poll_updated'], "?mod=forum&action=polls");
        return;
    }

    $poll = $db->super_query(
        "SELECT p.*, t.title AS topic_title
         FROM " . PREFIX . "_forum_polls p
         LEFT JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
         WHERE p.id='{$poll_id}'",
    );
    if (empty($poll['id'])) {
        msg("error", $lang['forum_error_title'], $lang['forum_poll_not_found'], "?mod=forum&action=polls");
        return;
    }

    $option_lines = [];
    $db->query(
        "SELECT text FROM " . PREFIX . "_forum_poll_options
         WHERE poll_id='{$poll_id}' ORDER BY posi ASC, id ASC",
    );
    while ($o = $db->get_row()) {
        $option_lines[] = stripslashes($o['text']);
    }

    $end_value = !empty($poll['end_date']) ? substr($poll['end_date'], 0, 10) : '';
    $question_val = htmlspecialchars($poll['question'], ENT_QUOTES, 'UTF-8');
    $options_val = htmlspecialchars(implode("\n", $option_lines), ENT_QUOTES, 'UTF-8');
    $topic_lbl = $poll['topic_title']
        ? htmlspecialchars($poll['topic_title'], ENT_QUOTES, 'UTF-8')
        : '';
    ?>

<div class="panel panel-default">
  <div class="panel-heading">
    <i class="fa fa-pencil position-left"></i> <?php echo $lang['forum_poll_edit_title']; ?>
    <?php if ($topic_lbl !== ''): ?>
    <span class="text-muted text-size-small position-right"><i class="fa fa-comments-o"></i> <?php echo $topic_lbl; ?></span>
    <?php endif; ?>
  </div>
  <form method="post" action="?mod=forum&action=polls&subaction=edit&poll_id=<?php echo $poll_id; ?>" class="form-horizontal">
    <div class="panel-body">
      <input type="hidden" name="user_hash" value="<?php echo $dle_login_hash; ?>">

      <div class="form-group">
        <label class="control-label col-sm-2"><?php echo $lang['forum_poll_th_q']; ?></label>
        <div class="col-sm-10">
          <input type="text" name="poll_question" class="form-control" maxlength="255" value="<?php echo $question_val; ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="control-label col-sm-2"><?php echo $lang['forum_poll_th_opts']; ?></label>
        <div class="col-sm-10">
          <textarea name="poll_options" rows="6" class="form-control" required><?php echo $options_val; ?></textarea>
          <span class="help-block text-muted text-size-small"><?php echo $lang['forum_poll_options_hint']; ?></span>
        </div>
      </div>

      <div class="form-group">
        <label class="control-label col-sm-2"><?php echo $lang['forum_poll_th_end']; ?></label>
        <div class="col-sm-4">
          <input type="date" name="poll_end_date" class="form-control" value="<?php echo htmlspecialchars($end_value, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-sm-6">
          <label class="checkbox-inline">
            <input type="checkbox" name="poll_closed" value="1" <?php echo !empty($poll['is_closed']) ? 'checked' : ''; ?>> <?php echo $lang['forum_poll_lbl_closed']; ?>
          </label>
          <label class="checkbox-inline">
            <input type="checkbox" name="poll_multiple" value="1" <?php echo !empty($poll['multiple']) ? 'checked' : ''; ?>> <?php echo $lang['forum_poll_multiple']; ?>
          </label>
        </div>
      </div>

      <div class="form-group">
        <label class="control-label col-sm-2"><?php echo $lang['forum_poll_max_choices']; ?></label>
        <div class="col-sm-2">
          <input type="number" min="1" name="poll_max_choices" class="form-control" value="<?php echo max(1, intval($poll['max_choices'])); ?>">
        </div>
      </div>
    </div>
    <div class="panel-footer">
      <button type="submit" name="save_poll" class="btn bg-teal btn-sm btn-raised"><i class="fa fa-floppy-o position-left"></i><?php echo $lang['forum_poll_save']; ?></button>
      <a href="?mod=forum&action=polls" class="btn bg-grey-400 btn-sm btn-raised"><?php echo $lang['forum_poll_cancel']; ?></a>
    </div>
  </form>
</div>
    <?php
    return;
}

// -----------------------------------------------------------------
// LİSTE
// -----------------------------------------------------------------
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perpage = 30;
$offset = ($page - 1) * $perpage;

$total = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_polls");
$total = intval($total['cnt']);

$polls = [];
$db->query(
    "SELECT p.*, t.title AS topic_title FROM " . PREFIX . "_forum_polls p
     LEFT JOIN " . PREFIX . "_forum_topics t ON t.id = p.topic_id
     ORDER BY p.id DESC LIMIT {$offset},{$perpage}",
);
while ($row = $db->get_row()) {
    $polls[] = $row;
}

// Sayfalama
$pages = ceil($total / $perpage);
$pagination = '';
if ($pages > 1) {
    $pagination .= '<ul class="pagination pagination-sm no-margin">';
    for ($i = 1; $i <= $pages; $i++) {
        $act = $i == $page ? 'active' : '';
        $pagination .= '<li class="' . $act . '"><a href="?mod=forum&action=polls&page=' . $i . '">' . $i . '</a></li>';
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
        <?php if (count($polls)): foreach ($polls as $p):
            $opts = $db->super_query("SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_poll_options WHERE poll_id='{$p['id']}'");
            $votes = $db->super_query("SELECT SUM(votes) AS total FROM " . PREFIX . "_forum_poll_options WHERE poll_id='{$p['id']}'");
            $closed = $p['is_closed'] ? '<span class="label label-danger">' . $lang['forum_poll_lbl_closed'] . '</span>' : '<span class="label label-success">' . $lang['forum_poll_lbl_open'] . '</span>';
        ?>
        <tr>
          <td><?php echo $p['id']; ?></td>
          <td><strong><?php echo htmlspecialchars($p['question'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
          <td><?php echo htmlspecialchars($p['topic_title'] ? mb_substr($p['topic_title'], 0, 40) : '—', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo intval($opts['cnt']); ?></td>
          <td><?php echo intval($votes['total']); ?></td>
          <td><?php echo $p['end_date'] ? $p['end_date'] : '<em>' . $lang['forum_poll_indefinite'] . '</em>'; ?></td>
          <td><?php echo $closed; ?></td>
          <td>
            <?php echo forum_admin_action_dropdown([
                [
                    "href" =>
                        "?mod=forum&action=polls&poll_id=" .
                        intval($p["id"]) .
                        "&subaction=results",
                    "label" => $lang["forum_poll_btn_results"],
                    "icon" => "fa fa-eye",
                ],
                [
                    "href" =>
                        "?mod=forum&action=polls&poll_id=" .
                        intval($p["id"]) .
                        "&subaction=edit",
                    "label" => $lang["forum_poll_btn_edit"],
                    "icon" => "fa fa-pencil-square-o",
                ],
                [
                    "href" =>
                        "?mod=forum&action=polls&poll_id=" .
                        intval($p["id"]) .
                        "&subaction=toggle&user_hash=" .
                        $dle_login_hash,
                    "label" => $p["is_closed"]
                        ? $lang["forum_poll_btn_resume"]
                        : $lang["forum_poll_btn_stop"],
                    "icon" => $p["is_closed"] ? "fa fa-play" : "fa fa-pause",
                ],
                [
                    "href" =>
                        "?mod=forum&action=polls&poll_id=" .
                        intval($p["id"]) .
                        "&subaction=clear&user_hash=" .
                        $dle_login_hash,
                    "label" => $lang["forum_poll_btn_clear"],
                    "icon" => "fa fa-eraser",
                    "onclick" =>
                        "return confirm('" .
                        addslashes($lang["forum_poll_confirm_clear"]) .
                        "');",
                ],
                [
                    "href" =>
                        "?mod=forum&action=polls&poll_id=" .
                        intval($p["id"]) .
                        "&subaction=delete&user_hash=" .
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
