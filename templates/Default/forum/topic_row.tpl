<div class="mybb-row {t_row_class}">
    <!-- Column 1: Topic Info -->
    <div class="mybb-col-forum">

        <!-- Topic Status Icon -->
        <div class="mybb-indicator">
            [t-pinned]<i class="fa fa-thumb-tack text-yellow-600 text-sm"></i>[/t-pinned]
            [t-locked]<i class="fa fa-lock text-gray-400 text-sm"></i>[/t-locked]
            [t-unread]<span class="forum-unread-dot"></span>[/t-unread]
            [t-read]<i class="fa fa-comments-o text-gray-300 text-sm"></i>[/t-read]
        </div>

        <div class="mybb-forum-info">
            <!-- Topic Title with Badges -->
            <div class="mybb-forum-name" style="{t_title_style}">
                [t-pinned]<span style="display: inline-flex; align-items: center; gap: 3px; background-color: #f59e0b; color: #ffffff; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; margin-right: 6px; text-transform: uppercase;"><i class="fa fa-thumb-tack" style="font-size: 8px;"></i> SABİT</span>[/t-pinned]
                [t-locked]<span style="display: inline-flex; align-items: center; gap: 3px; background-color: #ef4444; color: #ffffff; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; margin-right: 6px; text-transform: uppercase;"><i class="fa fa-lock" style="font-size: 8px;"></i> KİLİTLİ</span>[/t-locked]
                [t-has-prefix]<span class="mybb-prefix-badge" style="background:{t_prefix_color}; color:#fff; padding: 2px 5px; border-radius: 2px; font-size: 10px; font-weight: bold; margin-right: 4px; display: inline-flex; align-items: center; gap: 3px;"><i class="fa {t_prefix_icon}"></i> {t_prefix_name}</span>[/t-has-prefix]
                <a href="{t_url}">{t_title}</a>
            </div>
            <!-- Author & Date -->
            <div class="mybb-forum-desc">
                Oluşturan: {t_author} &nbsp;•&nbsp; {t_date}
            </div>
        </div>
    </div>

    <!-- Column 2: Stats -->
    <div class="mybb-col-stats">
        <div class="mybb-stats-box">
            <div class="mybb-stats-item"><span>Cevap:</span> <span class="mybb-stats-num">{t_replies}</span></div>
            <div class="mybb-stats-item"><span>Görüntüleme:</span> <span class="mybb-stats-num">{t_views}</span></div>
        </div>
    </div>

    <!-- Column 3: Last Post -->
    <div class="mybb-col-lastpost">
        [t-has-lastpost]
        <div class="mybb-lastpost-card">
            <div class="mybb-lastpost-title"><a href="{t_last_url}">{t_last_topic_short}</a></div>
            <div class="mybb-lastpost-meta">{t_last_date}</div>
            <div class="mybb-lastpost-author" style="display:flex; align-items:center; gap:5px; margin-top:4px;">
                {t_last_avatar}
                <span>Yazan: {t_last_user}</span>
            </div>
        </div>
        [/t-has-lastpost]
    </div>
</div>
