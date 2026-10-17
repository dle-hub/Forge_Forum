<div class="forum-wrapper">
    {forum_breadcrumb}

    <!-- Araç çubuğu -->
    <div class="forum-toolbar">
        <div class="forum-toolbar__links">
            <span class="forum-toolbar__title"><i class="fa fa-bar-chart" aria-hidden="true"></i> {stats_title}</span>
        </div>
        <div class="forum-toolbar__end">
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=online"><i class="fa fa-users" aria-hidden="true"></i><span>{forum_ui_online}</span></a>
        </div>
    </div>

    <!-- Genel bakış kutuları -->
    <div class="forum-statsgrid">
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-comments" aria-hidden="true"></i></div><div class="forum-statbox__num">{stat_topics}</div><div class="forum-statbox__label">Konu</div></div>
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-comment-o" aria-hidden="true"></i></div><div class="forum-statbox__num">{stat_posts}</div><div class="forum-statbox__label">Mesaj</div></div>
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-users" aria-hidden="true"></i></div><div class="forum-statbox__num">{stat_members}</div><div class="forum-statbox__label">{stat_members_label}</div></div>
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-calendar-plus-o" aria-hidden="true"></i></div><div class="forum-statbox__num">{stat_topics_today}</div><div class="forum-statbox__label">{forum_stats_today_topics}</div></div>
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-calendar-check-o" aria-hidden="true"></i></div><div class="forum-statbox__num">{stat_posts_today}</div><div class="forum-statbox__label">{forum_stats_today_posts}</div></div>
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-line-chart" aria-hidden="true"></i></div><div class="forum-statbox__num">{stat_per_day}</div><div class="forum-statbox__label">{forum_stats_per_day}</div></div>
    </div>

    <p class="result-card__text" style="margin-bottom:var(--sp-5)">{forum_stats_newest_prefix} {stat_newest}</p>

    <!-- Kategoriler -->
    <section class="forum-category-block">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-folder-open" aria-hidden="true"></i> {stats_cat_title}</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="forum-statstable__wrap">{stats_categories}</div>
    </section>

    <!-- En aktif üyeler -->
    <section class="forum-category-block">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-trophy" aria-hidden="true"></i> {stats_posters_title}</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="forum-statstable__wrap">{stats_top_posters}</div>
    </section>

    <!-- En çok görüntülenen -->
    <section class="forum-category-block">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-eye" aria-hidden="true"></i> {stats_viewed_title}</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="forum-statstable__wrap">{stats_top_viewed}</div>
    </section>

    <!-- En çok cevaplanan -->
    <section class="forum-category-block">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-comments-o" aria-hidden="true"></i> {stats_replied_title}</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="forum-statstable__wrap">{stats_top_replied}</div>
    </section>
</div>
