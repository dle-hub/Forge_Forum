# Forge Forum — Şablon & Etiket Kılavuzu

Bu belge, Forge Forum modülünün **ön yüz şablonlarında** kullanılan tüm etiketleri, blokları ve yapılandırma noktalarını açıklar.

---

## 1. Dosya yapısı

Şablonlar aktif DLE temanızın altında bulunur:

```
templates/{skin}/forum/
├── index.tpl              → Forum ana sayfa
├── category.tpl           → Kategori / konu listesi
├── topic.tpl              → Konu görünümü + hızlı cevap
├── topic_row.tpl          → Tek konu satırı (kategori listesinde)
├── subforum_row.tpl       → Alt kategori / forum satırı (ana sayfada)
├── post.tpl               → Tek mesaj satırı (konu içinde)
├── profile.tpl            → Üye forum profili
├── new_topic.tpl          → Yeni konu formu
├── edit.tpl               → Mesaj / konu düzenleme formu
├── search.tpl             → Arama sayfası
├── notifications.tpl      → Bildirimler listesi
├── notification_row.tpl   → Tek bildirim satırı
├── statistics.tpl         → Detaylı istatistik sayfası
├── subscriptions.tpl      → Abonelikler sayfası
├── tag.tpl                → Etiket sayfası
├── online.tpl             → Kimler çevrimiçi sayfası
├── mod.tpl                → Ön yüz moderasyon paneli
├── history.tpl            → Düzenleme geçmişi
├── forum.css              → Forum stilleri
└── forum.js               → Toolbar, katlanır bloklar ve AJAX yardımcıları
```

> Paket iki hazır set içerir: **`templates/air_tema`** (Air teması) ve **`templates/Default`**. Aktif temanızın adıyla `templates/{skin}/forum/` altına kopyalayın.

**Dil dosyaları:**

```
language/{Turkish|English|Russian}/forum.lng
```

**Rank görselleri (isteğe bağlı):**

```
templates/{skin}/forum/ranks/administrator.png
```

---

## 2. Temaya bağlama

Ana şablonunuza (`main.tpl` veya eşdeğeri) forum sayfalarında CSS/JS yükleyin:

```html
<link rel="stylesheet" href="{THEME}/forum/forum.css">
<script src="{THEME}/forum/forum.js" defer></script>
```

Forum içeriği DLE'nin `{content}` alanına derlenir; ayrı bir `forum/main.tpl` yoktur.

---

## 3. Etiket türleri

Forge Forum üç etiket türü kullanır:

| Tür | Sözdizimi | Açıklama |
|-----|-----------|----------|
| **Değişken** | `{etiket_adi}` | PHP tarafından doldurulan metin veya HTML |
| **Koşul bloğu** | `[blok]...[/blok]` | Koşul sağlanırsa içerik gösterilir, değilse tamamen silinir |
| **DLE grup** | `[group=1,2]...[/group]` | DLE çekirdeğinin kullanıcı grubu kontrolü (şablon derlendikten sonra işlenir) |

> **Not:** Koşul blokları `set_block()` ile yönetilir. Açılış/kapanış etiketleri boş string olarak set edilir veya regex ile blok silinir.

---

## 4. Global etiketler (tüm sayfalarda)

Bu etiketler `init.php` ve `forum.php` üzerinden tüm forum sayfalarına uygulanır.

| Etiket | Ne işe yarar |
|--------|----------------|
| `{forum_breadcrumb}` | Üst breadcrumb HTML. `forum.php` içinde `$forum_speedbar` dizisinden üretilir ve sayfa derlendikten sonra `{forum_breadcrumb}` ile değiştirilir. |
| `{http_home_url}` | Site kök URL (`https://site.com/`). |
| `{dle_login_hash}` | CSRF / güvenlik hash'i. Formlarda `user_hash` olarak gönderilir. |
| `{lang_code}` | Aktif dil kodu (`tr`, `en`, `ru`). |
| `{lang_dir}` | Yazı yönü (`ltr` / `rtl`). |

### Global koşul blokları (`init.php`)

| Blok | Ne zaman görünür |
|------|------------------|
| `[is-logged]...[/is-logged]` | Kullanıcı giriş yapmışsa |
| `[not-logged]...[/not-logged]` | Kullanıcı giriş yapmamışsa |
| `[has-forum-notifs]...[/has-forum-notifs]` | Giriş yapılmış + okunmamış bildirim varsa |

| Etiket | Ne işe yarar |
|--------|----------------|
| `{forum_notif_count}` | Okunmamış bildirim sayısı (sadece `[has-forum-notifs]` içinde anlamlı). |

### Arayüz metin etiketleri (`forum_set_ui_lang`)

