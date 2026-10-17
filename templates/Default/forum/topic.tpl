<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- Konu araç çubuğu -->
    <div class="forum-toolbar forum-toolbar--topic">
        <div class="forum-toolbar__head">
            <i class="fa fa-comments"></i>
            <span class="forum-toolbar__topic">Konu: {topic_title}</span>
            [topic-pinned]<span class="forum-topic-badge forum-badge-pinned"><i class="fa fa-thumb-tack"></i> {forum_ui_pinned_badge}</span>[/topic-pinned]
            [topic-locked]<span class="forum-topic-badge forum-badge-locked"><i class="fa fa-lock"></i> {forum_ui_locked_badge}</span>[/topic-locked]
            [topic-solved]<span class="forum-topic-badge" style="background:#16a34a;color:#fff;"><i class="fa fa-check"></i> ÇÖZÜLDÜ</span>[/topic-solved]
        </div>
        <div class="forum-toolbar__bar">
            <div class="forum-toolbar__links">
                [is-logged]
                <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=notifications"><i class="fa fa-bell"></i><span>{forum_ui_notifications}</span>[has-forum-notifs]<span class="forum-toolbar__count">{forum_notif_count}</span>[/has-forum-notifs]</a>
                <a class="forum-toolbar__btn" href="#" id="forum-subscribe-btn" onclick="forumToggleSubscribe({topic_id}); return false;">
                    [not-subscribed]<i class="fa fa-bell-o"></i><span>{forum_ui_subscribe}</span>[/not-subscribed]
                    [is-subscribed]<i class="fa fa-bell"></i><span>{forum_ui_unsubscribe}</span>[/is-subscribed]
                </a>
                [/is-logged]
            </div>
            <div class="forum-toolbar__end">
                [allow-bump]
                <button type="button" onclick="modAction('bump',{topic_id})" class="forum-toolbar__btn">
                    <i class="fa fa-level-up"></i><span>{forum_ui_bump}</span>
                </button>
                [/allow-bump]
                [can-solve]
                <button type="button" onclick="forumToggleSolved({topic_id})" class="forum-toolbar__btn" id="forum-solve-btn">
                    <i class="fa fa-check-circle"></i><span id="forum-solve-label">{solve_label}</span>
                </button>
                [/can-solve]
                <div class="forum-mod-wrap" id="mod-tools-wrap" style="display:{mod_display};">
                    <button type="button" onclick="document.getElementById('mod-dropdown').classList.toggle('hidden')" class="forum-toolbar__btn forum-mod-btn">
                        <i class="fa fa-shield"></i><span>{mod_label}</span><i class="fa fa-chevron-down" style="font-size:7px"></i>
                    </button>
                    <div id="mod-dropdown" class="forum-dropdown hidden">
                        [can-edit-topic]
                        <a href="{topic_edit_url}" class="forum-dropdown-item">
                            <i class="fa fa-pencil"></i> {forum_ui_topic_edit}
                        </a>
                        [/can-edit-topic]
                        [can-moderate-topic]
                        <div class="forum-dropdown-divider"></div>
                        <button onclick="modAction('toggle_pin',{topic_id})" class="forum-dropdown-item">
                            <i class="fa fa-thumb-tack"></i> {pin_label}
                        </button>
                        <button onclick="modAction('toggle_lock',{topic_id})" class="forum-dropdown-item">
                            <i class="fa fa-lock"></i> {lock_label}
                        </button>
                        <div class="forum-dropdown-divider"></div>
                        <button onclick="forumModMove()" class="forum-dropdown-item">
                            <i class="fa fa-arrows"></i> {forum_ui_move_topic}
                        </button>
                        <button onclick="forumModMerge()" class="forum-dropdown-item">
                            <i class="fa fa-compress"></i> {forum_ui_merge_topic}
                        </button>
                        <button onclick="forumModSplit()" class="forum-dropdown-item">
                            <i class="fa fa-code-fork"></i> {forum_ui_split_topic}
                        </button>
                        <div class="forum-dropdown-divider"></div>
                        <button onclick="modAction('soft_delete',{topic_id})" class="forum-dropdown-item forum-dropdown-danger">
                            <i class="fa fa-trash"></i> {forum_ui_trash_topic}
                        </button>
                        [/can-moderate-topic]
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:none"><select id="forum-move-cat-select">{move_category_options}</select></div>

    <!-- Interactive Poll Block -->
    {poll_block}

    <!-- En İyi Cevap Özeti -->
    {best_answer_box}

    <!-- Konu Puanlama -->
    {topic_rating}

    <!-- Posts Rows Container (Full Width) -->
    <div class="w-full">
        {post_rows}
    </div>

    <!-- Pagination -->
    {pagination}

    <!-- Çoklu Alıntı Çubuğu -->
    <div id="forum-mquote-bar" class="forum-mquote-bar" style="display:none;">
        <span class="forum-mquote-bar__count"><i class="fa fa-quote-left"></i> <span id="forum-mquote-count">0</span> {forum_ui_posts_selected}</span>
        <button type="button" onclick="forumInsertMultiquotes()" class="forum-btn-action"><i class="fa fa-edit"></i> {forum_ui_add_quotes}</button>
        <button type="button" onclick="forumMultiquoteClear()" class="forum-btn-mini"><i class="fa fa-times"></i> Temizle</button>
    </div>

    [has-similar]
    <div class="forum-category-block mt-4">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-sitemap"></i> Benzer Konular</span>
            <span class="forum-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="forum-similar">{similar_topics}</div>
    </div>
    [/has-similar]

    [has-viewers]
    <div class="forum-viewers" style="margin-top:12px;font-size:13px;">
        <i class="fa fa-users" style="color:#2563eb"></i>
        <b>{viewing_count}</b> {forum_ui_viewing_suffix} {viewing_users}
    </div>
    [/has-viewers]

    <!-- Reply Form Box (Forum Style) -->
    [not-group=5]
    <div class="forum-category-block mt-4">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-reply mr-1.5"></i> {forum_ui_quick_reply}</span>
            <span class="forum-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="p-6">
            <form method="post" action="{http_home_url}forum/reply/{topic_id}/">
                <input type="hidden" name="user_hash" value="{dle_login_hash}">
                <input type="hidden" name="topic_id" value="{topic_id}">
                
                <div class="w-full">
                    <div id="forum-draft-notice" class="forum-draft-notice" style="display:none;">
                        <i class="fa fa-floppy-o"></i> {forum_new_draft_loaded} (<span class="forum-draft-notice__time"></span>).
                        <button type="button" class="forum-btn-mini" onclick="forumDiscardDraft('reply',{topic_id})"><i class="fa fa-trash"></i> {forum_new_draft_discard}</button>
                    </div>
                    {reply_editor}
                </div>
                
                <div class="mt-2 flex items-center gap-2">
                    <button type="button" id="forum-upload-btn" onclick="triggerForumUpload()" class="btn btn-sm btn-flat btn-gray font-weight-bold" style="background:#f1f5f9; border:1px solid #cbd5e1; padding:6px 12px; border-radius:4px; font-size:12px; color:#475569; display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                        <i class="fa fa-paperclip"></i> Dosya Ekle
                    </button>
                    <input type="file" id="forum-upload-input" onchange="handleForumUpload(this)" style="display:none;">
                    <span id="forum-upload-status" class="text-xs font-semibold"></span>
                </div>
                
                {reply_captcha}

                <div class="flex justify-between items-center mt-4 pt-3 border-t">
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <i class="fa fa-info-circle text-gray-300 text-sm"></i> {forum_ui_editor_reply_hint}
                    </span>
                    <button type="submit" name="submit_reply"
                            class="forum-btn-action cursor-pointer">
                        {forum_ui_reply_submit}
                    </button>
                </div>
            </form>
        </div>
    </div>
    [/not-group]

    [group=5]
    <div class="forum-category-block mt-4">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-reply mr-1.5"></i> {forum_ui_quick_reply}</span>
        </div>
        <div class="p-6 text-center py-8" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-top: 0; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">
            <i class="fa fa-lock text-gray-300 text-4xl mb-3 block"></i>
            <p class="text-sm font-semibold text-gray-600 mb-4">{forum_ui_login_required_reply}</p>
            <div class="flex justify-center gap-3">
                <a href="{http_home_url}index.php?do=register" class="forum-btn-action cursor-pointer text-xs font-bold inline-flex items-center gap-1.5 bg-blue-600 border-blue-600" style="padding: 8px 16px; border-radius: 4px; color: #fff; text-decoration: none;"><i class="fa fa-user-plus"></i> {forum_ui_register}</a>
                <a onclick="document.getElementById('login-modal') ? document.getElementById('login-modal').classList.remove('hidden') : window.location.href='{http_home_url}index.php?do=login'" class="forum-btn-mini cursor-pointer text-xs font-bold inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 rounded shadow-2xs hover:bg-gray-50" style="background: #fff; cursor: pointer;"><i class="fa fa-sign-in"></i> {forum_ui_login}</a>
            </div>
        </div>
    </div>
    [/group]
