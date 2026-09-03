# Sunum Hazırlığı: Soru-Cevap Dokümanı + Kritik Güvenlik Düzeltmesi

## Context

Emre'nin **yarın (4 Eylül 2026)** şefiyle kod incelemesi var. Aynı süreçten geçmiş bir çalışandan şefin soru kalıbını öğrendi:

> migration ne yapıyor / model ne / farkı ne / neyi niye kullandın / ufak syntax / Laravel'in ORM'si ne / authentication üzerine **çok** soru (login) / çerez ne, token ne, token'ı nerede tutuyorsun / N+1'i nasıl önlüyorsun / XSS'i nasıl önlüyorsun / TypeScript

**Kalıp:** temel kavram sorup ardından **"senin projende nerede/nasıl"** diye bağlıyor. Ezber tanım yetmiyor, koddan kanıt istiyor. Ağırlık **backend ve güvenlik**.

Bu konuşmada iki denetim yapıldı ve birkaç gerçek bulgu çıktı — bazıları güçlü cevap malzemesi, biri gerçek açık.

**Kullanıcının kararları:** (1) kritik açık düzeltilecek, gerisi için dürüst cevap hazırlanacak; (2) zayıf noktalar "eksik + sebebi + nasıl çözerdim" biçiminde, savunmacı değil.

**Çıktı:** `C:\Users\emre.guneri\Desktop\hazirlik-sorulari-ve-cevaplari.md`
*(dosya adında boşluk yerine tire — komut satırında sorun çıkarmasın diye)*

---

## Bölüm A — Kritik güvenlik düzeltmesi (önce bu)

### Bulgu

`POST /api/login` uç noktasında **hiçbir rate limit yok.** Web tarafında Breeze'in koruması duruyor (`app/Http/Requests/Auth/LoginRequest.php` → `ensureIsNotRateLimited()`, 5 deneme, email+IP anahtarı), ama `Api\AuthController::login` o sınıfı **kullanmıyor**, kendi elle yazılmış akışını çalıştırıyor:

```php
$request->validate(['email' => 'required|email', 'password' => 'required|string']);
$kullanici = User::where('email', $request->email)->first();
if (! $kullanici || ! Hash::check($request->password, $kullanici->password)) { ... }
```

`routes/api.php:30` de çıplak: `Route::post('/login', [AuthController::class, 'login']);`

Sonuç: **sınırsız şifre denemesi.** `/api/forgot-password` ve `/api/reset-password` de throttle'sız. `bootstrap/app.php`'de `throttleApi()` çağrısı yok.

### Düzeltme

**`app/Http/Controllers/Api/AuthController.php`** — web tarafındaki davranışın **aynısını** uygula (tutarlılık savunulabilir bir cevap veriyor: "web ve API aynı kuralı kullanıyor"):

- `RateLimiter::tooManyAttempts($anahtar, 5)` → aşılmışsa `Lockout` event'i + `ValidationException` ile kalan süre
- Başarısız denemede `RateLimiter::hit()`, başarılıda `RateLimiter::clear()`
- Anahtar: `Str::lower($email) . '|' . $request->ip()` — Breeze'in `throttleKey()` deseniyle aynı

**`routes/api.php`** — üç misafir rotasına ikinci savunma katmanı:

```php
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/forgot-password', [...])->middleware('throttle:6,1');
Route::post('/reset-password', [...])->middleware('throttle:6,1');
```

İki katman bilinçli: `RateLimiter` **kimlik bazlı** (bir hesaba saldırıyı durdurur), `throttle` middleware'i **IP bazlı** (uç noktayı dövmeyi durdurur).

### Düzeltilmeyecekler (dokümanda dürüst cevabı hazırlanacak)