Ön yüz şablonlarındaki etiketler, butonlar ve placeholder'lar sabit metin içermez. `engine/modules/forum/editor_helpers.php` içindeki **`forum_set_ui_lang($tpl, $lang)`** fonksiyonu, aşağıdaki etiket gruplarını `language/{dil}/forum.lng` dosyasından doldurur:

| Etiket grubu | Örnek | Kapsam |
|--------------|-------|--------|
| `{forum_ui_*}` | `{forum_ui_new_topic}`, `{forum_ui_topic_edit}` | Ortak arayüz: toolbar, butonlar, konu işlemleri, JS mesajları |
| `{forum_stats_*}` | `{forum_stats_today_topics}` | İstatistik sayfası etiketleri |
| `{forum_search_*}` | `{forum_search_btn}` | Arama formu ve sonuç başlıkları |
| `{forum_new_*}` | `{forum_new_title_label}` | Yeni konu formu |
| `{forum_edit_*}` | `{forum_edit_save}` | Mesaj / konu düzenleme formu |

> Yeni bir arayüz metni eklerken önce `forum.lng` dosyasına anahtarı ekleyin, ardından `forum_set_ui_lang()` haritasına tag → anahtar eşlemesini yazın ve şablonda `{tag}` kullanın.

### Tema rengi (otomatik enjekte)

Admin panelinden **Kategori Kart Başlık Rengi** (`theme_header_color`) ayarlandığında `init.php` şunu ekler:

```css
.forum-wrapper { --forum-bg-header: #1d2d44; }
```

---

## 5. Sayfa bazlı şablonlar

### 5.1 `index.tpl` — Forum ana sayfa

**Action:** `main.php`  
**URL:** `/forum/`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{categories}` | Tüm kategori bloklarının birleşik HTML çıktısı (içinde `subforum_row.tpl` satırları). |
| `{forum_title}` | Forum başlığı metni (`Forum`). |
| `{online_summary}` | “Son 15 dakikada aktif: X üye” özeti. |
| `{online_list}` | Çevrimiçi üyelerin **avatarlı çip** HTML’i (`forum-online-chip`). |
| `{total_posts}` | Toplam mesaj sayısı. |
| `{total_threads}` | Toplam konu sayısı. |
| `{total_members}` | Toplam üye sayısı. |
| `{newest_member}` | En yeni üyenin adı (linkli HTML). |

**Toolbar ve alt istatistik paneli:** Ana sayfa toolbar’ı `{forum_ui_new_posts}`, `{forum_ui_today_posts}`, `{forum_ui_online}`, `{forum_ui_stats_nav}`, `{forum_ui_notifications}`, `{forum_ui_subscriptions}`, `{forum_ui_moderation}`, `{forum_ui_mark_all_read}` etiketlerini kullanır. Alt blokta `{forum_ui_forum_stats}`, `{forum_ui_full_list}`, `{forum_ui_topic_label}`, `{forum_ui_posts_label}`, `{forum_ui_members_label}` ve `{forum_ui_newest_member}` bulunur.

---

### 5.2 `subforum_row.tpl` — Alt forum / kategori satırı

**Derleyen:** `main.php` (her alt kategori için döngü)

| Etiket | Ne işe yarar |
|--------|----------------|
| `{sf_url}` | Kategori sayfası URL'si. |
| `{sf_name}` | Kategori adı. |
| `{sf_description}` | Kategori açıklaması. |
| `{sf_topics}` | Konu sayısı. |
| `{sf_posts}` | Mesaj sayısı. |
| `{sf_icon_html}` | Font Awesome veya resim ikon HTML. |
| `{sf_subforums}` | Alt forum link listesi metni. |
| `{sf_last_url}` | Son mesaja giden URL. |
| `{sf_last_topic}` | Son konu başlığı (kısaltılmış). |
| `{sf_last_topic_full}` | Son konu başlığı (tam, `title` attribute için). |
| `{sf_last_date}` | Son mesaj tarihi. |
| `{sf_last_user}` | Son mesajı yazan kullanıcı. |
| `{sf_last_avatar}` | Son mesaj avatarı `<img>` HTML. |

| Blok | Koşul |
|------|--------|
| `[sf-has-icon]` | Özel ikon tanımlıysa |
| `[sf-no-icon]` | İkon yoksa (varsayılan çift daire) |
| `[sf-has-subforums]` | Alt kategori varsa |
| `[sf-has-lastpost]` | Son mesaj kaydı varsa |
| `[sf-no-lastpost]` | Hiç mesaj yoksa |

---

### 5.3 `category.tpl` — Kategori konu listesi

**Action:** `category.php`  
**URL:** `/forum/{kategori-alt-adi}/`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{cat_name}` | Kategori adı. |
| `{cat_description}` | Kategori açıklaması. |
| `{cat_total_topics}` | Toplam konu (sayfalama öncesi). |
| `{cat_alt}` | SEO URL adı (yeni konu linki için). |
| `{cat_id}` | Kategori ID. |
| `{topic_rows}` | `topic_row.tpl` satırlarının birleşimi veya boş mesaj. |
| `{pagination}` | Sayfa numaraları HTML. |
| `{cat_unread_count}` | Kategorideki okunmamış konu sayısı. |
| `{forum_notif_count}` | Okunmamış bildirim sayısı. |

