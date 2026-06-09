<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- Thread Title Banner -->
    <div class="mybb-actions-strip">
        <div class="mybb-strip-left">
            <i class="fa fa-comments" style="color:#3b82f6;"></i>
            [topic-pinned]<span class="mybb-topic-badge mybb-badge-pinned"><i class="fa fa-thumb-tack"></i> SABİT</span>[/topic-pinned]
            [topic-locked]<span class="mybb-topic-badge mybb-badge-locked"><i class="fa fa-lock"></i> KİLİTLİ</span>[/topic-locked]
            <span class="mybb-strip-topic-title">Konu: {topic_title}</span>
            <span class="mybb-strip-sep">|</span>
            [is-logged]
            <a href="{http_home_url}index.php?do=forum&action=notifications" class="mybb-notif-nav-link">
                <i class="fa fa-bell"></i> Bildirimler
                [has-forum-notifs]<span class="notif-nav-badge">{forum_notif_count}</span>[/has-forum-notifs]
            </a>
            [/is-logged]
        </div>
        <div class="mybb-strip-right">
            [allow-bump]
            <button onclick="modAction('bump',{topic_id})" class="mybb-btn-bump">
                <i class="fa fa-level-up"></i> Öne Çıkar
            </button>
            [/allow-bump]

            <div class="mybb-mod-wrap" id="mod-tools-wrap" style="display:{mod_display};">
                <button onclick="document.getElementById('mod-dropdown').classList.toggle('hidden')" class="mybb-btn-mini mybb-mod-btn">
                    <i class="fa fa-shield" style="color:#6b7280;"></i> Yönetici <i class="fa fa-chevron-down" style="font-size:7px; color:#9ca3af;"></i>
                </button>
                <div id="mod-dropdown" class="mybb-dropdown hidden">
                    <button onclick="modAction('toggle_pin',{topic_id})" class="mybb-dropdown-item">
                        <i class="fa fa-thumb-tack"></i> {pin_label}
                    </button>
                    <button onclick="modAction('toggle_lock',{topic_id})" class="mybb-dropdown-item">
                        <i class="fa fa-lock"></i> {lock_label}
                    </button>
                    <div class="mybb-dropdown-divider"></div>
                    <button onclick="modAction('soft_delete',{topic_id})" class="mybb-dropdown-item mybb-dropdown-danger">
                        <i class="fa fa-trash"></i> Çöpe At
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Poll Block -->
    {poll_block}

    <!-- Posts Rows Container (Full Width) -->
    <div class="w-full">
        {post_rows}
    </div>

    <!-- Pagination -->
    {pagination}

    <!-- Reply Form Box (MyBB Style) -->
    [not-group=5]
    <div class="mybb-category-block mt-4">
        <div class="mybb-cat-header">
            <span class="mybb-cat-title"><i class="fa fa-reply mr-1.5"></i> Hızlı Cevap Yaz</span>
            <span class="mybb-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="p-6">
            <form method="post" action="{http_home_url}forum/reply/{topic_id}/">
                <input type="hidden" name="user_hash" value="{dle_login_hash}">
                <input type="hidden" name="topic_id" value="{topic_id}">
                
                <div class="w-full">
                    {reply_editor}
                </div>
                
                <div class="mt-2 flex items-center gap-2">
                    <button type="button" id="forum-upload-btn" onclick="triggerForumUpload()" class="btn btn-sm btn-flat btn-gray font-weight-bold" style="background:#f1f5f9; border:1px solid #cbd5e1; padding:6px 12px; border-radius:4px; font-size:12px; color:#475569; display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                        <i class="fa fa-paperclip"></i> Dosya Ekle
                    </button>
                    <input type="file" id="forum-upload-input" onchange="handleForumUpload(this)" style="display:none;">
                    <span id="forum-upload-status" class="text-xs font-semibold"></span>
                </div>
                
                <div class="flex justify-between items-center mt-4 pt-3 border-t">
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <i class="fa fa-info-circle text-gray-300 text-sm"></i> Görsel düzenleyici ile cevap yazın
                    </span>
                    <button type="submit" name="submit_reply"
                            class="mybb-btn-action cursor-pointer">
                        Cevap Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
    [/not-group]

    [group=5]
    <div class="mybb-category-block mt-4">
        <div class="mybb-cat-header">
            <span class="mybb-cat-title"><i class="fa fa-reply mr-1.5"></i> Hızlı Cevap Yaz</span>
        </div>
        <div class="p-6 text-center py-8" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-top: 0; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">
            <i class="fa fa-lock text-gray-300 text-4xl mb-3 block"></i>
            <p class="text-sm font-semibold text-gray-600 mb-4">Cevap yazabilmek için foruma giriş yapmalı veya kayıt olmalısınız.</p>
            <div class="flex justify-center gap-3">
                <a href="{http_home_url}index.php?do=register" class="mybb-btn-action cursor-pointer text-xs font-bold inline-flex items-center gap-1.5 bg-blue-600 border-blue-600" style="padding: 8px 16px; border-radius: 4px; color: #fff; text-decoration: none;"><i class="fa fa-user-plus"></i> Kayıt Ol</a>
                <a onclick="document.getElementById('login-modal') ? document.getElementById('login-modal').classList.remove('hidden') : window.location.href='{http_home_url}index.php?do=login'" class="mybb-btn-mini cursor-pointer text-xs font-bold inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 rounded shadow-2xs hover:bg-gray-50" style="background: #fff; cursor: pointer;"><i class="fa fa-sign-in"></i> Giriş Yap</a>
            </div>
        </div>
    </div>
    [/group]
