# Faz 4 + Faz 5 + Faz 6: Ekranların SPA'ya Taşınması, Dosya Yükleme ve Paralel Geçiş — Eğitim + Uygulama

Bu doküman üç fazı birlikte anlatıyor:
- **Faz 4** — planın en büyük fazı: uygulama ekranlarının Vue sayfalarına taşınması ve ortak bir düzen (layout) bileşeniyle birleştirilmesi.
- **Faz 5** — öğretmen fotoğrafı ve CV gibi dosyaların SPA üzerinden yüklenmesi ve gösterilmesi.
- **Faz 6** — geçişin aktifleştirilmesi: SPA'nın varsayılan arayüz olması, eski sistemin doğrulama ve geri dönüş amacıyla yedekte kalması.

Kullanılan her kavramı sıfırdan açıklıyor, sonra her ekranın nasıl yazıldığını dosya yollarıyla gösteriyor.

> **Faz 5 neden ayrı bir ana bölüm gibi ilerlemiyor?** Plandaki "dosya yükleme" işi (öğretmen fotoğraf/CV), Faz 4'te `Ogretmenler.vue` yazılırken aynı form akışının içinde uygulandı. Yine de ayrı bir fazın sorumluluğudur; kodu ve sonradan bulunan gösterim eksiğinin düzeltilmesi bu dokümanın **5. bölümünde** ayrıntılı biçimde anlatılıyor.

---

## 1. Faz 4 Neyi Kapsıyor?

Faz 0-3'te **temel** kurulmuştu: API hazır, kimlik doğrulama çalışıyor, router/state/HTTP altyapısı ayakta. Ama gerçek ekranlar (Seviyeler, Dersler, Program, Çizelge...) hâlâ sadece eski Blade tarafında vardı.

Faz 4, uygulama ekranlarını Vue'ya taşıdı. İlk dönüşümde temel ilke, karşılığı olan Blade dosyasının HTML/Tailwind yapısını referans almak ve davranış eşliğini korumaktı. Daha sonra gerçek kullanımda bulunan ihtiyaçlar için giriş tasarımı, profil ve kayıtlar arası gezinme gibi iyileştirmeler yapıldı; bunlar 12–15. bölümlerde ayrıca anlatılıyor. Temel veri akışı değişimi şöyledir:

```
ESKİ (Blade):  Controller → view('seviyeler.index', ['seviyeler' => ...]) → sunucuda HTML üretilir
YENİ (Vue):    Sayfa açılır → api.get('/seviyeler') → JSON gelir → tarayıcıda HTML üretilir
```

---

## 2. Ortak Düzen: `Layout.vue`

Blade'de her sayfa `<x-app-layout>` ile sarılıydı — üstte nav menüsü, altta içerik. Vue'da bunun karşılığı bir **bileşen** ve `<slot>`.

**Dosya: `resources/js/components/Layout.vue`**

```vue
<script setup>
defineProps({
    baslik: { type: String, default: '' },
});

const auth = useAuthStore();

const yoneticiMenu = [
    { ad: 'Dashboard', rota: 'dashboard' },
    { ad: 'Okul Ayarları', rota: 'okul-ayarlari' },
    // ... navigation.blade.php'deki linklerin aynısı
];

const ogretmenMenu = [
    { ad: 'Panelim', rota: 'ogretmen-panel' },
    { ad: 'İzinlerim', rota: 'ogretmen-izinler' },
];
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav>
            <router-link
                v-for="oge in (auth.rol === 'admin' ? yoneticiMenu : ogretmenMenu)"
                :key="oge.rota"
                :to="{ name: oge.rota }"
                :class="$route.name === oge.rota ? 'border-indigo-400 ...' : 'border-transparent ...'"
            >
                {{ oge.ad }}
            </router-link>
        </nav>

        <header v-if="baslik"><h2>{{ baslik }}</h2></header>

        <main>
            <slot />
        </main>
    </div>
</template>
```

**Kavramlar:**

- **`<slot />`** — Blade'deki `{{ $slot }}` ile birebir aynı fikir: "bu bileşeni kullanan sayfa, içeriğini buraya koyacak". Her sayfa şöyle yazılıyor:
  ```vue
  <Layout baslik="Seviyeler">
      <div>...sayfanın kendi içeriği...</div>
  </Layout>
  ```
- **`defineProps`** — bileşenin dışarıdan alacağı değerleri tanımlar. `baslik` burada bir prop, Blade'in `<x-slot name="header">` yapısının karşılığı.
- **`v-for` + `:key`** — Blade'deki `@foreach`'in Vue karşılığı. `:key`, özellikle sırası değişebilen veya kendi form/DOM durumunu taşıyan listelerde Vue'nun öğe kimliğini doğru koruması için gereklidir. Basit listeler bazı durumlarda `key` olmadan da çizilebilir; fakat kararlı ve tahmin edilebilir güncelleme için benzersiz bir `key` kullanmak doğru pratiktir.
- **`$route.name === oge.rota`** — aktif menü öğesini vurgulamak için. Blade'de `request()->routeIs('seviyeler.*')` ile yapılan şeyin aynısı.
- **`auth.rol === 'admin' ? ... : ...`** — rol bazlı menü, Blade'deki `@if (auth()->user()->role === 'admin')` bloğunun karşılığı.

---

## 3. Bir Sayfanın Anatomisi — `Seviyeler.vue` Örneği

En basit CRUD sayfası. Diğer bütün liste ekranları (Derslikler, Şubeler, Dersler...) **aynı kalıbı** izliyor, o yüzden bunu anlarsan hepsini anlarsın.

**Dosya: `resources/js/pages/Seviyeler.vue`**

```vue
<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const seviyeler = ref([]);
const yukleniyor = ref(true);
const mesaj = ref('');
const hata = ref('');

const formAcik = ref(false);
const duzenlenenId = ref(null);
const gradeNumber = ref('');

async function yukle() {
    const { data } = await api.get('/seviyeler');
    seviyeler.value = data;
    yukleniyor.value = false;
}

function formAc(seviye = null) {
    duzenlenenId.value = seviye?.id ?? null;
    gradeNumber.value = seviye?.grade_number ?? '';
    formAcik.value = true;
}

async function kaydet() {
    try {
        if (duzenlenenId.value) {
            await api.put(`/seviyeler/${duzenlenenId.value}`, { grade_number: gradeNumber.value });
        } else {
            await api.post('/seviyeler', { grade_number: gradeNumber.value });
        }

        formAcik.value = false;
        await yukle();
    } catch (e) {
        hata.value = e.response?.data?.errors?.grade_number?.[0] ?? 'Bir hata oluştu.';
    }
}

onMounted(yukle);
</script>
```

**Kavramlar (her sayfada tekrar edecek):**

