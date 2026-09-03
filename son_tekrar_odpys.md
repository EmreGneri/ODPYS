# ODPYS — Son Tekrar: Dosya Haritası + Authentication/Login

Bu tur soru-cevap yerine projenin **dosya haritası** anlatılıyor. Ardından authentication ve login, bu dosyaların birlikte nasıl çalıştığı gösterilerek açıklanıyor.

Graphify kullanılmadı; mevcut dosyalar, rotalar ve kodlar doğrudan kontrol edildi. Dosya değiştirilmedi.

**Kapsam:** Uygulamanın kaynak, yapılandırma, migration, test ve dokümantasyon dosyaları tek tek ele alınıyor. `vendor`, `node_modules`, önbellek ve derlenmiş dosyalar tek tek değil, görevleriyle açıklanıyor; bunlar çoğunlukla uygulama mantığının yazıldığı yerler değil.

---

## 1. Önce projenin büyük resmi

Projede aynı anda **iki arayüz yapısı** bulunuyor:

| Yapı | Ekran nasıl hazırlanıyor? | Hangi dosyalar çalışıyor? |
|---|---|---|
| Eski Blade yapısı | Laravel HTML sayfası hazırlıyor. Bazı formlara küçük Vue uygulamaları yerleştiriliyor. | Web controller'ları, Blade dosyaları, `app.js` |
| Yeni Vue SPA | Laravel başlangıç HTML'ini veriyor; sonraki ekranları Vue yönetiyor, verileri API'den alıyor. | Vue sayfaları, Vue Router, Axios, API controller'ları |

Bu yüzden iki tane `CourseController.php` veya hem `DersFormu.vue` hem `Dersler.vue` görmek normal.

Bunlar aynı görevin birebir gereksiz kopyaları değil:

- Biri eski Blade sayfasına hizmet ediyor.
- Diğeri yeni Vue ekranına JSON veriyor.
- Ortak iş mantığının bir kısmı servislerde paylaşılıyor.

**Genel işlem zinciri:**

```
Ekran → HTTP isteği → Route → Middleware → Request doğrulaması → Controller → Service/Model → Veritabanı → Cevap → Ekran
```

Her işlem bütün katmanları kullanmak zorunda değil. Basit bir işlem controller'dan doğrudan modele gidebilir.

---

## 2. Uygulamayı başlatan ve yönlendiren dosyalar

### Başlangıç dosyaları

| Dosya | Ne yapıyor? |
|---|---|
| `public/index.php` | Laravel'in HTTP giriş noktası. Web sunucusundan gelen istek uygulamaya buradan giriyor. |
| `bootstrap/app.php` | Laravel uygulamasını yapılandırıyor. Rota dosyalarını, middleware alias'larını ve hataların JSON olarak gösterilme koşullarını tanımlıyor. |
| `bootstrap/providers.php` | Uygulama açılırken yüklenecek service provider sınıflarını listeliyor. |
| `app/Providers/AppServiceProvider.php` | Uygulama başlangıcındaki özelleştirmeleri yapıyor. Parola sıfırlama e-postasındaki bağlantıyı Vue ekranına yönlendirecek şekilde değiştiriyor. |
| `artisan` | Komut satırından Laravel komutlarını çalıştıran giriş dosyası. |

### `bootstrap/app.php` neden önemli?

Özel middleware isimleri burada gerçek sınıflara bağlanıyor:

- `admin`
- `teacher`
- `aktifokul`
- `superadmin`

Ayrıca Sanctum'un SPA isteklerini session/cookie ile işleyebilmesi için gerekli middleware API grubuna eklenmiş.

Dolayısıyla authentication yalnızca login controller'ından oluşmuyor; bu başlangıç yapılandırması da sistemin parçası.

### Rota dosyaları

| Dosya | Ne yapıyor? |
|---|---|
| `routes/web.php` | Eski Blade ekranlarının adreslerini, PDF/Excel çıktı adreslerini, SPA kabuğunu ve API dokümanı ekranını tanımlıyor. `/` adresini giriş durumuna göre `/app` veya `/app/giris` adresine yönlendiriyor. |
| `routes/api.php` | Vue'nun kullandığı JSON uç noktalarını tanımlıyor. Ders, öğretmen, okul, program, izin, profil ve yönetici işlemleri burada. Ayrı token üreten API login de burada. |
| `routes/auth.php` | Web oturumu açma/kapatma, parola sıfırlama, parola doğrulama ve e-posta doğrulama rotaları. |
| `routes/console.php` | Konsol üzerinden kullanılacak closure tabanlı komutları tanımlar. Örnek `inspire` komutu bulunuyor. |

Route'un görevi işlem yapmak değil, **isteği doğru yere bağlamaktır**.

Örneğin: `POST /api/dersler` geldiğinde hangi controller'ın hangi metodu çalışacak? Bunun cevabını rota verir.

---

## 3. Controller dosyaları

Controller'ı şöyle düşün:

> "İstek geldi. Gerekli veriyi ve servisleri alayım, işlemi yaptırayım, uygun cevap döndüreyim."

### Ortak temel sınıf

`app/Http/Controllers/Controller.php` — Diğer controller'ların türediği ortak temel sınıf. Tek başına ders ekleme veya giriş yapma işlemi gerçekleştirmiyor.

### Hem API hem Blade karşılığı bulunan controller'lar

API dosyası Vue'ya veri/JSON verir, Blade dosyası eski HTML ekranları ve yönlendirmelerle çalışır.

| Dosyalar | Görevleri |
|---|---|
| `CourseController` — `Api/CourseController.php` / `CourseController.php` | Dersleri listeler, oluşturur, günceller ve siler. Kayıt işlemlerinde `DersServisi` kullanılır. Üretilmiş programda kullanılan bir dersin silinmesini kontrol eder. |
| `LevelController` — `Api/LevelController.php` / `LevelController.php` | 9, 10, 11, 12 gibi sınıf seviyelerini yönetir. |
| `RoomController` — `Api/RoomController.php` / `RoomController.php` | Fiziksel derslikleri yönetir. Derslik ile öğrenci şubesi farklı kavramlar. |
| `SectionController` — `Api/SectionController.php` / `SectionController.php` | 9A, 9B gibi şubeleri, bağlı oldukları seviyeyi ve dersliklerini yönetir. |
| `TeacherController` — `Api/TeacherController.php` / `TeacherController.php` | Öğretmen bilgilerini, fotoğraf/CV yüklemelerini, öğretmene giriş hesabı açmayı ve ders-seviye yetkilerini yönetir. |
| `SchoolController` — `Api/SchoolController.php` / `SchoolController.php` | Okul bilgileri, eğitim günleri, saat ayarları, okul listesi, yeni okul ve aktif okul seçimi işlemlerini karşılar. |
| `DashboardController` — `Api/DashboardController.php` / `DashboardController.php` | Okul, seviye, şube, derslik, ders, aktif öğretmen ve program kaydı gibi özet bilgileri hazırlar. Web sürümü öğretmeni kendi paneline yönlendirir. |
| `ProgramController` — `Api/ProgramController.php` / `ProgramController.php` | Otomatik öğretmen atamasını, slot yerleştirmeyi ve programdaki tek hücrenin elle değiştirilmesini/silinmesini başlatır. |
| `CizelgeController` — `Api/CizelgeController.php` / `CizelgeController.php` | Şube ve öğretmen çizelgeleri ile yük dağılımını sunar. PDF ve Excel indirme işlemleri web controller'ında. |
| `IzinController` — `Api/IzinController.php` / `IzinController.php` | Öğretmenin izin talebini, izin listesini, yönetici incelemesini ve onay/ret işlemlerini yönetir. API sürümünde takas adayları, takas ve takası geri alma işlemleri de var. |
| `OgretmenPanelController` — `Api/OgretmenPanelController.php` / `OgretmenPanelController.php` | Giriş yapan öğretmenin kendi bilgilerini ve programını hazırlar. |
| `UcretController` — `Api/UcretController.php` / `UcretController.php` | `UcretServisi` üzerinden ücret raporunu getirir. |
| `ProfileController` — `Api/ProfileController.php` / `ProfileController.php` | Kullanıcının kendi profilini yönetir. API sürümü ad/e-posta ve parola günceller. Web sürümünde profil ekranı ve hesap silme işlemi de bulunur. |

