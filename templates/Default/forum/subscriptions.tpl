<div class="forum-wrapper">
    {forum_breadcrumb}

    <section class="forum-category-block">
        <header class="forum-cat-bar">
            <h2 class="forum-cat-title"><i class="fa fa-bell" aria-hidden="true"></i> {sub_title} <span class="badge">{sub_count}</span></h2>
        </header>
        <div class="forum-rows" id="subs-container">
            {subscription_rows}
        </div>
    </section>
</div>

<script>
function forumUnsubscribe(id) {
    if (typeof jQuery === 'undefined') return;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_subscribe', {
        topic_id: id, user_hash: dle_login_hash
    }, function (r) {
        if (r && r.success) { window.location.reload(); }
    }, 'json');
}
</script>