- **`ref()`** — Vue'da "değişince ekranı güncelle" özelliği olan bir değişken. Normal bir JS değişkeni değiştiğinde ekran güncellenmez; `ref` ile sarılınca Vue değişikliği fark eder ve o değişkeni kullanan her yeri otomatik yeniden çizer. `<script>` içinde değerine `.value` ile erişilir (`seviyeler.value`), `<template>` içinde ise doğrudan (`seviyeler`) — Vue bunu otomatik açar.
- **`onMounted(yukle)`** — "bileşen ekrana yerleştiği anda şu fonksiyonu çalıştır". Sayfa açılır açılmaz veriyi çeken kanca (lifecycle hook) budur.
- **`async/await`** — API çağrıları anında sonuçlanmaz (ağ gecikmesi). `await`, yalnızca içinde bulunduğu `async` fonksiyonun devamını Promise sonuçlanana kadar erteler; tarayıcının arayüz iş parçacığını kilitleyip bütün sayfayı bekletmez. `async`, fonksiyonun Promise döndürdüğünü ve içinde `await` kullanılabildiğini belirtir.
- **`const { data } = await api.get(...)`** — bu bir "destructuring": axios'un döndürdüğü cevap nesnesinin içinden sadece `data` alanını çıkarıyor. Cevapta ayrıca `status`, `headers` gibi alanlar da var ama bize sadece veri lazım.
- **`try/catch`** — hata yakalama. API 422 (doğrulama hatası) dönerse axios bir "hata" fırlatır, `catch` bloğu onu yakalayıp kullanıcıya gösteriyor.
- **`seviye?.id ?? null`** — iki güvenlik operatörü: `?.` ("seviye null ise patlama, undefined dön"), `??` ("soldaki null/undefined ise sağdakini kullan"). Blade'deki `$seviye?->id ?? null` ile birebir aynı mantık.

### Blade'den önemli bir sapma: ayrı create/edit sayfası yok

Blade'de `/seviyeler/create` ve `/seviyeler/{id}/edit` diye **ayrı sayfalar** vardı (her biri sunucudan tam sayfa yükleniyordu). SPA'da bu gereksiz — aynı sayfada açılıp kapanan bir form yeterli:

```vue
<div v-if="formAcik" class="bg-white ...">
    <h3>{{ duzenlenenId ? 'Seviyeyi Düzenle' : 'Yeni Seviye' }}</h3>
    <input v-model="gradeNumber" type="number" min="9" max="12">
    <button @click="kaydet">Kaydet</button>
</div>
```

- **`v-if`** — koşullu gösterim (Blade'deki `@if`). Koşul yanlışsa element DOM'a hiç eklenmez.
- **`v-model`** — **iki yönlü bağlama**: input'a yazılan değer otomatik `gradeNumber`'a gider, `gradeNumber` koddan değişirse input da güncellenir. Blade'de bunun karşılığı yoktu (form gönderilene kadar sunucu değeri bilmezdi) — Vue'nun en büyük kolaylıklarından biri.
- **`@click="kaydet"`** — olay dinleyici (Blade'de `onsubmit` / form action'ın karşılığı).

---

## 4. Formların Backend'in Beklediği Şekle Çevrilmesi

Bazı ekranlarda backend, Blade formunun ürettiği **iç içe dizi** yapısını bekliyor. Vue'da bu yapıyı elle kurmak gerekiyor.

### Dersler: seviye-saat matrisi

**Dosya: `resources/js/pages/Dersler.vue`**

```js
// Backend CourseRequest'in bekledigi sekil:
// seviyeler[levelId][secili] ve [weekly_hours]
const seviyelerVerisi = {};
satirlar.value.forEach((s) => {
    seviyelerVerisi[s.id] = {
        secili: s.secili ? 1 : 0,
        weekly_hours: s.saat || null,
    };
});

await api.post('/dersler', {
    name: ad.value,
    hourly_rate: saatlikUcret.value,
    block_ders: blockDers.value ? 1 : 0,
    seviyeler: seviyelerVerisi,
});
```

Blade formunda bu yapı `name="seviyeler[3][weekly_hours]"` gibi input isimleriyle kuruluyordu; JSON API'de aynı yapıyı JavaScript nesnesi olarak kuruyoruz. **`CourseRequest` doğrulama sınıfına hiç dokunulmadı** — aynı veriyi aldığı için aynen çalışıyor.

### Öğretmen ders yetkileri: matris

**Dosya: `resources/js/pages/OgretmenDersleri.vue`**

```js
// Yetkiler "dersId-seviyeId" anahtarli duz bir nesnede tutuluyor -
// matriste her kutu icin O(1) arama saglıyor.
function anahtar(dersId, seviyeId) {
    return `${dersId}-${seviyeId}`;
}

// Gonderirken backend'in bekledigi sekle ceviriyoruz:
// yetkiler[dersId][seviyeId] = 1
const yetkiler = {};
Object.entries(secili.value).forEach(([k, v]) => {
    if (! v) return;
    const [dersId, seviyeId] = k.split('-');
    yetkiler[dersId] = yetkiler[dersId] ?? {};
    yetkiler[dersId][seviyeId] = 1;
});
```

Bu, eski `OgretmenDersleriFormu.vue` bileşenindeki mantığın aynısı — o da aynı düz anahtar yapısını kullanıyordu, sadece sonucu gizli input'lara yazıyordu, biz JSON'a yazıyoruz.

---

## 5. Dosya Yükleme: `FormData` ve Method Spoofing

Öğretmen formunda fotoğraf ve CV yükleniyor. Bir `File` nesnesi normal JSON gövdesine doğrudan dosya içeriği olarak eklenmez; metin alanlarıyla ikili dosyaları aynı istekte taşımak için `FormData` ve `multipart/form-data` kullanılır.

**Dosya: `resources/js/pages/Ogretmenler.vue`**

```js
const fd = new FormData();
Object.entries(form.value).forEach(([anahtar, deger]) => {
    fd.append(anahtar, anahtar === 'is_active' ? (deger ? 1 : 0) : deger);
});

if (fotografDosya.value) fd.append('photo', fotografDosya.value);
if (cvDosya.value) fd.append('cv', cvDosya.value);

if (duzenlenenId.value) {
    fd.append('_method', 'PUT');
    await api.post(`/ogretmenler/${duzenlenenId.value}`, fd);   // POST + _method=PUT
} else {
    await api.post('/ogretmenler', fd);
}
```

**Neden `POST` + `_method=PUT`, doğrudan `PUT` değil?** `fetch` ve axios teknik olarak `PUT` isteğiyle `FormData` gönderebilir. Sorun HTTP metodunun bunu yasaklaması değil; PHP/Laravel tarafında `multipart/form-data` gövdelerinin `POST` dışındaki metodlarda ayrıştırılması, kullanılan sunucu ve PHP istek işleme hattına göre sorun çıkarabilir. Laravel projelerinde güvenilir ve yaygın çözüm **method spoofing** kullanmaktır: istek `POST` gönderilir, gövdeye `_method=PUT` eklenir ve Laravel bunu uygulama seviyesinde `PUT` olarak işler. Blade formlarındaki `@method('PUT')` direktifi de aynı gizli alanı üretir.

**`Content-Type` neden kodda elle yazılmıyor?** `FormData` gönderilirken axios/tarayıcı `multipart/form-data` başlığını ve zorunlu `boundary` değerini birlikte üretir. Başlığı elle yalnızca `multipart/form-data` diye yazmak, `boundary` bilgisini bozabileceği için gereksiz ve risklidir.

### Sonradan fark edilen eksik: yüklenen dosyanın gösterilmesi

İlk yazımda yükleme çalışıyordu ama **yüklenen fotoğraf hiçbir yerde görünmüyordu** — dosya sunucuya gidiyordu, listede ve formda gösterim kısmı atlanmıştı. Bu, gerçek kullanımda (senin denemenle) ortaya çıktı ve düzeltildi. Düzeltirken Blade'i birebir kopyalamak yerine iki noktada daha iyisi yapıldı:

**1) URL üretimi modele taşındı — `app/Models/Teacher.php`**