> **Önemli ayrıntı:** API ile Blade dosyalarının aynı isimde olması, bütün davranış ve yetki kontrollerinin birebir aynı olduğu anlamına gelmez. Özellikle eski ve yeni yolların birlikte tutulduğu projelerde ikisini de kontrol etmek gerekir.

### Yalnızca API tarafındaki diğer controller'lar

| Dosya | Görevi |
|---|---|
| `Api/AuthController.php` | `/api/login` isteğini karşılar. E-posta/parola kontrolü yapıp Sanctum kişisel erişim tokenı üretir. Başarısız deneme sınırı uygular. Vue'nun normal login'i bunu kullanmıyor. |
| `Api/PasswordController.php` | Vue'nun "Şifremi unuttum" ve "Şifre sıfırla" işlemlerini karşılar. Laravel Password mekanizmasını kullanır. |
| `Api/GezinmeController.php` | Önceki/sonraki kayıt gezinmesi için şube ve öğretmenlerin sade kimlik/etiket listelerini hazırlar. |
| `Api/YoneticiController.php` | Yönetici hesaplarını listeler, oluşturur, günceller ve siler. Rol ve okul atamasıyla ilgilenir; son süper yönetici gibi koruma kontrolleri içerir. |

### Controller metot isimleri

| Metot | Anlamı |
|---|---|
| `index()` | Listeyi getir. |
| `create()` | Eski web yapısında oluşturma formunu aç. |
| `store()` | Yeni kaydı oluştur. |
| `edit()` | Eski web yapısında düzenleme formunu aç. |
| `update()` | Mevcut kaydı güncelle. |
| `destroy()` | Kaydı sil. |

SPA tarafında ayrı HTML form sayfası döndürülmediği için API controller'ında genellikle `create()` ve `edit()` bulunmuyor. Formu Vue açıyor.

---

## 4. Request dosyaları: gelen veriyi kontrol eden bölüm

Bu sınıflar `FormRequest` sınıfları. Görevleri:

> "Kullanıcı ne göndermiş? Bu veri beklediğim biçimde mi? İş kuralına uygun mu?"

| Dosya | Ne kontrol ediyor? |
|---|---|
| `Auth/LoginRequest.php` | Web login'inde e-posta/parola alanları, başarısız giriş sınırı ve `Auth::attempt()` ile kimlik doğrulaması. |
| `CourseRequest.php` | Ders adı, saatlik ücret, blok ders seçimi, seviyelere ait haftalık saatler ve kapasite kontrolü. |
| `LevelRequest.php` | Seviye adı/sınıf numarası ve okul kapsamındaki benzersizlik kuralları. |
| `RoomRequest.php` | Derslik adı ve okul içindeki benzersizliği. |
| `SectionRequest.php` | Şubenin seviyesi, adı ve derslik seçimi; aynı dersliğin başka şubeye atanmış olması gibi durumlar. |
| `SchoolRequest.php` | Okul ayarlarını güncellerken isim, konum, ders/mola süreleri, eğitim günleri ve saatler. En az bir eğitim günü gibi ek kurallar. |
| `OkulOlusturRequest.php` | Yeni okulun temel bilgilerini doğrular. Eğitim günlerinin detaylı ayarı ayrı akışta. |
| `IzinRequest.php` | İzin başlangıcı, bitişi ve gerekçesi. Bitişin başlangıçtan sonra olmasını ister. |
| `ProgramDuzenleRequest.php` | Elle program düzenlerken dersin müfredata uygunluğunu, öğretmenin o ders/seviye için yetkisini ve aynı saatte başka şubede dersini kontrol eder. |
| `ProfileUpdateRequest.php` | Kullanıcının ad ve e-posta güncellemesini doğrular. |

### Neden bütün doğrulamalar burada değil?

Örneğin ayrı bir `TeacherRequest.php` yok. Öğretmen doğrulamalarının bir kısmı controller içindeki `kurallar()` gibi metotlarda.

Dolayısıyla "Projede bütün validation FormRequest'te" demek doğru olmaz. İki kullanım da var.

### Request ile Model farkı

- **Request:** Gelen veriyi doğrular.
- **Model:** Veritabanındaki kaydı temsil eder.

İzin formundan `teacher_id` alınmaması güzel bir örnek: Hangi öğretmenin talep oluşturduğu, kullanıcının kendi hesabından backend tarafından belirleniyor.

---

## 5. Model dosyaları: veritabanının uygulamadaki karşılığı

Laravel'in ORM'si Eloquent. Bu sınıflar onunla çalışıyor.

| Dosya | Temsil ettiği şey ve önemli görevi |
|---|---|
| `User.php` | Sisteme giriş yapan kullanıcı. Ad, e-posta, parola, rol ve okul bağlantısı. Parola hash cast'i, gizlenen alanlar, Sanctum token yeteneği ve rol yardımcıları burada. |
| `School.php` | Okul. Aktif okulun belirlenmesi, haftalık kapasite ve slotların gerçek başlangıç/bitiş saatlerinin hesaplanması da burada. |
| `SchoolDay.php` | Okulun belirli bir eğitim günü: haftanın günü, başlangıç saati ve aktiflik. |
| `Level.php` | 9. sınıf gibi sınıf seviyesi. Okula ve şubelere bağlanır. |
| `Section.php` | 9A gibi şube. Seviyeye ve dersliğe bağlanır. Okul kapsamını seviye ilişkisi üzerinden alır. |
| `Room.php` | Fiziksel derslik. Okulu ve bağlı şubesiyle ilişkileri vardır. |
| `Course.php` | Matematik gibi ders. Saatlik ücret, blok ders seçimi, okul ve müfredat ilişkileri. |
| `CourseLevel.php` | Bir dersin belirli seviyede haftada kaç saat okutulduğu. Örneğin Matematik–9. sınıf–6 saat. |
| `Teacher.php` | Öğretmenin personel bilgileri, kapasitesi, aktifliği, fotoğraf/CV yolları ve ilişkileri. Tam ad ve dosya URL'leri gibi hesaplanan alanlar içerir. |
| `TeacherCourseLevel.php` | Öğretmenin hangi dersi hangi seviyede verebileceği. Bu bir yeterlilik/yetki kaydıdır. |
| `SectionCourseTeacher.php` | Belirli şubenin belirli dersine fiilen hangi öğretmenin atandığı. |
| `ScheduleEntry.php` | Haftalık çizelgenin bir hücresi: şube, ders, öğretmen, derslik, gün ve slot numarası. |
| `LeaveRequest.php` | Öğretmenin tarih/saat aralıklı izin talebi; durum, onaylayan kullanıcı ve karar zamanı. |
| `LessonSwap.php` | Belirli bir tarihte iki dersin yer değiştirmesi. Haftalık şablondan ayrı tutulur. |

### Birbirine benzeyen üç modeli özellikle ayır

