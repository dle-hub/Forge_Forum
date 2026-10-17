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
            [type-reply]<strong>{sender}</strong>, <a href="{notif_url}">{topic_title}</a> konusuna cevap yazdı.[/type-reply]
            [type-like]<strong>{sender}</strong>, <a href="{notif_url}">{topic_title}</a> konusundaki mesajınızı beğendi.[/type-like]
            [type-mention]<strong>{sender}</strong>, <a href="{notif_url}">{topic_title}</a> konusunda sizden bahsetti.[/type-mention]
            [type-follow_topic]<span class="forum-node__text">Takip ettiğiniz <strong>{sender}</strong> yeni bir konu açtı: <a href="{notif_url}">{topic_title}</a></span>[/type-follow_topic]
            [type-cat_topic]<span class="forum-node__text"><strong>{sender}</strong>, abone olduğunuz kategoride yeni bir konu açtı: <a href="{notif_url}">{topic_title}</a></span>[/type-cat_topic]
        </div>
        <div class="forum-node__meta">
            <span class="meta-item"><i class="fa fa-clock-o" aria-hidden="true"></i>{date}</span>
        </div>
    </div>

    <button onclick="deleteNotif({id})" class="icon-action" title="Bildirimi sil">
        <i class="fa fa-trash" aria-hidden="true"></i>
    </button>
</article>
