<?php
/*
=====================================================
 Forge Forum Engine — Kelime Filtresi (Sansür)
-----------------------------------------------------
 File: engine/inc/forum/wordfilter.php
 Author: Dlehub & Elegance
 Github: https://github.com/dlehub
=====================================================
*/

if( !defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );

// -------------------------------------------------
// POST İŞLEMLERİ
// -------------------------------------------------
$word_id   = isset( $_REQUEST['word_id'] ) ? intval( $_REQUEST['word_id'] ) : 0;
$subaction = isset( $_REQUEST['subaction'] ) ? totranslit( $_REQUEST['subaction'] ) : '';

// --- KAYDET / GÜNCELLE ---
if ( isset( $_POST['save_word'] ) ) {

    if ( $_POST['dle_post_hash'] !== $dle_login_hash ) {
        msg( "error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc'] );
        return;
    }

    $filter_word    = $db->safesql( trim( $_POST['filter_word'] ) );
    $filter_replace = $db->safesql( trim( $_POST['filter_replace'] ) );

    if ( empty( $filter_word ) ) {
        msg( "error", $lang['forum_error_title'], $lang['forum_filter_err_word'] );
        return;
    }

    if ( empty( $filter_replace ) ) $filter_replace = '***';

    if ( $word_id > 0 ) {
        $db->query( "UPDATE " . PREFIX . "_forum_banned_words SET
            word = '{$filter_word}',
            replacement = '{$filter_replace}'
            WHERE id = '{$word_id}'
        " );
        msg( "success", $lang['forum_set_success_title'], $lang['forum_filter_updated'], "?mod=forum&action=wordfilter" );
    } else {
        $db->query( "INSERT INTO " . PREFIX . "_forum_banned_words
            (word, replacement) VALUES ('{$filter_word}', '{$filter_replace}')
        " );
        msg( "success", $lang['forum_set_success_title'], $lang['forum_filter_added'], "?mod=forum&action=wordfilter" );
    }
    return;
}

// --- SİL ---
if ( $subaction == 'delete' && $word_id > 0 ) {

    if ( $_REQUEST['dle_post_hash'] !== $dle_login_hash ) {
        msg( "error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc'] );
        return;
    }

    $db->query( "DELETE FROM " . PREFIX . "_forum_banned_words WHERE id = '{$word_id}'" );
    msg( "success", $lang['forum_set_success_title'], $lang['forum_filter_deleted'], "?mod=forum&action=wordfilter" );
    return;
}

// --- TOPLU İÇE AKTAR ---
if ( isset( $_POST['bulk_import'] ) ) {

    if ( $_POST['dle_post_hash'] !== $dle_login_hash ) {
        msg( "error", $lang['forum_set_csrf_title'], $lang['forum_set_csrf_desc'] );
        return;
    }

    $bulk_text = trim( $_POST['bulk_text'] );
    if ( ! empty( $bulk_text ) ) {
        $lines = explode( "\n", $bulk_text );
        $added = 0;
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( empty( $line ) ) continue;
            // Format: kelime|yenisi  veya sadece kelime
            if ( strpos( $line, '|' ) !== false ) {
                list( $w, $r ) = explode( '|', $line, 2 );
            } else {
                $w = $line;
                $r = '***';
            }
            $w = $db->safesql( trim( $w ) );
            $r = $db->safesql( trim( $r ) );
            if ( ! empty( $w ) ) {
                $db->query( "INSERT IGNORE INTO " . PREFIX . "_forum_banned_words (word, replacement) VALUES ('{$w}', '{$r}')" );
                $added++;
            }
        }
        $msg_str = str_replace('{added}', $added, $lang['forum_filter_imported']);
        msg( "success", $lang['forum_set_success_title'], $msg_str, "?mod=forum&action=wordfilter" );
    }
    return;
}

// --- DÜZENLEME ---
$edit_word = null;
if ( $word_id > 0 && $subaction != 'delete' ) {
    $edit_word = $db->super_query( "SELECT * FROM " . PREFIX . "_forum_banned_words WHERE id = '{$word_id}'" );
    if ( ! $edit_word['id'] ) $word_id = 0;
}

// --- TÜM KELİMELER ---
$all_words = array();
$db->query( "SELECT * FROM " . PREFIX . "_forum_banned_words ORDER BY id DESC" );
while ( $row = $db->get_row() ) {
    $all_words[] = $row;
}
?>

<!-- FORM -->
<div class="mb-20">
    <button type="button" class="btn bg-teal btn-sm btn-raised" data-toggle="collapse" data-target="#word-form-panel">
        <b><i class="fa fa-plus"></i></b>
        <?php echo $word_id > 0 ? $lang['forum_filter_btn_edit'] : $lang['forum_filter_btn_add']; ?>
    </button>
    <?php if ( $word_id > 0 ): ?>
    <a href="?mod=forum&action=wordfilter" class="btn btn-default btn-xs"><i class="fa fa-times"></i> <?php echo $lang['forum_btn_cancel']; ?></a>
    <?php endif; ?>
</div>

<div id="word-form-panel" class="collapse<?php echo ( $word_id > 0 || isset($_POST['save_word']) ) ? ' in' : ''; ?>">
    <div class="panel panel-default">
        <div class="panel-heading">
            <i class="fa fa-<?php echo $word_id > 0 ? 'edit' : 'plus'; ?> position-left"></i>
            <?php echo $word_id > 0 ? $lang['forum_filter_edit'] : $lang['forum_filter_btn_add']; ?>
        </div>
        <div class="panel-body">
            <form method="post" action="?mod=forum&action=wordfilter<?php echo $word_id > 0 ? '&word_id=' . $word_id : ''; ?>">
                <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">

                <table class="form">
                    <tr>
                        <td class="field"><label><?php echo $lang['forum_filter_word']; ?> <span class="text-danger">*</span></label></td>
                        <td>
                            <input type="text" name="filter_word" class="form-control" required
                                   value="<?php echo $edit_word ? htmlspecialchars( $edit_word['word'], ENT_QUOTES, 'UTF-8' ) : ''; ?>"
                                   placeholder="<?php echo $lang['forum_filter_word_ph']; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="field"><label><?php echo $lang['forum_filter_replace']; ?></label></td>
                        <td>
                            <input type="text" name="filter_replace" class="form-control"
                                   value="<?php echo $edit_word ? htmlspecialchars( $edit_word['replacement'], ENT_QUOTES, 'UTF-8' ) : '***'; ?>"
                                   placeholder="***">
                        </td>
                    </tr>
                    <tr>
                        <td class="submit" colspan="2">
                            <button type="submit" name="save_word" class="btn bg-teal btn-sm btn-raised">
                                <i class="fa fa-check"></i> <?php echo $word_id > 0 ? $lang['forum_btn_update'] : $lang['forum_btn_save']; ?>
                            </button>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <!-- SOL: Kelime Listesi -->
    <div class="col-lg-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-filter position-left"></i> <?php echo $lang['forum_filter_list']; ?>
                <span class="label label-primary position-right"><?php echo count( $all_words ); ?> <?php echo $lang['forum_promo_count']; ?></span>
            </div>

            <?php if ( count( $all_words ) > 0 ): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo $lang['forum_filter_th_word']; ?></th>
                            <th><?php echo $lang['forum_filter_th_rep']; ?></th>
                            <th width="80"><?php echo $lang['forum_poll_th_action']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $all_words as $w ): ?>
                        <tr>
                            <td><?php echo $w['id']; ?></td>
                            <td><code><?php echo htmlspecialchars( $w['word'], ENT_QUOTES, 'UTF-8' ); ?></code></td>
                            <td><code><?php echo htmlspecialchars( $w['replacement'], ENT_QUOTES, 'UTF-8' ); ?></code></td>
                            <td>
                                <div class="btn-group btn-group-xs">
                                    <a href="?mod=forum&action=wordfilter&word_id=<?php echo $w['id']; ?>#word-form-panel"
                                       class="btn btn-primary" title="<?php echo $lang['forum_btn_edit']; ?>"><i class="fa fa-pencil"></i></a>
                                    <a href="?mod=forum&action=wordfilter&word_id=<?php echo $w['id']; ?>&subaction=delete&dle_post_hash=<?php echo $dle_login_hash; ?>"
                                       class="btn btn-danger" title="<?php echo $lang['forum_btn_delete']; ?>"
                                       onclick="return confirm('<?php echo $lang['forum_promo_del_conf']; ?>');"><i class="fa fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="panel-body">
                <div class="alert alert-info no-margin">
                    <i class="fa fa-info-circle"></i> <?php echo $lang['forum_filter_empty']; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SAĞ: Toplu İçe Aktarma -->
    <div class="col-lg-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-upload position-left"></i> <?php echo $lang['forum_filter_bulk']; ?>
            </div>
            <div class="panel-body">
                <form method="post" action="?mod=forum&action=wordfilter">
                    <input type="hidden" name="dle_post_hash" value="<?php echo $dle_login_hash; ?>">
                    <p class="text-muted small">
                        <?php echo $lang['forum_filter_bulk_desc']; ?>
                    </p>
                    <textarea name="bulk_text" class="form_textarea" rows="8"
                              placeholder="<?php echo $lang['forum_filter_bulk_ph']; ?>"></textarea>
                    <div class="mt-10">
                        <button type="submit" name="bulk_import" class="btn btn-primary btn-sm">
                            <i class="fa fa-upload"></i> <?php echo $lang['forum_filter_btn_bulk']; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