| Model | Cevapladığı soru |
|---|---|
| `TeacherCourseLevel` | "Bu öğretmen bu dersi bu seviyede verebilir mi?" |
| `SectionCourseTeacher` | "9A'nın matematik dersini hangi öğretmen verecek?" |
| `ScheduleEntry` | "O ders pazartesi kaçıncı saatte, hangi derslikte?" |

Yani: **Yetkinlik → Atama → Zaman çizelgesi**. Bunlar aynı veri değil.

### `User` ile `Teacher` neden ayrı?

- **User:** Giriş hesabı.
- **Teacher:** Öğretmen/personel kaydı.

Her öğretmenin henüz bir giriş hesabı olmayabilir. Yönetici kullanıcı da öğretmen olmak zorunda değildir. Bağlantı `teachers.user_id` üzerinden kuruluyor.

### Scope dosyaları

| Dosya | Görevi |
|---|---|
| `Scopes/OkulScope.php` | Uygulandığı modellerin sorgularına aktif okul filtresi ekler. |
| `Scopes/SubeOkulScope.php` | Şubelerin okul filtresini, şubenin seviyesi üzerinden uygular. Çünkü `sections` tablosunda doğrudan `school_id` yok. |

> **Önemli sınır:** `OkulScope`, session'da aktif okul kimliği yoksa filtre eklemiyor. Ayrıca bütün modellerde uygulanmış değil. Bu nedenle middleware ve kayıt bazlı erişim kontrolleri önemini koruyor.

---

## 6. Service dosyaları: işin kurallarının bulunduğu bölüm

Controller "işi başlatan", service "işi nasıl yapacağını bilen" taraf.

| Dosya | Ne yapıyor? |
|---|---|
| `DersServisi.php` | Ders oluşturma/güncelleme ile müfredat satırlarını birlikte yönetir. `courses` ve `course_level` yazımlarını transaction içinde yapar. |
| `OgretmenServisi.php` | Öğretmene giriş hesabı açar; kullanıcı ile öğretmeni bağlar. Öğretmenin ders-seviye yetkilerini kaydeder. |
| `OkulAyarServisi.php` | Okulun genel ayarlarını ve eğitim günlerini birlikte günceller. Sonrasında bellekteki eski `days` ilişkisini temizler. |
| `OgretmenAtamaServisi.php` | Müfredata göre her şubenin derslerine uygun öğretmen atar. Yetki, aktiflik, kapasite ve yük dağılımını dikkate alır. |
| `SlotYerlestirmeServisi.php` | Öğretmeni belirlenen dersleri haftanın günlerine ve ders saatlerine yerleştirir. Dolu slotları ve çakışmaları takip eder. |
| `CizelgeServisi.php` | Veritabanı kayıtlarını ekranda gösterilecek haftalık ızgaraya dönüştürür. Öğretmen/şube çizelgesi, yük dağılımı, çakışma kontrolü ve tarihli izin/takas etkileriyle ilgilenir. |
| `IzinServisi.php` | İzin zaman aralığıyla derslerin çakışmasını bulur, etkilenen dersleri hesaplar ve haftalık izin bilgilerini hazırlar. |
| `TakasServisi.php` | İzin nedeniyle değiştirilebilecek dersleri bulur, takas uygunluğunu kontrol eder, tarihli takası kaydeder ve geri alır. |
| `UcretServisi.php` | Programdaki ders saatleri ve saatlik ücretlerden öğretmen ücret raporunu hesaplar. Mevcut hesap dört haftalık aylık tahmine dayanır. |

### İki kritik fark

1. **Program oluşturmak ile çizelge göstermek aynı iş değil.** Atama/yerleştirme servisleri programı üretir; çizelge servisi üretilmiş programı gösterilecek biçime dönüştürür.
2. **İzin vermek ile haftalık şablonu kalıcı değiştirmek aynı iş değil.** Belirli tarihteki izin ve takaslar, her hafta tekrarlanan programdan ayrı ele alınıyor.

---

## 7. Middleware dosyaları

| Dosya | Ne yapıyor? |
|---|---|
| `EnsureUserIsAdmin.php` | Kullanıcının yönetici işlemlerine erişebilecek rolde olup olmadığını kontrol eder. `admin` ve `superadmin` kabul edilir. |
| `EnsureUserIsSuperAdmin.php` | Yalnızca `superadmin` rolünü kabul eder. |
| `EnsureUserIsTeacher.php` | Rolün `teacher` olmasını ve kullanıcıya bağlı öğretmen kaydı bulunmasını ister. Aktif okulu öğretmenin okuluna ayarlar. |
| `EnsureAktifOkul.php` | `School::aktif()` ile okul bağlamını hazırlar. Okula bağlı yöneticinin okulu bulunmuyorsa 403 döndürür. |

Gördüğün **"Hesabınıza bir okul atanmamış."** mesajı son dosyadan geliyor.

Bu mesaj "Parolan yanlış" demek değil; **"Hesabın doğrulandı ama okul erişimi için gerekli bağlantın yok"** anlamına gelebiliyor.

---

## 8. Authentication controller dosyaları

Bunlar API controller'larından ayrı olarak Laravel'in web oturumu ve klasik auth akışlarıyla ilgili.

| Dosya | Görevi |
|---|---|
| `Auth/AuthenticatedSessionController.php` | Giriş türü seçimi, eski giriş formu, web oturumu açma, rol kontrolü ve çıkış. **Vue login'inin backend tarafında kullandığı controller budur.** |
| `Auth/ConfirmablePasswordController.php` | Hassas bir işlem öncesi kullanıcının parolasını tekrar doğrulama ekranı ve işlemi. |
| `Auth/EmailVerificationNotificationController.php` | E-posta doğrulama bildirimini yeniden gönderir. |
| `Auth/EmailVerificationPromptController.php` | E-posta doğrulama bekleme ekranını veya uygun yönlendirmeyi döndürür. |
| `Auth/VerifyEmailController.php` | İmzalı doğrulama bağlantısını işleyip e-posta doğrulamasını tamamlar. |
| `Auth/PasswordResetLinkController.php` | Eski web yapısında "Şifremi unuttum" formunu gösterir ve sıfırlama e-postasını başlatır. |
| `Auth/NewPasswordController.php` | Eski web yapısında token ile yeni parola belirleme işlemi. |
| `Auth/PasswordController.php` | Giriş yapmış kullanıcının mevcut parolasını doğrulayarak parolasını değiştirmesi. |
| `Auth/RegisteredUserController.php` | Hazır kayıt olma akışının controller'ı. Dosya duruyor ama mevcut auth rota dosyasında `/register` rotaları tanımlı değil. |

> Bir dosyanın projede bulunması, o özelliğin aktif olarak kullanıldığı anlamına gelmez.

Benzer şekilde e-posta doğrulama dosyalarının bulunmasından hareketle "bütün kullanıcılar e-postalarını doğrulamadan hiçbir işlem yapamaz" sonucu çıkarılamaz. Rotalara ve kullanıcı modelindeki sözleşmelere bakmak gerekir.

---

## 9. Vue uygulamasının temel dosyaları

| Dosya | Görevi |
|---|---|
| `resources/js/spa.js` | Yeni Vue SPA'yı başlatır. Pinia ve router'ı bağlayıp uygulamayı `#app` alanına yerleştirir. |
| `resources/js/App.vue` | Kök Vue bileşeni. `router-view` üzerinden seçili sayfayı gösterir. |
| `resources/js/router.js` | URL → Vue sayfası eşlemesi. Giriş ve rol durumuna göre frontend yönlendirmelerini yapar. |
| `resources/js/api.js` | Ortak Axios istemcisi. `/api` taban adresi, JSON beklentisi, cookie/CSRF ayarları ve interceptor'lar. |
| `resources/js/stores/auth.js` | Pinia auth store'u. Kullanıcı bilgisi, rol getter'ları ve `/api/user` ile oturum kontrolü. |
| `resources/js/app.js` | Eski yapının başlangıç dosyası. Alpine'ı başlatır ve Blade içindeki `data-vue-component` alanlarına ayrı küçük Vue uygulamaları yerleştirir. |
| `resources/css/app.css` | Tailwind temel, bileşen ve yardımcı stil katmanlarını içerir. |

