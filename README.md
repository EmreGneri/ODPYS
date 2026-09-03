# ODPYS — Okul Ders Programı Yönetim Sistemi

Okulların haftalık ders programını **otomatik üreten** web uygulaması. Elle yapılınca günler süren çakışmasız çizelge kurma işini; öğretmen yetkilerini, müfredat saatlerini ve kapasite kısıtlarını gözeterek saniyeler içinde tamamlar.

**Laravel 12** · **PostgreSQL** · **Vue 3 SPA** · **Laravel Sail (Docker)**

---

## Ne yapıyor?

- **Otomatik program üretimi** — iki fazlı sihirbaz: önce şube-ders çiftlerine en az yüklü uygun öğretmeni atar, sonra çakışmasız biçimde gün ve saatlere yerleştirir. 40 şube / 100 öğretmen ölçeğinde **~1 saniye**.
- **Çoklu okul** — tek kurulumda birden fazla okul; her yönetici yalnızca yetkili olduğu okulun verisini görür.
- **Rol bazlı yetkilendirme** — süper yönetici (tüm okullar), okul yöneticisi (tek okul), öğretmen (kendi programı).
- **Çizelge çıktıları** — şube ve öğretmen bazlı haftalık çizelge, yük dağılımı raporu, PDF ve Excel indirme.
- **Elle düzenleme** — üretilen programda hücre bazlı müdahale; müfredat, öğretmen yetkisi ve çakışma kontrolünden geçer.
- **İzin yönetimi** — öğretmen talep oluşturur, yönetici onaylar; izne denk gelen dersler çizelgede işaretlenir.
- **Ders takası** — izin yüzünden boşalan ders, aynı gün içinde başka bir ders saatiyle yer değiştirebilir. Haftalık program bozulmaz.
- **Aylık ücret raporu** — üretilmiş programdaki saatler ders bazlı ücretle çarpılarak hesaplanır.
- **REST API + Swagger dokümanı** — 66 uç nokta, `/api-dokuman` adresinde gezilebilir ve denenebilir.

---

## Teknoloji yığını

| Katman | Teknoloji |
|---|---|
| Backend | PHP 8.5, Laravel 12 |
| Veritabanı | PostgreSQL 18 |
| Frontend | Vue 3 (Composition API), Vue Router 4, Pinia |
| HTTP istemcisi | Axios |
| Derleme | Vite 8 |
| Stil | Tailwind CSS 3 |
| Kimlik doğrulama | Laravel Sanctum (çerez + token) |
| Ortam | Docker / Laravel Sail |

---

## Kurulum

**Gereken:** Docker ve Docker Compose. PHP veya Node'un makinede kurulu olmasına gerek yok — hepsi konteynerde.

```bash
git clone <depo-adresi> odpys
cd odpys
cp .env.example .env
```

Bağımlılıkları kur. İlk seferde Composer'ı geçici bir konteynerde çalıştırıyoruz — `./vendor/bin/sail` henüz yok, çünkü onu kuracak olan bu adım:

```bash
docker run --rm -v "$(pwd)":/app -w /app composer:latest \
    install --ignore-platform-reqs
```

> `--ignore-platform-reqs`: composer imajındaki PHP sürümü projeninkinden (8.5) farklı olabilir. Bu adımda yalnızca dosyalar indiriliyor, kod çalıştırılmıyor; sürüm kontrolünü atlamak güvenli. Gerçek çalıştırma Sail konteynerinde, doğru PHP sürümüyle olacak.

Konteynerleri başlat:

```bash
./vendor/bin/sail up -d
```