```php
/**
 * Bu iki alan veritabaninda YOK - photo_path/cv_path'ten
 * turetiliyor ama her JSON cevabina otomatik ekleniyor.
 */
protected $appends = ['photo_url', 'cv_url'];

public function getPhotoUrlAttribute(): ?string
{
    return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
}

public function getCvUrlAttribute(): ?string
{
    return $this->cv_path ? Storage::disk('public')->url($this->cv_path) : null;
}
```

**Kavramlar:**
- **Accessor** (`getXxxAttribute`) — veritabanında olmayan, ama modelden okunabilen "türetilmiş" alan. `$ogretmen->photo_url` yazınca Laravel bu metodu çağırıyor.
- **`$appends`** — accessor'ları JSON'a da ekler. Bu olmadan `photo_url` sadece PHP tarafında erişilebilir olurdu, API cevabında görünmezdi.
- **Neden model'de?** Blade tarafında her gösterim yerinde ayrı ayrı `Storage::url(...)` çağrılıyordu (listede, formda, düzenleme ekranında). Model kendi verisinin nasıl adreslendiğini bilirse, tüketen taraf (Vue, API, ileride mobil app) hazır URL alır — "dosya yolundan URL nasıl üretilir" bilgisi tek yerde durur.
- Fotoğrafı olmayan öğretmende `null` dönüyor, şablon da ona göre baş harfleri gösteriyor.

**Not — `storage:link`:** Yüklenen dosyalar `storage/app/public/` altında durur ama web'den erişilebilen dizin `public/`'tir. `php artisan storage:link` komutu ikisi arasında bir sembolik bağlantı kurar (`public/storage → storage/app/public`). Bu bağlantı olmadan `Storage::url()` doğru adresi üretir ama dosya 404 verir. Bu projede bağlantı zaten kuruluydu.

**2) Formda anlık önizleme — `resources/js/pages/Ogretmenler.vue`**

Blade'de sadece **kayıtlı** dosya görünüyordu; seçtiğin dosyayı ancak kaydettikten sonra görebiliyordun. SPA'da seçer seçmez gösteriyoruz:

```js
// Yeni secilen fotografin ANLIK onizlemesi - dosya henuz sunucuya
// gitmeden tarayicida gosteriliyor.
const yeniFotografOnizleme = ref(null);

function fotografSecildi(olay) {
    const dosya = olay.target.files[0] ?? null;
    fotografDosya.value = dosya;

    // Onceki gecici adresi serbest birak - yoksa sayfa acik kaldikca
    // her secimde bellekte bir kopya birikir.
    if (yeniFotografOnizleme.value) {
        URL.revokeObjectURL(yeniFotografOnizleme.value);
    }

    yeniFotografOnizleme.value = dosya ? URL.createObjectURL(dosya) : null;
}
```

- **`URL.createObjectURL(dosya)`** — tarayıcının, seçilen dosya için ürettiği geçici yerel adres (`blob:http://...`). Sunucuya hiç gitmeden `<img src>` içinde kullanılabiliyor.
- **`URL.revokeObjectURL(...)`** — nesne URL'si artık kullanılmayınca tarayıcıya ilişkili kaynağın serbest bırakılabileceğini bildirir. Belge tamamen kapandığında tarayıcı bu kaynakları temizler; ancak uzun süre açık kalan bir SPA'da kullanıcı art arda dosya seçerse eski URL'leri erken serbest bırakmak gereksiz bellek kullanımını önler. Buradaki kod, yeni seçim yapılırken önceki önizleme URL'sini serbest bırakıyor.

Şablon tarafında: yeni seçim varsa **onu**, yoksa sunucudaki mevcut fotoğrafı gösteriyor:

```vue
<img v-if="yeniFotografOnizleme || mevcutFotografUrl"
     :src="yeniFotografOnizleme ?? mevcutFotografUrl"
     class="h-16 w-16 rounded object-cover border border-gray-200">

<span v-if="yeniFotografOnizleme" class="text-xs text-green-700">
    Yeni seçildi — kaydedilince değişecek
</span>
```

**3) Listede avatar**

```vue
<img v-if="o.photo_url" :src="o.photo_url" class="h-9 w-9 rounded-full object-cover shrink-0">
<div v-else class="h-9 w-9 rounded-full bg-gray-200 flex items-center justify-center text-xs ...">
    {{ basHarfler(o) }}
</div>
```