### `app.js` ve `spa.js` farkı

- **`app.js`:** Bir Blade sayfasında birkaç bağımsız Vue formu olabilir.
- **`spa.js`:** Tek kök Vue uygulaması vardır; ekran geçişlerini Vue Router yapar.

Bu yüzden iki ayrı Vite giriş noktası tanımlanmış.

### Vue sayfalarının tamamı

| Dosya | Kullanıcının gördüğü iş |
|---|---|
| `Anasayfa.vue` | `/app` için geçiş bileşeni. Gerçek panel seçimini router rol bilgisine göre yapar. |
| `GirisSecim.vue` | Yönetici veya öğretmen giriş türü seçimi. |
| `Giris.vue` | E-posta/parola formu ve web login isteği. |
| `SifremiUnuttum.vue` | Sıfırlama bağlantısı isteme ekranı. |
| `SifreSifirla.vue` | Token ve e-postayla yeni parola belirleme ekranı. |
| `Dashboard.vue` | Yönetici özet ekranı ve sayaçlar. |
| `Okullar.vue` | Okulları listeleme ve uygun yetkide okul yönetim işlemleri. |
| `OkulAyarlari.vue` | Okul bilgileri, eğitim günleri, ders/mola süreleri ve başlangıç saatleri. |
| `Seviyeler.vue` | Sınıf seviyelerinin listesi ve ekleme/düzenleme formları. |
| `Subeler.vue` | Şube listesi, seviyesi ve derslik ataması. |
| `Derslikler.vue` | Fiziksel derslik yönetimi. |
| `Dersler.vue` | Ders listesi, ders formu, ücret ve seviye bazlı haftalık saatler. |
| `Ogretmenler.vue` | Öğretmen listesi, filtreleme, bilgiler, fotoğraf/CV ve hesap açma işlemleri. |
| `OgretmenDersleri.vue` | Öğretmenin hangi dersleri hangi seviyelerde verebileceğini belirleme. |
| `Program.vue` | Öğretmen atama ve saat yerleştirme işlemlerini başlatma, sonuçları görme. |
| `ProgramHucre.vue` | Programdaki belirli şube/gün/slot hücresini düzenleme veya boşaltma. |
| `Cizelge.vue` | Çizelge ekranının giriş/listeleme bölümü; şube veya öğretmen çizelgesine geçiş. |
| `CizelgeIzgara.vue` | Haftalık programı tablo olarak gösterir. Aynı bileşen şube ve öğretmen görünümünde kullanılır. |
| `CizelgeYuk.vue` | Öğretmenlerin ders yüklerini gösterir. |
| `Ucretler.vue` | Ücret raporu ekranı. |
| `Izinler.vue` | Yönetici tarafında izin talepleri listesi. |
| `IzinDetay.vue` | İzin ayrıntısı, etkilenen dersler, karar ve takas işlemleri. |
| `OgretmenPanel.vue` | Öğretmenin kişisel paneli ve program bilgileri. |
| `OgretmenIzinler.vue` | Öğretmenin kendi izin talepleri. |
| `IzinOlustur.vue` | Yeni izin talebi formu. |
| `Profil.vue` | Kullanıcının kendi ad, e-posta ve parolasını değiştirmesi. |
| `Yoneticiler.vue` | Süper yönetici için yönetici hesabı, rol ve okul ataması ekranı. |
| `NasilKullanilir.vue` | Role göre kullanım açıklamaları. |

### Sayfa ile component farkı

- **Sayfa:** Router'ın açtığı ekran.
- **Component:** Sayfanın içinde kullanılan tekrar kullanılabilir parça.

Örneğin `Giris.vue` bir sayfa; `GirisKarti.vue` o sayfanın yerleşimini sağlayan bileşen.

### Ortak Vue bileşenleri

| Dosya | Görevi |
|---|---|
| `components/Layout.vue` | SPA'nın ortak menüsü, başlığı, profil bağlantısı, rol bazlı navigasyonu ve çıkış işlemi. |
| `components/GirisKarti.vue` | Giriş, giriş türü seçimi ve parola ekranlarının ortak kart düzeni. |
| `components/KayitGezinme.vue` | Önceki/sonraki öğretmen veya şubeye geçme bileşeni. |

### Eski Blade yapısına yerleştirilen Vue formları

| Dosya | Görevi |
|---|---|
| `components/DersFormu.vue` | Eski ders ekleme/düzenleme sayfasındaki etkileşimli ders formu. |
| `components/DerslikFormu.vue` | Eski derslik formu. |
| `components/SeviyeFormu.vue` | Eski seviye formu. |
| `components/SubeFormu.vue` | Eski şube formu. |
| `components/OgretmenFormu.vue` | Eski öğretmen formu ve dosya seçimi. |
| `components/OgretmenDersleriFormu.vue` | Eski öğretmen ders/seviye yetki matrisi. |

Bu bileşenler klasik HTML form gönderimiyle çalışabiliyor. Yeni SPA'daki gibi her şeyin Axios ile gönderildiğini varsayma.

---

## 10. Blade dosyaları

Blade dosyası PHP sınıfı değil, Laravel'in HTML şablonudur.

### Ana şablonlar

| Dosya | Görevi |
|---|---|
| `spa.blade.php` | Yeni SPA'nın HTML kabuğu. `spa.js` yüklenir, Vue'nun yerleşeceği `#app` alanı bulunur. |
| `dashboard.blade.php` | Eski yönetici özet ekranı. |
| `api-dokuman.blade.php` | Swagger UI ile API dokümanını gösterir. |
| `welcome.blade.php` | Laravel'in başlangıç karşılama şablonu. Mevcut `/` rotasının hedefi değil. |
| `layouts/app.blade.php` | Eski giriş yapılmış kullanıcı ekranlarının ortak HTML düzeni. |
| `layouts/guest.blade.php` | Eski misafir/auth ekranlarının ortak düzeni. |
| `layouts/navigation.blade.php` | Eski sistemin menüsü ve kullanıcı navigasyonu. |

Bunlarla ilişkili PHP bileşenleri:

- `app/View/Components/AppLayout.php` — `layouts.app` şablonunu döndürür.
- `app/View/Components/GuestLayout.php` — `layouts.guest` şablonunu döndürür.

### Listeleme, ekleme ve düzenleme şablonları

Bu gruplarda görev düzeni aynı: `index` liste, `create` oluşturma ekranı, `edit` düzenleme ekranı.

| Modül | Dosyalar |
|---|---|
| Ders | `courses/index.blade.php`, `courses/create.blade.php`, `courses/edit.blade.php` |
| Seviye | `levels/index.blade.php`, `levels/create.blade.php`, `levels/edit.blade.php` |
| Derslik | `rooms/index.blade.php`, `rooms/create.blade.php`, `rooms/edit.blade.php` |
| Şube | `sections/index.blade.php`, `sections/create.blade.php`, `sections/edit.blade.php` |
| Öğretmen | `teachers/index.blade.php`, `teachers/create.blade.php`, `teachers/edit.blade.php` |

Öğretmenin ders yetkileri için ayrıca `teachers/courses.blade.php` bulunuyor.

### Diğer iş ekranları

