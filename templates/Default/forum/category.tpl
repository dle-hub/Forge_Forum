<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- Araç çubuğu -->
    <div class="forum-toolbar">
        <div class="forum-toolbar__links">
            <span class="forum-toolbar__title"><i class="fa fa-folder-open"></i> {cat_name}</span>
            [cat-has-unread]<span class="forum-toolbar__count" title="{forum_ui_unread_topics}">{cat_unread_count}</span>[/cat-has-unread]
            [is-logged]
            <span class="forum-toolbar__sep" aria-hidden="true"></span>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=notifications"><i class="fa fa-bell"></i><span>{forum_ui_notifications}</span>[has-forum-notifs]<span class="forum-toolbar__count">{forum_notif_count}</span>[/has-forum-notifs]</a>
            <a class="forum-toolbar__btn" href="#" onclick="forumMarkRead({cat_id}); return false;"><i class="fa fa-check-circle-o"></i><span>{forum_ui_mark_read}</span></a>
            [cat-sub-enabled]
            <a class="forum-toolbar__btn" href="#" id="forum-cat-subscribe-btn" onclick="forumToggleCatSub({cat_id}); return false;">
                [cat-subscribed]<i class="fa fa-bell"></i><span>{forum_ui_cat_unsubscribe}</span>[/cat-subscribed]
                [cat-not-subscribed]<i class="fa fa-bell-o"></i><span>{forum_ui_cat_subscribe}</span>[/cat-not-subscribed]
            </a>
            [/cat-sub-enabled]
            [/is-logged]
        </div>
        <div class="forum-toolbar__end">
            <a href="{http_home_url}forum/new-topic/{cat_alt}/" class="forum-btn-action">
                <i class="fa fa-plus"></i> {forum_ui_new_topic}
            </a>
        </div>
    </div>

    <!-- Forum-style Topic Listing Table -->
    <div class="forum-category-block">
        <div class="forum-cat-header">
            <span class="forum-cat-title">{cat_name} — {forum_ui_category_topics}</span>
            <span class="forum-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="forum-sub-header">
            <div class="forum-col-forum">Konu Başlığı</div>
            <div class="forum-col-stats">İstatistikler</div>
            <div class="forum-col-lastpost">Son İleti</div>
        </div>
        <div class="forum-cat-rows">
            {topic_rows}
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {pagination}
    </div>
</div>

<script>
function forumMarkRead(catId) {
    if (typeof jQuery === 'undefined') return;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_read', {
        cat_id: catId, user_hash: (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '{dle_login_hash}')
    }, function (r) {
        if (r && r.success) { window.location.reload(); }
    }, 'json');
}

function forumToggleCatSub(catId) {
    if (typeof jQuery === 'undefined') return;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_cat_subscribe', {
        cat_id: catId,
        user_hash: (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '{dle_login_hash}')
    }, function (r) {
        if (r && r.success) {
            var btn = document.getElementById('forum-cat-subscribe-btn');
            if (btn) { btn.innerHTML = r.subscribed ? '<i class="fa fa-bell" style="color:#2563eb"></i> {forum_ui_cat_unsubscribe}' : '<i class="fa fa-bell-o"></i> {forum_ui_cat_subscribe}'; }
            if (typeof showForumMessage === 'function') showForumMessage(r.message, false);
        } else if (r && r.message && typeof showForumMessage === 'function') {
            showForumMessage(r.message, true);
        }
    }, 'json');
}
</script>
