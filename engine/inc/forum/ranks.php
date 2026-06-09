<?php
/*
=====================================================
 Forge Forum Engine — Rütbe Ayarları
-----------------------------------------------------
 File: engine/inc/forum/ranks.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/

if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

include_once DLEPlugins::Check(
    ENGINE_DIR . "/inc/forum/admin_ui_helper.php",
);

$forum_cfg = [];
$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
while ($row = $db->get_row()) {
    $forum_cfg[$row["name"]] = stripslashes($row["value"]);
}
$forum_cfg = array_merge(["enable_ranks" => 1], $forum_cfg);

include_once DLEPlugins::Check(
    ENGINE_DIR . "/modules/forum/rank_helpers.php",
);

// -------------------------------------------------
// POST İŞLEMLERİ
// -------------------------------------------------
$rank_id = isset( $_REQUEST['rank_id'] ) ? intval( $_REQUEST['rank_id'] ) : 0;
$subaction = isset( $_REQUEST['subaction'] ) ? totranslit( $_REQUEST['subaction'] ) : '';

// --- KAYDET / GÜNCELLE ---
if ( isset( $_POST['save_rank'] ) ) {

    if ( $_POST['dle_post_hash'] !== $dle_login_hash ) {
        msg( "error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc'] );
        return;
    }

    $rank_title    = $db->safesql( trim( $_POST['rank_title'] ) );
    $rank_points   = intval( $_POST['rank_points'] );
    $rank_color    = $db->safesql( trim( $_POST['rank_color'] ) );
    $rank_icon     = $db->safesql( trim( $_POST['rank_icon'] ) );
    $rank_badge    = $db->safesql( trim( $_POST['rank_badge'] ) );
    $rank_image    = $db->safesql( trim( $_POST['rank_image'] ) );

    if ( empty( $rank_title ) ) {
        msg( "error", $lang['forum_error_title'], $lang['forum_rank_err_name'] );
        return;
    }

    if ( empty( $rank_color ) ) $rank_color = '#001f3f';
    if ( empty( $rank_icon ) )  $rank_icon  = 'fa-user';

    if ( $rank_id > 0 ) {
        $db->query( "UPDATE " . PREFIX . "_forum_ranks SET
            title      = '{$rank_title}',
            points     = '{$rank_points}',
            color      = '{$rank_color}',
            icon       = '{$rank_icon}',
            image      = '{$rank_image}',
            badge_text = '{$rank_badge}'
            WHERE id   = '{$rank_id}'
        " );
        forum_sync_all_user_ranks();
        msg( "success", $lang['forum_set_success_title'], $lang['forum_rank_updated'], "?mod=forum&action=ranks" );
    } else {
        $db->query( "INSERT INTO " . PREFIX . "_forum_ranks
            (title, points, color, icon, image, badge_text)
            VALUES
            ('{$rank_title}', '{$rank_points}', '{$rank_color}', '{$rank_icon}', '{$rank_image}', '{$rank_badge}')
        " );
        forum_sync_all_user_ranks();
        msg( "success", $lang['forum_set_success_title'], $lang['forum_rank_added'], "?mod=forum&action=ranks" );
    }
    return;
}

// --- SİL ---
if ( $subaction == 'delete' && $rank_id > 0 ) {

    if ( $_REQUEST['dle_post_hash'] !== $dle_login_hash ) {
        msg( "error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc'] );
        return;
    }

    $db->query( "DELETE FROM " . PREFIX . "_forum_ranks WHERE id = '{$rank_id}'" );
    forum_sync_all_user_ranks();

    msg( "success", $lang['forum_set_success_title'], $lang['forum_rank_deleted'], "?mod=forum&action=ranks" );
    return;
}

// --- ÜYEYE MANUEL RÜTBE ATA ---
if ( isset( $_POST['assign_user_rank'] ) ) {
    if ( $_POST['dle_post_hash'] !== $dle_login_hash ) {
        msg( "error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc'] );
        return;
    }

    $assign_username = trim( (string) $_POST['assign_username'] );
    $assign_rank_id  = intval( $_POST['assign_rank_id'] ?? 0 );
    if ( $assign_username === '' ) {
        msg( "error", $lang['forum_error_title'], $lang['forum_rank_assign_err_user'] );
        return;
    }

    $uname_sql = $db->safesql( $assign_username );
    $assign_user = $db->super_query(
        "SELECT user_id FROM " . USERPREFIX . "_users WHERE name='{$uname_sql}' LIMIT 1",
    );
    if ( empty( $assign_user['user_id'] ) ) {
        msg( "error", $lang['forum_error_title'], $lang['forum_rank_assign_err_notfound'] );
        return;
    }

    if ( ! forum_assign_manual_rank( intval( $assign_user['user_id'] ), $assign_rank_id ) ) {
        msg( "error", $lang['forum_error_title'], $lang['forum_rank_assign_err_rank'] );
        return;
    }

    $success_msg = $assign_rank_id > 0
        ? $lang['forum_rank_assign_ok']
        : $lang['forum_rank_assign_auto_ok'];
    msg( "success", $lang['forum_set_success_title'], $success_msg, "?mod=forum&action=ranks" );
    return;
}

// --- DÜZENLEME VERİSİ ---
$edit_rank = null;
if ( $rank_id > 0 && $subaction != 'delete' ) {
    $edit_rank = $db->super_query( "SELECT * FROM " . PREFIX . "_forum_ranks WHERE id = '{$rank_id}'" );
    if ( ! $edit_rank['id'] ) $rank_id = 0;
}

// --- TÜM RÜTBELER ---
$all_ranks = array();
$db->query( "SELECT * FROM " . PREFIX . "_forum_ranks ORDER BY points ASC" );
while ( $row = $db->get_row() ) {
    $all_ranks[] = $row;
}
?>

<!-- FORM -->
<div class="mb-20">
    <button type="button" class="btn bg-teal btn-sm btn-raised" data-toggle="collapse" data-target="#rank-form-panel">
        <b><i class="fa fa-plus"></i></b>
        <?php echo $rank_id > 0 ? $lang['forum_rank_btn_edit'] : $lang['forum_rank_btn_add']; ?>
    </button>
    <?php if ( $rank_id > 0 ): ?>
    <a href="?mod=forum&action=ranks" class="btn btn-default btn-xs"><i class="fa fa-times"></i> <?php echo $lang['forum_btn_cancel']; ?></a>
    <?php endif; ?>
</div>

<div id="rank-form-panel" class="collapse<?php echo ( $rank_id > 0 || isset($_POST['save_rank']) ) ? ' in' : ''; ?>">
    <div class="panel panel-default">
        <div class="panel-heading">
            <i class="fa fa-<?php echo $rank_id > 0 ? 'edit' : 'plus'; ?> position-left"></i>
            <?php echo $rank_id > 0 ? $lang['forum_rank_edit'] : $lang['forum_rank_new']; ?>
        </div>
        <div class="panel-body">
            <form method="post" action="?mod=forum&action=ranks<?php echo $rank_id > 0 ? '&rank_id=' . $rank_id : ''; ?>">
                <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">

                <table class="form">
                    <tr>
                        <td class="field"><label><?php echo $lang['forum_rank_name']; ?> <span class="text-danger">*</span></label></td>
                        <td>
                            <input type="text" name="rank_title" class="form-control" required
                                   value="<?php echo $edit_rank ? htmlspecialchars( $edit_rank['title'], ENT_QUOTES, 'UTF-8' ) : ''; ?>"
                                   placeholder="<?php echo $lang['forum_rank_name_ph']; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="field"><label><?php echo $lang['forum_rank_pts']; ?></label></td>
                        <td>
                            <input type="number" name="rank_points" class="form-control" style="width:150px;"
                                   value="<?php echo $edit_rank ? intval( $edit_rank['points'] ) : '0'; ?>">
                            <span class="help-block"><?php echo $lang['forum_rank_pts_help']; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="field"><label><?php echo $lang['forum_prefix_color']; ?></label></td>
                        <td>
                            <input type="text" name="rank_color" class="form-control" style="width:150px;"
                                   value="<?php echo $edit_rank ? htmlspecialchars( $edit_rank['color'], ENT_QUOTES, 'UTF-8' ) : '#001f3f'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="field"><label><?php echo $lang['forum_prefix_icon']; ?></label></td>
                        <td>
                            <input type="text" name="rank_icon" class="form-control"
                                   value="<?php echo $edit_rank ? htmlspecialchars( $edit_rank['icon'], ENT_QUOTES, 'UTF-8' ) : 'fa-user'; ?>"
                                   placeholder="fa-user">
                        </td>
                    </tr>
                    <tr>
                        <td class="field"><label><?php echo $lang['forum_rank_image']; ?></label></td>
                        <td>
                            <input type="text" name="rank_image" class="form-control" style="max-width:320px;"
                                   value="<?php echo $edit_rank ? htmlspecialchars( $edit_rank['image'] ?? '', ENT_QUOTES, 'UTF-8' ) : ''; ?>"
                                   placeholder="<?php echo $lang['forum_rank_image_ph']; ?>">
                            <span class="help-block text-muted text-size-small"><?php echo $lang['forum_rank_image_help']; ?></span>
                            <?php if ( $edit_rank && ! empty( $edit_rank['image'] ) && forum_rank_image_url( $edit_rank['image'] ) ): ?>
                            <div style="margin-top:8px;">
                                <img src="<?php echo htmlspecialchars( forum_rank_image_url( $edit_rank['image'] ), ENT_QUOTES, 'UTF-8' ); ?>" alt="" style="max-height:32px;border:1px solid #e5e7eb;border-radius:4px;padding:2px;background:#fff;">
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="field"><label><?php echo $lang['forum_rank_badge']; ?></label></td>
                        <td>
                            <input type="text" name="rank_badge" class="form-control"
                                   value="<?php echo $edit_rank ? htmlspecialchars( $edit_rank['badge_text'], ENT_QUOTES, 'UTF-8' ) : ''; ?>"
                                   placeholder="<?php echo $lang['forum_rank_badge_ph']; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="submit" colspan="2">
                            <button type="submit" name="save_rank" class="btn bg-teal btn-sm btn-raised">
                                <i class="fa fa-check"></i> <?php echo $rank_id > 0 ? $lang['forum_btn_update'] : $lang['forum_btn_save']; ?>
                            </button>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>

<!-- RÜTBE LİSTESİ -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-trophy position-left"></i> <?php echo $lang['forum_rank_list']; ?>
        <span class="label label-primary position-right"><?php echo count( $all_ranks ); ?> <?php echo $lang['forum_promo_count']; ?></span>
    </div>

    <?php if ( count( $all_ranks ) > 0 ): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo $lang['forum_rank_th_rank']; ?></th>
                    <th><?php echo $lang['forum_rank_th_pts']; ?></th>
                    <th><?php echo $lang['forum_prefix_th_color']; ?></th>
                    <th><?php echo $lang['forum_prefix_th_icon']; ?></th>
                    <th><?php echo $lang['forum_rank_th_image']; ?></th>
                    <th><?php echo $lang['forum_rank_th_badge']; ?></th>
                    <th width="100"><?php echo $lang['forum_poll_th_action']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $all_ranks as $r ): ?>
                <tr>
                    <td><?php echo $r['id']; ?></td>
                    <td>
                        <span style="color:<?php echo htmlspecialchars( $r['color'], ENT_QUOTES, 'UTF-8' ); ?>;">
                            <i class="fa <?php echo htmlspecialchars( $r['icon'], ENT_QUOTES, 'UTF-8' ); ?> position-left"></i>
                            <strong><?php echo htmlspecialchars( $r['title'], ENT_QUOTES, 'UTF-8' ); ?></strong>
                        </span>
                    </td>
                    <td><?php echo intval( $r['points'] ); ?></td>
                    <td>
                        <span style="display:inline-block;width:30px;height:18px;background:<?php echo htmlspecialchars( $r['color'], ENT_QUOTES, 'UTF-8' ); ?>;border-radius:3px;"></span>
                        <code><?php echo htmlspecialchars( $r['color'], ENT_QUOTES, 'UTF-8' ); ?></code>
                    </td>
                    <td><i class="fa <?php echo htmlspecialchars( $r['icon'], ENT_QUOTES, 'UTF-8' ); ?>"></i></td>
                    <td>
                        <?php if ( ! empty( $r['image'] ) && forum_rank_image_url( $r['image'] ) ): ?>
                        <img src="<?php echo htmlspecialchars( forum_rank_image_url( $r['image'] ), ENT_QUOTES, 'UTF-8' ); ?>" alt="" style="max-height:22px;max-width:60px;object-fit:contain;">
                        <code><?php echo htmlspecialchars( $r['image'], ENT_QUOTES, 'UTF-8' ); ?></code>
                        <?php else: ?>
                        <?php echo $lang['forum_promo_none']; ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $r['badge_text'] ? '<span class="label label-default">' . htmlspecialchars( $r['badge_text'], ENT_QUOTES, 'UTF-8' ) . '</span>' : $lang['forum_promo_none']; ?></td>
                    <td>
                        <?php echo forum_admin_action_dropdown([
                            [
                                "href" =>
                                    "?mod=forum&action=ranks&rank_id=" .
                                    intval($r["id"]) .
                                    "#rank-form-panel",
                                "label" => $lang["forum_btn_edit"],
                                "icon" => "fa fa-pencil-square-o",
                            ],
                            [
                                "href" =>
                                    "?mod=forum&action=ranks&rank_id=" .
                                    intval($r["id"]) .
                                    "&subaction=delete&dle_post_hash=" .
                                    $dle_login_hash,
                                "label" => $lang["forum_btn_delete"],
                                "icon" => "fa fa-trash-o",
                                "danger" => true,
                                "divider_before" => true,
                                "onclick" =>
                                    "return confirm('" .
                                    addslashes($lang["forum_rank_del_conf"]) .
                                    "');",
                            ],
                        ]); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="panel-body">
        <div class="alert alert-info no-margin">
            <i class="fa fa-info-circle"></i> <?php echo $lang['forum_rank_empty']; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="panel panel-default mt-20">
    <div class="panel-heading"><i class="fa fa-user-plus position-left"></i> <?php echo $lang['forum_rank_assign_title']; ?></div>
    <div class="panel-body">
        <form method="post" action="?mod=forum&action=ranks" class="form-horizontal">
            <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">
            <div class="form-group">
                <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_rank_assign_user']; ?></label>
                <div class="col-md-10 col-sm-9">
                    <input type="text" name="assign_username" class="form-control" style="max-width:280px;" placeholder="<?php echo $lang['forum_rank_assign_user_ph']; ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-md-2 col-sm-3"><?php echo $lang['forum_rank_assign_rank']; ?></label>
                <div class="col-md-10 col-sm-9">
                    <select name="assign_rank_id" class="uniform" data-width="320">
                        <option value="0"><?php echo $lang['forum_rank_assign_auto']; ?></option>
                        <?php foreach ( $all_ranks as $r ): ?>
                        <option value="<?php echo intval( $r['id'] ); ?>"><?php echo htmlspecialchars( $r['title'], ENT_QUOTES, 'UTF-8' ); ?> (<?php echo intval( $r['points'] ); ?> <?php echo $lang['forum_rank_pts_short']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <span class="help-block text-muted text-size-small"><?php echo $lang['forum_rank_assign_help']; ?></span>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-10 col-md-offset-2 col-sm-9 col-sm-offset-3">
                    <button type="submit" name="assign_user_rank" class="btn bg-teal btn-sm btn-raised">
                        <i class="fa fa-check position-left"></i><?php echo $lang['forum_rank_assign_btn']; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info mt-20">
    <i class="fa fa-lightbulb-o"></i>
    <strong><?php echo $lang['forum_rank_info_title']; ?></strong> <?php echo $lang['forum_rank_info_desc']; ?>
</div>
