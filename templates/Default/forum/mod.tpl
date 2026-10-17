<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- Moderasyon Özeti -->
    <div class="result-card" style="display:flex;align-items:center;justify-content:space-between;gap:var(--sp-4);flex-wrap:wrap;margin-bottom:var(--sp-5)">
        <span style="font-weight:700;color:var(--text)"><i class="fa fa-shield" aria-hidden="true" style="color:var(--accent)"></i> {mod_title}</span>
        <div style="display:flex;align-items:center;gap:var(--sp-4);flex-wrap:wrap;font-size:var(--fs-sm);font-weight:600">
            <span class="read-link"><i class="fa fa-clock-o" aria-hidden="true"></i> Onay bekleyen konu: <b>{pending_count}</b></span>
            <span class="read-link"><i class="fa fa-comment-o" aria-hidden="true"></i> Onay bekleyen mesaj: <b>{pending_posts}</b></span>
            <span class="read-link"><i class="fa fa-flag" aria-hidden="true"></i> Açık şikayet: <b>{report_count}</b></span>
        </div>
    </div>

    <!-- Hızlı Yönetim Bağlantıları -->
    <div class="result-card" style="display:flex;align-items:center;gap:var(--sp-3);flex-wrap:wrap;margin-bottom:var(--sp-5);font-size:var(--fs-sm);font-weight:600">
        <a href="{admin_approval_url}" class="read-link"><i class="fa fa-check-square-o" aria-hidden="true"></i> Onay Kuyruğu</a>
        <a href="{admin_reports_url}" class="read-link"><i class="fa fa-flag-o" aria-hidden="true"></i> Şikayetler</a>
        <a href="{admin_topics_url}" class="read-link"><i class="fa fa-list" aria-hidden="true"></i> Konular</a>
    </div>

    <!-- Onay Bekleyen Konular -->
    <section class="forum-category-block">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-clock-o" aria-hidden="true"></i> Onay Bekleyen Konular</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="result-card" style="display:flex;align-items:center;gap:var(--sp-3);flex-wrap:wrap;margin:0 0 var(--sp-2);font-size:var(--fs-sm);font-weight:600">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="checkbox" id="forum-pending-all"> Tümünü Seç</label>
            <button type="button" class="soft-button" onclick="forumBulkApproval('approve')"><i class="fa fa-check" aria-hidden="true"></i> Seçilenleri Onayla</button>
            <button type="button" class="forum-btn-mini forum-report-btn" onclick="forumBulkApproval('reject')"><i class="fa fa-trash" aria-hidden="true"></i> Seçilenleri Sil</button>
        </div>
        <div class="forum-rows">
            {pending_rows}
        </div>
    </section>
    <section class="forum-category-block mt-4">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-flag" aria-hidden="true"></i> Açık Şikayetler</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="forum-rows">
            {report_rows}
        </div>
    </section>
</div>

<script>
function forumModAction(mod, params, confirmMsg) {
    if (typeof jQuery === 'undefined') return;
    var run = function () {
        params.user_hash = (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '{dle_login_hash}');
        jQuery.post('{http_home_url}index.php?controller=ajax&mod=' + mod, params, function (r) {
            if (r && (r.success === true || r.status === 'ok')) {
                location.reload();
            } else if (typeof showForumMessage === 'function') {
                showForumMessage((r && (r.error || r.message)) || 'İşlem başarısız.', true);
            } else {
                alert((r && (r.error || r.message)) || 'İşlem başarısız.');
            }
        }, 'json').fail(function () {
            alert('Bağlantı hatası.');
        });
    };
    if (confirmMsg) {
        if (typeof DLEconfirm === 'undefined') { if (confirm(confirmMsg)) run(); }
        else { DLEconfirm(confirmMsg, 'Moderasyon', run); }
    } else { run(); }
}
function forumApproveTopic(id) { forumModAction('forum_approval', { action: 'approve', type: 'topic', id: id }); }
function forumRejectTopic(id) { forumModAction('forum_approval', { action: 'reject', type: 'topic', id: id }, 'Konu kalıcı olarak silinsin mi?'); }
function forumReportResolve(id, st) { forumModAction('forum_reports', { action: 'resolve', id: id, status: st }); }
function forumReportTrash(id) { forumModAction('forum_reports', { action: 'trash_content', id: id }, 'İçerik çöpe atılıp şikayet kapatılsın mı?'); }
function forumBulkApproval(kind) {
    var boxes = document.querySelectorAll('.forum-pending-check:checked');
    var ids = [];
    for (var i = 0; i < boxes.length; i++) ids.push(boxes[i].value);
    if (!ids.length) { alert('Hiç konu seçilmedi.'); return; }
    var act = kind === 'approve' ? 'bulk_approve' : 'bulk_reject';
    var msg = kind === 'approve'
        ? (ids.length + ' konu onaylanacak. Emin misiniz?')
        : (ids.length + ' konu kalıcı olarak silinecek. Emin misiniz?');
    forumModAction('forum_approval', { action: act, ids: ids.join(',') }, msg);
}
(function () {
    var all = document.getElementById('forum-pending-all');
    if (all) {
        all.addEventListener('change', function () {
            var boxes = document.querySelectorAll('.forum-pending-check');
            for (var i = 0; i < boxes.length; i++) boxes[i].checked = all.checked;
        });
    }
})();
</script>
