<script setup>
import { computed, ref } from 'vue';
import Layout from '../components/Layout.vue';
import { useAuthStore } from '../stores/auth';

/**
 * Sartname 3.4 - "Nasil Kullanilir" sayfasi.
 *
 * Ic icerik ROLE GORE degisiyor: yonetici kurulum sirasini ve butun
 * ekranlari goruyor, ogretmen sadece kendi kullandigi iki ekrani.
 * Ayni sayfayi iki role bolmek yerine tek sayfada tutmanin sebebi,
 * ortak bolumlerin (profil, sifre, aktif okul) tekrarlanmamasi.
 *
 * Sayfa API'ye HIC istek atmiyor - icerik statik. Bu bilincli: yardim
 * metnini veritabanina koymak, guncellemek icin ayri bir yonetim
 * ekrani gerektirirdi; kod icinde durunca ekran degistiginde ayni
 * commit'te guncelleniyor.
 */
const auth = useAuthStore();
const yonetici = computed(() => auth.rol === 'admin');

// Yan menudeki baslik listesi. Icerikteki id'lerle birebir ayni
// olmali - buradan tiklaninca o bolume kayiyor.
const bolumler = computed(() => {
    const ortak = [
        { id: 'profil', ad: 'Profil ve şifre' },
    ];

    if (! yonetici.value) {
        return [
            { id: 'ogretmen-panel', ad: 'Panelim' },
            { id: 'ogretmen-izin', ad: 'İzin talebi oluşturma' },
            ...ortak,
        ];
    }

    return [
        { id: 'kurulum', ad: 'İlk kurulum sırası' },
        { id: 'sihirbaz', ad: 'Program sihirbazı' },
        { id: 'cizelge', ad: 'Çizelge ve çıktılar' },
        { id: 'izin', ad: 'İzin talepleri' },
        { id: 'ucret', ad: 'Aylık ücret raporu' },
        { id: 'okullar', ad: 'Çoklu okul ve aktif okul' },
        ...ortak,
        { id: 'sss', ad: 'Sık karşılaşılan durumlar' },
    ];
});

const aktifBolum = ref('');

/**
 * Icindekiler'den bir bolume kaydirma.
 *
 * behavior:'smooth' BILEREK kullanilmiyor. Test sirasinda goruldu ki
 * bazi ortamlarda (ve kullanici isletim sisteminde "hareketi azalt"
 * secenegini actiysa) smooth kaydirma SESSIZCE hic calismiyor -
 * sayfa oldugu yerde kaliyor, hicbir hata da vermiyor. Yani menuye
 * tikliyorsun ve hicbir sey olmuyor.
 *
 * Animasyonsuz scrollIntoView her ortamda calisiyor ve hedefe mutlaka
 * gidiyor. Bir yardim sayfasinda "dogru yere gitmek", "yumusak
 * gitmek"ten onemli.
 *
 * Bolumlerdeki scroll-mt-6 sinifi, hedefin ekranin en tepesine
 * yapismasini engelleyip ustunde biraz bosluk birakiyor.
 */
function git(id) {
    aktifBolum.value = id;
    document.getElementById(id)?.scrollIntoView({ block: 'start' });
}

