<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- MyBB-style Search Dashboard Container -->
    <div class="mybb-category-block mt-4">
        <div class="mybb-cat-header">
            <span class="mybb-cat-title"><i class="fa fa-search mr-1.5"></i> Forumda Arama Yap</span>
            <span class="mybb-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        
        <div class="p-6">
            <form method="get" action="{http_home_url}index.php">
                <input type="hidden" name="do" value="forum">
                <input type="hidden" name="action" value="search">
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" name="q" value="{q}" required minlength="3"
                               class="w-full border border-gray-300 rounded p-2.5 text-sm"
                               placeholder="Aramak istediğiniz kelimeyi girin (En az 3 karakter)...">
                    </div>
                    <button type="submit"
                            class="mybb-btn-action cursor-pointer flex items-center justify-center whitespace-nowrap">
                        <i class="fa fa-search mr-1.5"></i>Forumda Ara
                    </button>
                </div>

                <!-- Search Scope Options -->
                <div class="flex gap-4 mt-4 bg-gray-50 border p-3 rounded max-w-xs">
                    <label class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-600 cursor-pointer select-none">
                        <input type="radio" name="in" value="topics" {in_topics} class="cursor-pointer"> Konu Başlığı
                    </label>
                    <label class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-600 cursor-pointer select-none">
                        <input type="radio" name="in" value="posts" {in_posts} class="cursor-pointer"> Mesaj İçeriği
                    </label>
                </div>
            </form>
        </div>
    </div>

    <!-- Sonuç Sayısı (Search Results Count Bar) -->
    [results]
    <div class="mybb-actions-strip mt-4">
        <div class="text-xs font-bold text-gray-700">
            <i class="fa fa-info-circle text-blue-500 mr-1"></i> Arama Sonuçları: {total}
        </div>
    </div>
    [/results]

    <!-- Sonuçlar Listesi (Results Table) -->
    <div class="mybb-category-block">
        <div class="mybb-cat-header">
            <span class="mybb-cat-title"><i class="fa fa-list mr-1.5"></i> Bulunan Sonuçlar</span>
            <span class="mybb-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="mybb-sub-header">
            <div class="mybb-col-forum">Eşleşen Forum İçeriği</div>
            <div class="mybb-col-stats">Detaylar</div>
            <div class="mybb-col-lastpost">Son Aktivite</div>
        </div>
        <div class="mybb-cat-rows">
            {results}
        </div>
    </div>

    <!-- Sayfalama -->
    <div class="mt-4">
        {pagination}
    </div>
</div>
