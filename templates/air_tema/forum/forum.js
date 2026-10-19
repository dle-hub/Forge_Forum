(function () {
  function setBlockCollapsed(block, collapsed) {
    if (!block) {
      return;
    }
    block.classList.toggle("forum-collapsed", collapsed);
    var icon = block.querySelector(".forum-cat-header .forum-cat-toggle i");
    if (icon) {
      icon.className = collapsed ? "fa fa-plus" : "fa fa-minus";
    }
  }

  function toggleBlock(block) {
    setBlockCollapsed(block, !block.classList.contains("forum-collapsed"));
  }

  document.addEventListener("click", function (e) {
    var header = e.target.closest(".forum-cat-header");
    if (!header || !header.querySelector(".forum-cat-toggle")) {
      return;
    }
    var block = header.closest(".forum-category-block, .forum-stats-card");
    if (!block) {
      return;
    }
    e.preventDefault();
    toggleBlock(block);
  });
})();

/* --- Mesaj taslakları (drafts) --- */
function forumGetEditorHtml(editorId) {
  editorId = editorId || 'forum-reply-textarea';
  if (typeof tinymce !== 'undefined') { var ed = tinymce.get(editorId); if (ed) return ed.getContent(); }
  if (typeof jQuery !== 'undefined') {
    var fel = jQuery('#' + editorId);
    if (fel.length && typeof fel.froalaEditor === 'function' && fel.data('froala.editor')) return fel.froalaEditor('html.get');
  }
  var ta = document.getElementById(editorId);
  return ta ? ta.value : '';
}
function forumSetEditorHtml(editorId, html) {
  editorId = editorId || 'forum-reply-textarea';
  if (typeof tinymce !== 'undefined') { var ed = tinymce.get(editorId); if (ed) { ed.setContent(html); return true; } }
  if (typeof jQuery !== 'undefined') {
    var fel = jQuery('#' + editorId);
    if (fel.length && typeof fel.froalaEditor === 'function' && fel.data('froala.editor')) { fel.froalaEditor('html.set', html); return true; }
  }
  var ta = document.getElementById(editorId);
  if (ta) { ta.value = html; return true; }
  return false;
}
function forumDraftInit(opts) {
  opts = opts || {};
  var editorId = opts.editorId || 'forum-reply-textarea';
  if (typeof jQuery === 'undefined' || typeof dle_root === 'undefined') return;
  var url = dle_root + 'index.php?controller=ajax&mod=forum_draft';
  var hash = (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '');
  var lastSaved = forumGetEditorHtml(editorId);
  jQuery.post(url, { action: 'get', type: opts.type, topic_id: opts.topicId, user_hash: hash }, function (r) {
    if (r && r.success && r.draft) {
      var cur = forumGetEditorHtml(editorId);
      if (!cur || cur.replace(/<[^>]*>/g, '').trim() === '') {
        forumSetEditorHtml(editorId, r.draft.text);
        lastSaved = r.draft.text;
        if (opts.titleFieldId) { var tf = document.getElementById(opts.titleFieldId); if (tf && !tf.value) tf.value = r.draft.title || ''; }
        var el = document.getElementById('forum-draft-notice');
        if (el) { el.style.display = 'flex'; var t = el.querySelector('.forum-draft-notice__time'); if (t) t.textContent = r.draft.date || ''; }
      }
    }
  }, 'json');
  setInterval(function () {
    var text = forumGetEditorHtml(editorId);
    if (text === lastSaved) return;
    var title = opts.titleFieldId ? (document.getElementById(opts.titleFieldId) ? document.getElementById(opts.titleFieldId).value : '') : '';
    lastSaved = text;
    jQuery.post(url, { action: 'save', type: opts.type, topic_id: opts.topicId, cat_id: opts.catId || 0, title: title, text: text, user_hash: hash }, function () {}, 'json');
  }, 25000);
  window.addEventListener('beforeunload', function () {
    var text = forumGetEditorHtml(editorId);
    if (text === lastSaved) return;
    try {
      var fd = new FormData();
      fd.append('action', 'save'); fd.append('type', opts.type); fd.append('topic_id', opts.topicId); fd.append('cat_id', opts.catId || 0);
      if (opts.titleFieldId) { var tf = document.getElementById(opts.titleFieldId); fd.append('title', tf ? tf.value : ''); }
      fd.append('text', text); fd.append('user_hash', hash);
      navigator.sendBeacon(url, fd);
    } catch (e) {}
  });
}
function forumDiscardDraft(type, topicId, editorId) {
  if (typeof jQuery === 'undefined' || typeof dle_root === 'undefined') return;
  var hash = (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '');
  jQuery.post(dle_root + 'index.php?controller=ajax&mod=forum_draft', { action: 'delete', type: type, topic_id: topicId, user_hash: hash }, function () {
    forumSetEditorHtml(editorId || 'forum-reply-textarea', '');
    var el = document.getElementById('forum-draft-notice');
    if (el) el.style.display = 'none';
  }, 'json');
}

