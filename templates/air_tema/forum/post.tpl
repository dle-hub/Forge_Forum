<div id="post-{post_id}" class="forum-post-container forum-post-gap">
    <!-- Üst bar: konu + mesaj no -->
    <div class="forum-post-header forum-post-header-layout">
        <div class="forum-post-subject">{p_name} — Mesajı [is-best-answer]<span class="badge badge--best"><i class="fa fa-check" aria-hidden="true"></i> EN İYİ CEVAP</span>[/is-best-answer]</div>
        <div class="forum-post-num">
            Mesaj: #{p_num}
            [p-edited]<span class="forum-post-edited" title="{p_edited}"><i class="fa fa-pencil" aria-hidden="true"></i> {p_edited}</span>[/p-edited]
            [can-history]<a href="{p_history_url}" class="forum-history-link" title="Düzenleme geçmişi"><i class="fa fa-history" aria-hidden="true"></i></a>[/can-history]
            [group=1,2]<input type="checkbox" class="forum-post-select" data-postid="{post_id}" title="Toplu işlem için seçin">[/group]
        </div>
    </div>

    <!-- Orta alan: yazar (sol) + mesaj (sağ) -->
    <div class="forum-post-main">
        <!-- Sol: yazar kutusu -->
        <div class="forum-post-author-sidebar">
            <div class="forum-avatar-box">
                {avatar}
            </div>

            <div class="forum-author-name">{p_name}</div>

            [owner]
            <div class="forum-owner-badge">Konu Sahibi</div>
            [/owner]

            <div style="display:block; width:100%; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; letter-spacing:0.05em; padding:4px 6px; border-radius:4px; margin-top:8px; box-shadow:0 1px 2px rgba(0,0,0,.05); {p_group_style}">{p_group_name}</div>

            [p-has-rank]
            {p_rank_html}
            [/p-has-rank]

            <div class="forum-author-stars" data-stars="{p_star_count}" data-color="{p_star_color}"></div>

            <div class="forum-author-stats">
                <div class="forum-author-stat-row">
                    <span>Mesaj:</span>
                    <span class="forum-author-stat-val">{p_posts}</span>
                </div>
                <div class="forum-author-stat-row">
                    <span>Puan:</span>
                    <span class="forum-author-stat-val">{p_pts}</span>
                </div>
            </div>
        </div>

        <!-- Sağ: mesaj içeriği -->
        <div class="forum-post-content-area">
            <div class="forum-post-body forum-post-content">
                {p_text}
            </div>

            [group=1,2]
            <div class="forum-post-ip">
                <i class="fa fa-globe"></i> IP: {p_ip}
            </div>
            [/group]

            [p-has-signature]
            <div class="forum-post-signature">{p_signature}</div>
            [/p-has-signature]
        </div>
    </div>

    <!-- Alt bar: tarih + hızlı linkler / aksiyonlar -->
    <div class="forum-post-footer forum-post-footer-layout">
        <div class="forum-post-date-area">
            <span class="forum-post-date-text"><i class="fa fa-clock-o"></i> {p_date}</span>
            <div class="forum-post-quick-links">
                [has-email]
                <a href="{p_email_href}" class="forum-btn-mini forum-quicklink-btn" title="E-Posta Gönder">
                    <i class="fa fa-envelope-o"></i> E-POSTA
                </a>
                [/has-email]
                [can-pm]
                <a href="{p_pm_href}" onclick="{p_pm_onclick}" class="forum-btn-mini forum-quicklink-btn" title="Özel Mesaj Gönder">
                    <i class="fa fa-comment-o"></i> ÖZEL MESAJ
                </a>
                [/can-pm]
                [has-profile]
                <a href="{p_find_href}" class="forum-btn-mini forum-quicklink-btn" title="Bu üyenin forumda yazdıklarını bul">
                    <i class="fa fa-search"></i> BUL
                </a>
                [/has-profile]
            </div>
        </div>
        <div class="forum-post-actions">
            <button onclick="forumLikePost({post_id},'like')" class="forum-btn-mini forum-like-btn" title="Beğen">
                <i class="fa fa-thumbs-up"></i> Beğen (<span id="like-count-{post_id}">{likes}</span>)
            </button>
            <button onclick="forumLikePost({post_id},'dislike')" class="forum-btn-mini forum-dislike-btn" title="Beğenme">
                <i class="fa fa-thumbs-down"></i> (<span id="dislike-count-{post_id}">{dislikes}</span>)
            </button>
            [edit]
            <a href="{http_home_url}index.php?do=forum&action=edit&post_id={post_id}" class="forum-btn-mini forum-edit-btn" title="Düzenle">
                <i class="fa fa-edit"></i> DÜZENLE
            </a>
            [/edit]
            [complaint]
            <button onclick="forumReportPost({post_id})" class="forum-btn-mini forum-report-btn" title="Şikayet Et">
                <i class="fa fa-exclamation-triangle"></i> ŞİKAYET
            </button>
            [/complaint]
            [can-quote]
            <button type="button" id="mquote-btn-{post_id}" onclick="forumMultiquoteToggle({post_id})" class="forum-btn-mini forum-mquote-btn" title="Çoklu alıntıya ekle / çıkar">
                <i class="fa fa-plus-square-o"></i> ALINTI+
            </button>
            [/can-quote]
            [can-best]
            <button type="button" onclick="forumMarkBest({topic_id},{post_id})" class="forum-btn-mini forum-best-btn" title="En iyi cevap olarak işaretle">
                <i class="fa fa-check-circle-o"></i> EN İYİ
            </button>
            [/can-best]
            [can-quote]
            <button id="quote-btn-{post_id}" onclick="forumQuotePost({post_id})" class="forum-btn-action">
                <i class="fa fa-quote-left"></i> ALINTILA
            </button>
            [/can-quote]
        </div>
    </div>
</div>