</div>

<script>
/* ---- renderStars: post.tpl'deki data-stars / data-color ile yıldızları çizer ---- */
(function() {
    document.querySelectorAll('.forum-author-stars[data-stars]').forEach(function(el) {
        var count = parseInt(el.getAttribute('data-stars'), 10) || 1;
        var color = el.getAttribute('data-color') || '#9ca3af';
        var html  = '';
        for (var i = 0; i < 5; i++) {
            html += '<i class="fa fa-star" style="color:' + (i < count ? color : '#e5e7eb') + '; margin-right:1px;"></i>';
        }
        el.innerHTML = html;
    });
})();

function showForumMessage(text, isError) {
    if (typeof DLEPush !== 'undefined') {
        if (isError) {
            DLEPush.error(text, 'Forum');
        } else {
            DLEPush.info(text, 'Forum');
        }
    } else {
        alert(text);
    }
}

function triggerForumUpload() {
    var input = document.getElementById('forum-upload-input');
    if (input) input.click();
}

function forumInsertUploadedContent(res) {
    var url = res.url || res.link || '';
    if (!url) return false;

    var content = res.is_image
        ? '<img src="' + url + '" alt="" />'
        : '<a href="' + url + '">' + (res.filename || 'dosya') + '</a>';

    if (typeof tinymce !== 'undefined') {
        var editor = tinymce.get('forum-reply-textarea');
        if (editor) {
            editor.insertContent(content + ' ');
            editor.focus();
            return true;
        }
    }

    if (typeof jQuery !== 'undefined') {
        var froalaEl = jQuery('#forum-reply-textarea');
        if (froalaEl.length && typeof froalaEl.froalaEditor === 'function' && froalaEl.data('froala.editor')) {
            froalaEl.froalaEditor('html.insert', content + ' ');
            return true;
        }
    }

    var txtArea = document.getElementById('forum-reply-textarea');
    if (txtArea) {
        txtArea.value += ' ' + (res.bbcode || content) + ' ';
        txtArea.focus();
        return true;
    }

    return false;
}

