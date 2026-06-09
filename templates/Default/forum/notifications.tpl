<div class="forum-wrapper">

    <!-- Breadcrumb (forum.php tarafından otomatik doldurulur) -->
    {forum_breadcrumb}

    <!-- Actions Strip -->
    <div class="mybb-actions-strip">
        <div class="mybb-notif-page-title">
            <i class="fa fa-bell notif-bell-icon"></i>
            Bildirimleriniz
            [has-forum-notifs]
            <span class="notif-count-badge">{forum_notif_count}</span>
            [/has-forum-notifs]
        </div>
        <div>
            <button onclick="markAllNotifsRead()" class="mybb-btn-mini notif-mark-all-btn">
                <i class="fa fa-check-square-o"></i> Hepsini Okundu İşaretle
            </button>
        </div>
    </div>

    <!-- Bildirim Listesi -->
    <div class="mybb-category-block">
        <div class="mybb-cat-header">
            <span class="mybb-cat-title"><i class="fa fa-bell-o" style="margin-right:6px; opacity:.8;"></i> Son Bildirimler</span>
        </div>

        <div class="mybb-cat-rows" id="notif-rows-container">
            {notification_rows}
        </div>
    </div>

</div>

<script>
function deleteNotif(id) {
    var row = document.getElementById('notif-row-' + id);
    if (!row) return;

    fetch('{http_home_url}index.php?do=forum&action=notifications&subaction=delete&id=' + id)
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.success) {
                row.style.transition = 'opacity .3s, max-height .3s';
                row.style.opacity = '0';
                row.style.overflow = 'hidden';
                row.style.maxHeight = row.offsetHeight + 'px';
                setTimeout(function() {
                    row.style.maxHeight = '0';
                    row.style.padding = '0';
                }, 50);
                setTimeout(function() {
                    row.remove();
                    var container = document.getElementById('notif-rows-container');
                    if (container && container.querySelectorAll('.mybb-row').length === 0) {
                        container.innerHTML = '<div class="notif-empty-state"><i class="fa fa-bell-slash-o"></i><br>Henüz bildiriminiz yok.</div>';
                    }
                }, 380);
            }
        });
}

function markAllNotifsRead() {
    fetch('{http_home_url}index.php?do=forum&action=notifications&subaction=mark_all_read')
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.success) {
                /* Okunmamış satırları okunmuş görünüme çevir */
                document.querySelectorAll('.notif-unread-row').forEach(function(el) {
                    el.classList.remove('mybb-row-unread', 'notif-unread-row');
                    el.classList.add('notif-read-row');
                    var dot = el.querySelector('.forum-unread-dot');
                    if (dot) {
                        dot.outerHTML = '<i class="fa fa-bell-o text-gray-300" style="font-size:10px;"></i>';
                    }
                });
                /* Rozeti kaldır */
                document.querySelectorAll('.notif-count-badge').forEach(function(el){ el.remove(); });
            }
        });
}
</script>
