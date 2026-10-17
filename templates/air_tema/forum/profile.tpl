<div class="forum-wrapper">
    {forum_breadcrumb}

    <div style="max-width:900px;margin:0 auto">

        <!-- Profil kartı -->
        <div class="result-card">
            <div class="result-card__top">
                <span class="avatar avatar--lg" style="width:72px;height:72px;overflow:hidden;flex:none">{avatar_html}</span>
                <div style="flex:1;min-width:200px">
                    <div class="thread__who">
                        <span class="thread__author" style="font-size:var(--fs-h4)">{user_name}</span>
                        {online_dot}
                        [has-forum-rank]{rank_html}[/has-forum-rank]
                        <span class="badge" style="border-color:{group_color}40;background:{group_color}14;color:{group_color}">{group_name}</span>
                    </div>
                    [has-fullname]<div class="meta-item" style="margin-top:var(--sp-1)">{user_fullname}</div>[/has-fullname]
                    [has-land]<div class="meta-item" style="margin-top:var(--sp-1)"><i class="fa fa-map-marker" aria-hidden="true"></i>{user_land}</div>[/has-land]
                </div>
            </div>

            <!-- Eylem butonları -->
            <div class="thread__actions">
                [has-pm]
                <a href="{pm_url}" onclick="DLESendPM('{user_name}'); return false;" class="soft-button">
                    <i class="fa fa-comment" aria-hidden="true"></i> Mesaj Gönder
                </a>
                [/has-pm]
                [has-email]
                <a href="{email_url}" class="soft-button">
                    <i class="fa fa-envelope-o" aria-hidden="true"></i> E-Posta
                </a>
                [/has-email]
                <a href="{http_home_url}index.php?do=forum&action=search&q={user_name}&in=posts" class="soft-button">
                    <i class="fa fa-search" aria-hidden="true"></i> Gönderileri Bul
                </a>
                [can-follow]
                <button type="button" id="forum-follow-btn" class="soft-button" data-active="{follow_active}" onclick="forumToggleFollow({user_id}); return false;">
                    <i class="fa fa-user-plus" aria-hidden="true"></i> <span id="forum-follow-label">{follow_label}</span>
                </button>
                [/can-follow]
            </div>

            <div style="display:flex;gap:var(--sp-4);margin-top:var(--sp-3);font-size:var(--fs-sm);font-weight:600">
                <span><i class="fa fa-users" aria-hidden="true" style="color:var(--accent)"></i> <b id="forum-followers">{followers}</b> Takipçi</span>
                <span><i class="fa fa-user-plus" aria-hidden="true" style="color:var(--accent)"></i> <b>{following}</b> Takip</span>
            </div>
        </div>

        <!-- İstatistik bandı -->
        <div class="stats-cards" style="grid-template-columns:repeat(4,minmax(0,1fr));margin-top:var(--sp-4)">
            <div class="stats-card">
                <span class="stats-card__label"><i class="fa fa-trophy" aria-hidden="true"></i> Puan</span>
                <span class="stats-card__value">{points}</span>
            </div>
            <div class="stats-card">
                <span class="stats-card__label"><i class="fa fa-folder-o" aria-hidden="true"></i> Konu</span>
                <span class="stats-card__value">{topic_count}</span>
            </div>
            <div class="stats-card">
                <span class="stats-card__label"><i class="fa fa-comments-o" aria-hidden="true"></i> Mesaj</span>
                <span class="stats-card__value">{post_count}</span>
            </div>
            <div class="stats-card">
                <span class="stats-card__label"><i class="fa fa-calendar" aria-hidden="true"></i> Kayıt</span>
                <span class="stats-card__value" style="font-size:var(--fs-sm)">{reg_date}</span>
            </div>
        </div>

        <!-- Son görülme -->
        <div class="result-card" style="margin-top:var(--sp-4);display:flex;align-items:center;gap:var(--sp-2)">
            <i class="fa fa-clock-o" aria-hidden="true" style="color:var(--muted)"></i>
            <span class="meta-item">Son görülme: <strong>{last_date}</strong></span>
        </div>

        <!-- Son konular / son mesajlar -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4);margin-top:var(--sp-4)">
            <div class="result-card">
                <div class="result-card__top">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    <span class="result-card__title" style="margin:0;font-size:var(--fs-ui)">Son Açılan Konular</span>
                </div>
                <div style="margin-top:var(--sp-3)">{recent_topics}</div>
            </div>
            <div class="result-card">
                <div class="result-card__top">
                    <i class="fa fa-comments-o" aria-hidden="true"></i>
                    <span class="result-card__title" style="margin:0;font-size:var(--fs-ui)">Son Mesajlar</span>
                </div>
                <div style="margin-top:var(--sp-3)">{recent_posts}</div>
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
        } else if (r && r.message) {
            alert(r.message);
        }
    }, 'json').fail(function () { alert('Bağlantı hatası.'); });
}
</script>