| Blok | Koşul |
|------|--------|
| `[cat-has-unread]` | Kategoride okunmamış konu varsa |
| `[cat-sub-enabled]` | Kategori aboneliği özelliği açıksa |
| `[cat-subscribed]` | Kullanıcı bu kategoriye aboneyse |
| `[cat-not-subscribed]` | Kullanıcı bu kategoriye abone değilse |

**Toolbar etiketleri:** `{forum_ui_notifications}`, `{forum_ui_mark_read}`, `{forum_ui_cat_subscribe}`, `{forum_ui_cat_unsubscribe}`, `{forum_ui_new_topic}`, `{forum_ui_category_topics}`. JS: `forumMarkRead(cat_id)`, `forumToggleCatSub(cat_id)`.

---

### 5.4 `topic_row.tpl` — Konu satırı

**Derleyen:** `category.php` (her konu için)

| Etiket | Ne işe yarar |
|--------|----------------|
| `{t_row_class}` | Satır CSS sınıfı (`forum-row-unread` vb.). |
| `{t_title_style}` | Başlık inline stil (okunmamış kalın mavi vb.). |
| `{t_title}` | Konu başlığı (escape edilmiş). |
| `{t_url}` | Konu URL'si (`.html`). |
| `{t_author}` | Konuyu açan kullanıcı. |
| `{t_date}` | Açılış tarihi. |
| `{t_replies}` | Cevap sayısı. |
| `{t_views}` | Görüntülenme. |
| `{t_last_url}` | Son mesaja direkt link (doğru sayfa + `#post-ID`). |
| `{t_last_topic_short}` | “Son Mesaj” etiket metni (dilden). |
| `{t_last_date}` | Son mesaj tarihi. |
| `{t_last_user}` | Son mesajı yazan. |
| `{t_last_avatar}` | Son mesaj avatar HTML. |
| `{t_prefix_name}` | Konu öneki adı. |
| `{t_prefix_color}` | Önek rengi (hex). |
| `{t_prefix_icon}` | Önek Font Awesome sınıfı. |

| Blok | Koşul |
|------|--------|
| `[t-pinned]` | Sabitlenmiş konu |
| `[t-locked]` | Kilitli konu |
| `[t-unread]` | Oturumda okunmamış |
| `[t-read]` | Okunmuş |
| `[t-has-prefix]` | Önek atanmış |
| `[t-has-lastpost]` | Son mesaj bilgisi var |

---

### 5.5 `topic.tpl` — Konu sayfası