| Dosya | Görevi |
|---|---|
| `schools/index.blade.php` | Aktif okul bilgilerini gösterir. |
| `schools/edit.blade.php` | Okul ayarlarını düzenler. |
| `schools/ekle.blade.php` | Yeni okul formu. |
| `schools/okullar.blade.php` | Çoklu okul listesi ve seçim ekranı. |
| `program/index.blade.php` | Eski program oluşturma ekranı. |
| `program/hucre-duzenle.blade.php` | Tek çizelge hücresini elle düzenleme. |
| `cizelge/index.blade.php` | Çizelge seçim/liste ekranı. |
| `cizelge/izgara.blade.php` | Haftalık program tablosu. |
| `cizelge/pdf.blade.php` | PDF üretiminde kullanılan çıktı şablonu. |
| `cizelge/yuk.blade.php` | Öğretmen yük dağılımı. |
| `izinler/index.blade.php` | Yönetici izin listesi. |
| `izinler/goster.blade.php` | İzin ayrıntısı ve karar ekranı. |
| `ogretmen/panel.blade.php` | Öğretmenin kendi paneli. |
| `ogretmen/izinler/index.blade.php` | Öğretmenin kendi izinleri. |
| `ogretmen/izinler/create.blade.php` | Öğretmen izin oluşturma formu. |
| `ucret/index.blade.php` | Ücret raporu. |

### Eski authentication şablonları

| Dosya | Görevi |
|---|---|
| `auth/login-secim.blade.php` | Yönetici/öğretmen giriş seçimi. |
| `auth/login.blade.php` | Role göre klasik login formu. |
| `auth/forgot-password.blade.php` | Parola sıfırlama bağlantısı isteme. |
| `auth/reset-password.blade.php` | Yeni parola belirleme. |
| `auth/confirm-password.blade.php` | Mevcut parolayı yeniden doğrulama. |
| `auth/verify-email.blade.php` | E-posta doğrulama bekleme ekranı. |
| `auth/register.blade.php` | Hazır kayıt formu; mevcut rota yapısında kayıt ekranı olarak açılmıyor. |

### Profil şablonları

- `profile/edit.blade.php` — Ana profil ekranı.
- `profile/partials/update-profile-information-form.blade.php` — Ad/e-posta formu.
- `profile/partials/update-password-form.blade.php` — Parola değiştirme.
- `profile/partials/delete-user-form.blade.php` — Hesap silme bölümü.

### Küçük Blade bileşenleri

İş kuralı değil, ortak arayüz parçaları:

| Dosya | Görevi |
|---|---|
| `components/application-logo.blade.php` | Logo. |
| `components/auth-session-status.blade.php` | Auth işlemlerinin durum mesajı. |
| `components/danger-button.blade.php` | Silme gibi riskli işlem butonu. |
| `components/primary-button.blade.php` | Ana işlem butonu. |
| `components/secondary-button.blade.php` | İkincil işlem butonu. |
| `components/dropdown.blade.php` | Açılır menü. |
| `components/dropdown-link.blade.php` | Açılır menü bağlantısı. |
| `components/nav-link.blade.php` | Normal navigasyon bağlantısı. |
| `components/responsive-nav-link.blade.php` | Mobil/responsive navigasyon bağlantısı. |
| `components/input-label.blade.php` | Form alanı etiketi. |
| `components/text-input.blade.php` | Ortak input görünümü. |
| `components/input-error.blade.php` | Alan doğrulama hatası. |
| `components/modal.blade.php` | Açılır pencere/modal. |

---

## 11. Migration dosyaları

Migration: "Veritabanında hangi tablo, sütun ve kısıt bulunmalı?" sorusunu cevaplar.

| Migration | Ne oluşturuyor/değiştiriyor? |
|---|---|
| `create_users_table` | `users`, `password_reset_tokens` ve `sessions` tabloları. |
| `create_cache_table` | Veritabanı önbelleği ve cache lock tabloları. |
| `create_jobs_table` | Kuyruk işleri, toplu işler ve başarısız iş tabloları. |
| `create_schools_table` | Okullar ve temel zaman ayarları. |
| `create_school_days_table` | Okula ait günler, başlangıç saati ve aktiflik. |
| `create_levels_table` | Sınıf seviyeleri. |
| `create_sections_table` | Şubeler ve seviyeleri. |
| `create_rooms_table` | Fiziksel derslikler. |
| `create_courses_table` | Dersler ve saatlik ücretleri. |
| `create_course_level_table` | Ders–seviye–haftalık saat müfredatı. |
| `create_teachers_table` | Öğretmenler, kullanıcı bağlantısı, kişisel bilgiler, dosya yolları ve kapasite. |
| `create_teacher_course_level_table` | Öğretmenin ders/seviye yeterlilikleri. |
| `create_section_course_teacher_table` | Şubenin dersine öğretmen ataması. Şube–ders çifti için unique kısıtı var. |
| `create_schedule_entries_table` | Haftalık çizelge hücreleri. Şube, öğretmen ve derslik için gün/slot çakışmalarını engelleyen unique kısıtları var. |
| `create_leave_requests_table` | İzin talepleri ve karar bilgileri. |
| `add_role_to_users_table` | Kullanıcıya rol sütunu ekler. |
| `add_room_id_to_sections_table` | Şubeye derslik bağlantısı ekler. |
| `create_personal_access_tokens_table` | Sanctum kişisel erişim tokenlarının tablosu. |
| `add_block_ders_to_courses_table` | Derslere blok ders seçeneği ekler. |
| `create_lesson_swaps_table` | Tarihe özel ders takasları. |
| `add_school_to_users_table` | Kullanıcıya okul bağlantısı ekler. Mevcut admin kayıtlarını `superadmin` rolüne dönüştüren veri güncellemesi de içerir. |

> Son migration önemli bir şey gösteriyor: **Migration yalnızca tablo oluşturmaz; gerektiğinde veri dönüşümü de yapabilir.**

### Seeder ve factory

| Dosya | Görevi |
|---|---|
| `database/seeders/DatabaseSeeder.php` | Örnek yönetici, okul, günler, seviyeler, şubeler, derslikler ve diğer örnek verileri oluşturur. |
| `database/factories/UserFactory.php` | Test/örnek veri üretiminde kullanılacak sahte kullanıcı kayıtlarının kalıbı. |

Seeder, iş yerindeki veritabanının yedeği değildir. Çalıştırmak mevcut gerçek verileri iş yerinden çekmez.

---

## 12. Yapılandırma ve paket dosyaları

### Laravel config dosyaları

| Dosya | Görevi |
|---|---|
| `config/app.php` | Uygulama adı, URL, ortam, debug ve genel uygulama ayarları. |
| `config/auth.php` | Guard, kullanıcı provider'ı ve parola sıfırlama broker ayarları. |
| `config/sanctum.php` | SPA için stateful adresler, kullanılacak guard, token süresi ve ilgili middleware ayarları. |
| `config/session.php` | Oturum sürücüsü, süresi ve cookie özellikleri. |
| `config/database.php` | Veritabanı bağlantıları. |
| `config/cache.php` | Önbellek sürücüsü ve store ayarları. |
| `config/filesystems.php` | Public/private gibi depolama diskleri ve dosya erişimi. |
| `config/logging.php` | Logların nereye ve nasıl yazılacağı. |
| `config/mail.php` | E-posta gönderim altyapısı. |
| `config/queue.php` | Arka plan işlerinin kuyruk bağlantıları. Dosyanın varlığı program üretiminin kuyrukta çalıştığı anlamına gelmez. |
| `config/services.php` | Harici servis entegrasyonları için yapılandırma. |
| `config/api-dokuman.php` | API dokümanı üretiminde kullanılan açıklamalar ve ek bilgiler. |