function handleForumUpload(input) {
    if (!input.files || input.files.length === 0) return;
    var file = input.files[0];
    var statusEl = document.getElementById('forum-upload-status');
    if (statusEl) {
        statusEl.style.color = '#475569';
        statusEl.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {forum_ui_uploading}';
    }

    var formData = new FormData();
    formData.append('file', file);
    formData.append('subaction', 'upload');
    formData.append('quick', '1');
    formData.append('user_hash', (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '{dle_login_hash}'));

    var ajaxUrl = (typeof dle_root !== 'undefined' ? dle_root : '{http_home_url}') + 'index.php?controller=ajax&mod=forum_upload';

    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.status === 'success' || res.success) {
            if (statusEl) {
                statusEl.style.color = '#10b981';
                statusEl.innerHTML = '<i class="fa fa-check"></i> {forum_ui_upload_ok}';
            }
            forumInsertUploadedContent(res);
            input.value = '';
        } else {
            if (statusEl) {
                statusEl.style.color = '#ef4444';
                statusEl.innerHTML = '<i class="fa fa-times"></i> Hata: ' + (res.message || res.error || 'Bilinmeyen hata');
            }
        }
    })
    .catch(function() {
        if (statusEl) {
            statusEl.style.color = '#ef4444';
            statusEl.innerHTML = '<i class="fa fa-times"></i> {forum_ui_upload_fail}';
        }
    });
}

