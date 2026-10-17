<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- Araç çubuğu -->
    <div class="forum-toolbar">
        <div class="forum-toolbar__links">
            <span class="forum-toolbar__title"><i class="fa fa-search"></i> {forum_ui_search_title}</span>
            <span class="forum-toolbar__sep" aria-hidden="true"></span>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum"><i class="fa fa-arrow-left"></i><span>{forum_ui_back_to_forum}</span></a>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=search&mode=new"><i class="fa fa-list-alt"></i><span>{forum_ui_new_posts}</span></a>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=search&mode=today"><i class="fa fa-calendar-o"></i><span>{forum_ui_today_posts}</span></a>
        </div>
    </div>

    <!-- Arama formu -->
    <div class="forum-category-block mt-4">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-filter mr-1.5"></i> {forum_ui_search_filters}</span>
            <span class="forum-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        
        <div class="p-6">
            <form method="get" action="{http_home_url}index.php">
                <input type="hidden" name="do" value="forum">
                <input type="hidden" name="action" value="search">
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" name="q" value="{q}" minlength="3"
                               class="w-full border border-gray-300 rounded p-2.5 text-sm"
                               placeholder="{forum_search_placeholder}">
                    </div>
                    <button type="submit"
                            class="forum-btn-action cursor-pointer flex items-center justify-center whitespace-nowrap">
                        <i class="fa fa-search mr-1.5"></i>{forum_search_btn}
                    </button>
                </div>

                <!-- Search Scope Options -->
                <div class="flex gap-4 mt-4 bg-gray-50 border p-3 rounded max-w-xs">
                    <label class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-600 cursor-pointer select-none">
                        <input type="radio" name="in" value="topics" {in_topics} class="cursor-pointer"> {forum_search_in_topics}
                    </label>
                    <label class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-600 cursor-pointer select-none">
                        <input type="radio" name="in" value="posts" {in_posts} class="cursor-pointer"> {forum_search_in_posts}
                    </label>
                </div>

                <!-- Gelişmiş Filtreler (yazar / tarih) -->
                <div class="flex flex-col sm:flex-row gap-3 mt-3">
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">{forum_search_author}</label>
                        <input type="text" name="author" value="{author}" class="w-full border border-gray-300 rounded p-2.5 text-sm" placeholder="{forum_search_author_ph}" autocomplete="off">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">{forum_search_date_from}</label>
                        <input type="date" name="date_from" value="{date_from}" class="w-full border border-gray-300 rounded p-2.5 text-sm">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">{forum_search_date_to}</label>
                        <input type="date" name="date_to" value="{date_to}" class="w-full border border-gray-300 rounded p-2.5 text-sm">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sonuç Sayısı (Search Results Count Bar) -->
    [results]
    <div class="forum-actions-strip mt-4">
        <div class="text-xs font-bold text-gray-700">
            <i class="fa fa-info-circle text-blue-500 mr-1"></i> {forum_search_results_prefix} {total}
        </div>
    </div>
    [/results]

    <!-- Sonuçlar Listesi (Results Table) -->
    <div class="forum-category-block">
        <div class="forum-cat-header">
            <span class="forum-cat-title"><i class="fa fa-list mr-1.5"></i> {forum_search_found}</span>
            <span class="forum-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        <div class="forum-sub-header">
            <div class="forum-col-forum">{forum_search_col_content}</div>
            <div class="forum-col-stats">{forum_search_col_details}</div>
            <div class="forum-col-lastpost">{forum_search_col_activity}</div>
        </div>
        <div class="forum-cat-rows">
            {results}
        </div>
    </div>

    <!-- Sayfalama -->
    <div class="mt-4">
        {pagination}
    </div>
</div>