Fotoğrafı olmayan için baş harfler (Blade'deki desen). CV linki ise **ayrı bir sütun açmak yerine** adın altına küçük bir "Özgeçmiş (PDF)" bağlantısı olarak kondu — tablo zaten 6 sütundu, genişletmemek için.

**Doğrulama:** Tarayıcıda `/app/ogretmenler` açıldı; 2 fotoğraflı avatar (`naturalWidth > 0` — yani resim gerçekten yüklendi, kırık ikon değil), 18 baş harfli avatar, 2 CV linki bulundu. Düzenleme formunda mevcut fotoğraf önizlemesi yüklendi ve "Mevcut özgeçmişi görüntüle" linki çalıştı.

---

## 6. En Karmaşık Ekran: Çizelge Izgarası

**Dosya: `resources/js/pages/CizelgeIzgara.vue`**

Bu tek bileşen **iki rotayı** birden karşılıyor (şube çizelgesi ve öğretmen çizelgesi) — tıpkı Blade'de tek `izgara.blade.php`'nin iki ekranı basması gibi.

```js
// 'sube' ya da 'ogretmen' - rota adindan cikariyoruz.
// computed kullanildigi icin rota degisince deger de otomatik yenilenir.
const tur = computed(() => (route.name === 'cizelge-sube' ? 'sube' : 'ogretmen'));

async function yukle() {
    const { data } = await api.get(`/cizelge/${tur.value}/${route.params.id}`);
    veri.value = data;
}

// Ayni bilesende sube 9A -> 9B gecisi olursa (rota parametresi degisir
// ama bilesen yeniden olusturulmaz) veriyi tekrar cekmemiz gerekiyor.
watch(() => [route.name, route.params.id], yukle);

onMounted(yukle);
```

**`watch` neden gerekli — ince ama önemli bir Vue davranışı:** Kullanıcı 9A çizelgesindeyken 9B'ye tıklarsa, Vue Router **aynı bileşeni** yeniden kullanır (sadece parametre değişir), bileşen yeniden **oluşturulmaz**. Yani `onMounted` bir daha çalışmaz! Veri eski kalırdı. `watch`, "şu değerler değişirse şu fonksiyonu çalıştır" diyerek bunu çözüyor.

Izgara çizimi, Blade'deki iç içe `@foreach` yapısının aynısı:

```vue
<tr v-for="slot in veri.slotlar" :key="slot">
    <td>{{ slot }}. ders</td>

    <td v-for="(gunAdi, gunNo) in veri.gunler" :key="gunNo">
        <div v-if="veri.izgara[gunNo]?.[slot]"
             :class="veri.izgara[gunNo][slot].izinli
                ? 'border-amber-300 bg-amber-50'
                : 'border-gray-200 bg-gray-50'">
            <!-- izinli ise "İzinli" etiketi, degilse ders adi -->
        </div>
        <div v-else>boş</div>
    </td>
</tr>
```

**PDF/Excel indirme linkleri** — burada özel bir şey yapmaya gerek yok:

```vue
<a :href="`/cizelge/${tur}/${route.params.id}/pdf`">PDF indir</a>
```

Çünkü SPA **çerez** tabanlı kimlik doğrulama kullanıyor (Faz 2): tarayıcı bu düz linke gittiğinde oturum çerezini otomatik gönderiyor, mevcut web rotası çalışıyor. (Token tabanlı bir mimaride bu bir sorun olurdu — token linkte taşınamazdı.)

---

## 7. Router: Güncel Rotalar ve Rol Koruması

Bu bölüm ilk yazıldığında router'da 24 rota vardı. Profil, şifre sıfırlama, yük raporu ve sonraki ekranlar eklendikçe sayı değişti; güncel `resources/js/router.js` dosyasında **27 rota kaydı** bulunuyor. Burada önemli olan sabit sayı değil, her rotanın doğru bileşene ve gereken `meta.auth` / `meta.role` bilgisine bağlanmasıdır.

**Dosya: `resources/js/router.js`**

```js
{
    path: '/app/seviyeler',
    name: 'seviyeler',
    component: () => import('./pages/Seviyeler.vue'),
    meta: { auth: true, role: 'admin' },
},
```

`meta.role`, Faz 3'te altyapısını hazırladığımız alan — artık gerçekten kullanılıyor. Guard:

```js
if (to.meta.role && auth.rol !== to.meta.role) {
    return { name: auth.rol === 'teacher' ? 'ogretmen-panel' : 'dashboard' };
}
```

### Yol boyunca düzeltilen bir hata: `redirect` guard'dan önce çalışır

`/app` adresine gelen kullanıcıyı rolüne göre yönlendirmek istedim. İlk yazdığım şey şuydu:

```js
{ path: '/app', redirect: () => ({ name: 'dashboard' }) }   // YANLIŞ
```

**Sorun:** Vue Router'da `redirect`, navigation guard'lardan **önce** çalışıyor. O anda `auth.kontrolEt()` henüz çağrılmamış — yani kullanıcının rolünü bilmiyoruz, öğretmeni de dashboard'a yollardı. Çözüm: `redirect` yerine boş bir bileşen koyup yönlendirmeyi guard'a bırakmak (rol o noktada artık belli):

```js
{ path: '/app', name: 'anasayfa', component: () => import('./pages/Anasayfa.vue'), meta: { auth: true } }

// guard içinde:
if (to.name === 'anasayfa') {
    return { name: auth.rol === 'teacher' ? 'ogretmen-panel' : 'dashboard' };
}
```

---

## 8. Bulunan ve Düzeltilen Gerçek Bir Yapılandırma Hatası

Test sırasında öğretmen girişi **başarılı** oluyordu ama hemen ardından `/api/user` yine `401` dönüyordu — yani oturum kurulmuş ama API tanımıyordu.

**Sebep:** `config/sanctum.php`'deki "stateful domains" listesi, `SANCTUM_STATEFUL_DOMAINS` tanımlı olmadığı için varsayılana düşüyordu: `localhost, localhost:3000, 127.0.0.1, 127.0.0.1:8000, ::1` + `APP_URL`'den gelen `localhost:8000`. Bu listede **olmayan** bir adresten gelen istek, Sanctum tarafından "birinci taraf SPA" sayılmıyor → çerez kontrol edilmiyor → 401.

Benim test tünelim `localhost:18002` üzerindeydi — listede yok. Ama daha önemlisi: **senin yedek erişim adresin `192.168.0.92:8000` de listede yoktu.** Yani SPA'ya oradan girseydin giriş yapamayacaktın.

**Düzeltme — `.env`:**

```
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1,127.0.0.1:8000,192.168.0.92:8000,::1
```

Ardından `php artisan config:clear`. Bu, SPA'ya hangi adreslerden erişilebileceğini belirleyen kritik bir ayar — ileride yeni bir adres (gerçek alan adı vb.) eklenirse buraya da eklenmesi gerekiyor.

---

## 9. Uçtan Uca Doğrulama

Aşağıdaki sonuçlar, geliştirme sırasında gerçek tarayıcı ve o tarihteki gerçek test verisiyle yapılan doğrulamanın kaydıdır. İsimler ve sayılar kalıcı sistem sabitleri değil, test anındaki veri kümesinin sonuçlarıdır:

| Test | Sonuç |
|---|---|
| Öğretmen girişi | Kendi paneli açıldı: "Derin Köybaşı", branş Matematik, 18/30 saat |
| Öğretmen programı | Izgara gerçek verilerle doldu (Matematik, şube adları, saatler) |
| Rol bazlı menü | Öğretmende sadece "Panelim / İzinlerim" göründü, yönetici linkleri yok |
| Çıkış | Çalıştı, giriş seçim ekranına döndü |
| Yönetici girişi | Dashboard açıldı: 4 seviye, 13 şube, 13 derslik, 10 ders, 32 öğretmen, 416 ders saati |
| Seviyeler | Tablo doğru (9./10./11./12. Sınıf, şube sayıları 4/3/3/3) |
| Ücretler | 832.000 TL toplam, Ahmet Ayverdi 32.000 TL/ay — API testindeki rakamlarla birebir aynı |
| Çizelge ızgarası | 9A şubesi, 32 ders saati, "Düzenle" linkleri, PDF/Excel bağlantıları |
| 13 admin API uç noktası | Hepsi 200 |
| 40 sınıf / 100 öğretmen performans senaryosu | Test çalıştırıldı; ölçülen kesin süre bu dokümana kaydedilmediği için burada uydurma bir milisaniye değeri verilmiyor |
| `npm run build` | O testteki kod durumunda 21 lazy-loaded sayfa paketi derlendi; sonraki eklemeler nedeniyle güncel kaynak dosyası sayısı daha yüksektir |

**Test yöntemi notu:** Yönetici şifresini bilmediğim için, test öncesi admin'in şifre hash'ini bir dosyaya yedekleyip geçici bir şifre atadım; test bitince **orijinal hash'i aynen geri yazdım** ve geri yüklendiğini doğruladım. Yani hesabın şifresi değişmedi.

---

## 10. Özet Tablo — Ne Nerede

| Ekran | Dosya | Karşılığı olan Blade |
|---|---|---|
| Ortak düzen/menü | `components/Layout.vue` | `layouts/app` + `layouts/navigation` |
| Dashboard | `pages/Dashboard.vue` | `dashboard.blade.php` |
| Okul ayarları | `pages/OkulAyarlari.vue` | `schools/index` + `schools/edit` |
| Okullar (çoklu okul) | `pages/Okullar.vue` | `schools/okullar` + `schools/ekle` |
| Seviyeler | `pages/Seviyeler.vue` | `levels/*` |
| Şubeler | `pages/Subeler.vue` | `sections/*` |
| Derslikler | `pages/Derslikler.vue` | `rooms/*` |
| Dersler | `pages/Dersler.vue` | `courses/*` |
| Öğretmenler | `pages/Ogretmenler.vue` | `teachers/*` |
| Öğretmen ders yetkileri | `pages/OgretmenDersleri.vue` | `teachers/courses` |
| Program sihirbazı | `pages/Program.vue` | `program/index` |
| Hücre düzenleme | `pages/ProgramHucre.vue` | `program/hucre-duzenle` |
| Çizelge ana ekran | `pages/Cizelge.vue` | `cizelge/index` |
| Çizelge ızgarası | `pages/CizelgeIzgara.vue` | `cizelge/izgara` |
| Yük dağılımı | `pages/CizelgeYuk.vue` | `cizelge/yuk` |
| Ücretler | `pages/Ucretler.vue` | `ucret/index` |
| İzin talepleri (yönetici) | `pages/Izinler.vue` | `izinler/index` |
| İzin detay/onay | `pages/IzinDetay.vue` | `izinler/goster` |
| Öğretmen paneli | `pages/OgretmenPanel.vue` | `ogretmen/panel` |
| Öğretmen izinleri | `pages/OgretmenIzinler.vue` | `ogretmen/izinler/index` |
| İzin oluşturma | `pages/IzinOlustur.vue` | `ogretmen/izinler/create` |
| Profil (ad/e-posta, şifre) | `pages/Profil.vue` | `profile/edit` — Faz 4'te atlanmıştı, sonradan eklendi (bkz. 14. bölüm) |

**API'ye eklenenler (sayfaların ihtiyacı olan, Faz 1'de atlanmış uç noktalar):**
`Api\SchoolController` → `index`, `update`, `okullar`, `okulEkle`
`Api\CizelgeController` → `index`, `yuk`
`Api\ProfileController` → `guncelle`, `sifreDegistir` (bkz. 14. bölüm)
`Api\GezinmeController` → `index` (bkz. 15. bölüm)

