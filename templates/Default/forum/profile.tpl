<div class="forum-wrapper">
    {forum_breadcrumb}

    <!-- ====================================================
         FORUM PROFİL SAYFASI — Premium Tasarım
         ==================================================== -->
    <div style="max-width:900px; margin:0 auto;">

        <!-- KAPAK BANNER + AVATAR KARTI -->
        <div style="background:var(--forum-bg-card); border:1px solid var(--forum-border-color); border-radius:var(--forum-radius-lg); overflow:hidden; margin-bottom:1.25rem; box-shadow:0 2px 8px rgba(0,0,0,0.06);">

            <!-- Kapak Gradient -->
            <div style="height:110px; background:linear-gradient(135deg, var(--forum-bg-header) 0%, #1e3a5f 40%, #2d4a7a 70%, #3b5fa0 100%); position:relative;">
                <!-- Dekoratif desen -->
                <div style="position:absolute;inset:0;background:url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22><circle cx=%2230%22 cy=%2230%22 r=%221.5%22 fill=%22rgba(255,255,255,0.07)%22/></svg>') repeat;"></div>
            </div>

            <!-- Avatar + Kullanıcı Bilgisi -->
            <div style="padding:0 1.5rem 1.5rem; display:flex; align-items:flex-end; gap:1.25rem; margin-top:-52px; flex-wrap:wrap;">

                <!-- Avatar Yuvarlak Çerçeve -->
                <div style="width:100px; height:100px; border-radius:50%; border:4px solid var(--forum-bg-card); background:#e0eaf8; box-shadow:0 2px 8px rgba(0,0,0,0.15); overflow:hidden; flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                    {avatar_html}
                </div>

                <!-- İsim + Rozetler -->
                <div style="flex:1; min-width:200px; padding-top:54px;">
                    <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
                        <h1 style="font-size:1.3rem; font-weight:900; color:var(--forum-text-primary); margin:0; line-height:1.2;">
                            {user_name}
                        </h1>
                        {online_dot}
                        [has-forum-rank]
                        {rank_html}
                        [/has-forum-rank]
                        <!-- Grup Rozeti -->
                        <span style="display:inline-flex; align-items:center; gap:4px; font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:0.06em; padding:2px 8px; border-radius:3px; border:1px solid {group_color}40; background:{group_color}14; color:{group_color};">
                            {group_name}
                        </span>
                    </div>
                    [has-fullname]
                    <div style="font-size:12px; color:var(--forum-text-muted); margin-top:4px;">{user_fullname}</div>
                    [/has-fullname]
                    [has-land]
                    <div style="font-size:11px; color:var(--forum-text-muted); margin-top:3px;"><i class="fa fa-map-marker" style="color:#9ca3af; margin-right:3px;"></i>{user_land}</div>
                    [/has-land]
                </div>

                <!-- Eylem Butonları -->
                <div style="padding-top:54px; display:flex; gap:6px; flex-wrap:wrap; align-self:flex-end;">
                    [has-pm]
                    <a href="{pm_url}" onclick="DLESendPM('{user_name}'); return false;"
                       style="display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:bold; padding:6px 12px; border-radius:3px; border:1px solid var(--forum-border-color); background:linear-gradient(to bottom,#fff,#f3f4f6); color:var(--forum-text-primary); text-decoration:none; cursor:pointer; transition:all 0.15s;"
                       onmouseover="this.style.borderColor='#3b82f6'; this.style.color='#2563eb';"
                       onmouseout="this.style.borderColor='var(--forum-border-color)'; this.style.color='var(--forum-text-primary)';">
                        <i class="fa fa-comment" style="color:#6366f1;"></i> Mesaj Gönder
                    </a>
                    [/has-pm]
                    [has-email]
                    <a href="{email_url}"
                       style="display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:bold; padding:6px 12px; border-radius:3px; border:1px solid var(--forum-border-color); background:linear-gradient(to bottom,#fff,#f3f4f6); color:var(--forum-text-primary); text-decoration:none; transition:all 0.15s;"
                       onmouseover="this.style.borderColor='#3b82f6'; this.style.color='#2563eb';"
                       onmouseout="this.style.borderColor='var(--forum-border-color)'; this.style.color='var(--forum-text-primary)';">
                        <i class="fa fa-envelope-o" style="color:#0891b2;"></i> E-Posta
                    </a>
                    [/has-email]
                    <a href="{http_home_url}index.php?do=forum&action=search&q={user_name}&in=posts"
                       style="display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:bold; padding:6px 12px; border-radius:3px; border:1px solid var(--forum-border-color); background:linear-gradient(to bottom,#fff,#f3f4f6); color:var(--forum-text-primary); text-decoration:none; transition:all 0.15s;"
                       onmouseover="this.style.borderColor='#3b82f6'; this.style.color='#2563eb';"
                       onmouseout="this.style.borderColor='var(--forum-border-color)'; this.style.color='var(--forum-text-primary)';">
                        <i class="fa fa-search" style="color:#059669;"></i> Gönderileri Bul
                    </a>
                    [can-follow]
                    <button type="button" id="forum-follow-btn" data-active="{follow_active}" onclick="forumToggleFollow({user_id}); return false;"
                       style="display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:bold; padding:6px 12px; border-radius:3px; border:1px solid var(--forum-border-color); background:linear-gradient(to bottom,#fff,#f3f4f6); color:var(--forum-text-primary); cursor:pointer;">
                        <i class="fa fa-user-plus" style="color:#7c3aed;"></i> <span id="forum-follow-label">{follow_label}</span>
                    </button>
                    [/can-follow]
                </div>
                <div style="padding-top:8px; display:flex; gap:14px; font-size:11px; font-weight:600;">
                    <span><i class="fa fa-users" style="color:#2563eb"></i> <b id="forum-followers">{followers}</b> Takipçi</span>
                    <span><i class="fa fa-user-plus" style="color:#2563eb"></i> <b>{following}</b> Takip</span>
                </div>
            </div>
        </div>

        <!-- İSTATİSTİK BANDI -->
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:1.25rem;">

            <div style="background:var(--forum-bg-card); border:1px solid var(--forum-border-color); border-radius:var(--forum-radius-md); padding:0.9rem 1rem; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,0.04); border-top:3px solid #f59e0b;">
                <div style="font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:0.07em; color:var(--forum-text-muted); margin-bottom:6px;"><i class="fa fa-trophy" style="color:#f59e0b; margin-right:3px;"></i>Puan</div>
                <div style="font-size:1.6rem; font-weight:900; color:#d97706; line-height:1;">{points}</div>
            </div>

            <div style="background:var(--forum-bg-card); border:1px solid var(--forum-border-color); border-radius:var(--forum-radius-md); padding:0.9rem 1rem; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,0.04); border-top:3px solid #3b82f6;">
                <div style="font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:0.07em; color:var(--forum-text-muted); margin-bottom:6px;"><i class="fa fa-folder-o" style="color:#3b82f6; margin-right:3px;"></i>Konu</div>
                <div style="font-size:1.6rem; font-weight:900; color:#2563eb; line-height:1;">{topic_count}</div>
            </div>

            <div style="background:var(--forum-bg-card); border:1px solid var(--forum-border-color); border-radius:var(--forum-radius-md); padding:0.9rem 1rem; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,0.04); border-top:3px solid #6366f1;">
                <div style="font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:0.07em; color:var(--forum-text-muted); margin-bottom:6px;"><i class="fa fa-comments-o" style="color:#6366f1; margin-right:3px;"></i>Mesaj</div>
                <div style="font-size:1.6rem; font-weight:900; color:#4f46e5; line-height:1;">{post_count}</div>
            </div>

            <div style="background:var(--forum-bg-card); border:1px solid var(--forum-border-color); border-radius:var(--forum-radius-md); padding:0.9rem 1rem; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,0.04); border-top:3px solid #10b981;">
                <div style="font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:0.07em; color:var(--forum-text-muted); margin-bottom:6px;"><i class="fa fa-calendar" style="color:#10b981; margin-right:3px;"></i>Kayıt</div>
                <div style="font-size:11px; font-weight:700; color:#059669; line-height:1.3; padding-top:4px;">{reg_date}</div>
            </div>
        </div>

        <!-- SON AKTİVİTE BANDI -->
        <div style="background:var(--forum-bg-subheader); border:1px solid var(--forum-border-color); border-radius:var(--forum-radius-md); padding:0.6rem 1.2rem; margin-bottom:1.25rem; font-size:11px; color:var(--forum-text-secondary); display:flex; align-items:center; gap:8px;">
            <i class="fa fa-clock-o" style="color:var(--forum-text-muted);"></i>
            Son görülme: <strong style="color:var(--forum-text-primary);">{last_date}</strong>
        </div>

        <!-- İKİ KOLON GRID: SON KONULAR / SON MESAJLAR -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">

            <!-- Son Konular -->
            <div style="background:var(--forum-bg-card); border:1px solid var(--forum-border-color); border-radius:var(--forum-radius-lg); overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                <div style="background:var(--forum-bg-subheader); border-bottom:1px solid var(--forum-border-color); padding:0.65rem 1rem; display:flex; align-items:center; gap:7px;">
                    <i class="fa fa-file-text-o" style="color:#3b82f6; font-size:13px;"></i>
                    <span style="font-size:11px; font-weight:bold; text-transform:uppercase; letter-spacing:0.05em; color:var(--forum-text-primary);">Son Açılan Konular</span>
                </div>
                <div style="padding:0.5rem 0;">
                    {recent_topics}
                </div>
            </div>

            <!-- Son Mesajlar -->
            <div style="background:var(--forum-bg-card); border:1px solid var(--forum-border-color); border-radius:var(--forum-radius-lg); overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                <div style="background:var(--forum-bg-subheader); border-bottom:1px solid var(--forum-border-color); padding:0.65rem 1rem; display:flex; align-items:center; gap:7px;">
                    <i class="fa fa-comments-o" style="color:#6366f1; font-size:13px;"></i>
                    <span style="font-size:11px; font-weight:bold; text-transform:uppercase; letter-spacing:0.05em; color:var(--forum-text-primary);">Son Mesajlar</span>
                </div>
                <div style="padding:0.5rem 0;">
                    {recent_posts}
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function forumToggleFollow(targetId) {
    if (typeof jQuery === 'undefined') return;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_follow', {
        target_id: targetId,
        user_hash: (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '{dle_login_hash}')
    }, function (r) {
        if (r && r.success) {
            var lbl = document.getElementById('forum-follow-label');
            if (lbl) lbl.textContent = r.label;
            var fc = document.getElementById('forum-followers');
            if (fc) fc.textContent = r.followers;
            var btn = document.getElementById('forum-follow-btn');
            if (btn) btn.setAttribute('data-active', r.following ? '1' : '0');
        } else if (r && r.message) { alert(r.message); }
    }, 'json').fail(function () { alert('Bağlantı hatası.'); });
}
</script>
