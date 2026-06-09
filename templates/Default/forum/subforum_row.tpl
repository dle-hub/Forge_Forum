<div class="mybb-row">
    <!-- Column 1: Forum Info -->
    <div class="mybb-col-forum">
        <div class="mybb-indicator" title="{sf_name}">
            [sf-has-icon]{sf_icon_html}[/sf-has-icon]
            [sf-no-icon]<span class="mybb-double-circle"></span>[/sf-no-icon]
        </div>
        <div class="mybb-forum-info">
            <div class="mybb-forum-name"><a href="{sf_url}">{sf_name}</a></div>
            <div class="mybb-forum-desc">{sf_description}</div>
            [sf-has-subforums]
            <div class="mybb-subforums">Alt Forumlar: {sf_subforums}</div>
            [/sf-has-subforums]
        </div>
    </div>
    <!-- Column 2: Stats -->
    <div class="mybb-col-stats">
        <div class="mybb-stats-box">
            <div class="mybb-stats-item"><span>Konu:</span> <span class="mybb-stats-num">{sf_topics}</span></div>
            <div class="mybb-stats-item"><span>Mesaj:</span> <span class="mybb-stats-num">{sf_posts}</span></div>
        </div>
    </div>
    <!-- Column 3: Last Post -->
    <div class="mybb-col-lastpost">
        [sf-has-lastpost]
        <div class="mybb-lastpost-card">
            <div class="mybb-lastpost-title"><a href="{sf_last_url}" title="{sf_last_topic_full}">{sf_last_topic}</a></div>
            <div class="mybb-lastpost-meta">{sf_last_date}</div>
            <div class="mybb-lastpost-author" style="display:flex; align-items:center; gap:5px; margin-top:4px;">
                {sf_last_avatar}
                <span>Yazan: {sf_last_user}</span>
            </div>
        </div>
        [/sf-has-lastpost]
        [sf-no-lastpost]
        <div class="mybb-lastpost-never">Hiç yok</div>
        [/sf-no-lastpost]
    </div>
</div>