**Modele eklenenler:**
`app/Models/Teacher.php` → `photo_url` / `cv_url` accessor'ları + `$appends` (yüklenen fotoğraf ve CV'nin adresi; bkz. 5. bölüm)

**Yapılandırma düzeltmesi:**
`tailwind.config.js` → `content` listesine `.vue`/`.js` dosyaları eklendi (bkz. 12. bölüm) — bu tek düzeltme SPA'nın bütün görünümünü etkiledi.

**Yeni bileşenler:**
`components/GirisKarti.vue` (bkz. 13. bölüm), `components/KayitGezinme.vue` (bkz. 15. bölüm)

---

## 11. Faz 6: Geçişin Aktifleştirilmesi

Faz 4 bitince ortada **iki çalışan sistem** vardı: eski Blade (`/seviyeler`, `/cizelge`...) ve yeni SPA (`/app/...`). İkisi de ayakta ama hangisi "asıl" arayüz? Faz 6, bu soruyu cevaplıyor.

### Planın Faz 6 adımları ve durumları

| Adım | Durum |
|---|---|
| 1. SPA `/app` altında yayına alınır, eski sistem dokunulmadan çalışır | Faz 2'de yapıldı |
| 2. Linkler SPA'ya yönlendirilir | **Bu fazda yapıldı** |
| 3. Gerçek kullanıcıyla test edilene kadar eski sistem canlı kalır | Testler yapıldı; klasik sistem son kabul/temizlik kararına kadar yedek olarak tutuluyor |
| 4. Eski Blade rotaları/görünümleri silinir | **Henüz yapılmadı** (bilinçli) |

### Ne değişti

**Dosya: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`**

```php
$request->session()->regenerate();

// Faz 6: varsayilan arayuz artik SPA. Eski Blade ekranlari
// silinmedi - URL'i elle yazan (ya da giris oncesi bir eski
// sayfaya gitmeye calisan, yani intended() dolu olan) kullanici
// oraya gitmeye devam edebiliyor; sadece VARSAYILAN hedef degisti.
return redirect()->intended('/app');
```

Eskiden `route('dashboard')` yani eski Blade dashboard'una gidiyordu. Şimdi `/app`'e gidiyor.

**`intended()` neden korundu — ince bir detay:** Laravel'de `intended()`, "kullanıcı giriş yapmadan önce nereye gitmeye çalışıyordu" bilgisini tutar. Biri giriş yapmadan `/seviyeler` (eski Blade) adresine gitmeye çalışırsa, giriş sonrası oraya döner. Yani eski sistem hâlâ tam erişilebilir — sadece hiçbir yeri hedeflemeyen "düz giriş" artık SPA'ya düşüyor.

**Dosya: `routes/web.php`**

```php
// Faz 6: kok adres artik SPA'ya yonlendiriyor.
Route::get('/', function () {
    return redirect(auth()->check() ? '/app' : '/app/giris');
});
```

Kök adres eskiden Laravel'in varsayılan `welcome` sayfasını gösteriyordu. Artık giriş durumuna göre SPA'ya yönlendiriyor.

**Dosya: `resources/views/layouts/app.blade.php`** — eski Blade ekranlarının üstüne bir bant:

```blade
<div class="bg-amber-50 border-b border-amber-200 text-amber-900 text-sm">
    <div class="... flex items-center justify-between gap-4">
        <span>Klasik arayüzdesiniz. Yeni arayüz (SPA) yayında.</span>
        <a href="/app" class="underline">Yeni arayüze geç &rarr;</a>
    </div>
