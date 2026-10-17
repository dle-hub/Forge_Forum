<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- Forum-style Edit Post Container -->
    <div class="forum-category-block mt-4">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-edit mr-1.5"></i> {forum_edit_heading}</span>
            <span class="forum-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        
        <div class="p-6">
            <div class="mb-4 bg-gray-50 border p-4 rounded text-xs text-gray-600">
                <i class="fa fa-info-circle text-blue-500 mr-1.5"></i> {forum_edit_info_before} <span class="font-bold">{topic_title}</span> {forum_edit_info_after}
            </div>

            <form method="post" action="" class="space-y-5">
                <input type="hidden" name="user_hash" value="{dle_login_hash}">
                <input type="hidden" name="post_id" value="{post_id}">

                [first_post]
                [can-move-topic]
                <!-- Kategori Seçimi -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 flex items-center gap-1.5">
                        <i class="fa fa-folder-open text-blue-500"></i> {forum_new_cat_select}
                    </label>
                    <div class="w-full relative">
                        {category_selector}
                    </div>
                </div>
                [/can-move-topic]

                <!-- Konu Başlığı & Önek -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 flex items-center gap-1.5">
                        <i class="fa fa-tag text-blue-500"></i> {forum_new_title_label}
                    </label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        {prefix_selector}
                        <input type="text" name="title" required maxlength="255"
                               class="flex-1 border border-gray-300 rounded p-3 text-sm"
                               value="{title_val}" placeholder="{forum_edit_title_ph}">
                    </div>
                </div>

                <!-- Etiketler -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 flex items-center gap-1.5">
                        <i class="fa fa-tags text-blue-500"></i> {forum_new_tags_label}
                    </label>
                    <input type="text" name="tags"
                           class="w-full border border-gray-300 rounded p-3 text-sm"
                           value="{tags_val}" placeholder="{forum_new_tags_ph}">
                </div>

                <!-- Anket Düzenleme (Poll Editor) -->
                <div class="border border-gray-200 rounded p-4 bg-gray-50 mt-4">
                    <div class="font-bold text-sm mb-3 flex items-center gap-1.5 text-gray-700 cursor-pointer" onclick="document.getElementById('forum-poll-fields').classList.toggle('hidden')">
                        <i class="fa fa-bar-chart text-blue-500"></i> {forum_edit_poll_toggle}
                    </div>
                    <div id="forum-poll-fields" class="hidden space-y-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">{forum_new_poll_question}</label>
                            <input type="text" name="poll_question" class="w-full border border-gray-300 rounded p-2.5 text-sm bg-white" placeholder="{forum_new_poll_question_ph}" value="{poll_question}">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">{forum_new_poll_options}</label>
                            <textarea name="poll_options" rows="4" class="w-full border border-gray-300 rounded p-2.5 text-sm bg-white" placeholder="{forum_new_poll_options_ph}">{poll_options}</textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">{forum_new_poll_max}</label>
                                <input type="number" name="poll_max_choices" value="{poll_max_choices}" min="1" class="w-full border border-gray-300 rounded p-2 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">{forum_new_poll_days}</label>
                                <input type="number" name="poll_days" value="{poll_days}" min="0" class="w-full border border-gray-300 rounded p-2 text-sm bg-white">
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" name="poll_multiple" id="poll_multiple" value="1" {poll_multiple_checked} class="rounded border-gray-300">
                            <label for="poll_multiple" class="text-xs font-semibold text-gray-600 cursor-pointer">{forum_new_poll_multiple}</label>
                        </div>
                    </div>
                </div>
                [/first_post]

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 flex items-center gap-1.5">
                        <i class="fa fa-align-left text-blue-500"></i> {forum_new_text_label}
                    </label>
                    {forum_editor}
                </div>

                <div class="flex items-center justify-between pt-4 border-t">
                    <a href="{http_home_url}forum/topic/{topic_id}-konu.html#post-{post_id}" 
                       class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 font-bold cursor-pointer">
                        <i class="fa fa-arrow-left mr-1 text-gray-400"></i> {forum_edit_cancel}
                    </a>
                    <button type="submit" name="submit_edit"
                            class="forum-btn-action cursor-pointer">
                        {forum_edit_save}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