/* --- Captcha yenile (resim captcha) --- */
function forumReloadCaptcha() {
  var box = document.getElementById('forum-captcha-img');
  if (box) {
    var img = box.querySelector('img');
    if (img) {
      var base = img.src.split('?')[0];
      img.src = base + '?controller=antibot&r=' + Date.now();
    }
  }
  return false;
}

/* ============================================================
   @mention otomatik tamamlama
   ============================================================ */
(function () {
  var NAME_RE = /^[a-zA-Z0-9_\-\.ğüşöçİĞÜŞÖÇ]{0,32}$/;
  var BOUNDARY_RE = /[\s\(\[\{>"'«„]/;

  var box = null, listEl = null;
  var open = false, items = [], active = 0, seq = 0, lastQ = '';
  var cur = null;

  function ajaxUrl() {
    var base = (typeof dle_root !== 'undefined') ? dle_root : '';
    return base + 'index.php?controller=ajax&mod=forum_mention_search';
  }
  function hashVal() {
    return (typeof dle_login_hash !== 'undefined' ? dle_login_hash : '');
  }

  function ensureBox() {
    if (box) return;
    box = document.createElement('div');
    box.className = 'forum-mention-box';
    box.setAttribute('role', 'listbox');
    box.style.display = 'none';
    listEl = document.createElement('div');
    listEl.className = 'forum-mention-list';
    box.appendChild(listEl);
    document.body.appendChild(box);
    listEl.addEventListener('mousedown', function (e) {
      var it = (e.target && e.target.closest) ? e.target.closest('.forum-mention-item') : null;
      if (!it) return;
      e.preventDefault();
      var i = parseInt(it.getAttribute('data-index'), 10);
      if (!isNaN(i)) { active = i; choose(); }
    });
  }

  function hide() {
    open = false; items = []; cur = null; lastQ = '';
    if (box) box.style.display = 'none';
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function renderItems() {
    if (!listEl) return;
    var q = lastQ || '';
    var html = '';
    for (var i = 0; i < items.length; i++) {
      var u = items[i];
      var name = esc(u.name);
      var hl = name;
      if (q && name.toLowerCase().indexOf(q.toLowerCase()) === 0) {
        hl = '<b>' + name.substring(0, q.length) + '</b>' + name.substring(q.length);
      }
      var av = u.avatar
        ? '<img class="forum-mention-avatar" src="' + esc(u.avatar) + '" alt="">'
        : '<span class="forum-mention-avatar forum-mention-avatar--ph"><i class="fa fa-user"></i></span>';
      html += '<div class="forum-mention-item' + (i === active ? ' is-active' : '') + '" data-index="' + i + '">' +
        av + '<span class="forum-mention-name">' + hl + '</span></div>';
    }
    listEl.innerHTML = html;
  }

  function textBeforeCaret(kind, ctx) {
    try {
      if (kind === 'textarea') {
        return ctx.el.value.slice(0, ctx.el.selectionStart || 0);
      }
      if (kind === 'froala') {
        var sel = window.getSelection();
        if (!sel || !sel.rangeCount) return null;
        var r = sel.getRangeAt(0).cloneRange();
        r.setStart(ctx.el, 0);
        return r.toString();
      }
      if (kind === 'tinymce') {
        var ed = ctx.ed;
        var s = ed.selection.getSel();
        if (!s || !s.rangeCount) return null;
        var rr = s.getRangeAt(0).cloneRange();
        rr.setStart(ed.getBody(), 0);
        return rr.toString();
      }
    } catch (e) {}
    return null;
  }

  function caretRect(kind, ctx) {
    try {
      if (kind === 'textarea') return ctx.el.getBoundingClientRect();
      if (kind === 'froala') {
        var sel = window.getSelection();
        if (sel && sel.rangeCount) {
          var r = sel.getRangeAt(0).cloneRange(); r.collapse(true);
          var rc = r.getBoundingClientRect();
          if (rc && (rc.top || rc.left)) return rc;
        }
        return ctx.el.getBoundingClientRect();
      }
      if (kind === 'tinymce') {
        var ed = ctx.ed;
        var offL = 0, offT = 0, ifr = null;
        try { ifr = ed.getContentAreaContainer().querySelector('iframe'); } catch (e) {}
        if (ifr) { var ir = ifr.getBoundingClientRect(); offL = ir.left; offT = ir.top; }
        var s = ed.selection.getSel();
        if (s && s.rangeCount) {
          var rr = s.getRangeAt(0).cloneRange(); rr.collapse(true);
          var rc2 = rr.getBoundingClientRect();
          if (rc2 && (rc2.top || rc2.left)) {
            return { left: rc2.left + offL, top: rc2.top + offT, bottom: rc2.bottom + offT };
          }
        }
        return ed.getContainer().getBoundingClientRect();
      }
    } catch (e) {}
    return null;
  }

  function positionBox(kind, ctx) {
    var rect = caretRect(kind, ctx);
    if (!rect || !box) return;
    var sx = window.pageXOffset || document.documentElement.scrollLeft || 0;
    var sy = window.pageYOffset || document.documentElement.scrollTop || 0;
    var top = ((rect.bottom || rect.top || 0)) + sy + 3;
    var left = (rect.left || 0) + sx;
    box.style.left = Math.max(4, left) + 'px';
    box.style.top = top + 'px';
    box.style.display = 'block';
  }

  function check(kind, ctx) {
    var before = textBeforeCaret(kind, ctx);
    if (before == null) { hide(); return; }
    var idx = before.lastIndexOf('@');
    if (idx < 0) { hide(); return; }
    var prev = idx > 0 ? before.charAt(idx - 1) : '';
    if (idx > 0 && !BOUNDARY_RE.test(prev)) { hide(); return; }
    var q = before.slice(idx + 1);
    if (!NAME_RE.test(q) || q === '') { hide(); return; }

    var mySeq = ++seq;
    ensureBox();

    fetch(ajaxUrl(), {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'q=' + encodeURIComponent(q) + '&user_hash=' + encodeURIComponent(hashVal())
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (mySeq !== seq) return;
        if (!res || !res.success || !res.users || !res.users.length) { hide(); return; }
        items = res.users; active = 0; lastQ = q; open = true;
        cur = { kind: kind, ctx: ctx, len: q.length };
        renderItems();
        positionBox(kind, ctx);
      })
      .catch(function () { if (mySeq === seq) hide(); });
  }

  function replaceQuery(kind, ctx, len, replacement) {
    if (kind === 'textarea') {
      var el = ctx.el, v = el.value, s = el.selectionStart;
      var start = s - (len + 1);
      el.value = v.slice(0, start) + replacement + v.slice(s);
      var np = start + replacement.length;
      el.selectionStart = el.selectionEnd = np;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.focus();
      return;
    }
    var doc, sel;
    if (kind === 'froala') { doc = document; sel = window.getSelection(); }
    else { doc = ctx.ed.getDoc(); sel = ctx.ed.selection.getSel(); }
    if (!sel || !sel.rangeCount) return;
    var r = sel.getRangeAt(0);
    var node = r.startContainer, off = r.startOffset;
    try {
      var rr = doc.createRange();
      rr.setStart(node, Math.max(0, off - (len + 1)));
      rr.setEnd(node, off);
      sel.removeAllRanges();
      sel.addRange(rr);
      doc.execCommand('insertText', false, replacement);
    } catch (e) {}
  }

  function choose() {
    if (!open || !cur || !items.length) return;
    var u = items[active];
    if (!u) { hide(); return; }
    var ctx = cur.ctx, kind = cur.kind, len = cur.len;
    hide();
    replaceQuery(kind, ctx, len, '@' + u.name + ' ');
  }

  function onKey(e) {
    if (!open) return;
    var k = e.key || '';
    if (k === 'ArrowDown') { active = (active + 1) % items.length; renderItems(); e.preventDefault(); e.stopPropagation(); }
    else if (k === 'ArrowUp') { active = (active - 1 + items.length) % items.length; renderItems(); e.preventDefault(); e.stopPropagation(); }
    else if (k === 'Enter' || k === 'Tab') { choose(); e.preventDefault(); e.stopPropagation(); }
    else if (k === 'Escape') { hide(); e.preventDefault(); e.stopPropagation(); }
  }

  function attach(id) {
    if (typeof dle_root === 'undefined') return;
    var ta = document.getElementById(id);
    if (!ta) return;
    var tries = 0;

    function setup() {
      if (typeof tinymce !== 'undefined' && tinymce.get(id)) {
        var ed = tinymce.get(id);
        if (!ed.__ffMention) {
          ed.__ffMention = true;
          ed.on('keyup', function () { check('tinymce', { ed: ed }); });
          ed.on('input', function () { check('tinymce', { ed: ed }); });
          ed.on('keydown', function (e) { onKey(e); });
        }
        return true;
      }
      if (typeof jQuery !== 'undefined') {
        var $ta = jQuery('#' + id);
        if ($ta.length && $ta.data('froala.editor')) {
          if (!$ta.data('ffMention')) {
            $ta.data('ffMention', true);
            var fel = null;
            try { fel = $ta.next('.fr-box').find('.fr-element').get(0); } catch (e) {}
            if (!fel) { try { fel = $ta.parent().find('.fr-element').get(0); } catch (e) {}
            }
            var ctx = { el: fel || ta };
            if (fel) fel.addEventListener('keydown', function (e) { onKey(e); });
            $ta.on('froalaEditor.keyup', function () { check('froala', ctx); });
            $ta.on('froalaEditor.input', function () { check('froala', ctx); });
          }
          return true;
        }
      }
      if (!ta.__ffMention) {
        ta.__ffMention = true;
        var tctx = { el: ta };
        ta.addEventListener('keydown', function (e) { onKey(e); });
        ta.addEventListener('keyup', function () { check('textarea', tctx); });
        ta.addEventListener('input', function () { check('textarea', tctx); });
        ta.addEventListener('blur', function () { setTimeout(hide, 150); });
      }
      return true;
    }

    if (setup()) return;
    var iv = setInterval(function () { tries++; if (setup() || tries > 60) clearInterval(iv); }, 250);
  }

  function attachAll() {
    attach('forum-editor');
    attach('forum-reply-textarea');
  }

  document.addEventListener('click', function (e) {
    if (box && e.target !== box && !box.contains(e.target)) hide();
  });
  window.addEventListener('scroll', hide, true);

  window.forumMentionAttachAll = attachAll;
  window.forumMentionHide = hide;

  if (document.readyState !== 'loading') attachAll();
  else document.addEventListener('DOMContentLoaded', attachAll);
})();

/* Forge Forum — Görsel büyütme (lightbox) */
(function () {
  function initForumLightbox() {
    if (document.getElementById('forum-lightbox')) return;

    var overlay = document.createElement('div');
    overlay.id = 'forum-lightbox';
    overlay.className = 'forum-lightbox';
    overlay.innerHTML = '<button type="button" class="forum-lightbox__close" aria-label="Kapat">&times;</button><img class="forum-lightbox__img" alt="">';
    document.body.appendChild(overlay);

    var imgEl = overlay.querySelector('.forum-lightbox__img');

    function close() {
      overlay.classList.remove('is-open');
      document.body.classList.remove('forum-lightbox-open');
      imgEl.removeAttribute('src');
    }

    function open(src, alt) {
      imgEl.src = src;
      imgEl.alt = alt || '';
      overlay.classList.add('is-open');
      document.body.classList.add('forum-lightbox-open');
    }

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay || e.target.classList.contains('forum-lightbox__close')) close();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
    });

    window.addEventListener('click', function (e) {
      var img = e.target.closest ? e.target.closest('.forum-post-content img') : null;
      var link = e.target.closest ? e.target.closest('.forum-post-content a') : null;
      if (!img && link) img = link.querySelector('img');
      if (!img) return;
      if (img.closest('.emoji') || img.classList.contains('emoji')) return;
      if (!link) link = img.closest('a');
      var href = link ? (link.getAttribute('href') || '') : '';
      var isImageLink = href && /\.(jpe?g|png|gif|webp|bmp|avif|svg)(\?|#|$)/i.test(href);

      /* Harici sayfa bağlantısına karışma */
      if (link && href && !isImageLink) return;

      e.preventDefault();
      e.stopImmediatePropagation();
      open(isImageLink ? link.href : img.src, img.alt);
    }, true);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initForumLightbox);
  else initForumLightbox();
})();