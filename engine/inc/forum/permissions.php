<?php
/*
=====================================================
 Forge Forum Engine — Yetki Matrisi
-----------------------------------------------------
 File: engine/inc/forum/permissions.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/

if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

// -------------------------------------------------
// POST: KATEGORI IZINLERINI TOPLU KAYDET
// -------------------------------------------------
if (isset($_POST["save_perms"])) {
    if ($_POST["dle_post_hash"] !== $dle_login_hash) {
        msg("error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc']);
        return;
    }

    $perms = isset($_POST["perms"]) ? $_POST["perms"] : [];

    foreach ($perms as $cat_id => $perm_value) {
        $cat_id = intval($cat_id);
        $safe_perm = $db->safesql(trim($perm_value));
        if (empty($safe_perm)) {
            $safe_perm = "all";
        }

        $db->query(
            "UPDATE " .
                PREFIX .
                "_forum_cats SET permissions = '{$safe_perm}' WHERE id = '{$cat_id}'",
        );
    }

    msg(
        "success",
        $lang['forum_set_success_title'],
        $lang['forum_perm_updated'],
        "?mod=forum&action=permissions",
    );
    return;
}

// -------------------------------------------------
// TÜM KATEGORİLERİ VE GRUPLARI ÇEK
// -------------------------------------------------
$all_cats = [];
$db->query(
    "SELECT id, name, parent_id, permissions FROM " .
        PREFIX .
        "_forum_cats ORDER BY posi ASC, id ASC",
);
while ($row = $db->get_row()) {
    $all_cats[$row["id"]] = $row;
}

function _perm_tree($cats, $parent_id = 0, $depth = 0)
{
    $result = [];
    foreach ($cats as $cat) {
        if (intval($cat["parent_id"]) == $parent_id) {
            $cat["depth"] = $depth;
            $result[] = $cat;
            $children = _perm_tree($cats, $cat["id"], $depth + 1);
            $result = array_merge($result, $children);
        }
    }
    return $result;
}
$cat_tree = _perm_tree($all_cats);

$groups = [];
$db->query(
    "SELECT id, group_name FROM " . PREFIX . "_usergroups ORDER BY id ASC",
);
while ($row = $db->get_row()) {
    $groups[] = $row;
}
?>

<!-- ============================================================ -->
<!-- YETKİ MATRİSİ -->
<!-- ============================================================ -->
<div class="panel panel-default">
  <div class="panel-heading">
    <?php echo $lang['forum_perm_title']; ?>
  </div>

  <?php if (count($cat_tree) > 0): ?>
  <div class="panel-body">
    <div class="alert alert-info no-margin mb-20">
      <?php echo $lang['forum_perm_desc1']; ?><br>
      <?php echo $lang['forum_perm_desc2']; ?><br>
      <?php echo $lang['forum_perm_desc3']; ?>
    </div>

    <form method="post" action="?mod=forum&action=permissions">
      <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">

      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th><?php echo $lang['forum_stat_cats']; ?></th>
              <th width="350"><?php echo $lang['forum_perm_allowed']; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cat_tree as $cat): ?>
            <tr>
              <td>
                <span style="padding-left:<?php echo $cat["depth"] * 20; ?>px;">
                  <?php if ($cat["depth"] > 0): ?>└&nbsp;<?php endif; ?>
                  <strong><?php echo htmlspecialchars(
                      $cat["name"],
                      ENT_QUOTES,
                      "UTF-8",
                  ); ?></strong>
                </span>
              </td>
              <td>
                <input type="text"
                       name="perms[<?php echo $cat["id"]; ?>]"
                       class="form-control"
                       value="<?php echo htmlspecialchars(
                           $cat["permissions"],
                           ENT_QUOTES,
                           "UTF-8",
                       ); ?>">
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
  </div>
  <div class="panel-footer">
    <button type="submit" name="save_perms" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-floppy-o position-left"></i><?php echo $lang['forum_perm_save']; ?></button>
  </div>
  </form>
  <?php else: ?>
  <div class="panel-body">
    <div class="alert alert-warning no-margin">
      <?php echo $lang['forum_perm_no_cats']; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- GRUP REFERANS TABLOSU -->
<!-- ============================================================ -->
<div class="panel panel-default mt-20">
  <div class="panel-heading">
    <?php echo $lang['forum_perm_ref_title']; ?>
  </div>
  <div class="panel-body">
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th><?php echo $lang['forum_perm_ref_id']; ?></th>
            <th><?php echo $lang['forum_perm_ref_name']; ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($groups as $g): ?>
          <tr>
            <td><code><?php echo $g["id"]; ?></code></td>
            <td><?php echo htmlspecialchars(
                $g["group_name"],
                ENT_QUOTES,
                "UTF-8",
            ); ?></td>
          </tr>
          <?php endforeach; ?>
          <tr>
            <td><code>all</code></td>
            <td><em><?php echo $lang['forum_perm_ref_all']; ?></em></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
