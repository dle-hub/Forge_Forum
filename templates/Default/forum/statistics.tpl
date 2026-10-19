<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- Araç çubuğu -->
    <div class="forum-toolbar">
        <div class="forum-toolbar__links">
            <span class="forum-toolbar__title"><i class="fa fa-bar-chart"></i> {stats_title}</span>
        </div>
        <div class="forum-toolbar__end">
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=online"><i class="fa fa-users"></i><span>{forum_ui_online}</span></a>
        </div>
    </div>

    <!-- Genel bakış kutuları -->
    <div class="forum-statsgrid">
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-comments"></i></div><div class="forum-statbox__num">{stat_topics}</div><div class="forum-statbox__label">{forum_ui_topics}</div></div>
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-comment-o"></i></div><div class="forum-statbox__num">{stat_posts}</div><div class="forum-statbox__label">{forum_ui_posts}</div></div>
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-users"></i></div><div class="forum-statbox__num">{stat_members}</div><div class="forum-statbox__label">{stat_members_label}</div></div>
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-calendar-plus-o"></i></div><div class="forum-statbox__num">{stat_topics_today}</div><div class="forum-statbox__label">{forum_stats_today_topics}</div></div>
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-calendar-check-o"></i></div><div class="forum-statbox__num">{stat_posts_today}</div><div class="forum-statbox__label">{forum_stats_today_posts}</div></div>
        <div class="forum-statbox"><div class="forum-statbox__icon"><i class="fa fa-line-chart"></i></div><div class="forum-statbox__num">{stat_per_day}</div><div class="forum-statbox__label">{forum_stats_per_day}</div></div>
    </div>

    <p style="margin:0 0 1.25rem;color:#4b5563;font-size:0.85rem;">{forum_stats_newest_prefix} {stat_newest}</p>

    <!-- Kategoriler -->
    <div class="forum-category-block">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-folder-open"></i> {stats_cat_title}</span>
            <span class="forum-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="forum-statstable__wrap">{stats_categories}</div>
    </div>

    <!-- En aktif üyeler -->
    <div class="forum-category-block">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-trophy"></i> {stats_posters_title}</span>
            <span class="forum-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="forum-statstable__wrap">{stats_top_posters}</div>
    </div>

    <!-- En çok görüntülenen -->
    <div class="forum-category-block">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-eye"></i> {stats_viewed_title}</span>
            <span class="forum-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="forum-statstable__wrap">{stats_top_viewed}</div>
    </div>

    <!-- En çok cevaplanan -->
    <div class="forum-category-block">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-comments-o"></i> {stats_replied_title}</span>
            <span class="forum-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="forum-statstable__wrap">{stats_top_replied}</div>
    </div>
</div>
