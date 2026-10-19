<article id="notif-row-{id}" class="forum-node forum-node--notif forum-row [notif-unread]forum-row-unread notif-unread-row[/notif-unread] [notif-read]notif-read-row[/notif-read]">
    <span class="icon-action forum-node__icon" aria-hidden="true">
        [type-reply]<i class="fa fa-reply" style="color:var(--accent)"></i>[/type-reply]
        [type-like]<i class="fa fa-heart" style="color:var(--negative)"></i>[/type-like]
        [type-mention]<i class="fa fa-at" style="color:var(--accent)"></i>[/type-mention]
        [type-follow_topic]<i class="fa fa-user-plus" style="color:var(--accent)"></i>[/type-follow_topic]
        [type-cat_topic]<i class="fa fa-folder-open" style="color:var(--accent)"></i>[/type-cat_topic]
    </span>

    <div class="forum-node__info">
        <div class="forum-node__text">
            [notif-unread]<span class="forum-unread-dot" aria-hidden="true"></span>[/notif-unread]
            [type-reply]{notif_text}[/type-reply]
            [type-like]{notif_text}[/type-like]
            [type-mention]{notif_text}[/type-mention]
            [type-follow_topic]<span class="forum-node__text">{notif_text}</span>[/type-follow_topic]
            [type-cat_topic]<span class="forum-node__text">{notif_text}</span>[/type-cat_topic]
        </div>
        <div class="forum-node__meta">
            <span class="meta-item"><i class="fa fa-clock-o" aria-hidden="true"></i>{date}</span>
        </div>
    </div>

    <button onclick="deleteNotif({id})" class="icon-action" title="{forum_ui_notif_delete}">
        <i class="fa fa-trash" aria-hidden="true"></i>
    </button>
</article>
