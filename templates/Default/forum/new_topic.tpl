<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- MyBB-style New Topic Form Container -->
    <div class="mybb-category-block mt-4">
        <div class="mybb-cat-header">
            <span class="mybb-cat-title"><i class="fa fa-pencil-square-o mr-1.5"></i> Yeni Konu Aç</span>
            <span class="mybb-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        
        <div class="p-6">
            <form method="post" action="" class="space-y-5">
                <input type="hidden" name="user_hash" value="{dle_login_hash}">

                <!-- Kategori -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 flex items-center gap-1.5">
                        <i class="fa fa-folder-open text-blue-500"></i> Kategori Seçin
                    </label>
                    <div class="w-full relative">
                        {category_selector}
                    </div>
                </div>

                <!-- Başlık -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 flex items-center gap-1.5">
                        <i class="fa fa-tag text-blue-500"></i> Konu Başlığı
                    </label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        {prefix_selector}
                        <input type="text" name="title" required maxlength="255"
                               class="flex-1 border border-gray-300 rounded p-3 text-sm"
                               placeholder="Konunuzu özetleyen açıklayıcı bir başlık yazın...">
                    </div>
                </div>

                <!-- Mesaj -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 flex items-center gap-1.5">
                        <i class="fa fa-align-left text-blue-500"></i> Mesaj İçeriği
                    </label>
                    {forum_editor}
                </div>

                <!-- Etiketler -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 flex items-center gap-1.5">
                        <i class="fa fa-tags text-blue-500"></i> Konu Etiketleri (Virgülle ayırın)
                    </label>
                    <input type="text" name="tags"
                           class="w-full border border-gray-300 rounded p-3 text-sm"
                           placeholder="örneğin: dle, eklenti, forum">
                </div>

                <!-- Anket Ekle (Poll Creator) -->
                <div class="border border-gray-200 rounded p-4 bg-gray-50 mt-4">
                    <div class="font-bold text-sm mb-3 flex items-center gap-1.5 text-gray-700 cursor-pointer" onclick="document.getElementById('mybb-poll-fields').classList.toggle('hidden')">
                        <i class="fa fa-bar-chart text-blue-500"></i> Konuya Anket Ekle (İsteğe Bağlı - Göstermek için tıklayın)
                    </div>
                    <div id="mybb-poll-fields" class="hidden space-y-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Anket Sorusu</label>
                            <input type="text" name="poll_question" class="w-full border border-gray-300 rounded p-2.5 text-sm bg-white" placeholder="Ankette neyi sormak istersiniz?">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Seçenekler (Her satıra bir tane yazın)</label>
                            <textarea name="poll_options" rows="4" class="w-full border border-gray-300 rounded p-2.5 text-sm bg-white" placeholder="Seçenek 1&#10;Seçenek 2&#10;Seçenek 3"></textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Maksimum Seçilebilir Seçenek Sayısı</label>
                                <input type="number" name="poll_max_choices" value="1" min="1" class="w-full border border-gray-300 rounded p-2 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Anket Süresi (Gün, 0 = Sınırsız)</label>
                                <input type="number" name="poll_days" value="0" min="0" class="w-full border border-gray-300 rounded p-2 text-sm bg-white">
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" name="poll_multiple" id="poll_multiple" value="1" class="rounded border-gray-300">
                            <label for="poll_multiple" class="text-xs font-semibold text-gray-600 cursor-pointer">Birden fazla seçeneğe oy verilebilsin</label>
                        </div>
                    </div>
                </div>

                <!-- Gönder -->
                <div class="flex items-center justify-between pt-4 border-t">
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <i class="fa fa-info-circle text-gray-300 text-sm"></i> Görsel düzenleyici ile yazın
                    </span>
                    <button type="submit" name="submit_topic"
                            class="mybb-btn-action cursor-pointer">
                        Yeni Konuyu Aç
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
