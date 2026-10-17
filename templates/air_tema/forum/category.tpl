<div class="forum-wrapper">
    {forum_breadcrumb}

    <!-- Araç çubuğu -->
    <div class="forum-toolbar">
        <div class="forum-toolbar__links">
            <span class="forum-toolbar__title"><i class="fa fa-folder-open" aria-hidden="true"></i> {cat_name}</span>
            [cat-has-unread]<span class="forum-toolbar__count" title="{forum_ui_unread_topics}">{cat_unread_count}</span>[/cat-has-unread]
            [is-logged]
            <span class="forum-toolbar__sep" aria-hidden="true"></span>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=notifications"><i class="fa fa-bell" aria-hidden="true"></i><span>{forum_ui_notifications}</span>[has-forum-notifs]<span class="forum-toolbar__count">{forum_notif_count}</span>[/has-forum-notifs]</a>
            <a class="forum-toolbar__btn" href="#" onclick="forumMarkRead({cat_id}); return false;"><i class="fa fa-check-circle-o" aria-hidden="true"></i><span>{forum_ui_mark_read}</span></a>
            [cat-sub-enabled]
            <a class="forum-toolbar__btn" href="#" id="forum-cat-subscribe-btn" onclick="forumToggleCatSub({cat_id}); return false;">
                [cat-subscribed]<i class="fa fa-bell" aria-hidden="true"></i><span>{forum_ui_cat_unsubscribe}</span>[/cat-subscribed]
                [cat-not-subscribed]<i class="fa fa-bell-o" aria-hidden="true"></i><span>{forum_ui_cat_subscribe}</span>[/cat-not-subscribed]
            </a>
            [/cat-sub-enabled]
            [/is-logged]
        </div>
        <div class="forum-toolbar__end">
            <a href="{http_home_url}forum/new-topic/{cat_alt}/" class="primary-button">
                <i class="fa fa-plus" aria-hidden="true"></i> {forum_ui_new_topic}
            </a>
        </div>
    </div>

    <!-- Konu listesi -->
    <section class="forum-category-block">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title">{cat_name} — {forum_ui_category_topics}</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="forum-rows">
            {topic_rows}
        </div>
    </section>

    <!-- Sayfalama -->
    <div style="margin-top:var(--sp-5)">
        {pagination}
    </div>
</div>

<script>
function forumMarkRead(catId) {
    if (typeof jQuery === 'undefined') return;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_read', {
        cat_id: catId, user_hash: dle_login_hash
    }, function (r) {
        if (r && r.success) { window.location.reload(); }
    }, 'json');
}

function forumToggleCatSub(catId) {
    if (typeof jQuery === 'undefined') return;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_cat_subscribe', {
        cat_id: catId, user_hash: dle_login_hash
    }, function (r) {
        if (r && r.success) {
            var btn = document.getElementById('forum-cat-subscribe-btn');
            btn.innerHTML = r.subscribed ? '<i class="fa fa-bell" aria-hidden="true"></i> {forum_ui_cat_unsubscribe}' : '<i class="fa fa-bell-o"></i> {forum_ui_cat_subscribe}';
            if (typeof showForumMessage === 'function') showForumMessage(r.message, false);
        } else if (r && r.message && typeof showForumMessage === 'function') {
            showForumMessage(r.message, true);
        }
    }, 'json');
}
</script>