function forumQuotePost(postId) {
    var btn = document.querySelector('#quote-btn-' + postId);
    if(btn) { btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>'; }

    var formData = new FormData();
    formData.append('post_id', postId);
    formData.append('user_hash', (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '{dle_login_hash}'));

    fetch('{http_home_url}index.php?controller=ajax&mod=forum_quote', {
        method: 'POST',
        body: formData
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        if(btn){ btn.disabled = false; btn.innerHTML = 'REPLY'; }
        if(res.status === 'success') {
            // Safely check TinyMCE first
            if (typeof tinymce !== 'undefined') {
                var editor = tinymce.get('forum-reply-textarea');
                if(editor) {
                    editor.setContent(res.text + editor.getContent());
                    editor.focus();
                    editor.scrollIntoView({behavior:'smooth',block:'center'});
                    return;
                }
            }
            
            // Fallback for plain Textarea
            var txtarea = document.getElementById('forum-reply-textarea');
            if (txtarea) {
                txtarea.value = res.text + txtarea.value;
                txtarea.focus();
                txtarea.scrollIntoView({behavior:'smooth',block:'center'});
            }
        } else { showForumMessage(res.msg || '{forum_ui_error}', true); }
    })
    .catch(function(err){
        if(btn){ btn.disabled = false; btn.innerHTML = 'REPLY'; }
        console.error("Quote error:", err);
    });
}

function forumLikePost(postId, type) {
    var likeEl  = document.getElementById('like-count-' + postId);
    var disEl   = document.getElementById('dislike-count-' + postId);

    var formData = new FormData();
    formData.append('post_id', postId);
    formData.append('type', type);
    formData.append('user_hash', (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '{dle_login_hash}'));

    fetch('{http_home_url}index.php?controller=ajax&mod=forum_like', {
        method: 'POST',
        body: formData
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        if(res.status === 'liked' || res.status === 'unliked') {
            if(likeEl) likeEl.textContent = res.new_count;
            if(disEl)  disEl.textContent  = res.new_dislikes;
            
            var msg = '';
            if (type === 'like') {
                msg = res.status === 'liked' ? '{forum_ui_like_saved}' : '{forum_ui_like_removed}';
            } else {
                msg = res.status === 'liked' ? '{forum_ui_dislike_saved}' : '{forum_ui_dislike_removed}';
            }
            showForumMessage(msg, false);
        } else { showForumMessage(res.msg || '{forum_ui_error}', true); }
    })
    .catch(function(){ showForumMessage('{forum_ui_connection_error}', true); });
}

function modAction(action, topicId) {
    var dropdown = document.getElementById('mod-dropdown');
    if (dropdown) dropdown.classList.add('hidden');

    var msg = action === 'soft_delete' ? '{forum_ui_mod_confirm_trash}' : '{forum_ui_mod_confirm_generic}';
    
    if (typeof DLEconfirm === 'undefined') {
        if (confirm(msg)) {
            executeModAction(action, topicId);
        }
    } else {
        DLEconfirm(msg, '{forum_ui_mod_title}', function() {
            executeModAction(action, topicId);
        });
    }
}

function executeModAction(action, topicId) {
    var formData = new FormData();
    formData.append('action', action);
    formData.append('topic_id', topicId);
    formData.append('user_hash', (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '{dle_login_hash}'));

    fetch('{http_home_url}index.php?controller=ajax&mod=forum_moderation', {
        method: 'POST',
        body: formData
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        if(res.status === 'success') {
            if(res.redirect) { window.location.href = res.redirect; return; }
            showForumMessage(res.msg || '{forum_ui_done}', false);
            setTimeout(function(){ location.reload(); }, 1000);
        } else { showForumMessage(res.msg || '{forum_ui_error}', true); }
    })
    .catch(function(){ showForumMessage('{forum_ui_connection_error}', true); });
}

function forumReportPost(postId) {
    if (typeof DLEprompt === 'undefined') {
        var reason = prompt('Şikayet Nedeniniz:');
        if (reason && reason.trim() !== '') {
            submitForumReport(postId, reason);
        }
        return;
    }

    DLEprompt('{forum_ui_report_reason}', '', '{forum_ui_report_title}', function(reason) {
        if (!reason || reason.trim() === '') {
            showForumMessage('{forum_ui_report_empty}', true);
            return;
        }
        submitForumReport(postId, reason);
    }, false);
}

function submitForumReport(postId, reason) {
    var formData = new FormData();
    formData.append('action', 'add');
    formData.append('target_id', postId);
    formData.append('target_type', 'post');
    formData.append('reason', reason);
    formData.append('user_hash', (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '{dle_login_hash}'));

    if (typeof ShowLoading !== 'undefined') ShowLoading('');
    fetch('{http_home_url}index.php?controller=ajax&mod=forum_reports', {
        method: 'POST',
        body: formData
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        if (typeof HideLoading !== 'undefined') HideLoading('');
        if(res.success) {
            showForumMessage(res.message, false);
        } else {
            showForumMessage(res.error || '{forum_ui_error}', true);
        }
    })
    .catch(function(){
        if (typeof HideLoading !== 'undefined') HideLoading('');
        showForumMessage('{forum_ui_connection_error}', true);
    });
}

// Close the moderation dropdown automatically when clicking anywhere outside of it
window.addEventListener('click', function(e) {
    var dropdown = document.getElementById('mod-dropdown');
    var wrap = document.getElementById('mod-tools-wrap');
    if (dropdown && wrap && !wrap.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

// Bulk moderation for posts via checkboxes
(function() {
    var checkboxes = document.querySelectorAll('.forum-post-select');
    if (!checkboxes.length) return;

    var bar = null;

    function getCheckedPostIds() {
        var ids = [];
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                var pid = cb.getAttribute('data-postid');
                if (pid) ids.push(pid);
            }
        });
        return ids;
    }

    function updateFloatingBar() {
        var checkedIds = getCheckedPostIds();
        if (checkedIds.length > 0) {
            if (!bar) {
                bar = document.createElement('div');
                bar.className = 'forum-floating-bar';
                bar.innerHTML = 
                    '<span class="forum-floating-bar-text"><i class="fa fa-tasks"></i> <strong class="checked-count"></strong> {forum_ui_posts_selected}</span>' +
                    '<div style="display:flex; gap:8px;">' +
                    '  <button class="forum-floating-bar-btn delete-btn"><i class="fa fa-trash"></i> {forum_ui_bulk_delete}</button>' +
                    '  <button class="forum-floating-bar-btn clear-btn"><i class="fa fa-times"></i> {forum_ui_cancel}</button>' +
                    '</div>';
                document.body.appendChild(bar);

                bar.querySelector('.clear-btn').addEventListener('click', function() {
                    checkboxes.forEach(function(cb) { cb.checked = false; });
                    updateFloatingBar();
                });

                bar.querySelector('.delete-btn').addEventListener('click', function() {
                    var ids = getCheckedPostIds();
                    if (!ids.length) return;

                    var confirmMsg = ids.length + ' {forum_ui_bulk_confirm_suffix}';
                    var doDelete = function() {
                        if (typeof ShowLoading !== 'undefined') ShowLoading('');
                        var formData = new FormData();
                        formData.append('action', 'bulk_delete_posts');
                        formData.append('topic_id', '{topic_id}');
                        formData.append('post_ids', ids.join(','));
                        formData.append('user_hash', (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '{dle_login_hash}'));

                        fetch('{http_home_url}index.php?controller=ajax&mod=forum_moderation', {
                            method: 'POST',
                            body: formData
                        })
                        .then(function(r){ return r.json(); })
                        .then(function(res) {
                            if (typeof HideLoading !== 'undefined') HideLoading('');
                            if (res.status === 'success') {
                                showForumMessage(res.msg, false);
                                setTimeout(function() { location.reload(); }, 800);
                            } else {
                                showForumMessage(res.msg, true);
                            }
                        })
                        .catch(function() {
                            if (typeof HideLoading !== 'undefined') HideLoading('');
                            showForumMessage('{forum_ui_connection_error}', true);
                        });
                    };

                    if (typeof DLEconfirm === 'undefined') {
                        if (confirm(confirmMsg)) doDelete();
                    } else {
                        DLEconfirm(confirmMsg, '{forum_ui_bulk_title}', doDelete);
                    }
                });
            }
            bar.querySelector('.checked-count').textContent = checkedIds.length;
            bar.style.display = 'flex';
        } else {
            if (bar) {
                bar.style.display = 'none';
            }
        }
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateFloatingBar);
    });
})();

