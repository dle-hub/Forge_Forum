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
├── edit.tpl               → Mesaj düzenleme formu
├── search.tpl             → Arama sayfası
├── notifications.tpl      → Bildirimler listesi
├── notification_row.tpl   → Tek bildirim satırı
├── forum.css              → Forum stilleri (MyBB tarzı)
└── forum.js               → Kategori kartı aç/kapa vb.
```

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

### Tema rengi (otomatik enjekte)

Admin panelinden **Kategori Kart Başlık Rengi** (`theme_header_color`) ayarlandığında `init.php` şunu ekler:

```css
.forum-wrapper { --mybb-bg-header: #1d2d44; }
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
| `{online_list}` | Çevrimiçi üye linkleri (virgülle ayrılmış). |
| `{total_posts}` | Toplam mesaj sayısı. |
| `{total_threads}` | Toplam konu sayısı. |
| `{total_members}` | Toplam üye sayısı. |
| `{newest_member}` | En yeni üyenin adı (linkli HTML). |

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

---

### 5.4 `topic_row.tpl` — Konu satırı

**Derleyen:** `category.php` (her konu için)

| Etiket | Ne işe yarar |
|--------|----------------|
| `{t_row_class}` | Satır CSS sınıfı (`mybb-row-unread` vb.). |
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

| Blok | Koşul |
|------|--------|
| `[topic-pinned]` | Konu sabit |
| `[topic-locked]` | Konu kilitli |
| `[allow-bump]` | Kullanıcı konuyu öne çıkarabilir |
| `[not-group=5]...[/not-group]` | Misafir olmayan — hızlı cevap formu |
| `[group=5]...[/group]` | Misafir — giriş/kayıt uyarısı |

**Gömülü JavaScript fonksiyonları** (`topic.tpl` içinde):

| Fonksiyon | İşlev |
|-----------|--------|
| `forumLikePost(id, type)` | Beğeni / dislike AJAX |
| `forumQuotePost(id)` | Alıntı AJAX |
| `forumReportPost(id)` | Şikayet gönder |
| `modAction(action, data)` | Sabitle, kilitle, sil, bump |
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

**Yıldızlar:** `.mybb-author-stars[data-stars="{p_star_count}"][data-color="{p_star_color}"]` — `topic.tpl` içindeki JS ile doldurulur.

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
| `[notif-unread]` | Okunmamış |
| `[notif-read]` | Okunmuş |

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
| `--mybb-bg-header` | `#1d2d44` | Kategori kart başlığı (admin ayarından override) |
| `--mybb-bg-body` | `#ebf0f6` | Sayfa arka planı |
| `--mybb-bg-card` | `#ffffff` | Kart arka planı |
| `--mybb-bg-subheader` | `#ebf2fb` | Alt başlık şeritleri |
| `--mybb-text-primary` | `#1d2d44` | Ana metin |
| `--mybb-text-muted` | `#8c9ba5` | Soluk metin |
| `--mybb-border-color` | `#c9d3e0` | Kenarlıklar |
| `--mybb-radius-sm/md/lg` | `4/6/8px` | Köşe yuvarlaklığı |

**Editör mavi şerit (DLE uyumu):**

```css
.wseditor { border-top: 5px solid #0c5f7e; }
```

---

## 8. AJAX uç noktaları (şablon JS)

| Modül | URL | İşlev |
|-------|-----|--------|
| `forum_like` | `?controller=ajax&mod=forum_like` | Beğeni / dislike |
| `forum_quote` | `?controller=ajax&mod=forum_quote` | Alıntı metni |
| `forum_upload` | `?controller=ajax&mod=forum_upload` | Dosya/resim yükleme |
| `forum_moderation` | `?controller=ajax&mod=forum_moderation` | Sabitle, kilitle, sil, bump |
| `forum_reports` | `?controller=ajax&mod=forum_reports` | Şikayet |
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

---

## 10. Dil dosyası (`forum.lng`)

Şablonlardaki sabit metinleri dil dosyasına taşıyın. PHP tarafında `$lang['forum_...']` anahtarları kullanılır.

Desteklenen diller:

- `language/Turkish/forum.lng`
- `language/English/forum.lng`
- `language/Russian/forum.lng`

DLE site dili ne ise o klasördeki `forum.lng` otomatik yüklenir (`engine/modules/forum.php`).

---

## 11. Özelleştirme ipuçları

1. **Yeni tema:** `templates/Default/forum/` klasörünü kopyalayıp `{skin}/forum/` altına yapıştırın; `main.tpl`'e CSS/JS ekleyin.
2. **Koşul bloğu eklemek:** PHP'de `$tpl->set("[blok-adi]", "");` + `$tpl->set("[/blok-adi]", "");` veya `set_block(..., "")` ile silin.
3. **Satır şablonu:** `topic_row.tpl` gibi alt şablonlar `$tpl2->load_template()` ile döngüde derlenir; ana şablona `{topic_rows}` olarak basılır.
4. **Rank görseli:** Admin → Rütbe Ayarları → `administrator.png` gibi dosya adı; dosyayı `templates/{skin}/forum/ranks/` altına koyun.
5. **Breadcrumb:** Çoğu sayfada `{forum_breadcrumb}` yeterli; `profile.php` özel breadcrumb üretir.

---

## 12. Sürüm

- **Eklenti:** Forge Forum v1.0  
- **Kılavuz:** Şablon etiketleri — Forge Forum motoru ile uyumlu

Sorular ve güncellemeler için: [forge_forum.xml](forge_forum.xml) ve `engine/modules/forum/actions/` altındaki action dosyalarına bakın.
