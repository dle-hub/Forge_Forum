<div class="forum-row">
    <!-- Column 1: Forum Info -->
    <div class="forum-col-forum">
        <div class="forum-indicator" title="{sf_name}">
            [sf-has-icon]{sf_icon_html}[/sf-has-icon]
            [sf-no-icon]<span class="forum-double-circle"></span>[/sf-no-icon]
        </div>
        <div class="forum-info">
            <div class="forum-name"><a href="{sf_url}">{sf_name}</a>[sf-unread] <span class="forum-unread-badge" title="Okunmamış konu">{sf_unread}</span>[/sf-unread]</div>
            <div class="forum-desc">{sf_description}</div>
            [sf-has-subforums]
            <div class="forum-subforums">Alt Forumlar: {sf_subforums}</div>
            [/sf-has-subforums]
        </div>
    </div>
    <!-- Column 2: Stats -->
    <div class="forum-col-stats">
        <div class="forum-stats-box">
            <div class="forum-stats-item"><span>Konu:</span> <span class="forum-stats-num">{sf_topics}</span></div>
            <div class="forum-stats-item"><span>Mesaj:</span> <span class="forum-stats-num">{sf_posts}</span></div>
        </div>
    </div>
    <!-- Column 3: Last Post -->
    <div class="forum-col-lastpost">
        [sf-has-lastpost]
        <div class="forum-lastpost-card">
            <div class="forum-lastpost-title"><a href="{sf_last_url}" title="{sf_last_topic_full}">{sf_last_topic}</a></div>
            <div class="forum-lastpost-meta">{sf_last_date}</div>
            <div class="forum-lastpost-author" style="display:flex; align-items:center; gap:5px; margin-top:4px;">
                {sf_last_avatar}
                <span>Yazan: {sf_last_user}</span>
            </div>
        </div>
        [/sf-has-lastpost]
        [sf-no-lastpost]
        <div class="forum-lastpost-never">Hiç yok</div>
        [/sf-no-lastpost]
    </div>
</div>
