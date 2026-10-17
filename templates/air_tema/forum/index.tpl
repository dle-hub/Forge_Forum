<div class="forum-wrapper">
    {forum_breadcrumb}

    <!-- Araç çubuğu -->
    <div class="forum-toolbar">
        <div class="forum-toolbar__links">
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=search&mode=new"><i class="fa fa-list-alt" aria-hidden="true"></i><span>{forum_ui_new_posts}</span></a>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=search&mode=today"><i class="fa fa-calendar-o" aria-hidden="true"></i><span>{forum_ui_today_posts}</span></a>
            [has-online]
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=online"><i class="fa fa-users" aria-hidden="true"></i><span>{forum_ui_online}</span></a>
            [/has-online]
            [has-stats]
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=statistics"><i class="fa fa-bar-chart" aria-hidden="true"></i><span>{forum_ui_stats_nav}</span></a>
            [/has-stats]
            [has-rss]
            <a class="forum-toolbar__btn" href="{http_home_url}forum/rss/" title="{forum_rss_title}"><i class="fa fa-rss" aria-hidden="true"></i><span>RSS</span></a>
            [/has-rss]
            [is-logged]
            <span class="forum-toolbar__sep" aria-hidden="true"></span>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=notifications"><i class="fa fa-bell" aria-hidden="true"></i><span>{forum_ui_notifications}</span>[has-forum-notifs]<span class="forum-toolbar__count">{forum_notif_count}</span>[/has-forum-notifs]</a>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=subscriptions"><i class="fa fa-bookmark" aria-hidden="true"></i><span>{forum_ui_subscriptions}</span></a>
            [group=1,2]
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=mod"><i class="fa fa-shield" aria-hidden="true"></i><span>{forum_ui_moderation}</span></a>
            [/group]
            <a class="forum-toolbar__btn" href="#" onclick="forumMarkReadAll(); return false;"><i class="fa fa-check-circle-o" aria-hidden="true"></i><span>{forum_ui_mark_all_read}</span></a>
            [/is-logged]
        </div>
        <form method="get" action="{http_home_url}index.php" class="forum-toolbar__search">
            <input type="hidden" name="do" value="forum">
            <input type="hidden" name="action" value="search">
            <input type="text" name="q" placeholder="{forum_ui_search_placeholder}" required minlength="3">
            <button type="submit" title="{forum_ui_search}"><i class="fa fa-search" aria-hidden="true"></i></button>
        </form>
    </div>

    <!-- Kategori blokları -->
    <div class="forum-categories">
        {categories}
    </div>

    <!-- Forum İstatistikleri -->
    <section class="forum-category-block forum-stats-block">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-bar-chart" aria-hidden="true"></i> {forum_ui_forum_stats}</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="forum-rows">
            <div class="forum-stats-panels">
                <div class="forum-panel">
                    <div class="forum-panel__title forum-panel__title--ico">
                        <span class="forum-online-dot" aria-hidden="true"></span>
                        {forum_ui_online}
                        [has-online]<a class="forum-panel__title-link" href="{http_home_url}index.php?do=forum&action=online">{forum_ui_full_list} <i class="fa fa-arrow-right" aria-hidden="true"></i></a>[/has-online]
                    </div>
                    <p class="forum-stats-meta">{online_summary}</p>
                    <div class="forum-online-chipwrap">{online_list}</div>
                </div>
                <div class="forum-panel">
                    <div class="forum-panel__title forum-panel__title--ico">
                        <i class="fa fa-pie-chart" aria-hidden="true"></i>
                        {forum_ui_forum_stats}
                    </div>
                    <div class="forum-mini-stats">
                        <div class="forum-mini-stat"><span class="forum-mini-stat__num">{total_threads}</span><span class="forum-mini-stat__label">{forum_ui_topic_label}</span></div>
                        <div class="forum-mini-stat"><span class="forum-mini-stat__num">{total_posts}</span><span class="forum-mini-stat__label">{forum_ui_posts_label}</span></div>
                        <div class="forum-mini-stat"><span class="forum-mini-stat__num">{total_members}</span><span class="forum-mini-stat__label">{forum_ui_members_label}</span></div>
                    </div>
                    <div class="forum-newest">
                        <span class="forum-newest__label"><i class="fa fa-user-plus" aria-hidden="true"></i> {forum_ui_newest_member}</span>
                        <span class="forum-newest__user">{newest_member}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function forumMarkReadAll() {
    if (typeof jQuery === 'undefined') return;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_read', {
        cat_id: 0, user_hash: dle_login_hash
    }, function (r) {
        if (r && r.success) { window.location.reload(); }
    }, 'json');
}
</script>
