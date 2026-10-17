<article class="forum-node {t_row_class}">
    <!-- Konu ikonu -->
    <span class="icon-action forum-node__icon" aria-hidden="true">
        [t-pinned]<i class="fa fa-thumb-tack"></i>[/t-pinned]
        [t-locked]<i class="fa fa-lock"></i>[/t-locked]
        [t-unread]<i class="fa fa-comment"></i>[/t-unread]
        [t-read]<i class="fa fa-comment-o"></i>[/t-read]
    </span>

    <!-- Başlık + meta -->
    <div class="forum-node__info">
        <div class="forum-node__badges">
            [t-has-prefix]<span class="badge" style="background:{t_prefix_color};border-color:{t_prefix_color};color:#fff"><i class="fa {t_prefix_icon}" aria-hidden="true"></i> {t_prefix_name}</span>[/t-has-prefix]
            [t-unread]<span class="badge badge--new">Yeni</span>[/t-unread]
        </div>
        <h3 class="forum-node__title" style="{t_title_style}"><a href="{t_url}">{t_title}</a></h3>
        <div class="forum-node__meta">
            <span class="meta-item meta-item--author">{t_author}</span>
            <span class="meta-dot" aria-hidden="true"></span>
            <span class="meta-item">{t_date}</span>
        </div>
        [t-first-unread]
        <div class="forum-node__firstunread">
            <a href="{t_first_unread_url}" class="read-link"><i class="fa fa-arrow-circle-right" aria-hidden="true"></i> İlk okunmamışa git</a>
        </div>
        [/t-first-unread]
    </div>

    <!-- İstatistik -->
    <div class="forum-node__stats">
        <span class="forum-node__stat"><b>{t_replies}</b> cevap</span>
        <span class="forum-node__stat"><b>{t_views}</b> görüntüleme</span>
    </div>

    <!-- Son ileti -->
    <div class="forum-node__last">
        [t-has-lastpost]
        {t_last_avatar}
        <span class="forum-node__lastinfo">
            <a class="forum-node__lastlink" href="{t_last_url}">{t_last_topic_short}</a>
            <span class="forum-node__lastmeta">{t_last_user} · {t_last_date}</span>
        </span>
        [/t-has-lastpost]
    </div>
</article>
