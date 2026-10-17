<article class="forum-node">
    <!-- Forum ikonu -->
    <span class="icon-action forum-node__icon" title="{sf_name}">
        [sf-has-icon]{sf_icon_html}[/sf-has-icon]
        [sf-no-icon]<i class="fa fa-comments" aria-hidden="true"></i>[/sf-no-icon]
    </span>

    <!-- Ad + açıklama -->
    <div class="forum-node__info">
        <h3 class="forum-node__title"><a href="{sf_url}">{sf_name}</a>[sf-unread] <span class="badge badge--new" title="Okunmamış konu">{sf_unread}</span>[/sf-unread]</h3>
        <p class="forum-node__desc">{sf_description}</p>
        [sf-has-subforums]
        <div class="chips chips--tags forum-node__subs">Alt forumlar: {sf_subforums}</div>
        [/sf-has-subforums]
    </div>

    <!-- İstatistik -->
    <div class="forum-node__stats">
        <span class="forum-node__stat"><b>{sf_topics}</b> konu</span>
        <span class="forum-node__stat"><b>{sf_posts}</b> mesaj</span>
    </div>

    <!-- Son ileti -->
    <div class="forum-node__last">
        [sf-has-lastpost]
        {sf_last_avatar}
        <span class="forum-node__lastinfo">
            <a class="forum-node__lastlink" href="{sf_last_url}" title="{sf_last_topic_full}">{sf_last_topic}</a>
            <span class="forum-node__lastmeta">{sf_last_user} · {sf_last_date}</span>
        </span>
        [/sf-has-lastpost]
        [sf-no-lastpost]
        <span class="forum-node__lastmeta">Hiç ileti yok</span>
        [/sf-no-lastpost]
    </div>
</article>
