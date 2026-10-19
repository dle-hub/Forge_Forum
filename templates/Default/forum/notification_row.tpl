<div id="notif-row-{id}" class="forum-row [notif-unread]forum-row-unread notif-unread-row[/notif-unread] [notif-read]notif-read-row[/notif-read]" style="align-items: center; justify-content: space-between;">

    <!-- Sol: İkon + Bildirim Metni -->
    <div class="forum-col-forum">

        <!-- Durum İkonu -->
        <div class="forum-indicator">
            [notif-unread]<span class="forum-unread-dot"></span>[/notif-unread]
            [notif-read]
                [type-reply]<i class="fa fa-reply text-gray-300 text-sm"></i>[/type-reply]
                [type-like]<i class="fa fa-heart text-gray-300 text-sm"></i>[/type-like]
                [type-mention]<i class="fa fa-at text-gray-300 text-sm"></i>[/type-mention]
                [type-follow_topic]<i class="fa fa-user-plus text-gray-300 text-sm"></i>[/type-follow_topic]
                [type-cat_topic]<i class="fa fa-folder-open text-gray-300 text-sm"></i>[/type-cat_topic]
            [/notif-read]
        </div>

        <div class="forum-info">
            <!-- Bildirim Metni -->
            <div class="forum-name">
                [type-reply]
                <i class="fa fa-reply notif-type-icon notif-icon-reply"></i>
                {notif_text}
                [/type-reply]
                [type-like]
                <i class="fa fa-heart notif-type-icon notif-icon-like"></i>
                {notif_text}
                [/type-like]
                [type-mention]
                <i class="fa fa-at notif-type-icon notif-icon-mention"></i>
                {notif_text}
                [/type-mention]
                [type-follow_topic]
                <i class="fa fa-user-plus notif-type-icon"></i>
                {notif_text}
                [/type-follow_topic]
                [type-cat_topic]
                <i class="fa fa-folder-open notif-type-icon"></i>
                {notif_text}
                [/type-cat_topic]
            </div>
            <!-- Tarih -->
            <div class="forum-desc">
                <i class="fa fa-clock-o"></i> {date}
            </div>
        </div>
    </div>

    <!-- Sağ: Sil Butonu -->
    <div style="flex-shrink:0; padding-left: 1rem;">
        <button onclick="deleteNotif({id})" class="forum-btn-mini notif-delete-btn" title="{forum_ui_notif_delete}">
            <i class="fa fa-trash"></i>
        </button>
    </div>

</div>