/* ---- Abonelik ---- */
function forumToggleSubscribe(topicId) {
    if (typeof jQuery === 'undefined') return;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_subscribe', {
        topic_id: topicId, user_hash: dle_login_hash
    }, function (r) {
        if (r && r.success) {
            var btn = document.getElementById('forum-subscribe-btn');
            if (btn) { btn.innerHTML = r.subscribed ? '<i class="fa fa-bell" style="color:#2563eb"></i> {forum_ui_unsubscribe}' : '<i class="fa fa-bell-o"></i> {forum_ui_subscribe}'; }
            if (typeof showForumMessage === 'function') showForumMessage(r.message, false);
        } else if (r && r.message && typeof showForumMessage === 'function') {
            showForumMessage(r.message, true);
        }
    }, 'json');
}

/* ---- Moderatör: Taşı / Birleştir / Böl ---- */
function forumModDialog(title, html, onOk) {
    var pop = jQuery('#forum-mod-dialog');
    if (!pop.length) { jQuery('body').append('<div id="forum-mod-dialog" style="display:none"></div>'); pop = jQuery('#forum-mod-dialog'); }
    pop.html(html);
    if (pop.hasClass('ui-dialog-content')) { pop.dialog('destroy'); }
    pop.dialog({ autoOpen: false, modal: true, title: title, width: 400, resizable: false, dialogClass: 'modalfixed',
        buttons: { '{forum_ui_apply}': function () { onOk(pop); }, '{forum_ui_cancel}': function () { pop.dialog('close'); } } });
    pop.dialog('open');
}
function forumModAction(action, data) {
    data.action = action;
    data.topic_id = {topic_id};
    data.user_hash = dle_login_hash;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_moderation', data, function (r) {
        if (r && r.status === 'success') {
            if (typeof showForumMessage === 'function') showForumMessage(r.msg, false);
            if (r.redirect) { window.location.href = r.redirect; } else { setTimeout(function () { window.location.reload(); }, 700); }
        } else if (typeof showForumMessage === 'function') {
            showForumMessage((r && r.msg) ? r.msg : '{forum_ui_error}', true);
        }
    }, 'json');
}
function forumModMove() {
    var el = document.getElementById('forum-move-cat-select');
    var opts = el ? el.innerHTML : '';
    forumModDialog('{forum_ui_move_topic}', '<label>{forum_ui_mod_move_label}</label><select id="forum-move-target" class="forum-select">' + opts + '</select>', function (pop) {
        var cid = jQuery('#forum-move-target').val();
        pop.dialog('close');
        forumModAction('move_topic', { cat_id: cid });
    });
}
function forumModMerge() {
    forumModDialog('{forum_ui_merge_topic}', '<label>{forum_ui_mod_merge_label}</label><input type="number" id="forum-merge-target" class="form-input" placeholder="{forum_ui_mod_merge_ph}">', function (pop) {
        var t = jQuery('#forum-merge-target').val();
        if (!t) return;
        pop.dialog('close');
        forumModAction('merge_topic', { target_topic_id: t });
    });
}
function forumModSplit() {
    var ids = [];
    document.querySelectorAll('.forum-post-select:checked').forEach(function (c) { ids.push(c.getAttribute('data-postid')); });
    if (!ids.length) { if (typeof showForumMessage === 'function') showForumMessage('{forum_ui_mod_split_select}', true); return; }
    forumModDialog('{forum_ui_split_topic}', '<label>{forum_ui_mod_split_label}</label><input type="text" id="forum-split-title" class="form-input" placeholder="{forum_ui_mod_split_label}">', function (pop) {
        var title = jQuery('#forum-split-title').val();
        if (!title) return;
        pop.dialog('close');
        forumModAction('split_topic', { post_ids: ids.join(','), new_title: title });
    });
}

