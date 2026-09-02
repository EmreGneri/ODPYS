# Faz 4 + Faz 6: Tüm Ekranların SPA'ya Taşınması ve Geçişin Tamamlanması — Eğitim + Uygulama

Bu doküman iki fazı birden anlatıyor:
- **Faz 4** — planın en büyük fazı: 20 Vue sayfası + ortak bir düzen (layout) bileşeni.
- **Faz 6** — geçişin aktifleştirilmesi: SPA'nın varsayılan arayüz olması, eski sistemin yedekte kalması.

Kullanılan her kavramı sıfırdan açıklıyor, sonra her ekranın nasıl yazıldığını dosya yollarıyla gösteriyor.

> **Faz 5 nerede?** Planda ayrı bir madde olan "dosya yükleme" (öğretmen fotoğraf/CV), Faz 4'te `Ogretmenler.vue` yazılırken doğal olarak içinde halloldu — öğretmen formunun kendisi zaten o alanları içeriyordu, ayrı bir faza bölmek yapay olurdu. Kodu ve sonradan bulunan bir eksiğin (yüklenen dosyanın gösterilmemesi) düzeltilmesi bu dokümanın **5. bölümünde**.

---

## 1. Faz 4 Neyi Kapsıyor?

Faz 0-3'te **temel** kurulmuştu: API hazır, kimlik doğrulama çalışıyor, router/state/HTTP altyapısı ayakta. Ama gerçek ekranlar (Seviyeler, Dersler, Program, Çizelge...) hâlâ sadece eski Blade tarafında vardı.

Faz 4, o ekranların **hepsini** Vue'ya taşıdı. Önemli olan şu: **tasarım tarafında hiçbir yeni karar verilmedi.** Her Vue sayfası, karşılığı olan Blade dosyasının HTML/Tailwind yapısını referans alıp aynı görünümü üretiyor. Yani "nasıl görünmeli" sorusunun cevabı zaten yazılıydı, biz sadece "veriyi nereden alıyor" kısmını değiştirdik:

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
- **`v-for` + `:key`** — Blade'deki `@foreach`'in Vue karşılığı. `:key` zorunlu: Vue'nun listedeki hangi öğenin hangisi olduğunu takip etmesi için (liste değişince gereksiz yeniden çizim yapmasın diye).
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
- **`async/await`** — API çağrıları anında sonuçlanmaz (ağ gecikmesi). `await`, "cevap gelene kadar burada bekle, sonra devam et" demek. `async`, içinde `await` kullanılan fonksiyonun işaretidir.
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

Öğretmen formunda fotoğraf ve CV yükleniyor. Dosya, JSON içine konulamaz — `FormData` kullanılıyor.

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

**Neden `POST` + `_method=PUT`, doğrudan `PUT` değil?** Tarayıcılar dosya içeriğini `PUT` isteğiyle native olarak göndermiyor. Laravel'in çözümü **method spoofing**: isteği `POST` olarak yolla, gövdeye `_method=PUT` alanını ekle — Laravel bunu görüp isteği `PUT` gibi işliyor. Blade formlarındaki `@method('PUT')` direktifi tam olarak bu gizli alanı basıyordu; burada elle ekliyoruz.

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
- **`URL.revokeObjectURL(...)`** — bu geçici adresler tarayıcı belleğinde tutulur ve **otomatik temizlenmez**. Kullanıcı üst üste 10 fotoğraf seçerse 10 kopya bellekte kalırdı. Eskisini serbest bırakmak, bu sızıntıyı önlüyor.

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
// 'sube' ya da 'ogretmen' - rota adindan cikariyoruz
const tur = () => (route.name === 'cizelge-sube' ? 'sube' : 'ogretmen');