</div>
```

Amacı, planın 3. adımını (**yan yana karşılaştırma**) kolaylaştırmak: eski bir ekranda olduğunu net görüyorsun ve tek tıkla yenisine geçebiliyorsun. Eski sistem tamamen kaldırıldığında bu bant da silinecek.

### SPA'nın kendi girişi neden etkilenmedi?

`Giris.vue`, `/giris/{rol}` rotasına istek atıyor ama dönen **yönlendirme cevabını kullanmıyor** — Faz 2'de anlatıldığı gibi, isteği attıktan sonra `auth.kontrolEt()` ile durumu kendisi doğrulayıp `router.push()` ile client-side geçiş yapıyor. Yani sunucunun "nereye yönlendirdiği" SPA için önemsiz. Bu sayede `intended('/app')` değişikliği SPA'yı hiç etkilemedi, sadece **eski Blade giriş formundan** gelenleri SPA'ya taşıdı.

### Neden eski sistem hâlâ silinmedi?

Plan, silme adımını açıkça şu koşula bağlamış: *"Tüm sayfalar dönüşüp gerçek kullanıcıyla test edilene kadar eski Blade sistemi canlı ve kullanılabilir kalır — bir şey ters giderse anında eskiye dönülebilir."*

SPA üzerinde işlevsel, uçtan uca ve 40 sınıf / 100 öğretmenlik performans senaryosu testleri yapıldı. Buna rağmen çalışan klasik sistemi kaldırmak, geri dönüş yolunu kapatan ayrı bir temizlik kararıdır. Bu yüzden eski Blade tarafı son kabul ve kaldırma kararı verilene kadar yedek olarak tutuluyor. Silme yapıldığında da `FormRequest` sınıfları ve `Services/` altındaki servisler **silinmeyecek**; API bunları kullanmaya devam ediyor.

### Faz 6 doğrulaması

| Test | Sonuç |
|---|---|
| `/` (giriş yapmadan) | 302 → `/app/giris` |
| Eski Blade giriş formundan (`/giris/ogretmen`) giriş | SPA'nın öğretmen paneline düştü (Derin Köybaşı, 18/30 saat) |
| Eski `/ogretmen/panel` | Hâlâ çalışıyor, üstünde yeni bant ve "Yeni arayüze geç" linki, aynı veriler |
| `/app`, `/app/giris` | 200 |

---

## 12. Faz 6 Sonrası Bulunan Bir Tasarım Hatası: Tailwind `.vue` Dosyalarını Hiç Taramıyordu

Sen SPA'yı gerçek kullanımda gezerken ("giriş ekranı çirkin görünüyor, profil sayfası yok" diye bildirdin) iki gerçek eksik ortaya çıktı. Bu bölüm birincisini, en temel olanı anlatıyor — çünkü sebebi tasarım değil, bir **yapılandırma hatasıydı** ve SPA'nın **tamamını** etkiliyordu.

### Sorun neydi?

Giriş seçim ekranındaki kart, ortalanmış dar bir kutu olması gerekirken tam genişliğe yayılıyordu. İlk bakışta "tasarım eksik" gibi görünüyor ama gerçek sebep başka:

**Dosya: `tailwind.config.js`** (hatalı hâli)

```js
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    // ...
};
```

**`content` nedir?** Tailwind, projede binlerce olası CSS sınıfı **üretmez** — sadece gerçekten kullanılan sınıfları üretir ("purge/tree-shaking" mantığı). Bunun için `content` listesindeki dosyaları **tarar**, içlerinde geçen `class="..."` string'lerini bulur, sadece onların CSS kuralını derlenmiş dosyaya koyar.

**Buradaki hata:** Liste sadece `.blade.php` dosyalarını içeriyordu. SPA'ya geçince bütün sayfalar `.vue` dosyalarına taşındı (bkz. bu dokümanın 1-11. bölümleri) — ama bu liste hiç güncellenmedi. Sonuç: `.vue` dosyalarında **sadece** geçen sınıflar (`max-w-sm`, `mt-24`, `sm:p-8`, `focus:ring-offset-2` gibi) derlenmiş CSS'e hiç girmedi. Tarayıcıda bu sınıfları yazmak **hiçbir hataya yol açmıyor** — sınıf HTML'de duruyor ama karşılığı CSS'te yok, tarayıcı sessizce hiçbir şey yapmıyor.

**Neden fark etmesi zordu?** Blade'de de tesadüfen geçen sınıflar (`bg-gray-800`, `rounded-md`, `px-4`) zaten deriye giriyordu — çünkü onlar `.blade.php` dosyalarında da geçiyordu. Yani sayfa "tamamen biçimsiz" değil, **"yarım biçimli"** görünüyordu: renkler ve köşe yuvarlamaları çalışıyor ama boşluklar/genişlikler rastgele. Bu, "component hiç render olmuyor" gibi net bir hatadan çok daha sinsi bir belirti.

**Düzeltme:**

```js
content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
    './resources/js/**/*.js',
],
```

İki yeni satır: `.vue` dosyaları ve `.js` dosyaları (bazı sınıf isimleri JS içinde string olarak da kurulabiliyor, ör. `:class="şart ? 'a' : 'b'"`). Bu **tek dosyadaki iki satır**, SPA'nın bütün sayfalarını aynı anda düzeltti — Tailwind'in `content` listesi proje genelinde tek bir yerden çalıştığı için.

### İkinci bir bayat dosya sorunu: `public/hot`

Aynı anda başka bir şey daha bulundu: sunucuda **27 Ağustos'tan beri unutulmuş bir `npm run dev` süreci** çalışıyordu. Laravel'in `@vite(...)` fonksiyonu, `public/hot` dosyası varsa "demek ki dev sunucusu ayakta" diye onun adresine (`http://localhost:5173`) yönlendirme yapıyor — üretim build'ini (`public/build/`) görmezden geliyor. O dev sunucusu haftalardır güncellenmemiş eski kodu sunuyordu.

```bash
kill <eski-vite-pid>
rm public/hot
npm run build
```

**Ders:** `.vue` değişikliğinden sonra `npm run build` çalıştırmak yetmiyor — `public/hot` duruyorsa build'in hiç etkisi olmuyor, tarayıcı hep dev sunucusundan (ya da onun çöktüğü an, hiçbir şeyden) veri çekmeye çalışıyor.

---

## 13. Giriş Ekranlarının Yeniden Tasarımı

Tailwind hatası düzeltilince kart doğru ortalandı; ardından gerçek kullanım geri bildirimiyle giriş akışının **görünümü** de iyileştirildi. Bu, ilk dönüşümdeki "hazır Blade görünümünü referans al" ilkesinin ötesine geçen bilinçli bir tasarım iyileştirmesidir.

### Ortak kabuk: `GirisKarti.vue`

