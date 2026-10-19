<div class="forum-row">
    <!-- Column 1: Forum Info -->
    <div class="forum-col-forum">
        <div class="forum-indicator" title="{sf_name}">
            [sf-has-icon]{sf_icon_html}[/sf-has-icon]
            [sf-no-icon]<span class="forum-double-circle"></span>[/sf-no-icon]
        </div>
        <div class="forum-info">
            <div class="forum-name"><a href="{sf_url}">{sf_name}</a>[sf-unread] <span class="forum-unread-badge" title="{forum_ui_unread_topic}">{sf_unread}</span>[/sf-unread]</div>
            <div class="forum-desc">{sf_description}</div>
            [sf-has-subforums]
            <div class="forum-subforums">{forum_ui_subforums}: {sf_subforums}</div>
            [/sf-has-subforums]
        </div>
    </div>
    <!-- Column 2: Stats -->
    <div class="forum-col-stats">
        <div class="forum-stats-box">
            <div class="forum-stats-item"><span>{forum_ui_topic_label}</span> <span class="forum-stats-num">{sf_topics}</span></div>
            <div class="forum-stats-item"><span>{forum_ui_message_label}</span> <span class="forum-stats-num">{sf_posts}</span></div>
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
                <span>{forum_ui_written_by}: {sf_last_user}</span>
            </div>
        </div>
        [/sf-has-lastpost]
        [sf-no-lastpost]
        <div class="forum-lastpost-never">{forum_ui_none}</div>
        [/sf-no-lastpost]
    </div>
</div>