| Bulgu | Neden şimdi değil |
|---|---|
| `APP_DEBUG=true`, `APP_ENV=local` (ve `.env.example`'da da) | Geliştirme ortamı; üretim adımı olarak anlatılacak |
| `SESSION_SECURE_COOKIE` tanımsız → çerezde `Secure` yok | HTTPS yokken anlamsız; üretim kontrol listesine girecek |
| `Password::defaults()` yapılandırılmamış (min 8) | Tek satırlık iyileştirme; "mimari hazır" cevabı verilecek |
| Öğretmen CV'leri public diskte | Tasarım değişikliği gerektirir (private disk + imzalı route) |
| Soft delete hiç yok | Şema değişikliği; teslime bir gün kala riskli |
| Her `/api/login` yeni token üretiyor, eskiler iptal olmuyor | Davranış değişikliği |

---

## Bölüm B — Doküman yapısı

Her soru **dört parçalı**:

> **S:** *(şefin soracağı biçimde)*
> **Cevap:** *(söylenecek şey — kısa, konuşulabilir)*
> **Projeden kanıt:** *(dosya/kod/ölçüm)*
> **Devamı gelirse:** *(muhtemel takip sorusu)*

### 1. Nasıl çalışılır + bilmediğin soruya ne denir
Format açıklaması. **"Bilmiyorum" nasıl söylenir**: kavramı bildiğin kadarını söyle, uydurma, "şöyle araştırırdım" ile bitir. Uydurulmuş cevap yakalanınca bütün güveni götürür.

### 2. Migration, Model, Eloquent *(~12 soru)*
Migration vs model farkı · `up()`/`down()` · ORM nedir, Laravel'inki hangisi · Active Record vs Data Mapper · `$fillable` ve mass assignment · `casts` · accessor + `$appends` · `$table` override · ilişki türleri ve FK'nin hangi tabloda durduğu · **`belongsToMany` neden hiç yok** (üç pivot da ayrı model — `weekly_hours` gibi kendi verisi var, `teacher_course_level` üçlü, ve global scope'suz pivot daha önce 4 veri sızıntısına yol açmıştı) · global scope · `withoutGlobalScopes()` ne zaman

### 3. Sözdizimi mini-quiz *(~12)*
"Ufak syntax sorabilir" dediği kısım. Kısa soru–kısa cevap: `?->` · `??` vs `||` · `fn () =>` vs `function () {}` · closure'da `use ()` · `static::` vs `self::` vs `$this->` · `<=>` ve dizilerle çok kriterli sıralama · `[$a,$b] = $dizi` · `"{$obj->alan}"` vs tek tırnak · anonim sınıf (migration) · `#[Fillable]` attribute · `match` · `private const`
**Projede olmayanlar** dürüstçe: `enum` yok (role/status string + `Rule::in`), `readonly` yok, `declare(strict_types=1)` yok, first-class callable yok.

### 4. Authentication *(~14)* — en çok soru buradan
Login akışı adım adım (csrf-cookie → POST → session çerezi) · şifre nasıl saklanıyor (`'password' => 'hashed'` cast + bcrypt, `BCRYPT_ROUNDS=12`) · `Hash::check` · Sanctum'un iki modu · `EnsureFrontendRequestsAreStateful` ne yapıyor · çıkış · şifre sıfırlama akışı ve **token DB'de hash'li** (`expire: 60`, `throttle: 60`) · `current_password` kuralı · **brute force koruması** *(düzeltme sonrası: var, hem kimlik hem IP bazlı, web ile aynı kural)* · ayrık giriş sayfaları ve `ROL_ESLESTIRME` dizisi · `auth:sanctum` guard'ı

### 5. Çerez, token, session *(~9)*
Çerez nedir · token nedir · **"token'ı nerede tutuyorsun"** → *tutmuyorum*, XSS gerekçesi, `HttpOnly` · Bearer token neden hâlâ var · CSRF nedir, `withXSRFToken` · session nerede (`SESSION_DRIVER=database`, çerezde sadece id) · `SameSite=lax` · `SESSION_SECURE_COOKIE` eksiği (dürüst) · `SANCTUM_STATEFUL_DOMAINS` ne işe yarar

### 6. Yetkilendirme *(~9)*
Authentication vs authorization · üç katman (router guard = UX, middleware = kilit, `School::aktif()` = veri kapsamı) · middleware nedir, zincir · Laravel 11+ `Kernel.php` kalktı, `bootstrap/app.php` · dört özel middleware · **Policy/Gate neden yok** (3 rol + izolasyon global scope'ta; `authorize()` hep `true` ve gerekçesi yorumda) · IDOR koruması nasıl (scope → route model binding 404) · fail-closed kararı · son süper yönetici koruması

### 7. N+1 ve performans *(~7)* — ölçüm var, en güçlü bölüm
N+1 nedir · nasıl önlüyorsun (`with()`, iç içe `with('courseLevels.level')`) · **ölçüm tablosu**:

| Ekran | `with()` ile | olmadan |
|---|---|---|
| Dersler | **3** | 51 |
| Şubeler | **3** | 27 |
| Şube çizelgesi (35 hücre) | **9** | — |
| Ücret raporu | **3** | — |

· `withCount()` ve **`SchoolController`'da neden kullanılmadığı** (global scope alt sorguya sızıyor, `withoutGlobalScopes` ile sayılıyor) · sihirbazda 6 sorgu + `keyBy`/`mapWithKeys` ile bellek içi indeks · toplu `insert` + `array_chunk(500)` · lazy vs eager

### 8. Güvenlik *(~16)*
XSS nedir, nasıl önlüyorsun → **`v-html` ve `{!! !!}` sıfır kullanım, denetlendi** · SQL injection → parametreli sorgu; **`selectRaw($sutun)` tuzağı ve neden güvenli** (üç sabit çağrı) · CSRF · mass assignment · **dosya yükleme** (`image|mimes|max`, rastgele ad, eski dosya siliniyor; CV public diskte — dürüst) · brute force *(düzeltildi)* · şifre politikası (min 8 — dürüst, tek satırda sıkılaşır) · `APP_DEBUG=true` (dürüst, üretim listesi) · HTTPS · `$hidden` ile şifrenin JSON'dan çıkması · `role` neden `$fillable` içinde (ve neden şu an sömürülemez) · rate limit iki katman

### 9. Frontend ve TypeScript *(~9)*
**TypeScript yok** — dürüst cevap + ne olduğunu bildiğini gösteren açıklama + **gerçek örnek**: `KayitGezinme.vue`'da `"153" === 153` yüzünden gezinme düğmeleri hiç açılmıyordu, TS bunu derlemede yakalardı · buna karşılık **PHP'de tip kullanıyorsun** (`: array`, `: ?self`) · SPA nedir · Vue reaktivite/`ref` · `computed` vs `watch` · neden Vue · Pinia niye · Vite niye · Tailwind `content` tuzağı

### 10. Veritabanı tasarımı *(~9)*
`schedule_entries` **üç composite unique** — çakışmanın DB katmanı garantisi · `decimal(8,2)` neden float değil (para, yuvarlama) · `sections`'ta `school_id` yok, `SubeOkulScope` · `cascadeOnDelete` vs `nullOnDelete` kuralı (sahiplik/referans) · `lesson_swaps` neden ayrı tablo (şablon vs tarihli olay) · migration'da **veri taşıma** (`DB::table()->update()` + simetrik `down()`) · saatlerin saklanmaması · **soft delete yok** (dürüst) · neden PostgreSQL

### 11. Mimari ve "neden" soruları *(~11)*
Neden servis katmanı · servisler HTTP bilmez ilkesi ve kazandırdığı · neden repository yok · neden queue yok · neden Inertia değil · Service Container / DI (metot enjeksiyonu) · Service Provider `boot()` vs `register()` · Facade nedir (`Pdf::loadView`) · Config dosyası (`config/api-dokuman.php` — kendi yazdığın) · Artisan komutu + **Reflection** kullanımı · tek üçüncü parti paket dompdf

### 12. Algoritma *(~6)*
İki faz neden ayrı · greedy nedir, optimal mi · iki sezgisel · determinizm nasıl (eşitlikte id, `ORDER BY`) · **karmaşıklık**: Faz 1 `O(S·D·A log A)`, Faz 2 `O(N·G·P)`, sorgu sayısı sabit · ölçüm 1,08 sn / %99,3 · neden genetik algoritma değil

### 13. Zayıf noktalar — dürüst cevaplar *(~9)*
Her biri **eksik → sebebi → nasıl çözerdim** biçiminde:
**test yok** (öncelik sırası: `SlotYerlestirmeServisi`, `School::aktif()`, `TakasServisi`, `CourseRequest`) · TypeScript yok · Policy yok · soft delete yok · `APP_DEBUG` · şifre politikası · CV public diskte · token birikmesi · optimistic locking yok · hafta seçici yok · eski Blade duruyor

⚠️ **`tests/Feature/ExampleTest.php` muhtemelen kırık** — `/` adresinin 200 dönmesini bekliyor ama artık 302 (`/app/giris`'e yönlendiriyor). Şef "testleri çalıştır" derse görünür. Doğrulanıp dokümanda uyarı olarak yer alacak.

### 14. Hızlı tekrar kartı
Son 10 dakikada bakılacak tek sayfa: en kritik 20 cevap, ölçüm tablosu, dosya yolları.

---

## Uygulama sırası

1. `Api\AuthController` + `routes/api.php` düzeltmesi
2. `php -l`, `route:list` ile doğrulama
3. Rate limit'in **gerçekten çalıştığını** test et (6 kez yanlış şifre → 429/kilit mesajı), sonra `RateLimiter::clear`
4. `tests/Feature/ExampleTest.php` durumunu doğrula
5. Dokümanı yaz
6. Commit (kod + doküman ayrı)

## Doğrulama

- Rate limit: art arda başarısız giriş denemesi → 5'inciden sonra kilit; başarılı girişten sonra sayaç sıfırlanıyor mu
- Mevcut girişin bozulmadığı: SPA'nın kullandığı `/giris/{rol}` web rotası etkilenmemeli (ayrı akış)
- Dokümandaki her kod alıntısı ve rakam gerçek dosyayla karşılaştırılacak — bu konuşmada Downloads'daki dokümanda bulunan türden hata (eksik `->names()`) tekrarlanmayacak
- N+1 ölçüm tablosu bu konuşmada ölçüldü, aynen kullanılacak

## Kapsam dışı

- Yeni özellik yok
- Bölüm A'daki tabloda "düzeltilmeyecek" denen maddelere dokunulmayacak
- `hazirlik-sorulari-ve-cevaplari.md` depoya **eklenmeyecek** (kişisel hazırlık notu); istenirse ayrıca eklenir