Dört giriş sayfası (tür seçimi, giriş formu, şifremi unuttum, şifre sıfırlama — bkz. bu dokümanın Faz 3'ü) neredeyse aynı iskelete sahip: marka başlığı + ortalanmış beyaz kart. Bunu dört dosyaya kopyalamak yerine ortak bir bileşene çıkarıldı:

**Dosya: `resources/js/components/GirisKarti.vue`** (yeni)

```vue
<script setup>
defineProps({
    baslik: { type: String, required: true },
    aciklama: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center px-4 py-12">
        <div class="mb-6 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gray-800 text-base font-semibold text-white">
                OD
            </div>
            <h1 class="mt-3 text-xl font-semibold text-gray-800">ODPYS</h1>
        </div>

        <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-base font-semibold text-gray-800">{{ baslik }}</h2>
            <p v-if="aciklama" class="mt-1 text-sm text-gray-500">{{ aciklama }}</p>

            <div class="mt-6">
                <slot />
            </div>
        </div>

        <div class="mt-6 text-center text-sm">
            <slot name="alt" />
        </div>
    </div>
</template>
```

**Yeni kavram: adlandırılmış slot (`<slot name="alt" />`).** Faz 4'te `Layout.vue`'da gördüğün `<slot />` **tek** bir boşluktu. Burada **iki** boşluk var: isimsiz `<slot />` kartın gövdesi (form) için, `<slot name="alt" />` kartın **altında** kalan küçük linkler için (ör. "Giriş türünü değiştir"). Kullanan sayfa şöyle dolduruyor:

```vue
<GirisKarti baslik="Yönetici Girişi">
    <form>...</form>                         <!-- isimsiz slot -->

    <template #alt>                            <!-- "alt" adlı slot -->
        <router-link :to="{ name: 'giris-secim' }">← Giriş türünü değiştir</router-link>
    </template>
</GirisKarti>
```

`<template #alt>` — `#alt` kısaltması `v-slot:alt` demek, "bu bloğu `alt` adlı slot'a koy" anlamına geliyor.

### Diğer üç sayfa aynı kabuğa taşındı

`GirisSecim.vue`, `Giris.vue`, `SifremiUnuttum.vue`, `SifreSifirla.vue` — dördü de artık `<GirisKarti>` içinde çalışıyor, kendi form mantıkları (Faz 3'te anlatılan axios çağrıları) **hiç değişmedi**, sadece etraflarındaki HTML iskeleti `GirisKarti`'ye devredildi. `GirisSecim.vue`'daki seçenekler de sade düğmelerden, açıklamalı satırlara çevrildi ("Yönetici Girişi / Okul, program, çizelge ve izin yönetimi").

---

## 14. Yeni Ekran: Profil (Ad/E-posta ve Şifre Değiştirme)

Sen "profil yok, şifre değiştiremiyorum" diye bildirdin — haklıydın, Faz 4 listesinde `/profile` hiç yoktu; eski Blade tarafında vardı ama SPA'ya hiç taşınmamıştı.

### Backend: `Api\ProfileController`

**Dosya: `app/Http/Controllers/Api/ProfileController.php`** (yeni)

```php
class ProfileController extends Controller
{
    public function guncelle(ProfileUpdateRequest $request): JsonResponse
    {
        $kullanici = $request->user();
        $kullanici->fill($request->validated());

        if ($kullanici->isDirty('email')) {
            $kullanici->email_verified_at = null;
        }

        $kullanici->save();

        return response()->json([
            'mesaj' => 'Profil bilgileriniz güncellendi.',
            'kullanici' => $kullanici,
        ]);
    }

    public function sifreDegistir(Request $request): JsonResponse
    {
        $dogrulanmis = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($dogrulanmis['password']),
        ]);

        return response()->json(['mesaj' => 'Şifreniz değiştirildi.']);
    }
}
```

**Yeni kavramlar:**

- **`ProfileUpdateRequest` yeniden kullanıldı.** Bu, web tarafındaki eski `ProfileController`'ın da kullandığı **aynı** `FormRequest` sınıfı — `e-posta benzersiz olmalı ama kendi kaydini es geç` (`Rule::unique(...)->ignore($this->user()->id)`) kuralı tek yerde duruyor. API ile web arasında kural tekrarı yok, ikisi de aynı kuraldan geçiyor.
- **`'current_password'` doğrulama kuralı** — Laravel'in hazır bir kuralı: girilen değeri, **giriş yapmış kullanıcının mevcut şifresiyle** karşılaştırır. Bu, "acık bırakılmış bir tarayıcının başına geçen biri eski şifreyi bilmeden yeni şifre koyamasın" güvenliği sağlıyor.
- **`Password::defaults()`** — sabit bir kural yazmak yerine (`'min:8'` gibi), uygulamanın **global şifre politikasına** işaret ediyor. Bu politika nerede tanımlıysa (genelde `AppServiceProvider`), hem kayıt hem şifre sıfırlama hem de bu profil ekranı **aynı kuralı** paylaşıyor.
- **Rotalar `admin`/`teacher` gruplarının DIŞINDA.** `routes/api.php`'de:
  ```php
  Route::put('/profil', [ApiProfileController::class, 'guncelle']);
  Route::put('/profil/sifre', [ApiProfileController::class, 'sifreDegistir']);
  ```
  Bu bilinçli bir karar: yönetici de öğretmen de kendi hesabını düzenleyebilmeli. Ekstra bir yetki kontrolü de gerekmiyor — controller her zaman `$request->user()` üzerinde çalışıyor, yani biri **başka birinin** hesabını asla düzenleyemiyor (URL'de id yok, hep "beni" demek).
- **Hesap silme taşınmadı** (web'de `ProfileController::destroy` var). Bilinçli: bu bir okul sistemi, tek admin hesabı kendini silerse kimse sisteme giremez — "başka bir admin tarafından silme" gibi farklı bir tasarım gerektirir, ileride ayrı bir iş.

### Frontend: `Profil.vue`

**Dosya: `resources/js/pages/Profil.vue`** (yeni)

İki ayrı form/kart — biri ad/e-posta, biri şifre. İkisinin de kendi hata state'i (`bilgiHatalari`, `sifreHatalari`) var, biri hata verirken öbürü etkilenmiyor. Kavramsal olarak Faz 4'teki `Seviyeler.vue` kalıbının aynısı (`ref`, `try/catch`, alan bazlı 422 hataları) — tek yeni şey:

```js
async function bilgiKaydet() {
    // ...
    const { data } = await api.put('/profil', { name: ad.value, email: eposta.value });
    auth.girisYapildi(data.kullanici);   // Pinia store'u tazele
}
```

**`auth.girisYapildi(...)` neden burada çağrılıyor?** Ad değiştiğinde, `Layout.vue`'daki üst menüde görünen isim de **aynı anda** güncellenmeli — o isim Pinia store'daki `kullanici.name`'den geliyor (bkz. Faz 2'deki `useAuthStore`). Sunucudan gelen güncel kullanıcıyı store'a yazmak, sayfayı yeniden yüklemeden her yerin senkron kalmasını sağlıyor.

### `Layout.vue`: sağ üstte artık bir profil bağlantısı

Eskiden sağ üstte düz bir isim yazısı vardı. Artık bir bağlantı, öğretmen listesindeki avatar desenini (baş harfler) tekrar kullanıyor:

```vue
<router-link :to="{ name: 'profil' }" class="flex items-center gap-2 text-sm">
    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-medium">
        {{ basHarfler }}
    </span>
    <span class="hidden sm:inline">{{ auth.kullanici?.name }}</span>
</router-link>
```

`basHarfler` bir `computed` — isim değiştikçe otomatik yeniden hesaplanıyor (`"Profil Test Guncel"` → `"PT"` gibi).

### Doğrulama

Geçici bir test hesabıyla (test sonunda silindi): ad değiştirildi ve üst menüye anında yansıdı; yanlış mevcut şifre "Mevcut şifreniz hatalı." verdi; doğru şifreyle değişiklik kaydedildi ve **yeni şifreyle tekrar giriş yapılabildi** — yani değişiklik gerçekten veritabanına yazıldı, sahte bir başarı mesajı değildi.

---

## 15. Çizelge ve Ders Yetkileri Ekranlarına Kayıtlar Arası Gezinme

Sen bir sonraki geri bildiriminde şunu istedin: çizelge (`/app/cizelge/sube/{id}`, `/app/cizelge/ogretmen/{id}`) ve ders yetkileri (`/app/ogretmenler/{id}/dersler`) ekranlarında, **başka bir kayda geçmek için her seferinde listeye dönmek** gerekiyordu. Üç ekrana da ortak bir gezinme çubuğu eklendi: önceki/sonraki düğmeleri, doğrudan atlamak için açılır liste, "3 / 32" sayacı.

### Neden yeni bir API uç noktası gerekti — `GET /api/gezinme`

İlk bakışta "zaten `/api/cizelge` şube ve öğretmen listelerini dönüyor" diye düşünülebilir, ama iki sebepten uygun değildi:

- `/api/cizelge`, gezinme için gereksiz bir **çakışma kontrolü** (üç ayrı gruplu SQL sorgusu) da çalıştırıyor — sadece bir açılır liste doldurmak için o maliyeti ödemek gereksiz.
- `/api/ogretmenler` **sayfalanmış** (20'şer kayıt) — gezinme için **bütün** liste lazım, yoksa 21. öğretmene "sonraki" ile hiç geçilemezdi.

Bu yüzden sade, tek işi olan bir controller yazıldı:

**Dosya: `app/Http/Controllers/Api/GezinmeController.php`** (yeni)

```php
public function index(): array
{
    return [
        'subeler' => $this->subeler(),        // [{id, ad}, ...] 9A, 9B, 10A sırasında
        'ogretmenler' => $this->ogretmenler(), // sadece aktif öğretmenler
    ];
}
```

Sıralama, kullanıcının **listede zaten gördüğü sırayla aynı** — yoksa "sonraki" düğmesi beklenmedik bir kayda giderdi.

### Yeniden kullanılabilir bileşen: `KayitGezinme.vue`

**Dosya: `resources/js/components/KayitGezinme.vue`** (yeni)

Bu bileşenin önemli tasarım kararı: **kendi verisini çekmiyor.** Listeyi ve "şu an hangi kayıttayım" bilgisini dışarıdan `props` olarak alıyor:

```js
const props = defineProps({
    kayitlar: { type: Array, default: () => [] },      // [{id, ad}]
    aktifId: { type: [String, Number], required: true },
    rota: { type: String, required: true },              // gidilecek rotanın adı
});
```

Bu sayede **tek bir bileşen üç farklı ekranda** çalışıyor (şube çizelgesi, öğretmen çizelgesi, ders yetkileri) — her ekran sadece kendi listesini ve rota adını veriyor. Yarın dördüncü bir ekran gerekirse, yine sadece prop vermek yetecek.

**İnce bir hata payı:** Vue Router'da rota parametreleri (`route.params.id`) her zaman **string** gelir (`"153"`), API'den gelen id'ler ise **number**'dır (`153`). Bunları doğrudan karşılaştırmak (`===`) hep `false` verir — "şu an hangi kayıttayım" hesabı hep `-1` çıkar, düğmeler hiç aktifleşmez. Çözüm, karşılaştırmadan önce ikisini de `String(...)`'e çevirmek:

```js
const sira = computed(() =>
    props.kayitlar.findIndex((kayit) => String(kayit.id) === String(props.aktifId))
);
```

### Ekranlar arası geçişte veri neden yeniden çekiliyor

`CizelgeIzgara.vue`'da Faz 4'ten hatırla: aynı bileşende 9A'dan 9B'ye geçince, Vue Router **aynı bileşeni yeniden kullanır**, `onMounted` bir daha çalışmaz. Faz 4'te bu `watch(() => [route.name, route.params.id], yukle)` ile çözülmüştü — gezinme düğmeleri de aynı mekanizmadan geçtiği için ekstra bir şey gerekmedi, watch zaten oradaydı.

`OgretmenDersleri.vue`'da ise bu watch **hiç yoktu** (eskiden tek bir öğretmene gidilip geri dönülürdü, hiç "aynı bileşende öğretmen değiştirme" senaryosu yoktu). Gezinme eklenince bu senaryo ilk kez ortaya çıktı, watch eklendi:

```js
watch(() => route.params.id, yukle);
```

Bu olmadan gezinme düğmesi adres çubuğunu değiştirir ama tablo **eski öğretmenin** yetkilerini göstermeye devam ederdi — üstüne "Kaydet"e basılsa **yanlış öğretmene** yazılırdı. Sessiz ve tehlikeli bir hata olurdu.

### Bulunan ikinci bir hata: 404'te sayfa sonsuza kadar "Yükleniyor..." kalıyordu

Gezinmeyi test ederken, **başka bir okula ait** bir çizelge adresi (`/app/cizelge/ogretmen/153` gibi) denendiğinde ilginç bir şey görüldü: sayfa sonsuza kadar "Yükleniyor..." yazıyordu. Sebep: iki ekranda da veri çekme isteği `try/catch` içinde değildi — istek 404 ile başarısız olunca `yukleniyor.value = false` satırına hiç ulaşılmıyordu.

Bu, çoklu okulda **çok olası bir durum**: aktif okul değiştiyse, önceden kaydedilmiş bir yer imi artık "aktif okulda yok" sayılır (rota model binding otomatik 404 döner — bkz. [[coklu-okul-pivot-scope-denetimi]]). Düzeltme:

```js
try {
    const { data } = await api.get(`/cizelge/${tur.value}/${route.params.id}`);
    veri.value = data;
} catch (e) {
    veri.value = null;
    hata.value = e.response?.status === 404
        ? 'Bu kayıt aktif okulda bulunamadı. Başka bir okula ait olabilir — "Okullar" ekranından aktif okulu değiştirip tekrar deneyin.'
        : 'Çizelge yüklenemedi.';
} finally {
    yukleniyor.value = false;
}
```

Artık kullanıcı ne olduğunu anlıyor **ve** gezinme çubuğundaki açılır listeden aktif okuldaki bir kayda geçebiliyor — çıkmaz sokak değil.

### Yan bir iyileştirme: ders yetkileri ekranının başlığı

Eskiden sayfa başlığı sabit "Öğretmen Ders Yetkileri" idi — hangi öğretmenin düzenlendiği **hiçbir yerde** yazmıyordu. Öğretmenler arası gezinme gelince bu ciddi bir eksiklik oldu (yanlış öğretmende olduğunu fark etmeden değişiklik yapabilirdin). `Api\TeacherController::dersler()` artık öğretmenin adını da dönüyor, başlık dinamik: "Ders Yetkileri — Ali Kaya".

Ayrıca kaydettikten sonra **artık listeye dönmüyor** (eskiden 1.2 saniye sonra otomatik dönüyordu) — doğal akış "kaydet → sonraki öğretmen" oldu, listeye dönmek bu akışı her seferinde kesiyordu.

### Doğrulama

Geçici test hesabıyla: öğretmen çizelgesinde "sonraki" 1/32 → 2/32 geçişinde başlık, tablo ve PDF/Excel linkleri birlikte güncellendi; şube çizelgesinde açılır listeden 12C'ye atlandı, son kayıtta "sonraki" pasifleşti; ders yetkilerinde öğretmen değişince işaretli kutular da gerçekten değişti (Ali Kaya → Kimya, 4 seviye); başka okula ait bir adres artık asılı kalmıyor, açıklayıcı hata veriyor.

---

## 16. Sırada Ne Var

Tek kalan adım, planın Faz 6/4. maddesi: **eski Blade sisteminin kaldırılması.** Bunun için önce senin SPA'yı gerçek kullanımda gezmen gerekiyor:

1. `/app` adresine gir, her ekranı aç (Seviyeler, Şubeler, Dersler, Öğretmenler, Program, Çizelge, Ücretler, İzinler, **Profil**).
2. Bir ekranda şüphe duyarsan, sarı banttaki linkten eski arayüze geçip aynı ekranı karşılaştır.
3. Bir kayıt ekleme/düzenleme/silme dene. (Öğretmen fotoğraf/CV yükleme, giriş ekranlarının görünümü, profil ve gezinme **artık gerçek kullanımda bulunup düzeltildi** — bu doküman bu düzeltmeleri 12-15. bölümlerde anlatıyor.)
4. Bir fark ya da hata görürsen not al.

Hepsi doğruysa, eski Blade rotaları/view'ları silinebilir. Ama unutma: teslim tarihine az kala çalışan bir yedeği silmenin **pratik bir faydası yok** — iki sistemin paralel durması teslim açısından risk değil, aksine güvence. Silme işlemi "temizlik" amaçlıdır, zorunluluk değil.
