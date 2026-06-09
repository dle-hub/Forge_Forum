<?php
/*
=====================================================
 Forge Forum Engine — Çöp Kutusu / Geri Dönüşüm
-----------------------------------------------------
 File: engine/inc/forum/trash.php
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

// Silinen konular
$offset = ($page - 1) * $perpage;
$totalTopics = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " .
        PREFIX .
        "_forum_topics WHERE is_deleted=1",
);
$totalTopics = intval($totalTopics["cnt"]);

$topics = [];
if ($tab == "topics") {
    $db->query(
        "SELECT t.*, u.name AS author_name FROM " .
            PREFIX .
            "_forum_topics t
        LEFT JOIN " .
            PREFIX .
            "_users u ON u.user_id = t.user_id
        WHERE t.is_deleted=1 ORDER BY t.date DESC LIMIT {$offset},{$perpage}",
    );
    while ($row = $db->get_row()) {
        $topics[] = $row;
    }
}

// Silinen mesajlar
$totalPosts = $db->super_query(
    "SELECT COUNT(*) AS cnt FROM " . PREFIX . "_forum_posts WHERE is_deleted=1",
);
$totalPosts = intval($totalPosts["cnt"]);

$posts = [];
if ($tab == "posts") {
    $db->query(
        "SELECT p.*, u.name AS author_name, t.title AS topic_title, t.id AS tid FROM " .
            PREFIX .
            "_forum_posts p
        LEFT JOIN " .
            PREFIX .
            "_users u ON u.user_id = p.user_id
        LEFT JOIN " .
            PREFIX .
            "_forum_topics t ON t.id = p.topic_id
        WHERE p.is_deleted=1 ORDER BY p.date DESC LIMIT {$offset},{$perpage}",
    );
    while ($row = $db->get_row()) {
        $posts[] = $row;
    }
}

function trashPagination($total, $page, $perpage, $tab)
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
            '"><a href="?mod=forum&action=trash&tab=' .
            $tab .
            "&page=" .
            $i .
            '">' .
            $i .
            "</a></li>";
    }
    $o .= "</ul>";
    return $o;
}
?>

<script>
var dle_root = '<?php echo $config["http_home_url"]; ?>';
function trashAction(action, id, type) {
    var msg = '';
    if(action == 'restore') msg = '<?php echo $lang['forum_trash_restore_conf']; ?>';
    else if(action == 'hard_delete') msg = '<?php echo $lang['forum_trash_del_conf']; ?>';

    DLEconfirm(msg, '<?php echo $lang['forum_trash_confirm']; ?>', function(){
        $.post(
            dle_root + 'index.php?controller=ajax&mod=forum_trash',
            { action: action, id: id, type: type, user_hash: '<?php echo $dle_login_hash; ?>' },
            function(res) {
                if(res.success) {
                    DLEPush.info(res.message);
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    DLEPush.error(res.error || '<?php echo $lang['forum_trash_err']; ?>');
                }
            }, 'json'
        );
    });
}

function emptyTrash(subtype) {
    var msg = subtype == 'all'
        ? '<?php echo $lang['forum_trash_empty_all']; ?>'
        : (subtype == 'topics'
            ? '<?php echo $lang['forum_trash_empty_t']; ?>'
            : '<?php echo $lang['forum_trash_empty_p']; ?>');

    DLEconfirm(msg, '<?php echo $lang['forum_trash_empty_btn']; ?>', function(){
        $.post(
            dle_root + 'index.php?controller=ajax&mod=forum_trash',
            { action: 'empty_trash', subtype: subtype, user_hash: '<?php echo $dle_login_hash; ?>' },
            function(res) {
                if(res.success) {
                    DLEPush.info(res.message);
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    DLEPush.error(res.error || '<?php echo $lang['forum_trash_err']; ?>');
                }
            }, 'json'
        );
    });
}
</script>

<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_trash_title']; ?>
    <div class="heading-elements">
      <div class="btn-group btn-group-xs">
        <button class="btn bg-danger btn-sm btn-raised" onclick="emptyTrash('all')"><i class="fa fa-trash-o position-left"></i><?php echo $lang['forum_trash_btn_all']; ?></button>
        <button class="btn bg-warning btn-sm btn-raised" onclick="emptyTrash('topics')"><i class="fa fa-trash-o position-left"></i><?php echo $lang['forum_trash_btn_t']; ?></button>
        <button class="btn bg-warning btn-sm btn-raised" onclick="emptyTrash('posts')"><i class="fa fa-trash-o position-left"></i><?php echo $lang['forum_trash_btn_p']; ?></button>
      </div>
    </div>
  </div>

  <!-- SEKMELER -->
  <ul class="nav nav-tabs nav-tabs-solid">
    <li class="<?php echo $tab == "topics" ? "active" : ""; ?>">
      <a href="?mod=forum&action=trash&tab=topics">
        <i class="fa fa-files-o position-left"></i> <?php echo $lang['forum_trash_list_t']; ?>
        <span class="label label-danger position-right"><?php echo $totalTopics; ?></span>
      </a>
    </li>
    <li class="<?php echo $tab == "posts" ? "active" : ""; ?>">
      <a href="?mod=forum&action=trash&tab=posts">
        <i class="fa fa-file-text-o position-left"></i> <?php echo $lang['forum_trash_list_p']; ?>
        <span class="label label-danger position-right"><?php echo $totalPosts; ?></span>
      </a>
    </li>
  </ul>

  <!-- KONULAR TABLOSU -->
  <?php if ($tab == "topics"): ?>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>#</th><th><?php echo $lang['forum_trash_th_t']; ?></th><th><?php echo $lang['forum_topic_th_author']; ?></th><th><?php echo $lang['forum_topic_th_cat']; ?></th><th><?php echo $lang['forum_cat_replies']; ?></th><th><?php echo $lang['forum_trash_th_time']; ?></th><th width="140"><?php echo $lang['forum_poll_th_action']; ?></th></tr></thead>
      <tbody>
        <?php if (count($topics)):
            foreach ($topics as $t):
                $cat = $db->super_query(
                    "SELECT name FROM " .
                        PREFIX .
                        "_forum_cats WHERE id='" .
                        intval($t["cat_id"]) .
                        "'",
                ); ?>
        <tr>
          <td><?php echo $t["id"]; ?></td>
          <td><strong><?php echo htmlspecialchars(
              mb_substr($t["title"], 0, 60),
              ENT_QUOTES,
              "UTF-8",
          ); ?></strong></td>
          <td><?php echo htmlspecialchars(
              $t["author_name"],
              ENT_QUOTES,
              "UTF-8",
          ); ?></td>
          <td><?php echo $cat["name"]
              ? htmlspecialchars($cat["name"], ENT_QUOTES, "UTF-8")
              : "—"; ?></td>
          <td><?php echo intval($t["replies"]) + 1; ?></td>
          <td><small class="text-muted"><?php echo $t["date"]; ?></small></td>
          <td>
            <div class="btn-group btn-group-xs">
              <button class="btn bg-teal" onclick="trashAction('restore',<?php echo $t[
                  "id"
              ]; ?>,'topic')" title="<?php echo $lang['forum_trash_restore']; ?>"><i class="fa fa-undo"></i></button>
              <button class="btn btn-danger" onclick="trashAction('hard_delete',<?php echo $t[
                  "id"
              ]; ?>,'topic')" title="<?php echo $lang['forum_trash_delete']; ?>"><i class="fa fa-times"></i></button>
            </div>
          </td>
        </tr>
        <?php
            endforeach;
        else:
             ?>
        <tr><td colspan="7" class="text-center text-muted"><?php echo $lang['forum_trash_empty']; ?></td></tr>
        <?php
        endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel-footer"><?php echo trashPagination(
      $totalTopics,
      $page,
      $perpage,
      "topics",
  ); ?></div>

  <!-- MESAJLAR TABLOSU -->
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>#</th><th><?php echo $lang['forum_trash_th_msg']; ?></th><th><?php echo $lang['forum_topic_th_author']; ?></th><th><?php echo $lang['forum_trash_th_t']; ?></th><th><?php echo $lang['forum_trash_th_time']; ?></th><th width="140"><?php echo $lang['forum_poll_th_action']; ?></th></tr></thead>
      <tbody>
        <?php if (count($posts)):
            foreach ($posts as $p): ?>
        <tr>
          <td><?php echo $p["id"]; ?></td>
          <td>
            <div class="text-muted text-size-small" style="max-width:300px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
              <?php echo htmlspecialchars(
                  strip_tags(mb_substr($p["text"], 0, 100)),
                  ENT_QUOTES,
                  "UTF-8",
              ); ?>
            </div>
          </td>
          <td><?php echo htmlspecialchars(
              $p["author_name"],
              ENT_QUOTES,
              "UTF-8",
          ); ?></td>
          <td>
            <?php if ($p["topic_title"]): ?>
            <small><?php echo htmlspecialchars(
                mb_substr($p["topic_title"], 0, 40),
                ENT_QUOTES,
                "UTF-8",
            ); ?></small>
            <?php else: ?><em class="text-muted"><?php echo $lang['forum_trash_topic_del']; ?></em><?php endif; ?>
          </td>
          <td><small class="text-muted"><?php echo $p["date"]; ?></small></td>
          <td>
            <div class="btn-group btn-group-xs">
              <button class="btn bg-teal" onclick="trashAction('restore',<?php echo $p[
                  "id"
              ]; ?>,'post')" title="<?php echo $lang['forum_trash_restore']; ?>"><i class="fa fa-undo"></i></button>
              <button class="btn btn-danger" onclick="trashAction('hard_delete',<?php echo $p[
                  "id"
              ]; ?>,'post')" title="<?php echo $lang['forum_trash_delete']; ?>"><i class="fa fa-times"></i></button>
            </div>
          </td>
        </tr>
        <?php endforeach;
        else:
             ?>
        <tr><td colspan="6" class="text-center text-muted"><?php echo $lang['forum_trash_empty']; ?></td></tr>
        <?php
        endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel-footer"><?php echo trashPagination(
      $totalPosts,
      $page,
      $perpage,
      "posts",
  ); ?></div>
  <?php endif; ?>
</div>

<!-- BİLGİ -->
<div class="alert alert-info mt-20">
  <i class="fa fa-info-circle position-left"></i>
  <strong><?php echo $lang['forum_trash_info_title']; ?></strong> <?php echo $lang['forum_trash_info_desc']; ?>
</div>
