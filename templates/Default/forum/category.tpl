<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- Secondary Nav Action Strip -->
    <div class="mybb-actions-strip">
        <div class="mybb-strip-left">
            <i class="fa fa-folder-open" style="color:#3b82f6;"></i> Kategori: {cat_name}
            <span class="mybb-strip-sep">|</span>
            [is-logged]
            <a href="{http_home_url}index.php?do=forum&action=notifications" class="mybb-notif-nav-link">
                <i class="fa fa-bell"></i> Bildirimler
                [has-forum-notifs]<span class="notif-nav-badge">{forum_notif_count}</span>[/has-forum-notifs]
            </a>
            [/is-logged]
        </div>
        <div class="mybb-strip-right">
            <a href="{http_home_url}forum/new-topic/{cat_alt}/" class="mybb-btn-action">
                <i class="fa fa-plus"></i> Yeni Konu Aç
            </a>
        </div>
    </div>

    <!-- MyBB-style Topic Listing Table -->
    <div class="mybb-category-block">
        <div class="mybb-cat-header">
            <span class="mybb-cat-title">{cat_name} — Tartışma Konuları</span>
            <span class="mybb-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="mybb-sub-header">
            <div class="mybb-col-forum">Konu Başlığı</div>
            <div class="mybb-col-stats">İstatistikler</div>
            <div class="mybb-col-lastpost">Son İleti</div>
        </div>
        <div class="mybb-cat-rows">
            {topic_rows}
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {pagination}
    </div>
</div>