// Yonetici kurulum adimlari. Sira RASTGELE DEGIL - her adim bir
// oncekine bagimli, "neden" alani bu bagimliligi anlatiyor.
const kurulumAdimlari = [
    {
        rota: 'okul-ayarlari',
        baslik: 'Okul Ayarları',
        ne: 'Okul adı, il/ilçe, günde kaç ders olduğu, bir dersin kaç dakika sürdüğü, mola süresi ve hangi günlerin eğitim günü olduğu (her gün için başlangıç saatiyle birlikte) girilir.',
        neden: 'Ders saatleri veritabanında saklanmıyor; "1. ders 08:00–08:40" bilgisi bu ayarlardan hesaplanıyor. Ayrıca bir öğretmenin haftalık azami kapasitesi de buradan çıkıyor. Bu yüzden en başta doğru girilmeli — sonradan ders süresini değiştirirsen bütün çizelge kendiliğinden kayar.',
    },
    {
        rota: 'seviyeler',
        baslik: 'Seviyeler',
        ne: '9, 10, 11, 12 gibi sınıf seviyeleri tanımlanır.',
        neden: 'Şubeler bir seviyeye bağlı açılıyor, dersler de haftalık saatlerini seviye seviye alıyor. Seviye yoksa ikisi de yapılamaz.',
    },
    {
        rota: 'derslikler',
        baslik: 'Derslikler',
        ne: 'Fizik-Lab, D-9A gibi derslikler tanımlanır.',
        neden: 'Bu sistemde her şubenin sabit bir dersliği var; öğretmenler dolaşıyor. Bu sayede derslik çakışması yapısal olarak imkânsız hale geliyor.',
    },
    {
        rota: 'subeler',
        baslik: 'Şubeler',
        ne: '9A, 9B gibi şubeler açılır; her biri bir seviyeye bağlanır ve bir derslik atanır.',
        neden: 'Dersliği olmayan bir şubenin programı üretilemez — sihirbaz bunu turuncu bir uyarıyla söyler ve o şubeyi atlar.',
    },
    {
        rota: 'dersler',
        baslik: 'Dersler',
        ne: 'Ders adı, saatlik ücreti, blok ders olup olmadığı ve hangi seviyede haftada kaç saat okutulacağı girilir.',
        neden: 'Müfredat burada tanımlanıyor. "10. sınıf Matematik haftada 6 saat" bilgisi olmadan sihirbazın üretecek bir şeyi olmaz. Saatlik ücret de ücret raporunun temeli.',
    },
    {
        rota: 'ogretmenler',
        baslik: 'Öğretmenler',
        ne: 'Öğretmen bilgileri, fotoğraf ve özgeçmiş yüklenir, haftalık azami ders saati belirlenir. Buradan öğretmene giriş hesabı da açılır.',
        neden: 'Haftalık azami saat, sihirbazın bir öğretmeni fazla yüklememesi için kullanılıyor. Hesap açılmadan öğretmen sisteme girip izin talebi oluşturamaz.',
    },
    {
        rota: 'ogretmenler',
        baslik: 'Öğretmen ders yetkileri',
        ne: 'Öğretmen listesindeki "Dersleri" bağlantısından, o öğretmenin hangi dersi hangi seviyelerde verebileceği matris üzerinde işaretlenir.',
        neden: 'Sihirbazın en kritik girdisi bu. Yetki matrisi boşsa sihirbaz hiçbir atama yapamaz ve "0 atama" der — sistemde en sık karşılaşılan takılma noktası budur.',
        vurgu: true,
    },
];
</script>

