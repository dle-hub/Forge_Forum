<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- Araç çubuğu -->
    <div class="forum-toolbar">
        <div class="forum-toolbar__links">
            <span class="forum-toolbar__title"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> {forum_ui_new_topic}</span>
        </div>
        <div class="forum-toolbar__end">
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum"><i class="fa fa-arrow-left" aria-hidden="true"></i><span>{forum_ui_back_to_forum}</span></a>
        </div>
    </div>

    <!-- Yeni konu formu -->
    <section class="forum-category-block mt-4">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-edit" aria-hidden="true"></i> {forum_ui_topic_details}</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        
        <div class="forum-rows">
        <div class="forum-panel">
            <form method="post" action="" class="space-y-5">
                <input type="hidden" name="user_hash" value="{dle_login_hash}">

                <!-- Kategori -->
                <div>
                    <label class="form-label">
                        <i class="fa fa-folder-open text-blue-500"></i> {forum_new_cat_select}
                    </label>
                    <div class="w-full relative">
                        {category_selector}
                    </div>
                </div>

                <!-- Başlık -->
                <div>
                    <label class="form-label">
                        <i class="fa fa-tag text-blue-500"></i> {forum_new_title_label}
                    </label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        {prefix_selector}
                        <input type="text" name="title" id="forum-topic-title" required maxlength="255"
                               class="form-input"
                               placeholder="{forum_new_title_ph}">
                    </div>
                </div>

                <!-- Mesaj -->
                <div>
                    <label class="form-label">
                        <i class="fa fa-align-left text-blue-500"></i> {forum_new_text_label}
                    </label>
                    <div id="forum-draft-notice" class="forum-draft-notice" style="display:none;">
                        <i class="fa fa-floppy-o" aria-hidden="true"></i> {forum_new_draft_loaded} (<span class="forum-draft-notice__time"></span>).
                        <button type="button" class="forum-btn-mini" onclick="forumDiscardDraft('new_topic',0,'forum-editor')"><i class="fa fa-trash"></i> {forum_new_draft_discard}</button>
                    </div>
                    {forum_editor}
                </div>

                <!-- Etiketler -->
                <div>
                    <label class="form-label">
                        <i class="fa fa-tags text-blue-500"></i> {forum_new_tags_label}
                    </label>
                    <input type="text" name="tags"
                           class="form-input"
                           placeholder="{forum_new_tags_ph}">
                </div>

                <!-- Anket Ekle (Poll Creator) -->
                <div class="border border-gray-200 rounded p-4 bg-gray-50 mt-4">
                    <div class="font-bold text-sm mb-3 flex items-center gap-1.5 text-gray-700 cursor-pointer" onclick="document.getElementById('forum-poll-fields').classList.toggle('hidden')">
                        <i class="fa fa-bar-chart text-blue-500"></i> {forum_new_poll_toggle}
                    </div>
                    <div id="forum-poll-fields" class="hidden space-y-4 pt-2">
                        <div>
                            <label class="form-label">{forum_new_poll_question}</label>
                            <input type="text" name="poll_question" class="form-input" placeholder="{forum_new_poll_question_ph}">
                        </div>
                        <div>
                            <label class="form-label">{forum_new_poll_options}</label>
                            <textarea name="poll_options" rows="4" class="form-input" placeholder="{forum_new_poll_options_ph}"></textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">{forum_new_poll_max}</label>
                                <input type="number" name="poll_max_choices" value="1" min="1" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">{forum_new_poll_days}</label>
                                <input type="number" name="poll_days" value="0" min="0" class="form-input">
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" name="poll_multiple" id="poll_multiple" value="1" class="rounded border-gray-300">
                            <label for="poll_multiple" class="form-hint">{forum_new_poll_multiple}</label>
                        </div>
                    </div>
                </div>

                {topic_captcha}

                <!-- Gönder -->
                <div class="flex items-center justify-between pt-4 border-t">
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <i class="fa fa-info-circle text-gray-300 text-sm"></i> {forum_new_editor_hint}
                    </span>
                    <button type="submit" name="submit_topic"
                            class="primary-button">
                        {forum_new_submit}
                    </button>
                </div>
            </form>
        </div>
        </div>
    </section>
</div>

<script>
(function () {
    if (typeof forumDraftInit !== 'function') return;
    if ('{drafts_enabled}' !== 'true') return;
    if (!document.getElementById('forum-editor')) return;
    var catSel = document.querySelector('select[name="cat_id"]');
    forumDraftInit({
        type: 'new_topic',
        topicId: 0,
        catId: catSel ? catSel.value : 0,
        titleFieldId: 'forum-topic-title',
        editorId: 'forum-editor'
    });
})();
</script>
