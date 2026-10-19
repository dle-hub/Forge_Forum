<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <div class="result-card" style="display:flex;align-items:center;justify-content:space-between;gap:var(--sp-4);flex-wrap:wrap;margin-bottom:var(--sp-5)">
        <span style="font-weight:700;color:var(--text)"><i class="fa fa-history" aria-hidden="true" style="color:var(--accent)"></i> {history_title}: {topic_title}</span>
        <a href="{topic_link}" class="read-link"><i class="fa fa-arrow-left" aria-hidden="true"></i> {forum_ui_back_to_topic}</a>
    </div>

    <section class="forum-category-block">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-clock-o" aria-hidden="true"></i> {forum_ui_old_versions} (<span id="forum-history-count">{history_count}</span>)</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="forum-rows">
            {history_rows}
        </div>
    </section>
</div>
