<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <div class="result-card" style="display:flex;align-items:center;justify-content:space-between;gap:var(--sp-4);flex-wrap:wrap;margin-bottom:var(--sp-5)">
        <span style="font-weight:700;color:var(--text)"><i class="fa fa-users" aria-hidden="true" style="color:var(--accent)"></i> {online_title}</span>
        <span style="font-size:var(--fs-sm);font-weight:600;color:var(--forum-text-secondary)"><i class="fa fa-circle" aria-hidden="true" style="color:#22c55e"></i> {online_count} üye çevrimiçi</span>
    </div>

    <section class="forum-category-block">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-signal" aria-hidden="true"></i> {forum_ui_online_members}</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="forum-rows">
            <div class="forum-online">{online_rows}</div>
        </div>
    </section>
</div>