### Kök klasördeki teknik dosyalar

| Dosya | Görevi |
|---|---|
| `.env` | Bu bilgisayara/ortama özgü gerçek ayarlar. Veritabanı, uygulama anahtarı, mail ve benzeri bilgiler. Gizli değerler paylaşılmaz. |
| `.env.example` | Yeni kurulum için örnek ortam dosyası. |
| `composer.json` | PHP paketleri, sürüm aralıkları, autoload ve Composer komutları. |
| `composer.lock` | Çözümlenmiş kesin PHP bağımlılık sürümleri. |
| `package.json` | Vue, Axios, Pinia, Vite ve diğer frontend paketleri; dev ve build komutları. |
| `package-lock.json` | Çözümlenmiş npm paket sürümleri. |
| `vite.config.js` | Vite derleme ayarları. Eski `app.js` ile yeni `spa.js` ayrı giriş noktaları olarak tanımlı. |
| `tailwind.config.js` | Tailwind'in tarayacağı dosyalar ve stil özelleştirmeleri. |
| `postcss.config.js` | Tailwind ve Autoprefixer gibi CSS işleme eklentileri. |
| `compose.yaml` | Docker/Sail uygulama ve PostgreSQL servislerinin tanımı. Bulunması bu bilgisayarda mutlaka Docker kullanıldığı anlamına gelmez. |
| `phpunit.xml` | PHPUnit test grupları ve test ortamı ayarları. |
| `.gitignore` | Git'in takip etmeyeceği dosyalar. |
| `.gitattributes` | Git'in dosyaları işlerken kullanacağı özellikler. |
| `.editorconfig` | Girinti, satır sonu gibi editör tutarlılığı ayarları. |
| `.npmrc` | npm davranış ayarları. Paket kurulum scriptlerini kapatan `ignore-scripts=true` ve audit ayarı var. |

### API dokümanı komutu

`app/Console/Commands/ApiDokumaniUret.php` — özel Artisan komutu:

1. API rotalarını inceliyor.
2. Controller/FormRequest bilgilerini kullanıyor.
3. Açıklama yapılandırmasıyla birleştiriyor.
4. OpenAPI JSON dokümanı üretiyor.

Çıktı `storage/app/openapi.json` konumuna yazılıyor. Web rotası bunu yetki kontrolü arkasından sunuyor.

Bu komut API'nin kendisi değil; API'yi anlatan dokümanı üretiyor.

### Public dosyaları

| Dosya | Görevi |
|---|---|
| `public/.htaccess` | Apache kullanıldığında URL yönlendirme kuralları. |
| `public/favicon.ico` | Tarayıcı sekme ikonu. |
| `public/robots.txt` | Arama motoru robotlarına verilen tarama yönergeleri. Yetkilendirme mekanizması değildir. |
| `public/swagger/swagger-ui-bundle.js` | Swagger arayüzünün hazır JavaScript paketi. |
| `public/swagger/swagger-ui-standalone-preset.js` | Swagger'ın bağımsız arayüz düzeni için hazır paket. |
| `public/swagger/swagger-ui.css` | Swagger arayüzünün stilleri. |

`public/index.php` başlangıç bölümünde anlatıldı.

---

## 13. Test ve dokümantasyon dosyaları

### Test dosyaları

| Dosya | Amaçlanan kontrol |
|---|---|
| `tests/TestCase.php` | Uygulama testlerinin ortak temel sınıfı. |
| `tests/Unit/ExampleTest.php` | Hazır basit örnek unit test. İş mantığını kapsamlı test etmiyor. |
| `tests/Feature/ExampleTest.php` | Ana sayfanın cevap durumunu kontrol eden örnek test. |
| `tests/Feature/ProfileTest.php` | Eski profil ekranı, profil güncelleme ve hesap silme davranışları. |
| `tests/Feature/Auth/AuthenticationTest.php` | Giriş, yanlış parola ve çıkış senaryoları. |
| `tests/Feature/Auth/RegistrationTest.php` | Hazır kullanıcı kayıt akışı testleri. |
| `tests/Feature/Auth/EmailVerificationTest.php` | E-posta doğrulama senaryoları. |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | Mevcut parolanın tekrar doğrulanması. |
| `tests/Feature/Auth/PasswordResetTest.php` | Parola sıfırlama bağlantısı ve tokenlı sıfırlama. |
| `tests/Feature/Auth/PasswordUpdateTest.php` | Mevcut parolayla yeni parola belirleme. |

**Güncellik sorunu var:**

- Authentication testi hâlâ `POST /login` kullanıyor.
- Mevcut giriş rotası `POST /giris/{rol}`.
- Registration testleri `/register` bekliyor ama mevcut rota tanımlı değil.
- Örnek ana sayfa testi 200 bekliyor, mevcut kök rota yönlendirme yapıyor.

Bunlar kod karşılaştırmasıyla görüldü; bu turda testler çalıştırılıp sonuç üretilmedi.

### Markdown dosyaları

| Dosya | İçeriği |
|---|---|
| `README.md` | Projenin genel tanıtımı, kurulum, kullanım ve mimari özeti. |
| `spa-gecis-plani.md` | Blade'den Vue SPA + API yapısına geçiş planı. |
| `faz3-sifre-sifirlama-egitimi.md` | Şifre sıfırlama akışının eğitim açıklaması. |
| `faz4-faz6-spa-gecisi-egitimi.md` | SPA ekranları, dosya yükleme, profil, gezinme ve geçiş açıklamaları. |
| `odpys-vue-spa-kod-uzerinden-egitim.md` | Vue, router, Pinia, Axios ve sözdizimini proje koduyla anlatan eğitim. |
| `coklu-okul-anlatim.md` | Aktif okul, scope ve çoklu okul yapısı açıklamaları. |
| `en-gelebilecek-sorular-bilmem-distributed-kahan.md` | Şefle görüşme hazırlığı ve yapılması planlanan işlemlerin notları. Bazı tespitleri mevcut koda göre eski. |

Bu dosyalar açıklamadır; uygulamanın çalışırken yürüttüğü kod değiller.

### Otomatik, yerel ve yardımcı dosyalar

- `vendor` — Composer'ın kurduğu Laravel ve PHP paketleri.
- `node_modules` — npm'in kurduğu frontend paketleri.
- Derleme çıktıları — Vue/JS/CSS kaynaklarının tarayıcıya sunulacak derlenmiş sürümleri.
- `bootstrap/cache` — Laravel'in ürettiği bazı önbellek dosyaları.
- `storage/framework` — Derlenmiş Blade, önbellek ve benzeri çalışma dosyaları.
- `storage/logs` — Uygulama logları.
- `storage/app/public` — Public diske yüklenen dosyalar.
- `storage/app/private` — Private disk için ayrılan alan.
- Alt klasörlerdeki `.gitignore` dosyaları — Çalışma dosyalarını Git dışında tutarken klasör yapısını korur.
- Yerel SQLite veritabanı — Bu bilgisayardaki kayıtları içerir; Git'teki kaynak kodla aynı şey değildir.
- `graphify-out` — Üretilmiş analiz çıktıları; uygulamanın çalışması için temel iş kodu değil. Bu incelemede kullanılmadı.

Git listesinde ayrıca adı PHP komut parçasına benzeyen, `skip-worktree` işaretli sıra dışı bir kayıt var. Yerel normal dosya listesinde bulunmadığı için ona uygulama görevi atfedilmiyor.

---

## 14. Authentication ve login'i birbirine bağlayalım

Buraya kadar dosyalar öğrenildi. Şimdi "bunlar birlikte ne yapıyor?" kısmı.

### A. Projedeki authentication yaklaşımı

