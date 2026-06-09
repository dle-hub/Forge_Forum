<div class="forum-wrapper">
    <!-- Breadcrumb -->
    {forum_breadcrumb}

    <!-- MyBB Musk Secondary Navigation Strip -->
    <div class="mybb-actions-strip">
        <div class="flex gap-4 text-xs font-bold text-gray-700 flex-wrap">
            <a href="{http_home_url}index.php?do=forum&action=search&mode=new" class="mybb-strip-link"><i class="fa fa-list-alt"></i> Yeni Mesajları Gör</a>
            <a href="{http_home_url}index.php?do=forum&action=search&mode=today" class="mybb-strip-link"><i class="fa fa-calendar-o"></i> Bugünkü Mesajları Gör</a>
            [is-logged]
            <a href="{http_home_url}index.php?do=forum&action=notifications" class="mybb-notif-nav-link">
                <i class="fa fa-bell"></i> Bildirimler
                [has-forum-notifs]<span class="notif-nav-badge">{forum_notif_count}</span>[/has-forum-notifs]
            </a>
            [/is-logged]
        </div>
        <form method="get" action="{http_home_url}index.php" class="flex items-center gap-1">
            <input type="hidden" name="do" value="forum">
            <input type="hidden" name="action" value="search">
            <input type="text" name="q" placeholder="Ara..." required minlength="3" class="px-2.5 py-1 border text-xs rounded" style="width: 140px; background: #ffffff;">
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 border border-gray-300 px-2 py-1 rounded text-xs cursor-pointer"><i class="fa fa-search text-gray-500"></i></button>
        </form>
    </div>

    <!-- Category Block Container (Full Width) -->
    <div class="forum-categories">
        {categories}
    </div>

    <!-- MyBB Board Statistics Panel -->
    <div class="mybb-stats-card">
        <div class="mybb-cat-header">
            <span class="mybb-cat-title"><i class="fa fa-bar-chart mr-1.5"></i> Forum İstatistikleri</span>
            <span class="mybb-cat-toggle"><i class="fa fa-minus"></i></span>
        </div>
        
        <!-- Subsection: Who's Online -->
        <div class="mybb-stats-section">
            <div class="mybb-stats-sec-title">Kimler Çevrimiçi <span class="text-gray-400 text-xs font-normal">[Tam Liste]</span></div>
            <div class="mybb-stats-sec-content mt-1">
                <p>{online_summary}</p>
                <div class="mt-1 font-semibold flex flex-wrap gap-2 text-sm">{online_list}</div>
            </div>
        </div>

        <!-- Subsection: Board Statistics -->
        <div class="mybb-stats-section">
            <div class="mybb-stats-sec-title">Forum İstatistikleri</div>
            <div class="mybb-stats-sec-content mt-1 space-y-1">
                <p>Üyelerimiz toplamda <span class="font-bold text-gray-700">{total_posts} mesaj</span> yazdı (<span class="font-bold text-gray-700">{total_threads} konu</span>).</p>
                <p>Şu anda kayıtlı <span class="font-bold text-gray-700">{total_members} üyemiz</span> var.</p>
                <p>Aramıza yeni katılan üyemize merhaba diyelim: {newest_member}</p>
            </div>
        </div>
    </div>
</div>
