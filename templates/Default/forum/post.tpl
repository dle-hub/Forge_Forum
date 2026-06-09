<div id="post-{post_id}" class="mybb-post-container mybb-post-gap">
    <!-- Top Bar: Subject & Post Number -->
    <div class="mybb-post-header mybb-post-header-layout">
        <div class="mybb-post-subject">
            {p_name} — Mesajı
        </div>
        <div class="mybb-post-num">
            Mesaj: #{p_num}
            [group=1,2]<input type="checkbox" class="mybb-post-select" data-postid="{post_id}" title="Toplu işlem için seçin">[/group]
        </div>
    </div>
    
    <!-- Middle Area: Author info + Message Content -->
    <div class="mybb-post-main">
        <!-- Left Sidebar: Author Info -->
        <div class="mybb-post-author-sidebar">
            <!-- Square Avatar Container -->
            <div class="mybb-avatar-box">
                {avatar}
            </div>
            
            <!-- Username with online indicator -->
            <div class="mybb-author-name">
                {p_name} <span class="mybb-online-dot" title="Çevrimiçi"></span>
            </div>
            
            <!-- Owner Badge -->
            [owner]
            <div class="mybb-owner-badge">
                Konu Sahibi
            </div>
            [/owner]
            
            <!-- Grup Rozeti -->
            <div style="display:block; width:100%; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; letter-spacing:0.05em; padding:4px 6px; border-radius:4px; margin-top:8px; box-shadow:0 1px 2px rgba(0,0,0,.05); {p_group_style}">{p_group_name}</div>

            <!-- Rütbe Rozeti (ikon + resim + başlık) -->
            [p-has-rank]
            {p_rank_html}
            [/p-has-rank]

            <!-- Yıldız Satırı -->
            <div class="mybb-author-stars" data-stars="{p_star_count}" data-color="{p_star_color}"></div>

            <!-- Author stats -->
            <div class="mybb-author-stats">
                <div class="mybb-author-stat-row">
                    <span>Mesaj:</span>
                    <span class="mybb-author-stat-val">{p_posts}</span>
                </div>
                <div class="mybb-author-stat-row">
                    <span>Puan:</span>
                    <span class="mybb-author-stat-val">{p_pts}</span>
                </div>
            </div>
        </div>
        
        <!-- Right Content Area -->
        <div class="mybb-post-content-area">
            <!-- Message text -->
            <div class="mybb-post-body forum-post-content">
                {p_text}
            </div>
            
            <!-- IP Adresi — sadece admin/mod görür -->
            [group=1,2]
            <div class="mybb-post-ip">
                <i class="fa fa-globe"></i> IP: {p_ip}
            </div>
            [/group]
        </div>
    </div>
    
    <!-- Bottom Footer Bar: Date & Quick Actions -->
    <div class="mybb-post-footer mybb-post-footer-layout">
        <div class="mybb-post-date-area">
            <span class="mybb-post-date-text"><i class="fa fa-clock-o"></i> {p_date}</span>
            <div class="mybb-post-quick-links">
                    [has-email]
                    <a href="{p_email_href}" class="mybb-btn-mini mybb-quicklink-btn" title="E-Posta Gönder">
                        <i class="fa fa-envelope-o"></i> E-POSTA
                    </a>
                    [/has-email]
                    [can-pm]
                    <a href="{p_pm_href}" onclick="{p_pm_onclick}" class="mybb-btn-mini mybb-quicklink-btn" title="Özel Mesaj Gönder">
                        <i class="fa fa-comment-o"></i> ÖZEL MESAJ
                    </a>
                    [/can-pm]
                    [has-profile]
                    <a href="{p_find_href}" class="mybb-btn-mini mybb-quicklink-btn" title="Bu üyenin forumda yazdıklarını bul">
                        <i class="fa fa-search"></i> BUL
                    </a>
                    [/has-profile]
                </div>
        </div>
        <div class="mybb-post-actions">
            <button onclick="forumLikePost({post_id},'like')" class="mybb-btn-mini mybb-like-btn">
                <i class="fa fa-thumbs-up"></i> Beğen (<span id="like-count-{post_id}">{likes}</span>)
            </button>
            <button onclick="forumLikePost({post_id},'dislike')" class="mybb-btn-mini mybb-dislike-btn">
                <i class="fa fa-thumbs-down"></i> (<span id="dislike-count-{post_id}">{dislikes}</span>)
            </button>
            [edit]
            <a href="{http_home_url}index.php?do=forum&action=edit&post_id={post_id}" class="mybb-btn-mini mybb-edit-btn">
                <i class="fa fa-edit"></i> DÜZENLE
            </a>
            [/edit]
            [complaint]
            <button onclick="forumReportPost({post_id})" class="mybb-btn-mini mybb-report-btn">
                <i class="fa fa-exclamation-triangle"></i> Şikayet Et
            </button>
            [/complaint]
            <button id="quote-btn-{post_id}" onclick="forumQuotePost({post_id})" class="mybb-btn-action">
                ALINTILA
            </button>
        </div>
    </div>
</div>
