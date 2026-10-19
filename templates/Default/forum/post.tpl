<div id="post-{post_id}" class="forum-post-container forum-post-gap">
    <!-- Top Bar: Subject & Post Number -->
    <div class="forum-post-header forum-post-header-layout">
        <div class="forum-post-subject">
            {p_name} — Mesajı [is-best-answer]<span class="badge badge--best"><i class="fa fa-check"></i> {forum_ui_best_answer}</span>[/is-best-answer]
        </div>
        <div class="forum-post-num">
            {forum_ui_message_label} #{p_num}
            [p-edited]<span class="forum-post-edited" title="{p_edited}"><i class="fa fa-pencil"></i> {p_edited}</span>[/p-edited]
            [can-history]<a href="{p_history_url}" class="forum-history-link" title="{forum_ui_edit_history}"><i class="fa fa-history"></i></a>[/can-history]
            [group=1,2]<input type="checkbox" class="forum-post-select" data-postid="{post_id}" title="{forum_ui_select_bulk}">[/group]
        </div>
    </div>
    
    <!-- Middle Area: Author info + Message Content -->
    <div class="forum-post-main">
        <!-- Left Sidebar: Author Info -->
        <div class="forum-post-author-sidebar">
            <!-- Square Avatar Container -->
            <div class="forum-avatar-box">
                {avatar}
            </div>
            
            <!-- Username with online indicator -->
            <div class="forum-author-name">
                {p_name} <span class="forum-online-dot" title="{forum_ui_online}"></span>
            </div>
            
            <!-- Owner Badge -->
            [owner]
            <div class="forum-owner-badge">
                {forum_ui_topic_owner}
            </div>
            [/owner]
            
            <!-- Grup Rozeti -->
            <div style="display:block; width:100%; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; letter-spacing:0.05em; padding:4px 6px; border-radius:4px; margin-top:8px; box-shadow:0 1px 2px rgba(0,0,0,.05); {p_group_style}">{p_group_name}</div>

            <!-- Rütbe Rozeti (ikon + resim + başlık) -->
            [p-has-rank]
            {p_rank_html}
            [/p-has-rank]

            <!-- Yıldız Satırı -->
            <div class="forum-author-stars" data-stars="{p_star_count}" data-color="{p_star_color}"></div>

            <!-- Author stats -->
            <div class="forum-author-stats">
                <div class="forum-author-stat-row">
                    <span>{forum_ui_message_label}</span>
                    <span class="forum-author-stat-val">{p_posts}</span>
                </div>
                <div class="forum-author-stat-row">
                    <span>{forum_ui_points_label}</span>
                    <span class="forum-author-stat-val">{p_pts}</span>
                </div>
            </div>
        </div>
        
        <!-- Right Content Area -->
        <div class="forum-post-content-area">
            <!-- Message text -->
            <div class="forum-post-body forum-post-content">
                {p_text}
            </div>
            
            <!-- IP Adresi — sadece admin/mod görür -->
            [group=1,2]
            <div class="forum-post-ip">
                <i class="fa fa-globe"></i> IP: {p_ip}
            </div>
            [/group]
        </div>
    </div>
    
    <!-- Bottom Footer Bar: Date & Quick Actions -->
    <div class="forum-post-footer forum-post-footer-layout">
        <div class="forum-post-date-area">
            <span class="forum-post-date-text"><i class="fa fa-clock-o"></i> {p_date}</span>
            <div class="forum-post-quick-links">
                    [has-email]
                    <a href="{p_email_href}" class="forum-btn-mini forum-quicklink-btn" title="{forum_ui_send_email}">
                        <i class="fa fa-envelope-o"></i> {forum_ui_btn_email}
                    </a>
                    [/has-email]
                    [can-pm]
                    <a href="{p_pm_href}" onclick="{p_pm_onclick}" class="forum-btn-mini forum-quicklink-btn" title="{forum_ui_send_pm}">
                        <i class="fa fa-comment-o"></i> {forum_ui_btn_pm}
                    </a>
                    [/can-pm]
                    [has-profile]
                    <a href="{p_find_href}" class="forum-btn-mini forum-quicklink-btn" title="{forum_ui_find_posts}">
                        <i class="fa fa-search"></i> {forum_ui_btn_find}
                    </a>
                    [/has-profile]
                </div>
        </div>
        <div class="forum-post-actions">
            <button onclick="forumLikePost({post_id},'like')" class="forum-btn-mini forum-like-btn">
                <i class="fa fa-thumbs-up"></i> {forum_ui_like} (<span id="like-count-{post_id}">{likes}</span>)
            </button>
            <button onclick="forumLikePost({post_id},'dislike')" class="forum-btn-mini forum-dislike-btn">
                <i class="fa fa-thumbs-down"></i> (<span id="dislike-count-{post_id}">{dislikes}</span>)
            </button>
            [edit]
            <a href="{http_home_url}index.php?do=forum&action=edit&post_id={post_id}" class="forum-btn-mini forum-edit-btn">
                <i class="fa fa-edit"></i> {forum_ui_btn_edit}
            </a>
            [/edit]
            [complaint]
            <button onclick="forumReportPost({post_id})" class="forum-btn-mini forum-report-btn">
                <i class="fa fa-exclamation-triangle"></i> {forum_ui_report}
            </button>
            [/complaint]
            [can-quote]
            <button type="button" id="mquote-btn-{post_id}" onclick="forumMultiquoteToggle({post_id})" class="forum-btn-mini forum-mquote-btn" title="{forum_ui_multiquote_toggle}">
                <i class="fa fa-plus-square-o"></i> {forum_ui_btn_multiquote}
            </button>
            [/can-quote]
            [can-best]
            <button type="button" onclick="forumMarkBest({topic_id},{post_id})" class="forum-btn-mini forum-best-btn" title="{forum_ui_mark_best}">
                <i class="fa fa-check-circle-o"></i> {forum_ui_btn_best}
            </button>
            [/can-best]
            [can-quote]
            <button id="quote-btn-{post_id}" onclick="forumQuotePost({post_id})" class="forum-btn-action">
                <i class="fa fa-quote-left"></i> {forum_ui_btn_quote}
            </button>
            [/can-quote]
        </div>
    </div>
</div>