Vue arayüzündeki normal giriş **session/cookie** tabanlı. Ayrı API girişi **Bearer token** tabanlı.

| Konu | Vue arayüzü | Ayrı API girişi |
|---|---|---|
| Giriş adresi | `/giris/yonetici` veya `/giris/ogretmen` | `/api/login` |
| Controller | `AuthenticatedSessionController` | `Api/AuthController` |
| Kimlik doğrulama | `LoginRequest` → `Auth::attempt()` | Kullanıcı sorgusu → `Hash::check()` |
| Devam eden erişim | Session cookie | Bearer token |
| Vue Pinia'da erişim tokenı | Tutulmuyor | Normal Vue akışının parçası değil |

Bunu anlamak authentication sorularının yarısını çözüyor.

### B. Giriş ekranı nasıl açılıyor?

Kullanıcı `/app/giris` adresine geliyor:

1. Laravel'de SPA kabuk rotası eşleşiyor.
2. `spa.blade.php` HTML'i veriliyor.
3. `spa.js` çalışıyor.
4. Vue, `#app` içine yerleşiyor.
5. `router.js`, `GirisSecim.vue` ekranını seçiyor.
6. Kullanıcı yönetici veya öğretmen seçiyor.
7. `Giris.vue` açılıyor.

**SPA'nın başlangıç kodu** — `resources/js/spa.js`:

```js
createApp(App)
    .use(createPinia())
    .use(router)
    .mount('#app');
```

- `createApp(App)` — Kök bileşenden uygulamayı oluştur.
- `.use(createPinia())` — Ortak state yönetimini ekle.
- `.use(router)` — Sayfa yönlendirmesini ekle.
- `.mount('#app')` — Uygulamayı HTML'deki alana yerleştir.

### C. Kullanıcı formu doldurunca ne oluyor?

`resources/js/pages/Giris.vue`:

```js
const email = ref('');
const password = ref('');
const hata = ref('');
const gonderiliyor = ref(false);
```

Formun reaktif durumları:

- `email` — Girilen e-posta.
- `password` — Girilen parola.
- `hata` — Ekranda gösterilecek hata.
- `gonderiliyor` — İstek sürüyor mu?

Template'te `v-model`, input'u bu değerlere bağlıyor.

Formun gönderimi:

```html
<form @submit.prevent="girisYap">
```

- `@submit` — Form gönderilince.
- `.prevent` — Tarayıcının normal form gönderimini engelle.
- `girisYap` — JavaScript fonksiyonunu çalıştır.

### D. Önce CSRF hazırlığı yapılıyor

`Giris.vue`:

```js
await csrfCerezGarantiEt();
```

Bu fonksiyon ortak API dosyasında — `resources/js/api.js`:

```js
export async function csrfCerezGarantiEt() {
    if (csrfCerezKuruldu) {
        return;
    }
    await axios.get('/sanctum/csrf-cookie');
    csrfCerezKuruldu = true;
}
```

Anlamı:

1. Bu sayfa yaşamı içinde hazırlık yapılmışsa tekrar yapma.
2. Yapılmamışsa Laravel'in CSRF çerezini hazırlayan adresini çağır.
3. Hazırlığın yapıldığını işaretle.

Bu istek giriş yapmaz. Yalnızca CSRF koruması için hazırlıktır.

Aynı dosyada:

```js
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
```

Bunlar Axios'un cookie ve CSRF başlığıyla çalışmasını yapılandırır. Tarayıcının domain, SameSite ve diğer çerez kuralları yine geçerlidir.

### E. E-posta ve parola web giriş rotasına gönderiliyor

`Giris.vue`:

```js
await axios.post(`/giris/${route.params.rol}`, {
    email: email.value,
    password: password.value,
}, {
    headers: { Accept: 'application/json' },
});
```

Örneğin `route.params.rol` değeri `yonetici` ise: `POST /giris/yonetici`

`Accept: application/json` — "Hata gibi cevaplarda JSON bekliyorum" anlamında kullanılıyor.

Bu başlık, controller'ın yazdığı her cevabı otomatik olarak JSON'a çevirmiyor. Başarılı giriş controller'ı hâlâ redirect döndürüyor.

### F. Laravel hangi metoda gideceğini buluyor

`routes/auth.php`:

```php
Route::post('giris/{rol}', [
    AuthenticatedSessionController::class,
    'store',
])
    ->whereIn('rol', ['yonetici', 'ogretmen'])
    ->name('login.attempt');
```

Buradan `AuthenticatedSessionController::store()` metoduna gidiliyor. `whereIn`, `rol` URL parametresini kabul edilen iki değerle sınırlandırıyor.

### G. LoginRequest alanları ve kimliği kontrol ediyor

`app/Http/Requests/Auth/LoginRequest.php` — ilk alan kuralları:

```php
return [
    'email' => ['required', 'string', 'email'],
    'password' => ['required', 'string'],
];
```

Ardından controller'ın çağırdığı `authenticate()` içinde:

```php
$this->ensureIsNotRateLimited();

if (! Auth::attempt(
    $this->only('email', 'password'),
    $this->boolean('remember')
)) {
    RateLimiter::hit($this->throttleKey());

    throw ValidationException::withMessages([
        'email' => trans('auth.failed'),
    ]);
}
```

Sırayla:

1. Çok fazla başarısız deneme var mı?
2. E-posta/parola doğru mu?
3. Yanlışsa başarısız deneme sayacını artır.
4. Validation hatası üret.

**`Auth::attempt()` neden `User` modelini kullanacağını biliyor?** Çünkü `config/auth.php` içinde `web` guard'ı session sürücüsünü, `users` provider'ını, provider da Eloquent `User` modelini kullanacak şekilde tanımlı.

**Guard ile rolü karıştırma:**

- **Guard:** Kullanıcı nasıl doğrulanıyor?
- **Rol:** Doğrulanan kullanıcı ne yapabilir?

### H. Parola nerede ve nasıl tutuluyor?

`app/Models/User.php`:

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

Parola düz metin olarak saklanmak yerine hash davranışıyla işleniyor.

Ayrıca:

```php
#[Hidden(['password', 'remember_token'])]
```

Bu, kullanıcı JSON'a çevrilirken parola hash'inin normal çıktıda görünmesini engelliyor.

İki ayrı görev var:

- `hashed` — Saklama davranışı.
- `Hidden` — Çıktıda gizleme.

### I. Şifre doğru olsa bile rol kontrolü yapılıyor

`app/Http/Controllers/Auth/AuthenticatedSessionController.php`:

```php
private const ROL_ESLESTIRME = [
    'yonetici' => ['admin', 'superadmin'],
    'ogretmen' => ['teacher'],
];
```

Örnek:

- Öğretmen hesabıyla yönetici girişinden geldin.
- Parolan doğru.
- Ancak hesabının rolü `teacher`.
- Yönetici girişinin kabul ettiği roller arasında değilsin.
- Oluşan web kimlik doğrulama durumu kapatılıp hata dönülüyor.

URL'de `yonetici` yazması seni yönetici yapmıyor. Gerçek rol sunucudaki kullanıcı kaydından okunuyor.

### J. Başarılı girişte oturum kimliği yenileniyor

```php
$request->session()->regenerate();
```

Bu, giriş öncesindeki oturum kimliğinin giriş sonrasında aynen kullanılmasını önlemeye yönelik bir güvenlik adımıdır.

Parola değişmez. Kullanıcı ID'si değişmez. Oturum kimliği yenilenir.

Controller sonrasında:

```php
return redirect()->intended('/app');
```

Vue'nin Axios isteği bu yönlendirmeyi ağ isteği kapsamında takip edebilir; Vue ekran geçişini ise kendi router'ıyla ayrıca yapıyor.

