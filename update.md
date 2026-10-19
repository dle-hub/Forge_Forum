# Forge Forum — Güncelleme Notları

**Sürüm:** 1.2.0 — **Tarih:** 19.10.2026

---

## 🆕 Yeni Özellikler

- **Konu aboneliği.** Konu sayfasına **Abone Ol / Abonelikten Çık** butonu, **Aboneliklerim** sayfası (`index.php?do=forum&action=subscriptions`), yanıt/konu açanın otomatik aboneliği ve abonelere site içi bildirim. `engine/ajax/forum_subscribe.php`, `engine/modules/forum/actions/subscriptions.php`, `templates/{skin}/forum/subscriptions.tpl`
- **Konu moderasyonu: taşı / birleştir / böl.** `engine/ajax/forum_moderation.php` (`move_topic`, `merge_topic`, `split_topic`).
- **İmza.** Kullanıcı imzası gönderi altında BBCode fallback ile render edilir. `engine/modules/forum/actions/topic.php`, `templates/{skin}/forum/post.tpl`
- **Etiketler.** Etiket sayfası + SEO URL (`/forum/etiket/{ad}/`) + etiketçe filtrelenmiş konu listesi. `engine/modules/forum/actions/tag.php`, `templates/{skin}/forum/tag.tpl`
- **Kategori/tüm forum "okundu işaretle"** ve konu başına kalıcı okundu takibi (`_forum_readlog`). `engine/ajax/forum_read.php`
- **E-posta bildirimi.** Abonelere ve konu yazarına yeni cevap geldiğinde DLE mail API'si (`dle_mail`) ile e-posta. Şablon `_email` tablosunda `forum` adıyla saklanır (yönetimden düzenlenebilir yapıda); gönderen hariç, alıcı tekilleştirilir. Yönetim > Forum > Ayarlar > **E-posta Bildirimi** ile açılıp kapatılır. `engine/modules/forum/email_helpers.php`, `engine/modules/forum/init.php`, `engine/modules/forum/actions/reply.php`, `engine/inc/forum/settings.php`
- **RSS akışı.** `/forum/rss/` (son konular), `/forum/rss/{kategori}/` (kategoriye özel) ve `?type=posts` (son mesajlar); RSS 2.0 + Atom self-link. Forum ana sayfasına **RSS** bağlantısı eklendi. `engine/modules/forum/actions/feed.php`, `engine/modules/forum/actions/main.php`, `templates/{skin}/forum/index.tpl`
- **Konu puanlama (5 yıldız).** Üyeler konuları 1–5 arası puanlar; ortalama/kullanan sayısı gösterilir, kendi konusunu puanlayamaz. Yönetimden açılıp kapatılır. `engine/ajax/forum_rate.php`, `_forum_topic_ratings`, `engine/modules/forum/actions/topic.php`, `engine/modules/forum/editor_helpers.php`
- **Çoklu alıntı.** Her mesajda **ALINTI+** ile birden fazla mesaj seçilir; yapışkan çubuktan hepsi tek seferde editöre eklenir. Alıntı ucu artık `post_ids` (toplu) destekler. `engine/ajax/forum_quote.php`, `templates/{skin}/forum/post.tpl`, `topic.tpl`
- **Gelişmiş arama.** Anahtar kelimeye ek olarak **yazar** ve **tarih aralığı** filtreleri (konu başlığı / mesaj içeriği kapsamında). `engine/modules/forum/actions/search.php`, `templates/{skin}/forum/search.tpl`
- **"Çözüldü" işareti.** Konu sahibi veya moderatör konuyu çözüldü olarak işaretler; toolbar'da rozet + buton. `engine/ajax/forum_solve.php`, `_forum_topics.is_solved`
- **Benzer konular.** Ortak etiket (yoksa aynı kategori) temelinde konu altında ilişkili konu listesi. `engine/modules/forum/actions/topic.php`
- **Kim görüntülüyor.** Konuyu son 2 dakikada görüntüleyen üyeler gösterilir (`_forum_viewing` varlık tablosu). `engine/modules/forum/actions/topic.php`
- **Ön yüz moderasyon paneli.** Moderatör/yöneticiler için `/forum/mod/` sayfası: onay bekleyen konular, mesaj sayısı ve açık şikayetler (işlem bağlantılarıyla). `engine/modules/forum/actions/mod.php`, `templates/{skin}/forum/mod.tpl`
- **Doğrudan moderasyon aksiyonları.** Mod panelinden artık **Onayla / Sil** (bekleyen konular) ve **Çözüldü / Reddet / Çöpe At** (şikayetler) doğrudan yapılabiliyor (mevcut `forum_approval` / `forum_reports` uçlarına bağlı, CSRF + yetki korumalı). `templates/{skin}/forum/mod.tpl`
- **En iyi cevap (best answer).** Konu sahibi/moderatör bir mesajı en iyi cevap olarak işaretler (toggle); mesajda rozet, konu başında özet kutusu, konu otomatik "çözüldü" olur. `engine/ajax/forum_best_answer.php`, `_forum_topics.best_post_id`, `templates/{skin}/forum/post.tpl`, `topic.tpl`
- **Düzenleme geçmişi.** Düzenlenen mesajlarda "Düzenlendi" ibaresi + (yazar/mod için) eski sürümleri listeleyen geçmiş sayfası. `engine/modules/forum/actions/history.php`, `templates/{skin}/forum/history.tpl`
- **Open Graph / sosyal paylaşım.** Konu sayfalarında `og:title/description/url/image` + canonical (DLE'nin `$social_tags`/`$meta` altyapısıyla); og:image konudaki ilk görselden alınır. `engine/modules/forum/actions/topic.php`
- **Kullanıcı takip (follow).** Profil sayfasında **Takip Et / Takibi Bırak** + takipçi/takip sayaçları; takip edilen üye yeni konu açınca takipçilere bildirim. `engine/ajax/forum_follow.php`, `_forum_follows`, `new_topic.php`, `profile.php`
- **Forum XML sitemap.** `/forum/sitemap.xml` — forum kökü, kategoriler ve tüm onaylı konular (lastmod/changefreq/priority). `engine/modules/forum/actions/sitemap.php`
- **Kimler çevrimiçi.** `/forum/…&action=online` — son 15 dakikada aktif üyeler (avatar, ad, grup). `engine/modules/forum/actions/online.php`, `templates/{skin}/forum/online.tpl`
- **Toplu moderasyon.** Mod panelinde onay bekleyen konular için **Tümünü Seç + Seçilenleri Onayla / Sil** (`bulk_approve` / `bulk_reject`). `engine/ajax/forum_approval.php`, `templates/{skin}/forum/mod.tpl`
- **Mesaj taslakları (auto-save).** Konu açma ve hızlı cevapta metin her ~25 sn'de ve sayfadan ayrılırken kaydedilir; geri dönüldüğünde taslak yüklenir + "Taslağı Sil". Gönderimde otomatik temizlenir. `engine/ajax/forum_draft.php`, `_forum_drafts`, `templates/{skin}/forum/forum.js`, `topic.tpl`, `new_topic.tpl`
- **Kategori bazlı moderatör.** Yönetimden kategoriye moderatör (kullanıcı adı/ID) atanır; bu üyeler yalnızca o kategorideki konuları yönetebilir (düzenle/taşı/birleştir/böl/sil/çözüldü/en iyi cevap). `_forum_cat_mods`, `engine/inc/forum/categories.php`, `editor_helpers.php`, `topic.php`, `forum_moderation.php`, `forum_solve.php`, `forum_best_answer.php`
- **Yönetim anahtarları (Ek Özellikler).** Yukarıdaki modüllerin tamamı artık **Forum → Ayarlar → "Ek Özellikler"** panelinden açılıp kapatılabilir: RSS, XML Sitemap, Çözüldü, En İyi Cevap, Benzer Konular, Kim Görüntülüyor, Kimler Çevrimiçi, Kullanıcı Takip, Taslaklar, Düzenleme Geçmişi, Open Graph (çoklu alıntı `enable_quotes` ile kontrol edilir). `engine/inc/forum/settings.php`, `init.php` ve ilgili tüm modül/AJAX dosyaları
- **İki tema seti de güncellendi.** Tüm yeni özellikler hem **`templates/air_tema`** (Air) hem **`templates/Default`** şablon setine işlendi; eksik şablonlar (`subscriptions/tag/mod/online/history`) Default'a da eklendi, ortak bileşen CSS'i (`.forum-node`, `.forum-rows`, `.result-card`, `.soft-button`, `.read-link` vb.) ve taslak JS'i Default'a taşındı. `templates/{air_tema,Default}/forum/*`

### (bu sürüm — devam)

- **Okunmamış rozeti + "İlk okunmamışa git".** Kategori sayfası başlığında okunmamış konu sayısı rozeti, ana sayfada alt forum rozetleri ve okunmamış konularda doğru sayfaya + `#post-ID`'ye giden **"İlk okunmamışa git"** bağlantısı (sayfalama destekli). Yönetimden açılıp kapatılır (`enable_unread_badge`). `engine/modules/forum/actions/{main,category}.php`, `engine/modules/forum/editor_helpers.php`, `templates/{skin}/forum/{topic_row,subforum_row,category}.tpl`
- **Kategori aboneliği.** Kategori sayfasında **Kategoriye Abone Ol / Kategoriden Çık**; o kategoriye yeni konu açılınca abonelere site içi bildirim + (açıksa) e-posta (yeni `forum_cat` e-posta şablonu, kategori adıyla). Yönetimden açılıp kapatılır (`enable_cat_subscribe`). `engine/ajax/forum_cat_subscribe.php`, `_forum_cat_subs`, `engine/modules/forum/email_helpers.php`, `new_topic.php`, `notifications.php`, `templates/{skin}/forum/category.tpl`
- **@mention otomatik tamamlama.** Editörlerde (konu açma / düzenleme / hızlı cevap) `@` yazınca kullanıcı öneri kutusu (avatar + isim, eşleşen kısım vurgulu); **↑/↓**, **Enter/Tab**, **Esc** ve fare ile seçim; düz `textarea`, **Froala** ve **TinyMCE** desteklenir. Mevcut `enable_mentions` anahtarına bağlıdır. `engine/ajax/forum_mention_search.php`, `templates/{skin}/forum/forum.js`, `forum.css`
- **Detaylı istatistik sayfası.** `?do=forum&action=statistics` — genel bakış kutuları (konu/mesaj/üye/bugün/günlük ortalama), kategori tablosu (canlı konu/mesaj + son mesaj), en aktif üyeler, en çok görüntülenen ve en çok cevaplanan konular. Ana sayfaya **İstatistikler** bağlantısı. Yönetimden açılıp kapatılır (`enable_statistics`). `engine/modules/forum/actions/statistics.php`, `templates/{skin}/forum/statistics.tpl`
- **CAPTCHA / anti-spam (yeni konu + cevap).** DLE native captcha entegrasyonu: reCAPTCHA kapalıysa **resim doğrulama kodu** (`antibot.php` + yenileme), açıksa **Google reCAPTCHA / hCaptcha / Turnstile / Yandex SmartCaptcha**. Yalnızca DLE grup izni ("Resim doğrulaması" / `captcha`) açık gruplarda gösterilir. Yönetimden ayrı ayrı açılıp kapatılır (`enable_captcha_topic`, `enable_captcha_reply`). `engine/modules/forum/antibot_helpers.php`, `new_topic.php`, `reply.php`, `topic.php`, `templates/{skin}/forum/{new_topic,topic}.tpl`
- **Yönetim anahtarları güncellendi (devam).** "Ek Özellikler" paneline 5 yeni anahtar eklendi: **Okunmamış Rozeti**, **Kategori Aboneliği**, **İstatistik Sayfası**, **CAPTCHA (Yeni Konu)**, **CAPTCHA (Cevap)**. `engine/inc/forum/settings.php`, `init.php`
- **Tek tip sayfa araç çubuğu.** İstatistik, bildirimler, arama ve yeni konu sayfalarındaki dağınık başlık blokları (`.result-card` / `.forum-actions-strip` / ayrı `.forum-cat-bar`) ana sayfa, kategori ve konu sayfalarıyla aynı **`.forum-toolbar`** bileşenine taşındı; solda başlık + rozet, sağda hızlı eylem (Kimler Çevrimiçi / Hepsini Okundu İşaretle / Foruma Dön), aramada ek hızlı bağlantılar. Form ve liste blokları "Arama Filtreleri" / "Konu Bilgileri" başlıklarıyla korunuyor. `templates/{air_tema,Default}/forum/{statistics,notifications,search,new_topic}.tpl`
- **Ana sayfa istatistik bloğu yenilendi.** Alt blok iki panelli düzene geçti: solda yeşil canlı nokta + "Kimler Çevrimiçi", avatarlı üye çipleri ve sağ üstte **"Tam Liste →"** bağlantısı; sağda Konu / Mesaj / Üye mini sayaç kartları ve kesikli çerçeveli "Aramıza yeni katılan" satırı. Çevrimiçi üyeler artık `foto` alanından avatar (Gravatar/dosya/URL) ile gösteriliyor, mobilde paneller alt alta iniyor. `engine/modules/forum/actions/main.php`, `templates/{air_tema,Default}/forum/{index.tpl,forum.css}`
- **Anket yönetimi DLE düzeyine çıkarıldı.** Admin > Forum > Anketler eylem menüsü DLE'nin anket menüsüyle eşleştirildi: **Anket sonuçları** (oy dağılımı, yüzdeler, renkli dağılım çubukları, oy veren üye sayısı), **Düzenle** (soru, seçenekler, bitiş tarihi, çoklu seçim, maksimum seçim, kapalı durumu), **Durdur / Devam Ettir**, **Temizle** (tüm oyları sıfırla) ve **Sil**. Düzenlemede oy almış seçenekler korunur, yalnızca oy almamış fazla seçenekler silinir. `engine/inc/forum/polls.php`, `language/{Turkish,English,Russian}/forum.lng`
- **Ön yüz "Konuyu Düzenle" + izin ayarları.** Konu sayfasındaki **Yönetici** menüsüne (yetkisi yoksa **Konu İşlemleri** etiketiyle) **Konuyu Düzenle** eklendi; ilk mesaj düzenleme formuna gider. Yönetim > Forum > Ayarlar'a iki anahtar eklendi: **Konu Sahibi Düzenleyebilsin** ve **Kategori Moderatörleri Düzenleyebilsin**. Konu sahibi başlık/önek/etiket/ilk mesajı düzenleyebilir ancak kategori taşıyamaz; kategori moderatörleri yalnızca yetkili oldukları kategoride düzenleyebilir. Ayrıca ilk mesaj düzenlenirken anket seçenekleri artık silinip yeniden oluşturulmuyor, **mevcut oylar korunuyor**. `engine/modules/forum/actions/{topic,edit}.php`, `engine/inc/forum/settings.php`, `templates/{air_tema,Default}/forum/{topic,edit}.tpl`, `forum.css`, `language/{Turkish,English,Russian}/forum.lng`
- **Arayüz metinleri tamamen dil dosyasına taşındı.** Ana sayfa, kategori, istatistik, bildirimler, arama, yeni konu, konu ve düzenleme sayfalarındaki görünür etiketler, butonlar, placeholder'lar ve JS bildirimleri `{forum_ui_*}`, `{forum_stats_*}`, `{forum_search_*}`, `{forum_new_*}`, `{forum_edit_*}` etiketlerine çevrildi; değerler `forum_set_ui_lang()` ile `language/{Turkish,English,Russian}/forum.lng` dosyalarından yükleniyor. `engine/modules/forum/editor_helpers.php`, `engine/modules/forum/actions/*`, `templates/{air_tema,Default}/forum/*.tpl`, `language/{Turkish,English,Russian}/forum.lng`

## ✅ Düzeltmeler (bu sürüm)

- **Kapalı kategori erişim açığı (P0).** Kategori `permissions` alanı yalnızca kategori sayfasında denetleniyordu; konuya doğrudan bağlantıyla (`/forum/topic/...`) girildiğinde, RSS, arama, etiket ve sitemap çıktılarında kapalı kategorilerin içeriği görünebiliyordu. Ortak `forum_cat_access_allowed()` / `forum_cat_access_where()` yardımcıları eklendi; tüm giriş noktaları artık kategori izinlerini denetliyor (yönetici grubu 1 her zaman erişir). `engine/modules/forum/permission_helpers.php`, `engine/modules/forum/init.php`, `engine/modules/forum/actions/{topic,reply,category,main,new_topic,search,feed,sitemap,tag,statistics,profile}.php`, `engine/ajax/forum_quote.php`

- **Kurulum / kaldırma tutarlılığı (P1).** Kaldırma işlemi artık 30 forum tablosunun tamamını siliyor (`_forum_topic_ratings`, `_forum_viewing`, `_forum_follows`, `_forum_drafts`, `_forum_cat_mods` eklendi). `forum_schema_v*.lock` dosyaları kaldırmada temizleniyor; `prefix_id` ilk kurulumdaki `CREATE TABLE` içine eklendi ve `init.php` artık kilit dosyasına güvenmeden kolonu her istekte doğruluyor. `forge_forum.xml`, `engine/modules/forum/init.php`

- **CSS/JS otomatik yükleme ve tema fallback (P1).** `forum.css` / `forum.js`, aktif temanın `main.tpl` dosyasında elle dahil edilmemişse `$tpl->css_array` / `$tpl->js_array` üzerinden otomatik ekleniyor. Aktif temada `forum/` klasörü yoksa (ör. Smart) şablonlar `templates/Default/forum` üzerinden yükleniyor; böylece her tema için ayrı kopya gerekmiyor. `engine/modules/forum/init.php`, `actions/{main,category,notifications}.php`

- **SEO rotaları (P2).** `/forum/sitemap.xml` rotası eklendi. `/forum/topic/{id}-{ad}/page/last/` için özel rota tanımlandı; konu rotalarındaki `{news_name}` deseni `[^/]+` ile sınırlandırılarak `page/last` bölümünün konu adına yutulması engellendi. `forge_forum.xml`

- **Yerelleştirme (P3).** Başlangıç rütbeleri ve yönetim bölümü adı artık kurulumda `$config['langs']` değerine göre TR/EN/RU oluşturuluyor; güncellemede yalnızca değiştirilmemiş varsayılan kayıtlar taşınıyor (özel rütbeler korunuyor). `mysqlenable` içindeki sabit Türkçe bölüm ekleme kaldırıldı; yerelleştirilmiş ekleme `phpenable`'a taşındı (kapat/aç sonrası da doğru dil). Kalan sabit Türkçe arayüz metinleri (`post`, `profile`, `mod`, `online`, `history`, `topic_row`, `subforum_row`, `notification_row`, `topic`, `statistics`, `category` şablonları) `{forum_ui_*}` etiketlerine ve dil dosyalarına taşındı. `forge_forum.xml`, `engine/modules/forum/editor_helpers.php`, `engine/modules/forum/actions/*`, `templates/{air_tema,Default}/forum/*.tpl`, `language/{Turkish,English,Russian}/forum.lng`

- **Temiz kurulum doğrulaması (P1/P3).** DLE 21.0.104 sıfırdan kuruldu (`dle_fresh`), plugin yönetim panelindeki yükleme formu üzerinden kuruldu. 30 forum tablosu, Rusça rütbeler ve "Управление форумом" bölümü doğrulandı. Kaldırma 30 tabloyu ve 8 şema kilidini temizledi; yeniden etkinleştirme yerelleştirilmiş bölümü geri ekledi. Ön yüz, kapalı kategori, arama/RSS/sitemap, `/forum/sitemap.xml` ve `page/last` testleri geçti.

- **Kategori kaydetme fatal hatası.** Yönetim > Forum > Kategoriler'de kaydetme sırasında `forum_admin_sync_cat_mods()` tanımsız kalıyordu. Koşullu fonksiyon tanımları PHP tarafından hoist edilmediği için POST işleyicisinden önce çalışmıyordu; moderatör yardımcıları (`forum_admin_sync_cat_mods`, `forum_admin_cat_mods_str`) dosyanın üstüne taşındı. `engine/inc/forum/categories.php`

- **`get_row()` / `INSERT` çakışması.** Yanıt sonrası abone bildirim döngüsünde, döngü gövdesindeki `INSERT` DLE'nin `$db->query_id` değerini ezip bir sonraki `get_row()` çağrısını ölümcül hataya düşürüyordu (birden fazla abone olduğunda). Satırlar artık önce toplanıyor, sonra yazılıyor. `engine/modules/forum/actions/reply.php`
- **`follow_topic` bildirimi boş görünüyordu.** Takip edilen üye yeni konu açınca oluşan bildirimler, bildirim sayfasında tip bloğu olmadığı için boş metinle render ediliyordu; `follow_topic` ve yeni `cat_topic` tipleri için metin/ikon blokları eklendi. `engine/modules/forum/actions/notifications.php`, `templates/{skin}/forum/notification_row.tpl`

### Yeniden paket — editör, yükleme ve BBCode düzeltmeleri (19.10.2026)

- **TinyMCE görsel yüklemesi.** `forum_upload.class.php` dosya alanını tespit ediyor ancak `$this->field_name` değişkenine atamıyordu; `file` alanıyla gelen yüklemeler "No file was uploaded" ile reddediliyordu. `engine/classes/uploads/forum_upload.class.php`
- **Görsellerin kayıtta silinmesi.** DLE'nin HTMLPurifier derlemesinde doctype modülleri bulunmadığından `img` elementi tanımsızdı ve sanitizer görselleri siliyordu. `forum_sanitize_html()` içinde `img` (src/alt/width/height/style) elle tanımlandı; `new_topic` ve `reply` kopya purifier blokları merkezi fonksiyona bağlandı. `engine/modules/forum/editor_helpers.php`, `actions/{new_topic,reply}.php`
- **Editörde çift HTML kaçışı.** `forum_prepare_editor_content()` zaten kaçış yaptığı halde `edit.php` bir kez daha `htmlspecialchars` uyguluyordu; içerik `&lt;p&gt;` olarak kaydediliyordu. Fazla kaçış kaldırıldı. `engine/modules/forum/actions/edit.php`
- **BBCode çevrilmiyordu.** DLE'nin `BB_Parse()` sürümü `[b]`, `[i]`, `[u]`, `[s]`, `[url]` gibi basit etiketleri işlemiyor; eklentinin hafif parser'ı yalnızca `BB_Parse` yoksa çalışıyordu. Parser artık her modda önce çalışıyor; `[s]` çıktısı `<s>` olarak düzeltildi (imza dahil). `engine/modules/forum/actions/topic.php`
- **Editörler arası görsel sızıntısı.** Yeni konu/cevap editörleri `news_id=0` gönderdiği için yükleme modalı kullanıcının tüm `post_id=0` yüklemelerini listeliyordu. Cevap editörü `-konu_id`, yeni konu editörü benzersiz negatif taslak anahtarı kullanıyor; modal listesi `post_id = news_id AND user_id` ile sınırlandı. `engine/ajax/forum_upload.php`, `actions/{topic,new_topic}.php`
- **Çift yanıt bildirimi.** Konu sahibi hem abone hem yazar olduğunda her yanıt için iki bildirim gidiyordu; abone sorgusunda konu sahibi dışlandı. `engine/modules/forum/actions/reply.php`
- **Arama sayfası başlığı.** `<title>` içine kaçışmış `noindex` meta etiketi basılıyordu; `$metatags['robots']` kullanımına çevrildi. `engine/modules/forum/actions/search.php`
- **Görsel büyütme (lightbox).** Forum gönderilerindeki görseller artık tıklanınca tam ekran katmanında açılıyor; harici sayfa bağlantılarına dokunulmuyor. Ek kütüphane gerekmez (`Esc`, arka plan veya × ile kapanır). `templates/{Default,air_tema}/forum/forum.js`, `forum.css`
---

## (10.10.2026)

## 🔒 Güvenlik

- **XSS koruması güçlendirildi.** İçerik artık whitelist tabanlı **HTMLPurifier** ile temizleniyor (hem kayıtta hem görüntülemede). Zayıf regex filtreler kaldırıldı.
  `topic.php`, `edit.php`, `editor_helpers.php`
- **CSRF doğrulaması eklendi.** Onay, çöp kutusu, bakım ve şikayet AJAX uçları artık `user_hash` istiyor.
  `forum_approval`, `forum_trash`, `forum_tools`, `forum_reports`
- **Anket oylaması koruması.** Oy vermeye CSRF doğrulaması ve seçeneğin ilgili ankete ait olduğu kontrolü eklendi.
  `topic.php`
- **Admin AJAX uçları whitelist'e alındı.** `forum_approval`, `forum_trash`, `forum_tools` artık yönetim paneli bağlamında (ülke kısıtlarıyla) çalışıyor.
  `forge_forum.xml` → `engine/ajax/controller.php`
- **Yetki düzeltmesi.** Grup 3 artık moderatör sayılmıyor (yalnızca admin 1 ve moderatör 2).
  `topic.php`, `edit.php`
- **Kısıtlı kategoriler misafirlere de kapalı.**
  `category.php`, `main.php`
- **Dosya yükleme sertleştirildi.** `author` parametresi JS bağlamında kaçışlandı; önizleme kartındaki dosya adı çıktısı HTML-escape edildi.
  `forum_upload.php`, `forum_upload.class.php`
- **SSRF koruması.** URL'den görsel ekleme artık özel/iç ağ adreslerini (10.x, 172.16–31.x, 192.168.x, 127.x, 169.254.x, `::1`, `localhost`), sayısal/onaltılık/sekizlik IP temsillerini ve DNS ile çözülen tüm adresleri engelliyor. Host DNS'e çözülüp `filter_var(NO_PRIV|NO_RES)` ile doğrulanıyor.
  `forum_upload.class.php`
- **Uzantı beyaz listesi.** Dosya yükleme kara liste yerine **beyaz liste** kullanıyor (ayarlardaki `allowed_filetypes` + görsel/video uzantıları); `php`, `phtml`, `svg`, `htaccess`, `html` gibi çalıştırılabilir/aktif uzantılar her koşulda reddediliyor.
  `forum_upload.class.php`

## 🛠️ Kurulum

- **MySQL uyumluluğu.** Kurulumda yalnızca MariaDB'de çalışan `ADD/DROP COLUMN IF NOT EXISTS` kullanımı kaldırıldı; kolonlar `SHOW COLUMNS` kontrolüyle PHP tarafında ekleniyor. Kurulum artık **MySQL 8.x**'te hatasız tamamlanıyor.
  `forge_forum.xml`

## ✅ Düzeltmeler

- **Harici bağlantılar.** `BB_Parse` slash sözleşmesi düzeltildi — harici linkler artık doğru render ediliyor.
  `topic.php`
- **"Yeni Mesajlar".** Tüm konuları listelemek yerine artık oturum bazlı **okunmamış** konuları gösteriyor (okunan konular listeden düşer).
  `search.php`
- **IP Ban.** DLE `_banned` şemasına uygun (descr/days/ip/users_id) kalıcı IP banı; ban önbelleği (`banned.json`) temizleniyor, IP konunun ilk mesajından alınıyor. (Hatalı `reason` kolonu ve olmayan `t.ip` giderildi.)
  `forum_approval.php`
- **Çöp kutusu boşaltma.** Tüm çöp konuların silinmesini engelleyen döngü hatası (yalnızca ilk konu siliniyordu) ve beğeni silme sırası düzeltildi.
  `forum_trash.php`, `forum_approval.php`
- **`access` kolonu.** Konu bazlı erişim kontrolünün çalışması için `_forum_topics.access` şemaya/migration'a eklendi.
  `forge_forum.xml`
- **Çift escape giderildi.** Yönetimde konu başlığı kaydedilirken HTML-encode edilip ikinci kez encode edilmesi sona erdi (`&`, `"`, `<` artık doğru görünüyor).
  `engine/inc/forum/topics.php`
- **Sıfıra bölme koruması.** Sayfa başına kayıt (`topics_per_page` / `posts_per_page`) 0 olduğunda oluşabilecek bölme hatası `max(1, …)` ile giderildi.
  `category.php`, `reply.php`
- **`isset`/`??` disiplini.** Form girdileri (`title`, `text`, `cat_id`, anket alanları, `user_hash`, vb.) artık tanımsız indeks uyarısı üretmeden okunuyor.
  `edit.php`, `new_topic.php`, `reply.php`, `topics.php`
- **Önek tutarlılığı.** Kullanıcı tablosu sorguları `PREFIX` yerine `USERPREFIX` kullanacak şekilde düzeltildi.
  `profile.php`, `topic.php`
- **`{post_text}` alanı.** Düzenleme ekranındaki gizli alanın kaçış sırası düzeltildi.
  `edit.php`
- **Breadcrumb (navigasyon yolu).** Tüm forum sayfalarındaki breadcrumb, tema Tailwind içermediği için bozuk görünüyordu (bitişik öğeler, dev ikonlar). Tailwind'siz, tema bağımsız `forum-breadcrumb__*` sınıflarına geçirildi.
  `engine/modules/forum.php`, `topic.php`, `templates/{skin}/forum/forum.css`

## 🧰 Yönetim (Admin) Uyumluluğu

- **DLE 21 renk sınıfları.** Yönetim panelinde kullanılan `bg-warning`, `bg-success`, `bg-blue-600`, `bg-indigo-600` (ve `text-white`) DLE 21 paletinde **yok** (Limitless dönemi kalıntısı); `bg-danger`/`bg-primary` ise yalnızca `<a>`'da arka plan veriyor. Bu sınıflar panel çatısında tanımlandı — butonlar ve istatistik kartları tekrar doğru renkleniyor.
  `engine/inc/forum.php`
- **Rozet (label) yazıları.** DLE 21'de `label-default`, `label-primary`, `label-warning` tanımsız; üstelik `.label` beyaz metin + şeffaf zemin olduğundan rozet yazıları görünmüyordu (ör. rütbe rozeti). Panel çatısında `label-*` sınıfları tanımlandı.
  `engine/inc/forum.php`
- **İkincil butonlar her iki temada görünür hâle getirildi.** Konu düzenleme sayfasındaki **İptal / Üret / Listeye Geri Dön** butonları `btn-default` kullanıyordu: DLE 21 açık temasında zemin neredeyse beyaz (`#f5f5f5` / `#fcfcfc`), koyu temasında ise DLE metni beyaza çevirip zemini açık bıraktığı için okunmaz oluyordu. Bu butonlar eklentinin diğer sayfalarıyla (kategoriler/ön ekler/etiketler) ve DLE çekirdek yönetimiyle aynı olan `bg-grey-400`'e çevrildi. Ayrıca konu formu altlığının (`panel-footer`) **sabit açık arka planı** (`style="… background:#fafafa …"`) kaldırıldı; artık koyu tema sınıfına uyum sağlıyor.
  `engine/inc/forum/topics.php`, `engine/inc/forum/ranks.php`
- **Koyu tema `btn-default` güvenlik ağı.** Kalan `btn-default` butonları (ör. kategori ikon seçici) için koyu tema zemini DLE paletine (`#495264`, hover `#55607a`) çevrildi; `bootstrap-select` (medya yükleme popup'ı) material-underline stili korunacak şekilde hariç tutuldu.
  `engine/inc/forum.php`

## ⚡ Performans

- **Gereksiz `clear_cache()` kaldırıldı.** Her forum isteğinde DLE'nin tüm önbelleğini (tema/şablon derlemeleri dahil) boşaltan çağrı kaldırıldı.
  `init.php`
- **Şema migration'ları istek yolundan çıkarıldı.** `SHOW COLUMNS`/`ALTER TABLE` kontrolleri artık kilit dosyasıyla yalnızca **bir kez** çalışıyor (sonraki isteklerde atlanıyor).
  `init.php`, `rank_helpers.php`, `editor_helpers.php`
- **Rütbe N+1 sorguları giderildi.** Rütbe tablosu ve kullanıcı satırı istek boyunca önbelleğe alınıyor; konu sayfasında **mesaj başına** yapılan sorgular tek sefere indi.
  `rank_helpers.php`

## 🌗 Tema Modu (Tema ile aynı / Açık / Koyu / Sistem)

- **Dark/Light/Auto desteği eklendi.** "Tema & Görünüm" panelinden forum renk şeması seçilebilir: **Tema ile aynı (varsayılan)**, **Açık**, **Koyu**, **Sistem**. **"Tema ile aynı"** seçilince forum, DLE temasının koyu/açık modunu (`html[data-theme]`) izler — tema switch'i değişince forum da anında değişir. **"Sistem"** cihazın `prefers-color-scheme` tercihini izler.
  `engine/inc/forum/settings.php`, `engine/modules/forum/init.php`, `templates/{skin}/forum/forum.css`, `language/*/forum.lng`
- **CSS değişken tabanlı yapıldı.** Sabit açık renkler (`#ffffff`, `#f3f4f6`, gri tonları ...) palet değişkenlerine (`--forum-*`) bağlandı; koyu tema tek override ile çalışıyor (light görünüm değişmedi).
  `templates/{skin}/forum/forum.css`
- **Koyu tema kapsamı genişletildi.** Kod blokları (tema `--code-*` / `--surface-*` değişkenleri), yazar kolonu, grup/rütbe rozetleri (rgba tint), mini butonlar ve özel satırlar (sabit/okunmamış) dahil edildi.
  `templates/{skin}/forum/forum.css`, `engine/modules/forum/actions/topic.php`

## 🎨 Tema (Air)

- `main.tpl` — forum `forum.css` + `forum.js` + **Font Awesome 4.7.0** (DLE'nin paketlediği `public/fonts/fontawesome/styles.min.css`) eklendi (ikonlar ve arama butonu görünür).
- `forum.css` — breadcrumb yerleşim stilleri (`forum-breadcrumb__*`) eklendi.
- `index.tpl` — istatistik kartındaki başlık tekrarı giderildi; çevrimiçi üye listesindeki fazladan boşluk düzeltildi.

## 🧩 Native Görünüm (Utility Katmanı)

- **Kapsanmış utility katmanı eklendi.** Forum şablonları ve PHP çıktısı bir dizi utility sınıfı kullanıyor (`flex`, `gap-2`, `p-6`, `text-sm`, `rounded`, `border-gray-300`, `sm:grid-cols-2` ...). Tema hiçbir Tailwind katmanı yüklemediği için bu sınıflar etkisizdi (formlar, sayfalama, etiketler, uyarılar ham görünüyordu). `.forum-wrapper` altına kapsanmış, dış bağımlılığı olmayan bir katman eklendi — site geneline sızmaz.
  `templates/{skin}/forum/forum.css`

## 🏷️ Native Adlandırma

- **`mybb-*` ön eki kaldırıldı.** MyBB'ye atıfta bulunan sınıf adları (ör. `mybb-forum-rank`) tüm CSS, şablon, PHP ve JS'te `forum-*` adlandırmasına geçirildi (~109 sınıf, 24 dosya).
  `forum.css`, `forum.js`, `templates/{skin}/forum/*.tpl`, ilgili PHP dosyaları
- **Admin CSRF alanı native yapıldı.** Yönetim panelindeki forum formları `dle_post_hash` yerine DLE'nin standart `user_hash` alanını kullanıyor.
  `engine/inc/forum/*.php`

## 🔤 Font Awesome

- **Paketlenen Froala FA'sı 4.7.0'a hizalandı** (4.5.0 → 4.7.0, yalnızca `woff2`).
  `public/editor/froala/fonts/`
- **Forum sayfalarında FA 4.7.0 garanti altına alındı.** Bazı temalar (ör. DLE 21 **Air**) `fa-*` ikon fontunu yüklemediği için `main.tpl`'e bağımlı kalmadan `init.php` üzerinden `public/fonts/fontawesome/styles.min.css` ekleniyor (tema zaten yüklüyorsa çift eklenmez). `engine/modules/forum/init.php`
- **Kategori başlığı görünümü düzeltildi.** `.forum-cat-bar` kuralı `.forum-cat-header`'ın koyu arka planını eziyor, başlık açık zeminde beyaz kalıyordu; `.forum-cat-bar.forum-cat-header` seçicisiyle koyu başlık + beyaz yazı sabitlendi. `templates/{air_tema,Default}/forum/forum.css`

---

### Dosya yapısı (değişen dosyalar)
```
engine/modules/forum/editor_helpers.php
engine/modules/forum/init.php
engine/modules/forum.php
engine/modules/forum/rank_helpers.php
engine/modules/forum/actions/topic.php
engine/modules/forum/actions/edit.php
engine/modules/forum/actions/new_topic.php
engine/modules/forum/actions/reply.php
engine/modules/forum/actions/profile.php
engine/modules/forum/actions/category.php
engine/modules/forum/actions/main.php
engine/modules/forum/actions/search.php
engine/inc/forum/topics.php
engine/inc/forum/settings.php
engine/ajax/forum_upload.php
engine/ajax/forum_approval.php
engine/ajax/forum_trash.php
engine/ajax/forum_tools.php
engine/ajax/forum_reports.php
engine/classes/uploads/forum_upload.class.php
language/{Turkish,English,Russian}/forum.lng
forge_forum.xml
public/editor/froala/fonts/font-awesome.css
templates/{skin}/main.tpl
templates/{skin}/forum/forum.css
templates/{skin}/forum/forum.js
templates/{skin}/forum/*.tpl
engine/inc/forum/*.php
```