/* ---- Çoklu alıntı ---- */
var forumMquoteSel = [];
function forumInsertQuoteIntoEditor(text) {
    if (typeof tinymce !== 'undefined') {
        var ed = tinymce.get('forum-reply-textarea');
        if (ed) { ed.setContent(text + ed.getContent()); ed.focus(); return true; }
    }
    if (typeof jQuery !== 'undefined') {
        var fel = jQuery('#forum-reply-textarea');
        if (fel.length && typeof fel.froalaEditor === 'function' && fel.data('froala.editor')) { fel.froalaEditor('html.insert', text); fel.froalaEditor('events.focus'); return true; }
    }
    var ta = document.getElementById('forum-reply-textarea');
    if (ta) { ta.value = text + ta.value; ta.focus(); return true; }
    return false;
}
function forumMultiquoteUpdateBar() {
    var bar = document.getElementById('forum-mquote-bar');
    var cnt = document.getElementById('forum-mquote-count');
    if (cnt) cnt.textContent = forumMquoteSel.length;
    if (bar) bar.style.display = forumMquoteSel.length ? 'flex' : 'none';
}
function forumMultiquoteToggle(postId) {
    postId = parseInt(postId, 10);
    var idx = forumMquoteSel.indexOf(postId);
    var btn = document.getElementById('mquote-btn-' + postId);
    var box = document.getElementById('post-' + postId);
    if (idx === -1) { forumMquoteSel.push(postId); if (btn) btn.classList.add('active'); if (box) box.classList.add('forum-mquote-selected'); }
    else { forumMquoteSel.splice(idx, 1); if (btn) btn.classList.remove('active'); if (box) box.classList.remove('forum-mquote-selected'); }
    forumMultiquoteUpdateBar();
}
function forumMultiquoteClear() {
    var ids = forumMquoteSel.slice();
    for (var i = 0; i < ids.length; i++) { forumMultiquoteToggle(ids[i]); }
    forumMquoteSel = [];
    forumMultiquoteUpdateBar();
}
function forumInsertMultiquotes() {
    if (!forumMquoteSel.length) return;
    var formData = new FormData();
    formData.append('post_ids', forumMquoteSel.join(','));
    formData.append('user_hash', dle_login_hash);
    fetch('{http_home_url}index.php?controller=ajax&mod=forum_quote', { method: 'POST', body: formData })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.status === 'success') { forumInsertQuoteIntoEditor(res.text); forumMultiquoteClear(); }
            else { if (typeof showForumMessage === 'function') showForumMessage(res.msg || '{forum_ui_error}', true); }
        })
        .catch(function(){ if (typeof showForumMessage === 'function') showForumMessage('{forum_ui_connection_error}', true); });
}

