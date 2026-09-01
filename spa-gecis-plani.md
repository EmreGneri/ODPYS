# ODPYS: Tam Ayrı SPA (Vue) + REST API Geçiş Planı

## Context

Şu an ODPYS, Laravel Blade ile render edilen, oturum (session cookie) tabanlı klasik bir web uygulaması. Bugün şartname 3.3 ("modüler mimari ve API desteği") için ayrı, küçük, salt-okunur bir JSON API eklendi (Sanctum token ile, sadece 3 endpoint: çizelge×2, ücret×1, admin-only). Mentörün planı bu API'yi büyütüp **tüm uygulamayı** bir Vue SPA'sına taşımak — kullanıcı "tam ayrı SPA + REST API" yaklaşımını seçti (Inertia.js gibi daha küçük bir alternatif değil).

**Neden bu büyük bir iş:** Envanter çıkarıldı — 18 web controller, 40 Blade sayfası, 9 FormRequest, 8 servis sınıfı var. Frontend tarafında şu an **hiç Vue Router, Pinia/state yönetimi, axios/fetch client yok** — sadece 6 küçük Vue bileşeni var, hepsi düz HTML form POST'u ile çalışıyor (JSON/fetch kullanmıyor).

**Kimlik doğrulama kararı (mentör düzeltmesi ile netleşti):** ODPYS'in **kendi** SPA'i (`/app/*`) için Bearer token + `localStorage` **kullanılmayacak** — bunun yerine Laravel'in resmi önerdiği **Sanctum çerez/oturum (cookie/session) SPA modu** kullanılacak. Bu, hem CSRF korumasını aktif tutar hem token'ı `localStorage`'da tutmanın XSS riskini ortadan kaldırır. Bugün eklenen Bearer token akışı (`/api/login`, `Authorization: Bearer <token>`) **silinmiyor** — o, Postman/mobil uygulama/harici entegrasyon gibi **üçüncü taraf** istemciler için doğru ve kalıcı bir yetenek olarak duruyor. Sanctum ikisini aynı anda destekler: `EnsureFrontendRequestsAreStateful` middleware'i, isteğin bilinen bir "stateful domain"den (kendi SPA'imiz) geldiğini görürse çerezi kontrol eder; gelmiyorsa (dış istemci) Bearer token'a düşer.

**Önemli sonuç — çoklu okul sorunu göründüğünden çok daha küçük:** SPA çerez/oturum tabanlı olduğu için, aslında **aynı PHP session'ı** web tarafıyla paylaşıyor. Yani `session('aktif_okul_id')`, `EnsureAktifOkul`, `School::aktif()` — bunların **hiçbiri değişmeden, aynen** SPA için de çalışır. Daha önce planlanan "durumsuz API" karmaşıklığına (her istekte okulu yeniden çözme) gerek yok; bu, mevcut web mimarisiyle zaten tutarlı. Tek eksik, SPA'nın aktif okulu **değiştirebilmesi** için mevcut `okullar.aktif` mantığının bir API karşılığı (Faz 1'de).

**Amaç:** Mevcut hiçbir işlevi kaybetmeden, adım adım, eski Blade sistemiyle paralel çalışacak şekilde SPA'ya geçiş.

---

## Faz 0 — Sanctum SPA (Çerez) Kimlik Doğrulamasını Etkinleştir

Çoklu okul sorunu artık **ayrı bir çözüm gerektirmiyor** (bkz. Context) — sadece Sanctum'un SPA modunu doğru kurmak yeterli:

1. **`bootstrap/app.php`** — `api` middleware grubunun **en başına** `\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class` eklenir. Bu, bugünkü `install:api` kurulumunun otomatik yapmadığı, Sanctum'un resmi dokümantasyonunda SPA modu için **zorunlu tuttuğu** tek ek adım.
2. **`config/sanctum.php`** — `stateful` domain listesi zaten `Sanctum::currentApplicationUrlWithPort()` içeriyor (bugünkü kurulumda hazır geldi), ekstra değişiklik gerekmiyor.
3. **Mevcut `EnsureAktifOkul`, `EnsureUserIsAdmin`, `EnsureUserIsTeacher` middleware'leri aynen, hiç değiştirilmeden, API rota gruplarına da eklenir** — web tarafında zaten çalışan aynı mantık, aynı session, aynı scope'lar.
4. **Okul değiştirme:** `Api\SchoolController@aktifYap` (Faz 1 tablosuna eklendi) — mevcut `SchoolController@aktifYap`'ın JSON karşılığı, SPA'da okul değiştirildiğinde çağrılır, `session(['aktif_okul_id' => ...])`'ı bugünkünle birebir aynı şekilde günceller.

**Not (ileride ele alınacak, şimdi kapsam dışı):** Şu an herhangi bir yönetici herhangi bir okula erişebiliyor — okul bazlı yönetici yetkilendirmesi (bu admin sadece şu okullara erişebilir) yok. Bu, mevcut web mimarisinin de bugünkü hâli; SPA geçişi bunu değiştirmiyor, sadece mevcut davranışı taşıyor. İleride gerekirse ayrı bir iş olarak ele alınmalı.

---

## Faz 1 — API Yüzeyini Genişlet (Yazma Uç Noktaları)

Şu an API'de sadece 3 okuma endpoint'i var. Her mevcut web controller'ın API karşılığı gerekiyor — **mevcut FormRequest ve Servis sınıfları aynen tekrar kullanılacak**, sadece controller katmanı Blade yerine JSON döner (bugünkü `Api\CizelgeController` deseni: servis zaten HTTP-agnostik dizi döndürüyor).

Yeni controller'lar, `app/Http/Controllers/Api/` altında, mevcut web controller'ların birebir karşılığı:

| Yeni API Controller | Karşılık gelen web controller | Reuse edilecek |
|---|---|---|
| `Api\SchoolController` | `SchoolController` (index/edit/update/okullar/okulEkle **+ aktifYap**) | `SchoolRequest`, `OkulOlusturRequest`, `OkulAyarServisi` |
| `Api\LevelController` | `LevelController` | `LevelRequest` |
| `Api\SectionController` | `SectionController` | `SectionRequest` |
| `Api\RoomController` | `RoomController` | `RoomRequest` |
| `Api\CourseController` | `CourseController` | `CourseRequest`, `DersServisi` |
| `Api\TeacherController` | `TeacherController` | (yeni bir `TeacherRequest` yazılmalı — şu an web tarafı da inline validation kullanıyor olabilir, kontrol edilecek), `OgretmenServisi` |
| `Api\ProgramController` | `ProgramController` | `ProgramDuzenleRequest`, `OgretmenAtamaServisi`, `SlotYerlestirmeServisi` |
| `Api\IzinController` | `IzinController` | `IzinRequest`, `IzinServisi` |

Her biri, standart REST fiilleriyle (`index`, `store`, `update`, `destroy`) ilgili web rotasının POST/PUT/DELETE mantığını JSON girdi/çıktıya çevirir. Doğrulama hataları Laravel'in standart 422 + `{errors: {...}}` formatında otomatik döner (FormRequest zaten bunu yapıyor, ekstra kod gerekmez).

**Eksik olan, eklenmesi gereken yeni uç noktalar** (bugünkü API'nin kendi yorum satırında zaten eksik diye işaretlenmiş):
- `GET /api/ogretmen/cizelge` — öğretmenin **kendi** programı (bugün sadece admin-only `/api/cizelge/ogretmen/{id}` var)
- `GET /api/ogretmen/izinler`, `POST /api/ogretmen/izinler` — öğretmenin kendi izin talepleri
- `POST /api/forgot-password`, `POST /api/reset-password` — şifre sıfırlama akışının API karşılığı (e-postadaki link SPA'nın bir sayfasına gidecek, o sayfa bu endpoint'lere POST atacak)
- `GET /api/dashboard` — dashboard özet kartları

---

## Faz 2 — Frontend Altyapısı (Sıfırdan Kurulacak)

Şu an **hiçbiri yok**, hepsi eklenecek:

```bash
npm install vue-router@4 pinia axios
```

- **`resources/js/router.js`** (yeni) — tüm sayfalar için rota tanımları, `beforeEach` guard'ı ile admin/öğretmen/guest ayrımı (mevcut web `admin`/`teacher` middleware'lerinin frontend karşılığı — backend zaten API tarafında yetkiyi zorunlu kılıyor, bu sadece UX için erken yönlendirme).
- **`resources/js/stores/auth.js`** (Pinia store, yeni) — **token tutmaz** (çerez tabanlı kimlik doğrulamada buna gerek yok, tarayıcı otomatik yönetir). Sadece: giriş yapmış kullanıcı bilgisi, rol, seçili okul id'si (`aktifOkulId`), arayüz durumu. Uygulama açılışında `GET /api/user` ile "hâlâ giriş yapılmış mı" kontrol edilir (çerez varsa otomatik doğrulanır).
- **`resources/js/api.js`** (yeni) — merkezi axios instance:
  ```js
  axios.defaults.withCredentials = true;   // her istekte oturum cerezini gonder
  axios.defaults.withXSRFToken = true;     // CSRF korumasini otomatik uygula
  ```
  Uygulama açılışında (ilk istekten önce, bir kere) `GET /sanctum/csrf-cookie` çağrılır — Sanctum'un SPA modunun zorunlu ilk adımı, bu XSRF çerezini kurar. 401 gelirse otomatik `/login`'e yönlendirme; 422 gelirse hatayı component'e olduğu gibi ileten bir yapı. `Authorization: Bearer` başlığı **eklenmez** — SPA'nın kendi istekleri için gerek yok.
- **`resources/js/App.vue`** (yeni, kök bileşen) — nav/layout (mevcut `layouts/app.blade.php` + `navigation.blade.php`'nin Vue karşılığı), `<router-view>`.
- **`resources/js/app.js`** — **tamamen değişecek**: bugünkü "her `[data-vue-component]` div'i için ayrı mini Vue app'i mount et" deseni yerine, **tek bir kök SPA** (`createApp(App).use(router).use(pinia).mount('#app')`) kurulacak.
- **Yeni Blade "kabuk" sayfası** — `resources/views/spa.blade.php` gibi tek bir dosya, sadece `<div id="app"></div>` içerir; SPA'nın tüm rotaları (`/app/*` gibi bir prefiks altında, bkz. Faz 6) bu tek Blade dosyasına düşer, gerisini Vue Router yönetir.

---

## Faz 3 — Kimlik Doğrulama Akışı

**Önemli basitleşme:** Sanctum'un SPA modu için resmi öneri, ayrı bir "API login" endpoint'i icat etmek değil, **var olan web giriş rotalarını aynen kullanmak.** Yani SPA, bugün zaten çalışan `POST /giris/{rol}` (`AuthenticatedSessionController@store`) ve `POST /logout` rotalarına axios ile istek atar — bu rotalar zaten oturum (session) kurup/kapatıyor, ekstra backend kodu **gerekmiyor**:

- Giriş: SPA önce `GET /sanctum/csrf-cookie`, sonra `POST /giris/yonetici` ya da `POST /giris/ogretmen` çağırır (bugünkü `AuthenticatedSessionController::store()` — yanlış sayfadan giriş reddi dahil — hiç değişmeden çalışır). Başarılı olursa çerez kurulur, sonraki tüm `/api/*` istekleri otomatik kimlik doğrulanmış olur.
- İki Vue sayfası: `GirisSecim.vue` (seçim ekranı), `Giris.vue` (rol'e göre parametreli form) — bugünkü `login-secim.blade.php`/`login.blade.php` ikilisinin Vue karşılığı, aynı iki rotaya POST atar.
- Çıkış: SPA `POST /logout`'u çağırır (mevcut `AuthenticatedSessionController::destroy()`, hiç değişmeden).
- `/api/login` (Bearer token üreten, bugün yapılan) **dokunulmadan kalır** — SPA bunu kullanmaz, sadece harici istemciler (Postman/mobil) için var olmaya devam eder.

---

## Faz 4 — Sayfa Sayfa Dönüşüm (Önerilen Sıra)

Basitten karmaşığa, deseni erken oturtacak şekilde:

1. **Basit CRUD ekranları önce** (Seviyeler, Derslikler, Şubeler, Dersler) — bunların Vue form bileşenleri (`SeviyeFormu.vue`, `DerslikFormu.vue`, `SubeFormu.vue`, `DersFormu.vue`) **zaten var**, sadece submit mantığı değişecek: native `<form method=POST>` yerine `api.post(...)` (axios), başarı sonrası `router.push(...)`, 422 hatası geldiğinde Laravel'in `$errors` bag'i yerine axios'un `error.response.data.errors` objesini component içinde göstermek.
2. **Öğretmenler** — `OgretmenFormu.vue` ve `OgretmenDersleriFormu.vue` da hazır, aynı submit dönüşümü + dosya yükleme (bkz. Faz 5).
3. **Okul ayarları / Okullar listesi** — `schools/*` Blade sayfalarının Vue karşılığı, yeni yazılacak (hazır Vue bileşeni yok).
4. **Dashboard, Profil** — basit, tek API çağrısıyla veri çeken sayfalar.
5. **İzinler** (hem öğretmen hem yönetici tarafı) — orta karmaşıklıkta, form + liste + onay/red aksiyonları.
6. **Program (sihirbaz + manuel düzenleme)** — **en karmaşık ekran**: sihirbaz çalıştırma butonları, sonuç raporu, ve bugün eklenen hücre-bazlı manuel düzenleme (`program/hucre-duzenle.blade.php`) — bir modal/route olarak Vue'ya taşınacak, `ProgramDuzenleRequest`'in 3 aşamalı çakışma kontrolü aynen API'de de çalışıyor olacak.
7. **Çizelge + Ücretler** — API zaten hazır (bugün yapıldı), sadece Vue tarafında tablo/ızgara görünümü yazılacak. **PDF/Excel indirme linkleri SPA'ya çevrilmeyecek, hiç değişmeyecek** — SPA çerez/oturum tabanlı kimlik doğrulama kullandığı için, düz `<a href="/cizelge/sube/1/pdf">` bugünkü web rotasına gider, tarayıcı oturum çerezini **otomatik** gönderir, ekstra hiçbir şey gerekmez (token bazlı bir mimaride olsaydı bu bir sorun olurdu — çerez modunda değil).

---

## Faz 5 — Dosya Yükleme (Öğretmen Fotoğraf/CV)

`OgretmenFormu.vue` zaten `enctype="multipart/form-data"` ile native submit yapıyor. API'ye taşınınca: axios ile `FormData` nesnesi oluşturup `Content-Type: multipart/form-data` ile `POST`/`PUT` atılacak (PUT ile dosya yüklemede Laravel'in `_method` spoofing'i axios'ta da aynen kullanılabilir — `POST` atıp body'e `_method=PUT` eklenir, tarayıcı dosya input'larını PUT ile native göndermediği için bu zaten mevcut web tarafında da aynı şekilde çözülmüş olmalı, kontrol edilecek).

---

## Faz 6 — Paralel Yürütme Stratejisi (Riski Azaltmak İçin)

Sert bir kesim (eski sistemi kapatıp yenisini açmak) yerine:

1. SPA, **yeni bir path altında** (`/app/*`) yayına alınır — eski Blade rotaları (`/okul`, `/cizelge` vb.) **hiç dokunulmadan, paralel** çalışmaya devam eder.
2. Her sayfa dönüştürüldükçe, o sayfaya giden linkler (nav menüsü, "gezinme" bağlantıları) `/app/...`'e yönlendirilir.
3. Tüm sayfalar dönüşüp gerçek kullanıcıyla (sen + mümkünse şef) test edilene kadar eski Blade sistemi **canlı ve kullanılabilir kalır** — bir şey ters giderse anında eskiye dönülebilir.
4. Son adımda: eski Blade route grupları, controller'lardaki view-döndüren metotlar, artık kullanılmayan Blade dosyaları silinir; FormRequest ve Servis sınıfları **silinmez** (API tarafından hâlâ kullanılıyor).

---

## Doğrulama (Her Fazın Sonunda)

- **Faz 0:** `GET /sanctum/csrf-cookie` + `POST /giris/yonetici` ile SPA'dan gerçek bir çerez oturumu kurulduğunu doğrula (tarayıcı DevTools → Application → Cookies'te `laravel_session` görünmeli). Sonra `/api/cizelge/sube/1` gibi bir korumalı endpoint'e axios ile (token değil, sadece çerezle) istek atıp 200 döndüğünü doğrula. Okul değiştirme (`Api\SchoolController@aktifYap`) çağrıldıktan sonra aynı endpoint'in farklı okulun verisini döndürdüğünü (bugünkü Ankara/İstanbul çapraz testi gibi) doğrula; öğretmen hesabıyla giriş yapıp kendi okulunun dışına çıkamadığını doğrula.
- **Faz 1:** Her yeni endpoint için `curl`/Postman ile CRUD döngüsü (oluştur → oku → güncelle → sil) gerçek veriyle test edilir, tıpkı bugünkü `/api/login` + `/api/ucretler` testlerinde yapıldığı gibi.
- **Faz 4 (her sayfa):** Dönüştürülen her sayfa, gerçek tarayıcıda (Chrome DevTools Network sekmesinden gerçek API çağrılarını izleyerek) elle denenir — hem admin hem öğretmen hesabıyla, hem başarı hem hata (422) senaryosuyla.
- **Faz 6 sonunda:** Eski Blade sayfası ile yeni Vue sayfası **yan yana**, aynı veriyle karşılaştırılıp tam eşleştiği doğrulanmadan eski sayfa silinmez.