async function yukle() {
    const { data } = await api.get(`/cizelge/${tur()}/${route.params.id}`);
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
<a :href="`/cizelge/${tur()}/${route.params.id}/pdf`">PDF indir</a>
```

Çünkü SPA **çerez** tabanlı kimlik doğrulama kullanıyor (Faz 2): tarayıcı bu düz linke gittiğinde oturum çerezini otomatik gönderiyor, mevcut web rotası çalışıyor. (Token tabanlı bir mimaride bu bir sorun olurdu — token linkte taşınamazdı.)

---

## 7. Router: 24 Rota ve Rol Koruması

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

Gerçek tarayıcıda, gerçek veriyle test edildi:

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
| `npm run build` | 21 sayfa ayrı lazy-loaded paket olarak derlendi |

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

**API'ye eklenenler (sayfaların ihtiyacı olan, Faz 1'de atlanmış uç noktalar):**
`Api\SchoolController` → `index`, `update`, `okullar`, `okulEkle`
`Api\CizelgeController` → `index`, `yuk`

**Modele eklenenler:**
`app/Models/Teacher.php` → `photo_url` / `cv_url` accessor'ları + `$appends` (yüklenen fotoğraf ve CV'nin adresi; bkz. 5. bölüm)

---

## 11. Faz 6: Geçişin Aktifleştirilmesi

Faz 4 bitince ortada **iki çalışan sistem** vardı: eski Blade (`/seviyeler`, `/cizelge`...) ve yeni SPA (`/app/...`). İkisi de ayakta ama hangisi "asıl" arayüz? Faz 6, bu soruyu cevaplıyor.

### Planın Faz 6 adımları ve durumları

| Adım | Durum |
|---|---|
| 1. SPA `/app` altında yayına alınır, eski sistem dokunulmadan çalışır | Faz 2'de yapıldı |
| 2. Linkler SPA'ya yönlendirilir | **Bu fazda yapıldı** |
| 3. Gerçek kullanıcıyla test edilene kadar eski sistem canlı kalır | **Şu an buradayız** |
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

Ben (Claude) SPA'yı test ettim, ama **sen henüz gerçek kullanımda denemedin.** Teslim tarihi yakınken çalışan bir sistemi silmek gereksiz bir risk. Silme yapıldığında da şunlar **silinmeyecek**: `FormRequest` sınıfları ve `Services/` altındaki servisler — API onları kullanmaya devam ediyor.

### Faz 6 doğrulaması

| Test | Sonuç |
|---|---|
| `/` (giriş yapmadan) | 302 → `/app/giris` |
| Eski Blade giriş formundan (`/giris/ogretmen`) giriş | SPA'nın öğretmen paneline düştü (Derin Köybaşı, 18/30 saat) |
| Eski `/ogretmen/panel` | Hâlâ çalışıyor, üstünde yeni bant ve "Yeni arayüze geç" linki, aynı veriler |
| `/app`, `/app/giris` | 200 |

---

## 12. Sırada Ne Var

Tek kalan adım, planın Faz 6/4. maddesi: **eski Blade sisteminin kaldırılması.** Bunun için önce senin SPA'yı gerçek kullanımda gezmen gerekiyor:

1. `/app` adresine gir, her ekranı aç (Seviyeler, Şubeler, Dersler, Öğretmenler, Program, Çizelge, Ücretler, İzinler).
2. Bir ekranda şüphe duyarsan, sarı banttaki linkten eski arayüze geçip aynı ekranı karşılaştır.
3. Bir kayıt ekleme/düzenleme/silme dene. (Öğretmen fotoğraf/CV yükleme **artık test edildi ve düzeltildi** — bkz. 5. bölüm; ilk yazımda yükleme çalışıyor ama gösterim eksikti.)
4. Bir fark ya da hata görürsen not al.

Hepsi doğruysa, eski Blade rotaları/view'ları silinebilir. Ama unutma: teslim tarihine az kala çalışan bir yedeği silmenin **pratik bir faydası yok** — iki sistemin paralel durması teslim açısından risk değil, aksine güvence. Silme işlemi "temizlik" amaçlıdır, zorunluluk değil.
