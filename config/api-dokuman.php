<?php

/**
 * API dokumanindaki INSAN DILIYLE yazilmis kisimlar.
 *
 * Adres, HTTP metodu, parametreler, yetki gereksinimi ve istek
 * govdesi otomatik cikariliyor (bkz. App\Console\Commands\
 * ApiDokumaniUret). Burada sadece kodun kendinden anlasilamayan
 * seyler duruyor: bu uc nokta NE ISE YARIYOR, dikkat edilmesi
 * gereken bir sey var mi.
 *
 * Anahtar bicimi: "METOT uri" - ornegin "POST api/program/atama".
 * Anahtari olmayan uc noktalar icin otomatik bir ozet uretiliyor ve
 * komut calisirken hangilerinin eksik oldugu bildiriliyor.
 */

return [

    'giris' => <<<'METIN'
    ODPYS (Okul Ders Programı Yönetim Sistemi) REST API'si.

    ## Kimlik doğrulama

    İki yol destekleniyor:

    1. **Oturum çerezi (SPA)** — Uygulamanın kendi arayüzü bunu kullanır.
       Tarayıcıda giriş yaptıysanız aşağıdaki "Try it out" düğmeleri
       doğrudan çalışır; çerez otomatik gönderilir.

    2. **Bearer token (harici istemciler)** — Postman, mobil uygulama vb.
       `POST /api/login` ile token alınır, sonraki isteklerde
       `Authorization: Bearer <token>` başlığıyla gönderilir.

    ## Roller

    | Rol | Kapsam |
    |---|---|
    | `superadmin` | Bütün okullar; okul ekleyebilir, yönetici atayabilir |
    | `admin` | Yalnızca kendi okulu |
    | `teacher` | Yalnızca kendi programı ve izin talepleri |

    ## Çoklu okul

    Yönetici uç noktaları **aktif okulun** verisini döndürür. Aktif okul
    süper yöneticide oturumda tutulur ve `POST /api/okullar/{okul}/aktif-yap`
    ile değiştirilir; okula bağlı yöneticide ise her zaman kendi okuludur
    ve değiştirilemez.

    ## Hata biçimi

    Doğrulama hatası (422) her zaman şu biçimde döner:

    ```json
    { "message": "...", "errors": { "alan_adi": ["Hata mesajı"] } }
    ```
    METIN,

    'aciklamalar' => [

        // ───────── Kimlik doğrulama ─────────

        'POST api/login' => [
            'ozet' => 'Token ile giriş (harici istemciler)',
            'aciklama' => 'Bearer token üretir. **SPA bunu kullanmaz** — o, oturum '
                . 'çerezi için `POST /giris/{rol}` web rotasına gider. Bu uç nokta '
                . 'Postman ve mobil uygulama gibi çerez kullanamayan istemciler için.',
        ],

        'GET api/user' => [
            'ozet' => 'Giriş yapmış kullanıcının bilgileri',
            'aciklama' => 'SPA açılışta bunu çağırıp "oturum hâlâ geçerli mi" diye '
                . 'kontrol eder. Giriş yapılmamışsa **401 dönmesi normaldir** — hata değil.',
        ],

        'POST api/forgot-password' => [
            'ozet' => 'Şifre sıfırlama bağlantısı gönder',
            'aciklama' => 'E-postadaki bağlantı SPA sayfasına yönlenir '
                . '(`/app/sifre-sifirla/{token}`).',
        ],

        'POST api/reset-password' => [
            'ozet' => 'Yeni şifreyi kaydet',
            'aciklama' => 'E-postadaki `token`, `email`, `password` ve '
                . '`password_confirmation` alanlarını bekler.',
        ],

        // ───────── Profil ─────────

        'PUT api/profil' => [
            'ozet' => 'Kendi ad ve e-postasını güncelle',
            'aciklama' => 'Her rol kendi hesabını düzenleyebilir. Controller her zaman '
                . '`$request->user()` üzerinde çalıştığı için kimse başkasının hesabına '
                . 'dokunamaz — ayrı bir yetki kontrolü gerekmiyor.',
        ],

        'PUT api/profil/sifre' => [
            'ozet' => 'Kendi şifresini değiştir',
            'aciklama' => '`current_password` alanı zorunlu: açık bırakılmış bir '
                . 'tarayıcının başına geçen biri eski şifreyi bilmeden değiştiremesin diye.',
        ],

        // ───────── Program sihirbazı ─────────

        'GET api/program' => [
            'ozet' => 'Sihirbaz durumu',
            'aciklama' => 'Kaç atama ve kaç ders saati üretilmiş, dersliksiz şube var mı.',
        ],

        'POST api/program/atama' => [
            'ozet' => '1. adım — öğretmen ataması',
            'aciklama' => "Her şube-ders çiftine, o dersi o seviyede verebilen "
                . "**en az yüklü** öğretmeni atar (hakkaniyet).\n\n"
                . "Mevcut atamaları önce siler, sonra yenisini yazar — tamamı tek "
                . "transaction içinde.\n\n"
                . "Cevapta atanamayan dersler **sebebiyle birlikte** döner: "
                . "yetkili öğretmen tanımlı değil mi, yoksa hepsinin kapasitesi mi dolmuş.",
        ],

        'POST api/program/yerlestir' => [
            'ozet' => '2. adım — gün/saat yerleştirme',
            'aciklama' => "1. adımdaki atamaları çakışmasız biçimde günlere ve ders "
                . "saatlerine yerleştirir.\n\n"
                . "**Dikkat:** Mevcut programı siler ve baştan üretir. Çizelge üzerinde "
                . "elle yapılan düzenlemeler ve o programa bağlı ders takasları da gider.\n\n"
                . "Cevapta doluluk oranı, gün dağılımı ve yerleştirilemeyenler sebebiyle döner.",
        ],

        'GET api/program/duzenle/{sube}/{weekday}/{slot}' => [
            'ozet' => 'Hücre düzenleme formu verisi',
            'aciklama' => 'O hücrede hâlen ne olduğu + seçilebilecek ders ve öğretmen listesi.',
            'parametreler' => [
                'sube' => 'Şube id',
                'weekday' => 'Gün numarası (1 = Pazartesi … 7 = Pazar)',
                'slot' => 'Kaçıncı ders (1 = ilk ders)',
            ],
        ],

        'PUT api/program/duzenle/{sube}/{weekday}/{slot}' => [
            'ozet' => 'Hücreyi elle düzenle',
            'aciklama' => "Kaydetmeden önce üç kontrolden geçer:\n"
                . "1. Ders bu şubenin seviyesinin müfredatında var mı\n"
                . "2. Öğretmen bu dersi bu seviyede vermeye yetkili mi\n"
                . "3. Öğretmenin o saatte başka bir şubede dersi var mı\n\n"
                . "Herhangi biri başarısızsa 422 döner.",
            'parametreler' => [
                'sube' => 'Şube id',
                'weekday' => 'Gün numarası (1–7)',
                'slot' => 'Kaçıncı ders',
            ],
        ],

        'DELETE api/program/duzenle/{sube}/{weekday}/{slot}' => [
            'ozet' => 'Hücreyi boşalt',
            'parametreler' => [
                'sube' => 'Şube id',
                'weekday' => 'Gün numarası (1–7)',
                'slot' => 'Kaçıncı ders',
            ],
        ],

        // ───────── Çizelge ─────────

        'GET api/cizelge' => [
            'ozet' => 'Çizelge ana ekranı',
            'aciklama' => 'Program durumu, gezinme listeleri ve zaman çakışması kontrolü. '
                . 'Çakışma listesinin **boş dönmesi beklenir** — `schedule_entries` '
                . 'tablosundaki üç unique index çakışmayı zaten fiziksel olarak engelliyor; '
                . 'bu kontrol o garantiyi doğruluyor.',
        ],

        'GET api/cizelge/sube/{sube}' => [
            'ozet' => 'Bir şubenin haftalık çizelgesi',
            'aciklama' => "Haftalık şablon + o haftanın izinleri + o haftanın ders "
                . "takasları birleştirilerek üretilir.\n\n"
                . "Her hücrede `izinli` ve `takasli` bayrakları döner; arayüz bunlara "
                . "göre renklendiriyor (kırmızı = izinli, sarı = takasla saati değişmiş).\n\n"
                . "**Kısıt:** yalnızca içinde bulunulan hafta gösterilir.",
            'parametreler' => ['sube' => 'Şube id'],
        ],

        'GET api/cizelge/ogretmen/{ogretmen}' => [
            'ozet' => 'Bir öğretmenin haftalık programı',
            'parametreler' => ['ogretmen' => 'Öğretmen id'],
        ],

        'GET api/cizelge/yuk' => [
            'ozet' => 'Yük dağılımı raporu',
            'aciklama' => 'Hangi öğretmen haftada kaç saat ders alıyor, kapasitesinin '
                . 'yüzde kaçı dolu.',
        ],

        // ───────── İzinler ve takas ─────────

        'GET api/ogretmen/panel' => [
            'ozet' => 'Öğretmenin kendi paneli',
            'aciklama' => 'Kendi haftalık programı, branşı ve toplam ders saati.',
        ],

        'GET api/ogretmen/izinler' => [
            'ozet' => 'Kendi izin taleplerim',
        ],

        'POST api/ogretmen/izinler' => [
            'ozet' => 'İzin talebi oluştur',
            'aciklama' => '`teacher_id` istekten **alınmaz** — oturum açmış kullanıcının '
                . 'kendi öğretmen kaydından okunur. Böylece kimse başkası adına talep '
                . 'oluşturamaz.',
        ],

        'GET api/izinler' => [
            'ozet' => 'Bütün izin talepleri (yönetici)',
            'aciklama' => 'Bekleyenler en üstte.',
        ],

        'GET api/izinler/{izin}' => [
            'ozet' => 'İzin detayı + etkilenen dersler + yapılmış takaslar',
            'aciklama' => 'Etkilenen ders saatleri, iznin tarih aralığı gün gün gezilerek '
                . 've her günün slot saatleri okul ayarlarından hesaplanarak bulunur.',
            'parametreler' => ['izin' => 'İzin talebi id'],
        ],

        'POST api/izinler/{izin}/onayla' => [
            'ozet' => 'İzni onayla',
            'aciklama' => "Onaylandığında haftalık program **silinmez veya değiştirilmez**. "
                . "Haftalık program tarihsiz bir şablon, izin ise tarihli bir olaydır; "
                . "ikisi yalnızca görüntülenirken birleşir.",
            'parametreler' => ['izin' => 'İzin talebi id'],
        ],

        'POST api/izinler/{izin}/reddet' => [
            'ozet' => 'İzni reddet',
            'parametreler' => ['izin' => 'İzin talebi id'],
        ],

        'GET api/izinler/{izin}/takas-adaylari' => [
            'ozet' => 'Ders takası adaylarını listele',
            'aciklama' => "İzin yüzünden boşalan dersin, **aynı gün ve aynı şube içinde** "
                . "yer değiştirebileceği dersleri döner.\n\n"
                . "Uygun olmayan adaylar **gizlenmez**, `uygun: false` ve `sebep` alanıyla "
                . "birlikte döner — yönetici listenin neden kısa olduğunu görebilsin diye.\n\n"
                . "Dört kural denetlenir: izinli öğretmen yeni saatte de izinli mi, karşı "
                . "öğretmen eski saatte izinli mi, ve her ikisinin yeni slotta başka şubede "
                . "dersi var mı. Derslik kontrolü yoktur — takas aynı şube içinde kaldığı "
                . "için derslik değişmez.",
            'parametreler' => ['izin' => 'İzin talebi id'],
            'sorgu' => [
                'entry_id' => ['tip' => 'integer', 'zorunlu' => true, 'aciklama' => 'İzinli ders kaydının id değeri'],
                'tarih' => ['tip' => 'string', 'zorunlu' => true, 'aciklama' => 'Takasın geçerli olacağı gün (YYYY-AA-GG)'],
            ],
        ],

        'POST api/izinler/{izin}/takas' => [
            'ozet' => 'Ders takası yap',
            'aciklama' => "İki dersi **yalnızca belirtilen tarihte** yer değiştirir. "
                . "`schedule_entries` tablosuna dokunulmaz; `lesson_swaps` tablosuna tek "
                . "bir satır yazılır.\n\n"
                . "Yalnızca **onaylanmış** izinlerde çalışır. Adayı transaction içinde "
                . "yeniden doğrular (iki yöneticinin aynı anda aynı dersi takasa sokmasını "
                . "engellemek için).",
            'parametreler' => ['izin' => 'İzin talebi id'],
            'govde' => [
                'type' => 'object',
                'required' => ['entry_id', 'karsi_entry_id', 'tarih'],
                'properties' => [
                    'entry_id' => ['type' => 'integer', 'description' => 'İzinli ders kaydının id değeri'],
                    'karsi_entry_id' => ['type' => 'integer', 'description' => 'Yer değiştirilecek dersin id değeri'],
                    'tarih' => ['type' => 'string', 'format' => 'date', 'description' => 'YYYY-AA-GG'],
                ],
            ],
        ],

        'DELETE api/izinler/{izin}/takas/{takas}' => [
            'ozet' => 'Takası geri al',
            'aciklama' => 'Tek bir satır silinir; şablona hiç dokunulmadığı için program '
                . 'kendiliğinden eski hâline döner ve izin işareti geri gelir.',
            'parametreler' => ['izin' => 'İzin talebi id', 'takas' => 'Takas kaydı id'],
        ],

        // ───────── Çoklu okul ve yöneticiler ─────────

        'GET api/okul' => [
            'ozet' => 'Aktif okulun ayarları',
            'aciklama' => 'Ders saatleri çizelgesi de hesaplanmış olarak döner — '
                . 'saatler veritabanında saklanmaz, okul ayarlarından türetilir.',
        ],

        'PUT api/okul' => [
            'ozet' => 'Okul ayarlarını güncelle',
            'aciklama' => 'Ders süresi değiştirilince bütün çizelgedeki saatler '
                . 'kendiliğinden kayar; program yeniden üretilmesi gerekmez.',
        ],

        'GET api/okullar' => [
            'ozet' => 'Okul listesi',
            'aciklama' => 'Süper yönetici bütün okulları görür. Okula bağlı yönetici '
                . 'yalnızca **kendi okulunu** görür. Cevaptaki `superAdmin` bayrağı, '
                . 'arayüzün "Okul Ekle" ve "Aktif Yap" düğmelerini gizlemesi için.',
        ],

        'POST api/okullar' => [
            'ozet' => 'Yeni okul ekle',
            'aciklama' => 'Eklenen okul otomatik olarak aktif okul yapılır.',
        ],

        'POST api/okullar/{okul}/aktif-yap' => [
            'ozet' => 'Aktif okulu değiştir',
            'aciklama' => 'Bütün ekranların hangi okulun verisini göstereceğini belirler. '
                . 'Okula bağlı yönetici bunu **çağıramaz** — kendini başka bir okula '
                . 'geçirerek kısıtlamayı aşabilirdi.',
            'parametreler' => ['okul' => 'Okul id'],
        ],

        'GET api/yoneticiler' => [
            'ozet' => 'Yönetici hesapları + okulları',
        ],

        'POST api/yoneticiler' => [
            'ozet' => 'Yönetici hesabı oluştur',
            'aciklama' => 'Rol `admin` ise `school_id` **zorunludur**; boş bırakılırsa '
                . 'kullanıcı hiçbir okula bağlı sayılmaz ve hiçbir şeye erişemez.',
        ],

        'PUT api/yoneticiler/{yonetici}' => [
            'ozet' => 'Yöneticinin rolünü/okulunu değiştir',
            'aciklama' => 'Sistemdeki **son süper yönetici** bu yetkiden çıkarılamaz — '
                . 'çıkarılabilseydi okul ekleyebilen ve yönetici atayabilen kimse kalmaz, '
                . 'geri dönüşü olmayan bir kilitlenme oluşurdu.',
            'parametreler' => ['yonetici' => 'Kullanıcı id'],
        ],

        'DELETE api/yoneticiler/{yonetici}' => [
            'ozet' => 'Yönetici hesabını sil',
            'aciklama' => 'Kullanıcı kendi hesabını ve son süper yöneticiyi silemez.',
            'parametreler' => ['yonetici' => 'Kullanıcı id'],
        ],

        // ───────── Öğretmenler ─────────

        'GET api/ogretmenler' => [
            'ozet' => 'Öğretmen listesi (sayfalı)',
            'aciklama' => '20\'şer sayfalanır.',
            'sorgu' => [
                'ara' => ['tip' => 'string', 'aciklama' => 'Ad, e-posta veya TC no içinde arar'],
                'durum' => ['tip' => 'string', 'aciklama' => '"aktif" veya "pasif"'],
                'page' => ['tip' => 'integer', 'aciklama' => 'Sayfa numarası'],
            ],
        ],

        'POST api/ogretmenler' => [
            'ozet' => 'Öğretmen ekle',
            'aciklama' => 'Fotoğraf ve özgeçmiş yüklenebildiği için istek '
                . '`multipart/form-data` olarak gönderilmelidir.',
        ],

        'POST api/ogretmenler/{ogretmen}/hesap' => [
            'ozet' => 'Öğretmene giriş hesabı aç',
            'parametreler' => ['ogretmen' => 'Öğretmen id'],
        ],

        'GET api/ogretmenler/{ogretmen}/dersler' => [
            'ozet' => 'Öğretmenin ders yetkileri',
            'aciklama' => 'Hangi dersi hangi seviyede verebildiği. **Sihirbazın en kritik '
                . 'girdisi** — boşsa sihirbaz hiçbir atama yapamaz.',
            'parametreler' => ['ogretmen' => 'Öğretmen id'],
        ],

        'PUT api/ogretmenler/{ogretmen}/dersler' => [
            'ozet' => 'Ders yetkilerini kaydet',
            'parametreler' => ['ogretmen' => 'Öğretmen id'],
            'govde' => [
                'type' => 'object',
                'properties' => [
                    'yetkiler' => [
                        'type' => 'object',
                        'description' => 'yetkiler[dersId][seviyeId] = 1 biçiminde iç içe nesne',
                    ],
                ],
            ],
        ],

        // ───────── Diğer ─────────

        'GET api/dashboard' => [
            'ozet' => 'Dashboard özet kartları',
        ],

        'GET api/ucretler' => [
            'ozet' => 'Aylık ücret raporu',
            'aciklama' => 'Üretilmiş programdaki haftalık saatler, dersin saatlik ücretiyle '
                . 'çarpılıp 4 hafta üzerinden aya çevrilir. **Bordro değil, gösterge** '
                . 'niteliğindedir — resmî tatiller ve gerçek hafta sayısı hesaba katılmaz.',
        ],

        'GET api/gezinme' => [
            'ozet' => 'Gezinme listeleri (id + etiket)',
            'aciklama' => 'Detay ekranlarındaki "önceki/sonraki kayıt" çubuğu için sade '
                . 'şube ve öğretmen listeleri. `/api/cizelge` de aynı listeleri döner ama '
                . 'yanında çakışma kontrolü çalıştırır; `/api/ogretmenler` ise sayfalıdır.',
        ],

        // ───────── Standart CRUD ─────────
        //
        // apiResource her kaynak icin PUT ve PATCH'i birlikte kaydediyor
        // (ikisi de ayni controller metoduna gidiyor), o yuzden ikisi de
        // burada yaziyor.

        'GET api/seviyeler' => ['ozet' => 'Seviye listesi (şube sayılarıyla)'],
        'POST api/seviyeler' => ['ozet' => 'Seviye ekle'],
        'PUT api/seviyeler/{seviye}' => ['ozet' => 'Seviyeyi güncelle', 'parametreler' => ['seviye' => 'Seviye id']],
        'PATCH api/seviyeler/{seviye}' => ['ozet' => 'Seviyeyi güncelle (PUT ile aynı)', 'parametreler' => ['seviye' => 'Seviye id']],
        'DELETE api/seviyeler/{seviye}' => [
            'ozet' => 'Seviyeyi sil',
            'aciklama' => 'Üretilmiş programda kullanılan bir seviye silinemez.',
            'parametreler' => ['seviye' => 'Seviye id'],
        ],

        'GET api/subeler' => ['ozet' => 'Şube listesi'],
        'POST api/subeler' => ['ozet' => 'Şube ekle'],
        'PUT api/subeler/{sube}' => ['ozet' => 'Şubeyi güncelle', 'parametreler' => ['sube' => 'Şube id']],
        'PATCH api/subeler/{sube}' => ['ozet' => 'Şubeyi güncelle (PUT ile aynı)', 'parametreler' => ['sube' => 'Şube id']],
        'DELETE api/subeler/{sube}' => ['ozet' => 'Şubeyi sil', 'parametreler' => ['sube' => 'Şube id']],

        'GET api/derslikler' => ['ozet' => 'Derslik listesi'],
        'POST api/derslikler' => ['ozet' => 'Derslik ekle'],
        'PUT api/derslikler/{derslik}' => ['ozet' => 'Dersliği güncelle', 'parametreler' => ['derslik' => 'Derslik id']],
        'PATCH api/derslikler/{derslik}' => ['ozet' => 'Dersliği güncelle (PUT ile aynı)', 'parametreler' => ['derslik' => 'Derslik id']],
        'DELETE api/derslikler/{derslik}' => [
            'ozet' => 'Dersliği sil',
            'aciklama' => 'Bir şubeye atanmışsa şube dersliksiz kalır, silinmez.',
            'parametreler' => ['derslik' => 'Derslik id'],
        ],

        'GET api/dersler' => ['ozet' => 'Ders listesi (seviye saatleriyle)'],
        'POST api/dersler' => [
            'ozet' => 'Ders ekle',
            'aciklama' => 'Bir seviyedeki bütün derslerin toplamı haftalık kapasiteyi '
                . '(günlük ders × aktif gün) aşamaz.',
        ],
        'PUT api/dersler/{ders}' => ['ozet' => 'Dersi güncelle', 'parametreler' => ['ders' => 'Ders id']],
        'PATCH api/dersler/{ders}' => ['ozet' => 'Dersi güncelle (PUT ile aynı)', 'parametreler' => ['ders' => 'Ders id']],
        'DELETE api/dersler/{ders}' => ['ozet' => 'Dersi sil', 'parametreler' => ['ders' => 'Ders id']],

        'PUT api/ogretmenler/{ogretmen}' => [
            'ozet' => 'Öğretmeni güncelle',
            'aciklama' => 'Dosya yüklendiği için `multipart/form-data` + `_method=PUT` ile gönderilir.',
            'parametreler' => ['ogretmen' => 'Öğretmen id'],
        ],
        'PATCH api/ogretmenler/{ogretmen}' => [
            'ozet' => 'Öğretmeni güncelle (PUT ile aynı)',
            'parametreler' => ['ogretmen' => 'Öğretmen id'],
        ],
        'DELETE api/ogretmenler/{ogretmen}' => [
            'ozet' => 'Öğretmeni sil',
            'aciklama' => 'Üretilmiş programda ders ataması olan öğretmen silinemez — '
                . 'önce atamaları kaldırın veya öğretmeni pasife alın.',
            'parametreler' => ['ogretmen' => 'Öğretmen id'],
        ],
    ],
];