Uygulama anahtarını üret, veritabanını kur ve örnek veriyi yükle:

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
```

Ön yüzü derle:

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

API dokümanını üret:

```bash
./vendor/bin/sail artisan api:dokuman
```

Uygulama: **http://localhost:8000**

> **`storage:link` neden gerekli?** Yüklenen öğretmen fotoğrafları ve özgeçmişleri `storage/app/public/` altında durur ama web'den erişilebilen dizin `public/`'tir. Bu komut ikisi arasında sembolik bağlantı kurar; olmadan dosyalar 404 verir.

### Farklı bir adresten erişecekseniz

Uygulamaya `localhost` dışında bir adresten (örneğin sunucunun IP'si) erişilecekse, `.env` içindeki iki değeri güncelleyin:

```
APP_URL=http://192.168.1.10:8000
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,192.168.1.10:8000,127.0.0.1,::1
```

Ardından `./vendor/bin/sail artisan config:clear`.

`SANCTUM_STATEFUL_DOMAINS` listesinde olmayan bir adresten gelen istek "birinci taraf SPA" sayılmaz, oturum çerezi kontrol edilmez ve bütün `/api/*` istekleri **401** döner.

---

## Varsayılan hesaplar

`migrate --seed` sonrası:

| Rol | E-posta | Şifre |
|---|---|---|
| Süper yönetici | `admin@okul.edu.tr` | `password` |
| Öğretmen | `ogretmen@okul.edu.tr` | `password` |

Giriş adresleri ayrıdır: `/app/giris/yonetici` ve `/app/giris/ogretmen`.

> Bunlar yalnızca geliştirme içindir. Gerçek kullanımda ilk iş şifreleri değiştirmek olmalı (Profil ekranından).

---

## İlk kullanım: kurulum sırası

Adımlar **birbirine bağımlıdır**; sıra atlanırsa sonraki adım çalışacak veri bulamaz.

| # | Ekran | Neden bu sırada |
|---|---|---|
| 1 | **Okul Ayarları** | Ders saatleri veritabanında saklanmaz, bu ayarlardan hesaplanır. Haftalık kapasite de buradan çıkar. |
| 2 | **Seviyeler** | Şubeler seviyeye bağlı açılır, dersler haftalık saatlerini seviye seviye alır. |
| 3 | **Derslikler** | Her şubenin sabit dersliği olur. |
| 4 | **Şubeler** | Dersliksiz şubenin programı üretilemez. |
| 5 | **Dersler** | Müfredat burada tanımlanır: hangi ders, hangi seviyede, haftada kaç saat. |
| 6 | **Öğretmenler** | Haftalık azami saat, sihirbazın kapasite kısıtı. |
| 7 | **Öğretmen ders yetkileri** | ⚠️ Sihirbazın en kritik girdisi. Boşsa sihirbaz **hiçbir atama yapamaz**. |
| 8 | **Program Sihirbazı** | 1. adım öğretmen ataması, 2. adım gün/saat yerleştirme. |
| 9 | **Çizelge** | Sonucu görüntüle, PDF/Excel indir, gerekirse hücre düzenle. |

Sistem içinden ayrıntılı kullanım kılavuzu: **`/app/nasil-kullanilir`**

---

## Mimari

```
Tarayıcı (Vue 3 SPA)
      │  JSON + oturum çerezi
HTTP katmanı      routes/api.php → Middleware → Controller → FormRequest
      │
İş mantığı        app/Services/ — 9 servis (sihirbaz, çizelge, izin, takas, ücret)
      │
Model katmanı     Eloquent + Global Scope (çoklu okul filtrelemesi)
      │
PostgreSQL        Çakışma kuralları unique index olarak burada da kilitli
```

Öne çıkan tasarım kararları:

- **Çakışma kuralları veritabanında.** `schedule_entries` tablosunda üç composite unique index var (şube+gün+saat, öğretmen+gün+saat, derslik+gün+saat). Algoritma hatalı olsa bile veritabanı bozuk satırı reddeder.

- **Çoklu okul filtrelemesi global scope ile.** Her sorguya elle `where('school_id', ...)` yazmak yerine `OkulScope` otomatik ekliyor — birini unutmanın veri sızıntısı demek olduğu bir tasarımda bu riski yapısal olarak kaldırıyor. Yetkinin tek karar noktası `School::aktif()`.

- **Haftalık program bir şablondur.** `schedule_entries` tarihsizdir ("her Perşembe 3. ders"). İzinler ve ders takasları **tarihli olaylardır** ve ayrı tablolarda tutulur; ikisi yalnızca görüntülenirken birleşir. Bu sayede bir takas geri alındığında program kendiliğinden eski hâline döner ve sonraki haftalar hiç etkilenmez.

- **Servisler HTTP bilmez.** Request almaz, Response döndürmez; dizi döndürür. Aynı iş mantığı hem eski Blade ekranlarını hem yeni JSON API'yi besliyor.

- **Derslik çakışması yapısal olarak imkânsız.** Her şubenin sabit dersliği var ve şube aynı anda tek ders görüyor; ayrıca kontrol etmeye gerek yok.

Ayrıntılı anlatım için aşağıdaki dokümanlara bakın.

---

## Proje yapısı

```
app/
├── Console/Commands/     API dokümanı üreteci
├── Http/
│   ├── Controllers/      Blade döndüren eski controller'lar
│   │   └── Api/          JSON döndüren API controller'ları
│   ├── Middleware/       Yetki kontrolü (admin, teacher, superadmin, aktifokul)
│   └── Requests/         FormRequest — doğrulama kuralları
├── Models/               Eloquent modelleri
│   └── Scopes/           OkulScope, SubeOkulScope
└── Services/             İŞ MANTIĞI

database/migrations/      Veritabanı şeması
resources/js/             Vue SPA (sayfalar, bileşenler, router, store)
resources/views/          Blade (eski sistem + SPA kabuğu + PDF şablonu)
routes/
├── web.php               Blade rotaları + /app/{any?} SPA kabuğu
└── api.php               REST API
config/api-dokuman.php    API dokümanı açıklamaları
```

---

## Komutlar

```bash
# Program sihirbazı arayüzden çalıştırılır (/app/program)

# API dokümanını yeniden üret (rota eklendiğinde)
./vendor/bin/sail artisan api:dokuman

# Ön yüzü derle (.vue veya .js değişikliğinden sonra ZORUNLU)
./vendor/bin/sail npm run build

# Veritabanını sıfırla ve örnek veriyi yeniden yükle
./vendor/bin/sail artisan migrate:fresh --seed

# Ayar önbelleğini temizle (.env değişikliğinden sonra)
./vendor/bin/sail artisan config:clear
```

> **Sık yapılan hata:** `.vue` dosyası değiştirildikten sonra `npm run build` çalıştırmayı unutmak. Ayrıca `public/hot` dosyası varsa (`npm run dev`'den kalma) derleme sonucu yerine ölü bir geliştirme sunucusu kullanılmaya çalışılır — SPA boş açılır. Çözüm: `rm public/hot`.

---

## Dokümantasyon

| Dosya | İçerik |
|---|---|
| [`coklu-okul-anlatim.md`](coklu-okul-anlatim.md) | Çoklu okul mimarisi ve global scope'lar |
| [`spa-gecis-plani.md`](spa-gecis-plani.md) | Blade'den SPA'ya geçiş planı |
| [`faz3-sifre-sifirlama-egitimi.md`](faz3-sifre-sifirlama-egitimi.md) | Şifre sıfırlama akışı |
| [`faz4-faz6-spa-gecisi-egitimi.md`](faz4-faz6-spa-gecisi-egitimi.md) | Ekranların SPA'ya taşınması, karşılaşılan hatalar |
| **`/api-dokuman`** | Swagger UI — 66 uç nokta, denenebilir *(yönetici girişi gerekir)* |
| **`/app/nasil-kullanilir`** | Son kullanıcı kılavuzu, sistem içinden |

---

## Bilinen sınırlar

Dürüstçe:

- **Otomatik test yok.** Projenin en büyük eksiği; doğrulamalar elle ve tarayıcı üzerinden yapıldı.
- **Program algoritması optimal değil.** Greedy bir yaklaşım; 40 şube ölçeğinde %99,3 yerleşme sağlıyor, kalanı yönetici elle düzeltiyor. Optimal çözüm (kısıt programlama) sonucu deterministik ve açıklanabilir olmaktan çıkarırdı.
- **Çizelge yalnızca içinde bulunulan haftayı gösterir.** Hafta seçici yok; gelecek tarihli izin veya takas o hafta gelene kadar çizelgede görünmez.
- **Eski Blade ekranları hâlâ yayında.** SPA'ya geçiş kademeli yapıldı; eski sistem güvenlik ağı olarak duruyor ve eski adreslerden erişilebilir.
- **Optimistic locking yok.** İki kullanıcı aynı kaydı düzenlerse son yazan kazanır.
- **Üretim sunucusu yapılandırması yok.** Geliştirmede `artisan serve` kullanılıyor; gerçek dağıtımda nginx + PHP-FPM gerekir.