**Action:** `topic.php`  
**URL:** `/forum/topic/{id}-{baslik}.html`, `/forum/topic/{id}-{baslik}/page/{n}/`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{topic_title}` | Konu başlığı (önek HTML dahil olabilir). |
| `{topic_id}` | Konu ID. |
| `{topic_views}` | Görüntülenme. |
| `{topic_replies}` | Cevap sayısı. |
| `{topic_date}` | Açılış tarihi. |
| `{topic_is_locked}` | `1` / `0` |
| `{topic_is_pinned}` | `1` / `0` |
| `{post_rows}` | Tüm `post.tpl` derlemelerinin birleşimi. |
| `{pagination}` | Mesaj sayfalama linkleri. |
| `{poll_block}` | Anket HTML (varsa PHP üretir). |
| `{tags_block}` | Etiket bloğu (şu an boş; genişletilebilir). |
| `{reply_editor}` | Hızlı cevap editörü (TinyMCE/Froala veya textarea). |
| `{pin_label}` | “Sabitle” / “Sabitlemeyi Kaldır” (dilden). |
| `{lock_label}` | “Kilitle” / “Kilidi Aç”. |
| `{mod_display}` | Mod araçları: `block` veya `none`. |
| `{mod_label}` | Menü başlığı: “Yönetici” veya (yalnızca konu sahibi için) “Konu İşlemleri”. |
| `{topic_edit_url}` | “Konuyu Düzenle” bağlantısı — ilk mesajın düzenleme formuna gider. |
| `{solve_label}` | “Çözüldü olarak işaretle” / “Çözüldü işaretini kaldır”. |
| `{viewing_count}` | Konuyu son 2 dakikada görüntüleyen üye sayısı. |
| `{viewing_users}` | Görüntüleyen üyelerin link listesi. |
| `{similar_topics}` | Benzer konular HTML’i. |

| Blok | Koşul |
|------|--------|
| `[topic-pinned]` | Konu sabit |
| `[topic-locked]` | Konu kilitli |
| `[topic-solved]` | Konu çözüldü olarak işaretli |
| `[allow-bump]` | Kullanıcı konuyu öne çıkarabilir |
| `[can-edit-topic]` | Kullanıcı konuyu düzenleyebilir (yönetici, yetkili kategori moderatörü veya izinli konu sahibi) |
| `[can-moderate-topic]` | Kullanıcı sabitleme, kilitleme, taşıma, birleştirme, bölme ve çöpe atma yapabilir |
| `[can-solve]` | Kullanıcı konuyu çözüldü işaretleyebilir |
| `[has-similar]` | Benzer konu varsa |
| `[has-viewers]` | Konuyu görüntüleyen üye varsa |
| `[not-group=5]...[/not-group]` | Misafir olmayan — hızlı cevap formu |
| `[group=5]...[/group]` | Misafir — giriş/kayıt uyarısı |

> **Konu düzenleme izinleri:** Yönetim > Forum > Ayarlar altındaki **Konu Sahibi Düzenleyebilsin** ve **Kategori Moderatörleri Düzenleyebilsin** anahtarlarına bağlıdır. Konu sahibi başlık, önek, etiket ve ilk mesajı düzenleyebilir; **kategori taşıyamaz**. Kategori değişikliği yalnızca moderatörlerde görünür (`[can-move-topic]`).

**Gömülü JavaScript fonksiyonları** (`topic.tpl` içinde):

| Fonksiyon | İşlev |
|-----------|--------|
| `forumLikePost(id, type)` | Beğeni / dislike AJAX |
| `forumQuotePost(id)` | Alıntı AJAX |
| `forumReportPost(id)` | Şikayet gönder |
| `modAction(action, data)` | Sabitle, kilitle, sil, bump |
| `forumToggleSubscribe(topicId)` | Konu aboneliği aç/kapat |
| `forumToggleSolved(topicId)` | Çözüldü işaretini aç/kapat |
| `forumModMove()` / `forumModMerge()` / `forumModSplit()` | Konu taşı / birleştir / böl |
| `forumInsertMultiquotes()` | Çoklu alıntıları editöre ekle |
| `handleForumUpload(input)` | Hızlı cevap dosya yükleme |
| `triggerForumUpload()` | Dosya seçiciyi aç |

---

### 5.6 `post.tpl` — Tek mesaj

**Derleyen:** `topic.php` (her mesaj için, `post_rows` içinde)

| Etiket | Ne işe yarar |
|--------|----------------|
| `{post_id}` | Mesaj ID (`#post-{id}` anchor). |
| `{p_name}` | Kullanıcı adı. |
| `{p_posts}` | Kullanıcının forum mesaj sayısı. |
| `{p_pts}` | Forum puanı. |
| `{p_text}` | Mesaj içeriği (BBCode/HTML parse edilmiş). |
| `{p_date}` | Gönderim tarihi. |
| `{p_num}` | Mesaj sıra numarası (#1, #2…). |
| `{likes}` | Beğeni sayısı. |
| `{dislikes}` | Dislike sayısı. |
| `{avatar}` | Avatar `<img>` veya varsayılan kutu HTML. |
| `{p_group_id}` | DLE kullanıcı grubu ID. |
| `{p_group_name}` | Grup adı (YÖNETİCİ, ÜYE vb.). |
| `{p_group_style}` | Grup rozeti inline CSS. |
| `{p_rank_name}` | Forum rütbe adı. |
| `{p_rank_color}` | Rütbe rengi. |
| `{p_rank_html}` | Hazır rütbe rozeti HTML (resim veya ikon). |
| `{p_star_count}` | Yıldız sayısı (1–5, puana göre). |
| `{p_star_color}` | Yıldız rengi. |
| `{p_ip}` | IP (admin için linkli; diğerleri görmez). |
| `{p_email_href}` | E-posta geri bildirim linki. |
| `{p_pm_href}` | Özel mesaj linki. |
| `{p_pm_onclick}` | PM modal onclick. |
| `{p_find_href}` | Kullanıcının mesajlarını arama linki. |
| `{p_uid}` | Kullanıcı ID. |

| Blok | Koşul |
|------|--------|
| `[owner]` | Konunun ilk mesajı (konu sahibi) |
| `[p-has-rank]` | Forum rütbesi tanımlı ve sistem açık |
| `[has-email]` | E-posta gösterilebilir |
| `[no-email]` | E-posta yok |
| `[can-pm]` | Özel mesaj gönderilebilir |
| `[no-pm]` | PM kapalı |
| `[has-profile]` | Kayıtlı üye (profil/arama linki) |
| `[edit]` | Kullanıcı bu mesajı düzenleyebilir |
| `[complaint]` | Şikayet butonu göster |
| `[group=1,2]` | Admin / moderatör (IP, toplu seçim) |

**Yıldızlar:** `.forum-author-stars[data-stars="{p_star_count}"][data-color="{p_star_color}"]` — `topic.tpl` içindeki JS ile doldurulur.

---

### 5.7 `profile.tpl` — Forum profili

**Action:** `profile.php`  
**URL:** `/forum/user/{kullanici-adi}/`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{user_name}` | Kullanıcı adı. |
| `{user_id}` | Kullanıcı ID. |
| `{user_fullname}` | Tam ad. |
| `{user_land}` | Ülke / konum. |
| `{points}` | Forum puanı. |
| `{topic_count}` | Açılan konu sayısı. |
| `{post_count}` | Yazılan mesaj sayısı. |
| `{reg_date}` | Kayıt tarihi. |
| `{last_date}` | Son görülme. |
| `{avatar_html}` | Profil avatar HTML (Gravatar destekli). |
| `{rank_title}` | Rütbe adı. |
| `{rank_color}` | Rütbe rengi. |
| `{rank_icon}` | Rütbe FA ikonu. |
| `{rank_badge}` | Rütbe kısa metni. |
| `{rank_html}` | Tam rütbe rozeti HTML. |
| `{group_name}` | DLE grup adı. |
| `{group_color}` | DLE grup rengi. |
| `{online_dot}` | Çevrimiçi göstergesi HTML veya boş. |
| `{pm_url}` | Özel mesaj URL. |
| `{email_url}` | E-posta URL. |
| `{recent_topics}` | Son konular listesi (PHP üretir HTML). |
| `{recent_posts}` | Son mesajlar listesi (PHP üretir HTML). |

| Blok | Koşul |
|------|--------|
| `[has-forum-rank]` | Forum rütbesi var |
| `[has-pm]` | PM linki göster |
| `[has-email]` | E-posta linki göster |
| `[has-land]` | Konum bilgisi var |
| `[has-fullname]` | Tam ad var |

---

### 5.8 `new_topic.tpl` — Yeni konu

**Action:** `new_topic.php`  
**URL:** `/forum/new-topic/` veya `/forum/new-topic/{kategori}/`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{forum_editor}` | Konu editörü (wseditor + TinyMCE/Froala). |
| `{category_selector}` | Hazır `<select name="cat_id">` HTML. |
| `{prefix_selector}` | Hazır `<select name="prefix_id">` HTML. |
| `{cat_id}` | Ön seçili kategori ID. |
| `{cat_name}` | Ön seçili kategori adı. |
| `{topic_captcha}` | CAPTCHA / anti-spam alanı (ayar ve grup iznine bağlı). |
| `{drafts_enabled}` | Taslak özelliği açıksa `true`. |
| `{dle_login_hash}` | Form CSRF hash'i. |

> Form etiketleri, placeholder'lar ve buton metinleri `{forum_new_*}` / `{forum_ui_*}` etiketleriyle dil dosyasından gelir. CAPTCHA yalnızca ilgili ayar ve grubun **Resim doğrulaması** izni açıksa gösterilir.

**Form alanları (şablonda sabit):** `title`, `text`, `tags`, `poll_question`, `poll_options`, `poll_max_choices`, `poll_days`, `poll_multiple`, `submit_topic`, `user_hash`.

---

### 5.9 `edit.tpl` — Mesaj düzenleme

**Action:** `edit.php`  
**URL:** `?do=forum&action=edit&post_id={id}`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{post_id}` | Düzenlenen mesaj ID. |
| `{topic_id}` | Konu ID. |
| `{topic_title}` | Konu başlığı (bilgi bandında). |
| `{post_text}` | Ham mesaj (textarea fallback). |
| `{forum_editor}` | Editör HTML. |
| `{category_selector}` | Sadece ilk mesajda. |
| `{prefix_selector}` | Sadece ilk mesajda. |
| `{title_val}` | Mevcut başlık. |
| `{tags_val}` | Mevcut etiketler (virgülle). |
| `{poll_question}` | Anket sorusu. |
| `{poll_options}` | Anket seçenekleri (satır satır). |
| `{poll_max_choices}` | Maks. seçim. |
| `{poll_days}` | Anket süresi (gün). |
| `{poll_multiple_checked}` | `checked` veya boş. |

| Blok | Koşul |
|------|--------|
| `[first_post]` | Düzenlenen mesaj konunun ilk mesajı — başlık, kategori, anket alanları |
| `[can-move-topic]` | Kategori seçiciyi göster (yalnızca yönetici / moderatör / kategori moderatörü) |

> **Yetki:** Konu sahibi kendi konusunun başlığını, öneğini, etiketlerini ve ilk mesajını düzenleyebilir; kategori taşıyamaz. Kategori moderatörleri yalnızca yetkili oldukları kategoride düzenleme yapabilir. İlk mesaj düzenlenirken anket seçenekleri silinip yeniden oluşturulmaz — **oylar korunur**.

---

### 5.10 `search.tpl` — Arama

**Action:** `search.php`  
**URL:** `?do=forum&action=search` (+ `q`, `in`, `mode`)

| Etiket | Ne işe yarar |
|--------|----------------|
| `{q}` | Arama terimi (formda). |
| `{in_topics}` | `checked` — konu başlığında ara. |
| `{in_posts}` | `checked` — mesaj içeriğinde ara. |
| `{total}` | Sonuç sayısı. |
| `{results}` | Sonuç satırları HTML (PHP üretir). |
| `{pagination}` | Sayfalama. |

| Blok | Koşul |
|------|--------|
| `[results]` | En az bir sonuç varsa üst bilgi şeridi |

**Ek modlar (URL):** `mode=new` (yeni mesajlar), `mode=today` (bugünkü mesajlar).

---

### 5.11 `notifications.tpl` + `notification_row.tpl`

**Action:** `notifications.php`  
**URL:** `?do=forum&action=notifications`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{notification_rows}` | Bildirim satırları birleşimi. |

**`notification_row.tpl`:**

| Etiket | Ne işe yarar |
|--------|----------------|
| `{id}` | Bildirim ID. |
| `{sender}` | Gönderen kullanıcı adı. |
| `{topic_title}` | İlgili konu başlığı. |
| `{notif_url}` | Konu / mesaj linki. |
| `{date}` | Bildirim tarihi. |

| Blok | Koşul |
|------|--------|
| `[type-reply]` | Cevap bildirimi |
| `[type-like]` | Beğeni bildirimi |
| `[type-mention]` | @mention bildirimi |
| `[type-follow_topic]` | Takip edilen üye yeni konu açtı |
| `[type-cat_topic]` | Abone olunan kategoride yeni konu açıldı |
| `[notif-unread]` | Okunmamış |
| `[notif-read]` | Okunmuş |

---

### 5.12 `statistics.tpl` — Detaylı istatistikler

**Action:** `statistics.php`  
**URL:** `?do=forum&action=statistics`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{stats_title}` | Sayfa başlığı. |
| `{stat_topics}` / `{stat_posts}` / `{stat_members}` | Genel toplamlar. |
| `{stat_topics_today}` / `{stat_posts_today}` | Bugünkü konu / mesaj. |
| `{stat_per_day}` | Günlük ortalama mesaj. |
| `{stat_members_label}` | “Üye” etiketi (dilden). |
| `{stat_newest}` | En yeni üye (linkli HTML). |
| `{stats_cat_title}` + `{stats_categories}` | Kategori tablosu. |
| `{stats_posters_title}` + `{stats_top_posters}` | En aktif üyeler. |
| `{stats_viewed_title}` + `{stats_top_viewed}` | En çok görüntülenen konular. |
| `{stats_replied_title}` + `{stats_top_replied}` | En çok cevaplanan konular. |

---

### 5.13 `subscriptions.tpl` — Abonelikler

**Action:** `subscriptions.php`  
**URL:** `?do=forum&action=subscriptions`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{sub_title}` | Sayfa başlığı. |
| `{sub_count}` | Abonelik sayısı. |
| `{subscription_rows}` | Abonelik satırları HTML. |

---

### 5.14 `tag.tpl` — Etiket sayfası

**Action:** `tag.php`  
**URL:** `/forum/etiket/{tag}/`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{tag_name}` | Etiket adı. |
| `{topic_rows}` | Etikete ait konu satırları. |
| `{pagination}` | Sayfalama. |

---

### 5.15 `online.tpl` — Kimler çevrimiçi

**Action:** `online.php`  
**URL:** `?do=forum&action=online`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{online_title}` | Sayfa başlığı. |
| `{online_count}` | Çevrimiçi üye sayısı. |
| `{online_rows}` | Üye satırları (avatar + ad + grup). |

---

### 5.16 `mod.tpl` — Ön yüz moderasyon paneli

**Action:** `mod.php`  
**URL:** `?do=forum&action=mod`  
**Yetki:** Yönetici, moderatör veya kategori moderatörü.

| Etiket | Ne işe yarar |
|--------|----------------|
| `{mod_title}` | Panel başlığı. |
| `{pending_count}` / `{pending_posts}` | Onay bekleyen konu / mesaj sayısı. |
| `{report_count}` | Açık şikayet sayısı. |
| `{pending_rows}` / `{report_rows}` | Onay ve şikayet satırları. |
| `{admin_approval_url}` / `{admin_reports_url}` / `{admin_topics_url}` | Yönetim paneli kısayolları. |

**JS:** `forumBulkApproval('approve'|'reject')`, `forumApproveTopic(id)`, `forumRejectTopic(id)`, `forumReportResolve(id)`, `forumReportTrash(id)`.

---

### 5.17 `history.tpl` — Düzenleme geçmişi

**Action:** `history.php`  
**URL:** `?do=forum&action=history&post_id={id}`

| Etiket | Ne işe yarar |
|--------|----------------|
| `{history_title}` | Sayfa başlığı. |
| `{topic_title}` | İlgili konu başlığı. |
| `{topic_link}` | Konuya dönüş linki. |
| `{history_count}` | Eski sürüm sayısı. |
| `{history_rows}` | Eski sürüm satırları HTML. |

---

## 6. DLE yerel grup etiketleri

Şablonlarda DLE'nin standart grup etiketleri de çalışır:

```html
[group=1]Sadece admin[/group]
[group=1,2]Admin ve moderatör[/group]
[not-group=5]Misafir değil[/not-group]
```

| Grup | Tipik rol |
|------|-----------|
| `1` | Yönetici |
| `2` | Moderatör |
| `5` | Misafir |

---

## 7. CSS değişkenleri (`forum.css`)

Tema özelleştirmesi için `:root` / `.forum-wrapper` altında:

| Değişken | Varsayılan | Kullanım |
|----------|------------|----------|
| `--forum-bg-header` | `#1d2d44` | Kategori kart başlığı (admin ayarından override) |
| `--forum-bg-body` | `#ebf0f6` | Sayfa arka planı |
| `--forum-bg-card` | `#ffffff` | Kart arka planı |
| `--forum-bg-subheader` | `#ebf2fb` | Alt başlık şeritleri |
| `--forum-text-primary` | `#1d2d44` | Ana metin |
| `--forum-text-muted` | `#8c9ba5` | Soluk metin |
| `--forum-border-color` | `#c9d3e0` | Kenarlıklar |
| `--forum-radius-sm/md/lg` | `4/6/8px` | Köşe yuvarlaklığı |

**Editör mavi şerit (DLE uyumu):**

```css
.wseditor { border-top: 5px solid #0c5f7e; }
```

---

## 8. AJAX uç noktaları (şablon JS)

| Modül | URL | İşlev |
|-------|-----|--------|
| `forum_like` | `?controller=ajax&mod=forum_like` | Beğeni / dislike |
| `forum_quote` | `?controller=ajax&mod=forum_quote` | Tekli / çoklu alıntı metni |
| `forum_upload` | `?controller=ajax&mod=forum_upload` | Dosya / resim yükleme |
| `forum_moderation` | `?controller=ajax&mod=forum_moderation` | Sabitle, kilitle, sil, bump, taşı, birleştir, böl, toplu işlem |
| `forum_reports` | `?controller=ajax&mod=forum_reports` | Şikayet gönder |
| `forum_approval` | `?controller=ajax&mod=forum_approval` | Onayla / reddet (tek veya toplu) |
| `forum_trash` | `?controller=ajax&mod=forum_trash` | Çöp kutusu işlemleri |
| `forum_solve` | `?controller=ajax&mod=forum_solve` | Çözüldü işaretini aç/kapat |
| `forum_best_answer` | `?controller=ajax&mod=forum_best_answer` | En iyi cevap seç / kaldır |
| `forum_subscribe` | `?controller=ajax&mod=forum_subscribe` | Konu aboneliği |
| `forum_cat_subscribe` | `?controller=ajax&mod=forum_cat_subscribe` | Kategori aboneliği |
| `forum_read` | `?controller=ajax&mod=forum_read` | Konu / kategori okundu işaretle |
| `forum_rate` | `?controller=ajax&mod=forum_rate` | Konu puanlama (1–5) |
| `forum_follow` | `?controller=ajax&mod=forum_follow` | Kullanıcı takip et / bırak |
| `forum_draft` | `?controller=ajax&mod=forum_draft` | Taslak kaydet / yükle / sil |
| `forum_mention_search` | `?controller=ajax&mod=forum_mention_search` | @mention kullanıcı önerileri |
| `forum_tools` | `?controller=ajax&mod=forum_tools` | Admin bakım araçları |

Tüm isteklerde `user_hash` / `dle_login_hash` gönderilmelidir.

---

## 9. SEO URL yapısı

Kurallar `forge_forum.xml` içindeki `DLEUrl::AddRule` ile tanımlıdır:

| Sayfa | Örnek URL |
|-------|-----------|
| Ana sayfa | `/forum/` |
| Kategori | `/forum/{kategori-alt}/` |
| Kategori sayfa | `/forum/{kategori-alt}/page/2/` |
| Konu | `/forum/topic/{id}-{baslik}.html` |
| Konu sayfa | `/forum/topic/{id}-{baslik}/page/2/` |
| Son mesaj | `.../page/N/#post-{id}` |
| Yeni konu | `/forum/new-topic/` |
| Profil | `/forum/user/{kullanici}/` |
| Arama | `/forum/search/` |
| Etiket | `/forum/etiket/{tag}/` |
| RSS | `/forum/rss/` |
| RSS (kategori) | `/forum/rss/{kategori}/` |
| İstatistik | `?do=forum&action=statistics` |
| Abonelikler | `?do=forum&action=subscriptions` |
| Çevrimiçi üyeler | `?do=forum&action=online` |
| Moderasyon paneli | `?do=forum&action=mod` |
| Düzenleme geçmişi | `?do=forum&action=history&post_id={id}` |
| Sitemap | `?do=forum&action=sitemap` (`/forum/sitemap.xml`) |

---

## 10. Dil dosyası (`forum.lng`)

Ön yüz metinleri iki yolla gelir:

1. **PHP mesajları:** `$lang['forum_...']` (msg, AJAX yanıtları, e-posta şablonları).
2. **Şablon etiketleri:** `forum_set_ui_lang($tpl, $lang)` ile `{forum_ui_*}`, `{forum_stats_*}`, `{forum_search_*}`, `{forum_new_*}` ve `{forum_edit_*}` tag’leri doldurulur.

Desteklenen diller:

- `language/Turkish/forum.lng`
- `language/English/forum.lng`
- `language/Russian/forum.lng`

DLE site dili ne ise o klasördeki `forum.lng` otomatik yüklenir (`engine/modules/forum.php`).

> **Kalan sabit metinler:** `post.tpl`, `profile.tpl`, `mod.tpl`, `online.tpl`, `history.tpl`, `topic_row.tpl`, `subforum_row.tpl` ve `notification_row.tpl` içinde doğrudan Türkçe metinler bulunur. Bunları da taşımak için `forum.lng`’ye anahtar ekleyip `forum_set_ui_lang()` haritasına bağlayın.

---

## 11. Özelleştirme ipuçları

1. **Yeni tema:** Tema `forum/` klasörü içermiyorsa şablonlar otomatik olarak `templates/Default/forum/` üzerinden yüklenir; özelleştirmek için `Default/forum` klasörünü kopyalayıp `{skin}/forum/` altına yapıştırın. CSS/JS, temanın `main.tpl` dosyasında elle dahil edilmemişse otomatik eklenir.
2. **Koşul bloğu eklemek:** PHP'de `$tpl->set("[blok-adi]", "");` + `$tpl->set("[/blok-adi]", "");` veya `set_block(..., "")` ile silin.
3. **Satır şablonu:** `topic_row.tpl` gibi alt şablonlar `$tpl2->load_template()` ile döngüde derlenir; ana şablona `{topic_rows}` olarak basılır.
4. **Rank görseli:** Admin → Rütbe Ayarları → `administrator.png` gibi dosya adı; dosyayı `templates/{skin}/forum/ranks/` altına koyun.
5. **Breadcrumb:** Çoğu sayfada `{forum_breadcrumb}` yeterli; `profile.php` özel breadcrumb üretir.

---

## 12. Sürüm

- **Eklenti:** Forge Forum **v1.2.0** (19.10.2026)
- **Kılavuz:** Şablon etiketleri — Forge Forum motoru ile uyumlu

**1.2.0 öne çıkanlar:**

- Kategori izinlerinin konu, arama, RSS, etiket ve sitemap çıktılarında zorunlu kılınması (P0)
- Kurulum/kaldırma tablo paritesi, şema kilidi temizliği ve `prefix_id` düzeltmesi (P1)
- CSS/JS otomatik yükleme ve aktif tema şablon fallback'i (P1)
- `/forum/sitemap.xml` ve `page/last` SEO rotaları (P2)
- TR/EN/RU rütbe ve yönetim bölümü yerelleştirmesi + kalan arayüz metinleri (P3)

**1.1.0 öne çıkanlar:**

- Ana sayfa istatistik paneli (avatarlı çevrimiçi üyeler, mini sayaçlar, “Tam Liste”)
- Tüm sayfalarda tek tip `.forum-toolbar`
- Anket yönetiminde DLE düzeyi eylemler (sonuçlar, düzenle, durdur, temizle, sil)
- Ön yüzde “Konuyu Düzenle” + konu sahibi / kategori moderatörü izinleri
- İlk mesaj düzenlenirken anket oylarının korunması
- Ön yüz metinlerinin TR/EN/RU dil dosyalarına taşınması
- Kategori kaydetmede `forum_admin_sync_cat_mods` fatal hatası düzeltmesi

Ayrıntılı değişiklik listesi: [update.md](update.md)

Sorular ve güncellemeler için: [forge_forum.xml](forge_forum.xml) ve `engine/modules/forum/actions/` altındaki action dosyalarına bakın.