/* ---- Puanlama / Çözüldü / En iyi cevap ---- */
function forumRateTopic(tid, val) {
    if (typeof jQuery === 'undefined') return;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_rate', { topic_id: tid, rating: val, user_hash: dle_login_hash }, function (r) {
        if (!r || r.status !== 'ok') { if (r && r.msg && typeof showForumMessage === 'function') showForumMessage(r.msg, true); return; }
        var avgEl = document.getElementById('forum-rating-avg'); if (avgEl) avgEl.innerHTML = r.stars_html;
        var metaEl = document.getElementById('forum-rating-meta'); if (metaEl) metaEl.textContent = '(' + r.count + ' oy · ort. ' + Number(r.avg).toFixed(1) + ')';
        var pick = document.getElementById('forum-rating-pick-stars');
        if (pick) { var links = pick.getElementsByTagName('a'); for (var i = 0; i < links.length; i++) { var ic = links[i].getElementsByTagName('i')[0]; if (ic) ic.className = (i + 1 <= val) ? 'fa fa-star' : 'fa fa-star-o'; } }
    }, 'json');
}
function forumToggleSolved(tid) {
    if (typeof jQuery === 'undefined') { location.reload(); return; }
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_solve', { topic_id: tid, user_hash: dle_login_hash }, function (r) {
        if (r && r.status === 'ok') { location.reload(); }
        else if (r && r.msg && typeof showForumMessage === 'function') { showForumMessage(r.msg, true); }
    }, 'json');
}
function forumMarkBest(tid, pid) {
    if (typeof jQuery === 'undefined') return;
    jQuery.post('{http_home_url}index.php?controller=ajax&mod=forum_best_answer', { topic_id: tid, post_id: pid, user_hash: dle_login_hash }, function (r) {
        if (r && r.status === 'ok') { location.reload(); }
        else if (r && r.msg && typeof showForumMessage === 'function') { showForumMessage(r.msg, true); }
    }, 'json');
}

/* ---- Taslak ---- */
(function () {
    if (typeof forumDraftInit !== 'function') return;
    if ('{drafts_enabled}' !== 'true') return;
    if (document.getElementById('forum-reply-textarea')) {
        forumDraftInit({ type: 'reply', topicId: {topic_id}, catId: 0, titleFieldId: null, editorId: 'forum-reply-textarea' });
    }
})();
</script>
