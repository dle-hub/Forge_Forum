<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- Araç çubuğu -->
    <div class="forum-toolbar">
        <div class="forum-toolbar__links">
            <span class="forum-toolbar__title"><i class="fa fa-search" aria-hidden="true"></i> {forum_ui_search_title}</span>
            <span class="forum-toolbar__sep" aria-hidden="true"></span>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum"><i class="fa fa-arrow-left" aria-hidden="true"></i><span>{forum_ui_back_to_forum}</span></a>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=search&mode=new"><i class="fa fa-list-alt" aria-hidden="true"></i><span>{forum_ui_new_posts}</span></a>
            <a class="forum-toolbar__btn" href="{http_home_url}index.php?do=forum&action=search&mode=today"><i class="fa fa-calendar-o" aria-hidden="true"></i><span>{forum_ui_today_posts}</span></a>
        </div>
    </div>

    <!-- Arama formu -->
    <section class="forum-category-block mt-4">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-filter" aria-hidden="true"></i> {forum_ui_search_filters}</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        
        <div class="forum-rows">
        <div class="forum-panel">
            <form method="get" action="{http_home_url}index.php">
                <input type="hidden" name="do" value="forum">
                <input type="hidden" name="action" value="search">
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" name="q" value="{q}" minlength="3"
                               class="form-input"
                               placeholder="{forum_search_placeholder}">
                    </div>
                    <button type="submit"
                            class="primary-button flex items-center justify-center whitespace-nowrap">
                        <i class="fa fa-search mr-1.5"></i>{forum_search_btn}
                    </button>
                </div>

                <!-- Search Scope Options -->
                <div class="flex gap-4 mt-4 bg-gray-50 border p-3 rounded max-w-xs">
                    <label class="form-label">
                        <input type="radio" name="in" value="topics" {in_topics} class="cursor-pointer"> {forum_search_in_topics}
                    </label>
                    <label class="form-label">
                        <input type="radio" name="in" value="posts" {in_posts} class="cursor-pointer"> {forum_search_in_posts}
                    </label>
                </div>

                <!-- Gelişmiş Filtreler (yazar / tarih aralığı) -->
                <div class="flex flex-col sm:flex-row gap-3 mt-3">
                    <div class="flex-1">
                        <label class="form-label">{forum_search_author}</label>
                        <input type="text" name="author" value="{author}" class="form-input" placeholder="{forum_search_author_ph}" autocomplete="off">
                    </div>
                    <div class="flex-1">
                        <label class="form-label">{forum_search_date_from}</label>
                        <input type="date" name="date_from" value="{date_from}" class="form-input">
                    </div>
                    <div class="flex-1">
                        <label class="form-label">{forum_search_date_to}</label>
                        <input type="date" name="date_to" value="{date_to}" class="form-input">
                    </div>
                </div>
            </form>
        </div>
        </div>
    </section>

    <!-- Sonuç Sayısı -->
    [results]
    <p class="result-card__text" style="margin:var(--sp-2) 0 var(--sp-4);font-weight:600">
        <i class="fa fa-info-circle" style="color:var(--accent)" aria-hidden="true"></i> {forum_search_results_prefix} {total}
    </p>
    [/results]

    <!-- Sonuçlar Listesi (Results Table) -->
    <div class="forum-category-block">
        <header class="forum-cat-bar forum-cat-header">
            <h2 class="forum-cat-title"><i class="fa fa-list" aria-hidden="true"></i> {forum_search_found}</h2>
            <span class="forum-cat-toggle" role="button" tabindex="0"><i class="fa fa-minus" aria-hidden="true"></i></span>
        </header>
        <div class="forum-rows">
            {results}
        </div>
    </div>

    <!-- Sayfalama -->
    <div class="mt-4">
        {pagination}
    </div>
</div>
