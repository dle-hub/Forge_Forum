<div class="forum-wrapper">

    <!-- Breadcrumb (forum.php tarafından otomatik doldurulur) -->
    {forum_breadcrumb}

    <!-- Araç çubuğu -->
    <div class="forum-toolbar">
        <div class="forum-toolbar__links">
            <span class="forum-toolbar__title"><i class="fa fa-bell"></i> {forum_ui_notifications_title}</span>
            [has-forum-notifs]<span class="forum-toolbar__count notif-count-badge">{forum_notif_count}</span>[/has-forum-notifs]
        </div>
        <div class="forum-toolbar__end">
            <button type="button" onclick="markAllNotifsRead()" class="forum-toolbar__btn">
                <i class="fa fa-check-square-o"></i><span>{forum_ui_mark_all_notifs_read}</span>
            </button>
        </div>
    </div>

    <!-- Bildirim Listesi -->
    <div class="forum-category-block">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-bell-o" style="margin-right:6px; opacity:.8;"></i> {forum_ui_notifications_recent}</span>
        </div>

        <div class="forum-cat-rows" id="notif-rows-container">
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
                    if (container && container.querySelectorAll('.forum-row').length === 0) {
                        container.innerHTML = '<div class="notif-empty-state"><i class="fa fa-bell-slash-o"></i><br>{forum_ui_no_notifications}</div>';
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
                    el.classList.remove('forum-row-unread', 'notif-unread-row');
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
