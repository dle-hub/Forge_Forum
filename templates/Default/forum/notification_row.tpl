<div id="notif-row-{id}" class="mybb-row [notif-unread]mybb-row-unread notif-unread-row[/notif-unread] [notif-read]notif-read-row[/notif-read]" style="align-items: center; justify-content: space-between;">

    <!-- Sol: İkon + Bildirim Metni -->
    <div class="mybb-col-forum">

        <!-- Durum İkonu -->
        <div class="mybb-indicator">
            [notif-unread]<span class="forum-unread-dot"></span>[/notif-unread]
            [notif-read]
                [type-reply]<i class="fa fa-reply text-gray-300 text-sm"></i>[/type-reply]
                [type-like]<i class="fa fa-heart text-gray-300 text-sm"></i>[/type-like]
                [type-mention]<i class="fa fa-at text-gray-300 text-sm"></i>[/type-mention]
            [/notif-read]
        </div>

        <div class="mybb-forum-info">
            <!-- Bildirim Metni -->
            <div class="mybb-forum-name">
                [type-reply]
                <i class="fa fa-reply notif-type-icon notif-icon-reply"></i>
                <strong>{sender}</strong>, <a href="{notif_url}">{topic_title}</a> konusuna cevap yazdı.
                [/type-reply]
                [type-like]
                <i class="fa fa-heart notif-type-icon notif-icon-like"></i>
                <strong>{sender}</strong>, <a href="{notif_url}">{topic_title}</a> konusundaki mesajınızı beğendi.
                [/type-like]
                [type-mention]
                <i class="fa fa-at notif-type-icon notif-icon-mention"></i>
                <strong>{sender}</strong>, <a href="{notif_url}">{topic_title}</a> konusunda sizden bahsetti.
                [/type-mention]
            </div>
            <!-- Tarih -->
            <div class="mybb-forum-desc">
                <i class="fa fa-clock-o"></i> {date}
            </div>
        </div>
    </div>

    <!-- Sağ: Sil Butonu -->
    <div style="flex-shrink:0; padding-left: 1rem;">
        <button onclick="deleteNotif({id})" class="mybb-btn-mini notif-delete-btn" title="Bildirimi sil">
            <i class="fa fa-trash"></i>
        </button>
    </div>

</div>