<template>
    <Layout baslik="Nasıl Kullanılır">
        <div class="py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="lg:flex lg:gap-8">
                    <!-- ───── Yan menü ───── -->
                    <!-- lg:sticky: genis ekranda sayfa kayarken menu
                         yerinde kaliyor. Dar ekranda icerigin ustunde
                         normal bir kutu olarak duruyor. -->
                    <nav class="mb-6 shrink-0 lg:mb-0 lg:w-56 lg:sticky lg:top-6 lg:self-start">
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">
                                İçindekiler
                            </p>
                            <ul class="space-y-1">
                                <li v-for="bolum in bolumler" :key="bolum.id">
                                    <button
                                        type="button"
                                        class="w-full rounded px-2 py-1.5 text-left text-sm transition"
                                        :class="aktifBolum === bolum.id
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                                        @click="git(bolum.id)"
                                    >
                                        {{ bolum.ad }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </nav>

                    <!-- ───── İçerik ───── -->
                    <div class="min-w-0 flex-1 space-y-6">
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <p class="text-sm leading-relaxed text-gray-600">
                                <template v-if="yonetici">
                                    ODPYS, okulun haftalık ders programını otomatik üreten bir sistemdir.
                                    Sistemi ilk kez kuruyorsanız aşağıdaki sırayı takip edin — adımlar
                                    birbirine bağımlıdır, sıra atlanırsa program sihirbazı boş çalışır.
                                </template>
                                <template v-else>
                                    Bu sayfada kendi ekranlarınızı nasıl kullanacağınız anlatılıyor:
                                    haftalık programınızı görmek ve izin talebi oluşturmak.
                                </template>
                            </p>
                        </div>

                        <!-- ═══════════ YÖNETİCİ ═══════════ -->
                        <template v-if="yonetici">
                            <section id="kurulum" class="scroll-mt-6 rounded-lg bg-white p-6 shadow-sm">
                                <h3 class="text-base font-semibold text-gray-800">İlk kurulum sırası</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Her adım bir öncekinin verisini kullanır. Sırayı bozarsanız sonraki adım
                                    çalışacak veri bulamaz.
                                </p>

                                <ol class="mt-6 space-y-5">
                                    <li
                                        v-for="(adim, i) in kurulumAdimlari"
                                        :key="i"
                                        class="flex gap-4"
                                    >
                                        <span
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-medium"
                                            :class="adim.vurgu ? 'bg-amber-500 text-white' : 'bg-gray-800 text-white'"
                                        >
                                            {{ i + 1 }}
                                        </span>

                                        <div class="min-w-0">
                                            <router-link
                                                :to="{ name: adim.rota }"
                                                class="text-sm font-medium text-gray-900 hover:underline"
                                            >
                                                {{ adim.baslik }} &rarr;
                                            </router-link>

                                            <p class="mt-1 text-sm text-gray-600">{{ adim.ne }}</p>

                                            <p
                                                class="mt-1.5 rounded border px-3 py-2 text-xs leading-relaxed"
                                                :class="adim.vurgu
                                                    ? 'border-amber-200 bg-amber-50 text-amber-900'
                                                    : 'border-gray-200 bg-gray-50 text-gray-600'"
                                            >
                                                <strong>Neden bu sırada:</strong> {{ adim.neden }}
                                            </p>
                                        </div>
                                    </li>
                                </ol>
                            </section>

                            <section id="sihirbaz" class="scroll-mt-6 rounded-lg bg-white p-6 shadow-sm">
                                <h3 class="text-base font-semibold text-gray-800">Program sihirbazı</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <router-link :to="{ name: 'program' }" class="text-indigo-600 hover:underline">
                                        Program ekranı &rarr;
                                    </router-link>
                                </p>

                                <div class="mt-5 space-y-4 text-sm text-gray-600">
                                    <div class="rounded border border-gray-200 p-4">
                                        <p class="font-medium text-gray-800">1. adım — Öğretmen ataması</p>
                                        <p class="mt-1">
                                            Her şube-ders çiftine, o dersi o seviyede verebilen öğretmenler arasından
                                            <strong>en az yüklü olanı</strong> atanır. Amaç yükü hakkaniyetli dağıtmak.
                                            Çalıştıktan sonra kaç atama yapıldığını, ne kadar sürdüğünü ve
                                            <strong>atanamayan dersleri sebebiyle birlikte</strong> raporlar.
                                        </p>
                                    </div>

                                    <div class="rounded border border-gray-200 p-4">
                                        <p class="font-medium text-gray-800">2. adım — Gün / saat yerleştirme</p>
                                        <p class="mt-1">
                                            1. adımda belirlenen atamalar, öğretmen ve şube çakışması olmadan
                                            günlere ve ders saatlerine yerleştirilir. Sonuçta kaç ders saatinin
                                            yerleştiği, doluluk oranı ve yerleşemeyenler listelenir.
                                        </p>
                                    </div>

                                    <p class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                        <strong>Dikkat:</strong> 2. adımı tekrar çalıştırmak mevcut programı
                                        <strong>siler ve baştan üretir</strong>. Çizelge üzerinde elle yaptığınız
                                        düzenlemeler de bu sırada kaybolur.
                                    </p>
                                </div>
                            </section>

                            <section id="cizelge" class="scroll-mt-6 rounded-lg bg-white p-6 shadow-sm">
                                <h3 class="text-base font-semibold text-gray-800">Çizelge ve çıktılar</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <router-link :to="{ name: 'cizelge' }" class="text-indigo-600 hover:underline">
                                        Çizelge ekranı &rarr;
                                    </router-link>
                                </p>

                                <ul class="mt-4 space-y-2 text-sm text-gray-600">
                                    <li>
                                        <strong class="text-gray-800">Şube çizelgesi</strong> — bir şubenin haftalık
                                        programı. Her hücrede ders, öğretmen ve saat aralığı görünür.
                                    </li>
                                    <li>
                                        <strong class="text-gray-800">Öğretmen çizelgesi</strong> — bir öğretmenin
                                        haftalık programı ve toplam ders saati.
                                    </li>
                                    <li>
                                        <strong class="text-gray-800">Kayıtlar arası gezinme</strong> — çizelgenin
                                        üstündeki ok düğmeleri ve açılır listeyle, listeye dönmeden bir sonraki
                                        şubeye/öğretmene geçebilirsiniz.
                                    </li>
                                    <li>
                                        <strong class="text-gray-800">Elle düzenleme</strong> — şube çizelgesindeki
                                        hücrelerde "Düzenle" / "+ Ders Ekle" bağlantıları vardır. Sistem kaydetmeden
                                        önce müfredata uygunluk, öğretmen yetkisi ve çakışma kontrolü yapar.
                                    </li>
                                    <li>
                                        <strong class="text-gray-800">PDF / Excel indirme</strong> — her çizelgenin
                                        sağ üstündeki bağlantılardan indirilir.
                                    </li>
                                    <li>
                                        <strong class="text-gray-800">Yük dağılımı</strong> — öğretmenlerin haftalık
                                        saat dağılımını ve kapasite doluluğunu tek tabloda gösterir.
                                    </li>
                                </ul>
                            </section>

                            <section id="izin" class="scroll-mt-6 rounded-lg bg-white p-6 shadow-sm">
                                <h3 class="text-base font-semibold text-gray-800">İzin talepleri</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <router-link :to="{ name: 'izinler' }" class="text-indigo-600 hover:underline">
                                        İzin Talepleri ekranı &rarr;
                                    </router-link>
                                </p>

                                <p class="mt-4 text-sm text-gray-600">
                                    Öğretmenin oluşturduğu talebi açtığınızda, izin aralığına denk gelen
                                    <strong>etkilenen ders saatleri</strong> tarih, gün, saat, şube ve ders olarak
                                    listelenir. Kararı buna bakarak verirsiniz: <strong>Onayla</strong> veya
                                    <strong>Reddet</strong>.
                                </p>

                                <p class="mt-3 rounded border border-gray-200 bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-600">
                                    <strong>Önemli:</strong> İzin onaylandığında haftalık program
                                    <strong>silinmez veya değiştirilmez</strong>. Haftalık program bir şablondur,
                                    izin ise tarihli bir olaydır. İkisi sadece görüntülerken birleşir: o haftanın
                                    çizelgesinde ilgili hücreler "İzinli" olarak işaretlenir. Yerine öğretmen
                                    atama kararı otomatik verilmez, sizde kalır.
                                </p>
                            </section>

                            <section id="ucret" class="scroll-mt-6 rounded-lg bg-white p-6 shadow-sm">
                                <h3 class="text-base font-semibold text-gray-800">Aylık ücret raporu</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <router-link :to="{ name: 'ucretler' }" class="text-indigo-600 hover:underline">
                                        Ücretler ekranı &rarr;
                                    </router-link>
                                </p>

                                <p class="mt-4 text-sm text-gray-600">
                                    Her öğretmenin üretilmiş programdaki haftalık ders saatleri, dersin saatlik
                                    ücretiyle çarpılır ve 4 hafta üzerinden aylık tutara çevrilir. Tabloda ders
                                    bazlı kırılım da görünür (hangi dersten kaç saat, hangi birim ücretle).
                                </p>

                                <p class="mt-3 rounded border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">
                                    Bu ekran <strong>yalnızca yöneticiye</strong> açıktır; öğretmenler kendi
                                    panellerinde ücret bilgisi görmez.
                                </p>
                            </section>

                            <section id="okullar" class="scroll-mt-6 rounded-lg bg-white p-6 shadow-sm">
                                <h3 class="text-base font-semibold text-gray-800">Çoklu okul ve aktif okul</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <router-link :to="{ name: 'okullar' }" class="text-indigo-600 hover:underline">
                                        Okullar ekranı &rarr;
                                    </router-link>
                                </p>

                                <p class="mt-4 text-sm text-gray-600">
                                    Sistemde birden fazla okul tanımlanabilir. Her an <strong>bir tanesi
                                    "aktif okul"</strong>dur ve bütün ekranlar (seviyeler, şubeler, öğretmenler,
                                    program, çizelge, ücretler) yalnızca aktif okulun verisini gösterir.
                                    Okullar ekranından "Aktif Yap" ile geçiş yaparsınız. Yeni eklenen okul
                                    otomatik olarak aktif olur.
                                </p>

                                <p class="mt-3 rounded border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">
                                    Yeni bir okul ekledikten sonra kurulum sırasını o okul için baştan
                                    uygulamanız gerekir — seviyeler, derslikler ve öğretmenler okullar arasında
                                    paylaşılmaz.
                                </p>
                            </section>
                        </template>

                        <!-- ═══════════ ÖĞRETMEN ═══════════ -->
                        <template v-else>
                            <section id="ogretmen-panel" class="scroll-mt-6 rounded-lg bg-white p-6 shadow-sm">
                                <h3 class="text-base font-semibold text-gray-800">Panelim</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <router-link :to="{ name: 'ogretmen-panel' }" class="text-indigo-600 hover:underline">
                                        Panelim ekranı &rarr;
                                    </router-link>
                                </p>

                                <p class="mt-4 text-sm text-gray-600">
                                    Haftalık ders programınızı, branşınızı ve toplam ders saatinizi gösterir.
                                    Programdaki her hücrede dersin adı, şube ve saat aralığı yer alır.
                                    Onaylanmış bir izniniz varsa, o saatler programda
                                    <strong>"İzinli"</strong> olarak işaretlenir.
                                </p>
                            </section>

                            <section id="ogretmen-izin" class="scroll-mt-6 rounded-lg bg-white p-6 shadow-sm">
                                <h3 class="text-base font-semibold text-gray-800">İzin talebi oluşturma</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <router-link :to="{ name: 'ogretmen-izinler' }" class="text-indigo-600 hover:underline">
                                        İzinlerim ekranı &rarr;
                                    </router-link>
                                </p>

                                <ol class="mt-4 space-y-2 text-sm text-gray-600">
                                    <li>1. "İzinlerim" ekranından yeni talep oluşturun.</li>
                                    <li>2. İzin <strong>başlangıç</strong> ve <strong>bitiş</strong> tarih-saatini seçin.</li>
                                    <li>3. İsterseniz bir gerekçe yazın (zorunlu değil).</li>
                                    <li>4. Talebi gönderin; durumu "beklemede" olarak listede görünür.</li>
                                </ol>

                                <p class="mt-3 rounded border border-gray-200 bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-600">
                                    Yönetici talebi değerlendirirken, izin aralığınıza denk gelen ders saatlerini
                                    görür. Talebiniz onaylandığında programınız silinmez; o haftaki ilgili ders
                                    saatleri "İzinli" olarak işaretlenir.
                                </p>
                            </section>
                        </template>

                        <!-- ═══════════ ORTAK ═══════════ -->
                        <section id="profil" class="scroll-mt-6 rounded-lg bg-white p-6 shadow-sm">
                            <h3 class="text-base font-semibold text-gray-800">Profil ve şifre</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                <router-link :to="{ name: 'profil' }" class="text-indigo-600 hover:underline">
                                    Profil ekranı &rarr;
                                </router-link>
                            </p>

                            <p class="mt-4 text-sm text-gray-600">
                                Sağ üstteki adınıza tıklayarak açılır. Adınızı ve giriş e-postanızı
                                güncelleyebilir, şifrenizi değiştirebilirsiniz. Şifre değiştirmek için
                                güvenlik gereği önce <strong>mevcut şifrenizi</strong> girmeniz gerekir.
                            </p>

                            <p class="mt-3 text-sm text-gray-600">
                                Şifrenizi unuttuysanız giriş ekranındaki <strong>"Şifremi unuttum"</strong>
                                bağlantısını kullanın; e-posta adresinize sıfırlama bağlantısı gönderilir.
                            </p>
                        </section>

                        <!-- ═══════════ SSS (sadece yönetici) ═══════════ -->
                        <section
                            v-if="yonetici"
                            id="sss"
                            class="scroll-mt-6 rounded-lg bg-white p-6 shadow-sm"
                        >
                            <h3 class="text-base font-semibold text-gray-800">Sık karşılaşılan durumlar</h3>

                            <dl class="mt-4 space-y-4 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-800">
                                        Sihirbazın 1. adımı "0 atama" diyor.
                                    </dt>
                                    <dd class="mt-1 text-gray-600">
                                        Neredeyse her zaman <strong>öğretmen ders yetkileri</strong> boştur.
                                        Öğretmenler ekranından her öğretmenin "Dersleri" bağlantısına girip,
                                        hangi dersi hangi seviyelerde verebileceğini işaretleyin.
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-medium text-gray-800">
                                        "Dersliksiz şubeler var" uyarısı çıkıyor.
                                    </dt>
                                    <dd class="mt-1 text-gray-600">
                                        Uyarıda adı geçen şubelere Şubeler ekranından derslik atayın. Dersliksiz
                                        şubeler programa hiç dahil edilmez.
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-medium text-gray-800">
                                        "Bu kayıt aktif okulda bulunamadı" hatası alıyorum.
                                    </dt>
                                    <dd class="mt-1 text-gray-600">
                                        Açmaya çalıştığınız şube/öğretmen başka bir okula ait. Okullar ekranından
                                        aktif okulu değiştirin. Kaydedilmiş eski bir bağlantıyı açtığınızda sık
                                        karşılaşılır.
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-medium text-gray-800">
                                        Bazı dersler yerleştirilemedi.
                                    </dt>
                                    <dd class="mt-1 text-gray-600">
                                        2. adımın raporundaki "sebep" sütununa bakın. En yaygın sebepler:
                                        şubenin haftalık müfredatı boş slot sayısını aşıyor, ya da öğretmenin
                                        uygun olduğu bütün saatler dolmuş. Okul ayarlarından günlük ders sayısını
                                        artırmak veya müfredat saatlerini gözden geçirmek çözer.
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-medium text-gray-800">
                                        Öğretmeni silemiyorum, "ders atamaları var" diyor.
                                    </dt>
                                    <dd class="mt-1 text-gray-600">
                                        Üretilmiş programda dersi olan bir öğretmen silinemez — aksi halde
                                        program sessizce bozulurdu. İki seçeneğiniz var: atamaları kaldırmak
                                        (sihirbazı yeniden çalıştırmak da atamaları yeniler), ya da öğretmeni
                                        <strong>pasife almak</strong>. Ayrılan bir öğretmen için genellikle
                                        doğru olan pasife almaktır: geçmiş kayıtlar ve ücret geçmişi korunur,
                                        öğretmen yeni programlara dahil edilmez.
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-medium text-gray-800">
                                        Ders süresini değiştirdim, çizelgedeki saatler de değişti.
                                    </dt>
                                    <dd class="mt-1 text-gray-600">
                                        Beklenen davranış. Programda ders saatleri değil <strong>slot
                                        numaraları</strong> saklanıyor; gerçek saatler okul ayarlarından
                                        hesaplanıyor. Bu yüzden ayar değişince bütün çizelge kendiliğinden kayar,
                                        programı yeniden üretmeniz gerekmez.
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