</div>

<script>
/* ---- renderStars: post.tpl'deki data-stars / data-color ile yıldızları çizer ---- */
(function() {
    document.querySelectorAll('.mybb-author-stars[data-stars]').forEach(function(el) {
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
        statusEl.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Dosya yükleniyor...';
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
                statusEl.innerHTML = '<i class="fa fa-check"></i> Başarıyla yüklendi!';
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
            statusEl.innerHTML = '<i class="fa fa-times"></i> Yükleme başarısız oldu.';
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
        } else { showForumMessage(res.msg || 'Hata.', true); }
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
                msg = res.status === 'liked' ? 'Beğeniniz başarıyla kaydedildi!' : 'Beğeniniz geri çekildi!';
            } else {
                msg = res.status === 'liked' ? 'Beğenmeme oyunuz başarıyla kaydedildi!' : 'Beğenmeme oyunuz geri çekildi!';
            }
            showForumMessage(msg, false);
        } else { showForumMessage(res.msg || 'Hata.', true); }
    })
    .catch(function(){ showForumMessage('Bağlantı hatası.', true); });
}

function modAction(action, topicId) {
    var dropdown = document.getElementById('mod-dropdown');
    if (dropdown) dropdown.classList.add('hidden');

    var msg = action === 'soft_delete' ? 'Konu çöpe atılacak. Emin misiniz?' : 'Bu işlem yapılacaktır. Emin misiniz?';
    
    if (typeof DLEconfirm === 'undefined') {
        if (confirm(msg)) {
            executeModAction(action, topicId);
        }
    } else {
        DLEconfirm(msg, 'Konu Yönetimi', function() {
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
            showForumMessage(res.msg || 'İşlem tamam.', false);
            setTimeout(function(){ location.reload(); }, 1000);
        } else { showForumMessage(res.msg || 'Hata.', true); }
    })
    .catch(function(){ showForumMessage('Bağlantı hatası.', true); });
}

function forumReportPost(postId) {
    if (typeof DLEprompt === 'undefined') {
        var reason = prompt('Şikayet Nedeniniz:');
        if (reason && reason.trim() !== '') {
            submitForumReport(postId, reason);
        }
        return;
    }

    DLEprompt('Şikayet Nedeni (En az 5 karakter):', '', 'İçeriği Şikayet Et', function(reason) {
        if (!reason || reason.trim() === '') {
            showForumMessage('Şikayet nedeni boş bırakılamaz.', true);
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
            showForumMessage(res.error || 'Hata.', true);
        }
    })
    .catch(function(){
        if (typeof HideLoading !== 'undefined') HideLoading('');
        showForumMessage('Bağlantı hatası.', true);
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
    var checkboxes = document.querySelectorAll('.mybb-post-select');
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
                bar.className = 'mybb-floating-bar';
                bar.innerHTML = 
                    '<span class="mybb-floating-bar-text"><i class="fa fa-tasks"></i> <strong class="checked-count"></strong> mesaj seçildi</span>' +
                    '<div style="display:flex; gap:8px;">' +
                    '  <button class="mybb-floating-bar-btn delete-btn"><i class="fa fa-trash"></i> Seçilenleri Sil</button>' +
                    '  <button class="mybb-floating-bar-btn clear-btn"><i class="fa fa-times"></i> İptal</button>' +
                    '</div>';
                document.body.appendChild(bar);

                bar.querySelector('.clear-btn').addEventListener('click', function() {
                    checkboxes.forEach(function(cb) { cb.checked = false; });
                    updateFloatingBar();
                });

                bar.querySelector('.delete-btn').addEventListener('click', function() {
                    var ids = getCheckedPostIds();
                    if (!ids.length) return;

                    var confirmMsg = ids.length + ' adet mesaj silinecektir (çöpe atılacaktır). Emin misiniz?';
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
                            showForumMessage('Bağlantı hatası.', true);
                        });
                    };

                    if (typeof DLEconfirm === 'undefined') {
                        if (confirm(confirmMsg)) doDelete();
                    } else {
                        DLEconfirm(confirmMsg, 'Toplu Mesaj Yönetimi', doDelete);
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
</script>