### K. Vue kullanıcı bilgisini backend'den alıyor

`Giris.vue`:

```js
await auth.kontrolEt();
router.push({ name: 'anasayfa' });
```

`kontrolEt()` Pinia store'unda — `resources/js/stores/auth.js`:

```js
async kontrolEt() {
    try {
        const { data } = await api.get('/user');
        this.kullanici = data;
    } catch {
        this.kullanici = null;
    } finally {
        this.kontrolEdildi = true;
    }
}
```

Buradaki gerçek adres: `GET /api/user` — çünkü ortak Axios istemcisinin taban adresi `/api`.

Bu istekte e-posta/parola tekrar gönderilmiyor. Mevcut session cookie ile kullanıcı tanınıyor.

### L. Sanctum burada ne yapıyor?

API grubunda `auth:sanctum` var. Ayrıca `bootstrap/app.php`, kendi SPA'ndan gelen uygun isteklerin stateful işlenmesi için Sanctum middleware'ini ekliyor.

Böylece "Bu endpoint `/api` altında, o yüzden kesin token gerekir" diye bir kural yok.

Vue arayüzü, oturum çereziyle `/api/user` ve diğer API uç noktalarını kullanabiliyor. `config/sanctum.php` içindeki `guard => ['web']` ayarı da bu bağlantının parçası.

### M. Router doğru panele yönlendiriyor

`resources/js/router.js`:

- Kullanıcı bilgisi kontrol edilmemişse kontrol ettirir.
- Giriş gerekli ama kullanıcı yoksa giriş seçimine gönderir.
- Rol yetersizse uygun panele yönlendirir.
- `/app` hedefinde öğretmeni öğretmen paneline, yöneticiyi dashboard'a götürür.

Bu güvenliğin tek katmanı değil:

- **Router guard:** Kullanıcı yanlış ekranı görmesin.
- **Backend middleware:** Yetkisiz kullanıcı işlemi gerçekleştiremesin.

İkisi farklı sorumluluklar.

### N. "Hesabınıza okul atanmamış" tam olarak nerede çıkıyor?

`/api/user` dahil korumalı API grubunda `aktifokul` middleware'i bulunuyor.

Akış:

1. Kullanıcı session ile doğrulanmış olabilir.
2. `EnsureAktifOkul` çalışır.
3. `School::aktif()` çağrılır.
4. Kullanıcı admin ise kendi `school_id` bağlantısı aranır.
5. Okul bulunmazsa 403 döner.

Bu yüzden: **Başarılı parola kontrolü ≠ uygulamanın bütün ekranlarına erişim**

Ek bir mevcut davranış: Pinia'daki `kontrolEt()` bütün hatalarda `kullanici = null` yapıyor. Yani 403, ağ hatası ve 401 arayüz state'i açısından aynı sonuca indirgenebiliyor. Hataları yorumlarken dikkat edilecek nokta.

### O. Öğretmenin hesabı nasıl oluşturuluyor?

Öğretmen kaydı oluşturmak ile login hesabı oluşturmak farklı.

`app/Services/OgretmenServisi.php` içindeki `hesapAc()`:

1. Öğretmenin zaten kullanıcı hesabı var mı diye bakar.
2. E-posta başka kullanıcıda kullanılıyor mu kontrol eder.
3. Geçici parola üretir.
4. Transaction içinde `User` oluşturur.
5. Rolü `teacher` yapar.
6. `teachers.user_id` alanını yeni kullanıcıya bağlar.
7. Sonucu ve geçici parolayı çağıran tarafa döndürür.

Öğretmen giriş yaptıktan sonra `EnsureUserIsTeacher` hem rolü hem bu bağlantıyı kontrol eder.

### P. Çıkışta hangi dosyalar çalışıyor?

Frontend — `resources/js/components/Layout.vue`:

```js
async function cikisYap() {
    await axios.post('/logout', {}, {
        headers: { Accept: 'application/json' },
    });
    auth.cikisYap();
    router.push({ name: 'giris-secim' });
}
```

Backend — `AuthenticatedSessionController.php`:

```php
Auth::guard('web')->logout();
$request->session()->invalidate();
$request->session()->regenerateToken();
```

Sonuç:

- Backend web oturumunu kapatır.
- Session geçersiz kılınır.
- CSRF tokenı yenilenir.
- Frontend kullanıcı state'ini temizler.
- Giriş seçimine dönülür.

Yalnızca Pinia'yı temizlemek logout değildir. Sunucudaki oturumun da kapatılması gerekir.

### Q. Ayrı `/api/login` ne yapıyor?

`app/Http/Controllers/Api/AuthController.php`:

1. E-posta/parolayı doğrular.
2. Başarısız deneme sınırına bakar.
3. Kullanıcıyı e-postayla bulur.
4. `Hash::check()` ile parolayı doğrular.
5. Başarılıysa Sanctum tokenı oluşturur.

```php
'token' => $kullanici->createToken('api')->plainTextToken,
```

Bu token daha sonraki isteklerde Bearer kimlik bilgisi olarak gönderilebilir. Ama normal Vue login ekranı bu tokenı almıyor ve Pinia'da saklamıyor.

Ayrıca web logout'u bu kişisel API tokenlarını kendiliğinden silmez. Bunların yaşam döngüsü ayrı yönetilir.

### R. Şifre sıfırlamanın dosya zinciri

```
SifremiUnuttum.vue → Api/PasswordController::forgot() → Laravel Password mekanizması
→ e-posta → SifreSifirla.vue → Api/PasswordController::reset()
```

Bu akışta:

- `AppServiceProvider` bağlantının Vue ekranına gitmesini sağlar.
- `config/auth.php` broker ve süre ayarlarını belirler.
- `password_reset_tokens` tablosu sıfırlama kayıtlarını tutar.
- Başarılı işlemde kullanıcının parolası hash'lenerek güncellenir.

Buradaki token:

- Session cookie **değil**.
- CSRF tokenı **değil**.
- API erişim tokenı **değil**.

Parola sıfırlama işlemi için kullanılan **ayrı bir token**.

---

## 15. Hepsini akılda tutmanın en kolay yolu

Şef bir dosyayı açtığında önce şu soru: **"Bu dosya hangi katmanda?"**

Sonra görevini bağla:

| Katman | Tek cümle |
|---|---|
| Route | Hangi URL hangi işleme gidiyor? |
| Middleware | Bu istek devam edebilir mi? |
| Request | Gelen veri uygun mu? |
| Controller | Hangi işlem yapılacak, hangi cevap dönecek? |
| Service | İşin kuralları nasıl uygulanacak? |
| Model | Hangi veriler ve ilişkilerle çalışıyoruz? |
| Migration | Veritabanında bu yapı nasıl oluşturuluyor? |
| Vue page | Kullanıcı hangi ekranla etkileşiyor? |
| Component | Hangi ortak ekran parçasını kullanıyoruz? |
| Pinia | Frontend'in ortak durumu ne? |
| Config | Sistemin davranış ayarları ne? |

**Login'in tek cümlelik özeti:**

> Vue formu CSRF hazırlığını yapıp web giriş rotasına e-posta ve parola gönderiyor; Laravel kimliği ve rolü doğrulayıp session oluşturuyor/yeniliyor, Vue `/api/user` ile kullanıcıyı alıyor ve role göre panele yönlendiriyor.

**Uygulamanın tek cümlelik özeti:**

> Vue ekranları Laravel API'siyle konuşuyor; middleware erişimi, request sınıfları veriyi, controller'lar akışı, servisler iş kurallarını ve Eloquent modelleri veritabanı işlemlerini yönetiyor.
