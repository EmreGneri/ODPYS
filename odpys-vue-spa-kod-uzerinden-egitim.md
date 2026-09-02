# ODPYS Vue SPA — Kod Üzerinden Başlangıç Eğitimi

Bu eğitim, ayrı bir JavaScript müfredatı vermek yerine JavaScript ve Vue sözdizimini doğrudan ODPYS'nin çalışan kodu üzerinden açıklar. Amaç, dosyaları açtığında yalnızca ne yapıldığını değil, kullanılan sözdiziminin neden yazıldığını da anlayabilmendir.

## 1. Büyük Resim: Bir SPA Sayfası Nasıl Açılıyor?

ODPYS'nin yeni arayüzünde bir sayfa şu akışla oluşur:

**Akış: `/app/seviyeler` sayfasının açılması**

**İlgili dosyalar: `routes/web.php`, `resources/views/spa.blade.php`, `resources/js/spa.js`, `resources/js/router.js`, `resources/js/pages/Seviyeler.vue`**

```text
Tarayıcı /app/seviyeler adresini açar
        ↓
Laravel spa.blade.php dosyasını döndürür
        ↓
spa.js Vue uygulamasını başlatır
        ↓
router.js URL'ye bakıp Seviyeler.vue bileşenini seçer
        ↓
Seviyeler.vue /api/seviyeler adresine istek atar
        ↓
Laravel JSON döndürür
        ↓
Vue gelen veriyi ekranda tabloya çevirir
```

Blade sisteminde Laravel hazır HTML üretiyordu. SPA'da Laravel çoğunlukla JSON üretir; ekranda görünen HTML'yi Vue tarayıcıda oluşturur.

## 2. Vue Uygulamasının Başlangıç Noktası

**Dosya: `resources/js/spa.js`**

```js
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';

createApp(App)
    .use(createPinia())
    .use(router)
    .mount('#app');
```

### `import` ne yapıyor?

**Dosya: `resources/js/spa.js`**

```js
import { createApp } from 'vue';
```

Bu satır, `vue` paketinden `createApp` isimli fonksiyonu alır. Süslü parantez kullanılmasının nedeni bunun bir **named export** olmasıdır.

Buna karşılık aşağıdaki satırda süslü parantez yoktur:

**Dosya: `resources/js/spa.js`**

```js
import App from './App.vue';
```

Bir `.vue` dosyası bileşeni varsayılan, yani **default export** olarak sunduğu için istediğimiz yerel isimle içeri alabiliriz.

Kısaca iki biçim şunlardır:

**Dosya: `resources/js/spa.js` içindeki import biçimleri**

```js
import { createApp } from 'vue'; // İsmiyle dışa aktarılmış değer
import App from './App.vue';     // Varsayılan dışa aktarılmış değer
```

### `./` ne demek?

**Dosya: `resources/js/spa.js`**

```js
import App from './App.vue';
import router from './router';
```

`./`, “şu an bulunduğum klasörden başla” demektir. `spa.js`, `resources/js` içinde olduğu için yollar şöyle çözülür:

**Kaynak dosya: `resources/js/spa.js`**

```text
./App.vue → resources/js/App.vue
./router  → resources/js/router.js
```

### Vue uygulaması nasıl oluşturuluyor?

**Dosya: `resources/js/spa.js`**

```js
createApp(App)
    .use(createPinia())
    .use(router)
    .mount('#app');
```

`createApp(App)`, `App.vue` bileşenini kök kabul eden bir Vue uygulaması oluşturur. Devamındaki metotlar aynı uygulama nesnesi üzerinde zincirleme çağrılır:

- `.use(createPinia())`: Pinia durum yönetimini ekler.
- `.use(router)`: Vue Router'ı ekler.
- `.mount('#app')`: Vue uygulamasını HTML'deki `id="app"` elementine yerleştirir.

Laravel'in sunduğu SPA kabuğunda bu hedef bulunur:

**Dosya: `resources/views/spa.blade.php`**

```blade
<div id="app"></div>
```

`#app`, CSS seçici sözdiziminde “id değeri app olan element” demektir. Vue bu elementin içini yönetmeye başlar.

## 3. Kök Bileşen ve `<router-view>`

**Dosya: `resources/js/App.vue`**

```vue
<template>
    <router-view />
</template>
```

Bir `.vue` dosyasına **Single-File Component**, yani tek dosyalık bileşen denir. Bir Vue bileşeninde şu bölümler bulunabilir:

**Dosya: Örnek bir `.vue` bileşeninin bölümleri**

```vue
<script setup>
// JavaScript
</script>

<template>
    <!-- HTML benzeri Vue şablonu -->
</template>

<style>
/* CSS */
</style>
```

ODPYS'de stillerin çoğu Tailwind sınıflarıyla yazıldığı için genellikle ayrı bir `<style>` bölümü yoktur.

`<router-view />`, mevcut URL için router'ın seçtiği sayfayı buraya yerleştirir:

**İlgili dosyalar: `resources/js/App.vue`, `resources/js/router.js`**

```text
/app/seviyeler → Seviyeler.vue
/app/dersler   → Dersler.vue
/app/program   → Program.vue
```

`App.vue` hangi sayfanın gösterileceğine karar vermez. Bu kararı Vue Router verir.

## 4. Router: URL Hangi Vue Sayfasını Açıyor?

**Dosya: `resources/js/router.js`**

```js
{
    path: '/app/seviyeler',
    name: 'seviyeler',
    component: () => import('./pages/Seviyeler.vue'),
    meta: { auth: true, role: 'admin' },
},
```

Bu bir JavaScript nesnesidir. Anahtar-değer çiftlerinden oluşur:

- `path`: Tarayıcıdaki adres.
- `name`: Kod içinden kullanılacak rota adı.
- `component`: Açılacak Vue bileşeni.
- `meta`: Giriş ve rol gibi ek bilgiler.

### `() =>` ne demek?

**Dosya: `resources/js/router.js`**

```js
component: () => import('./pages/Seviyeler.vue')
```

Bu bir **arrow function**'dır. Uzun yazımı yaklaşık olarak şöyledir:

**Dosya: `resources/js/router.js` satırının uzun karşılığı**

```js
component: function () {
    return import('./pages/Seviyeler.vue');
}
```

Buradaki `import()` çağrısı bileşeni yalnızca gerektiğinde yükler. Buna **lazy loading** denir. Kullanıcı Seviyeler ekranına hiç girmezse ilgili sayfa paketinin başta indirilmesi gerekmez.

### `meta` ne işe yarıyor?

**Dosya: `resources/js/router.js`**

```js
meta: { auth: true, role: 'admin' }
```

- `auth: true`: Bu sayfa için giriş yapılmalı.
- `role: 'admin'`: Kullanıcı yönetici rolünde olmalı.

Öğretmen ekranında rol değişir:

**Dosya: `resources/js/router.js`**

```js
{
    path: '/app/ogretmen/panel',
    name: 'ogretmen-panel',
    component: () => import('./pages/OgretmenPanel.vue'),
    meta: { auth: true, role: 'teacher' },
},
```

Frontend'deki rol kontrolü kullanıcı deneyimi içindir. Gerçek güvenlik Laravel API middleware'lerinde de uygulanır.

### Router guard nasıl çalışıyor?

**Dosya: `resources/js/router.js`**

```js
router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (! auth.kontrolEdildi) {
        await auth.kontrolEt();
    }

    if (to.meta.auth && ! auth.girisYapilmis) {
        return { name: 'giris-secim' };
    }

    if (to.meta.role && auth.rol !== to.meta.role) {
        return {
            name: auth.rol === 'teacher'
                ? 'ogretmen-panel'
                : 'dashboard'
        };
    }
});
```

`beforeEach`, her sayfa geçişinden önce çalışır. `to`, gidilmek istenen rotadır.

#### `!`, `&&` ve `!==`

**Dosya: `resources/js/router.js`**

```js
if (! auth.kontrolEdildi) {
    await auth.kontrolEt();
}
```

`!`, mantıksal ters çevirme operatörüdür. `kontrolEdildi` false ise koşul true olur.

**Dosya: `resources/js/router.js`**

```js
if (to.meta.auth && ! auth.girisYapilmis) {
    return { name: 'giris-secim' };
}
```

`&&`, “ve” demektir. Sayfa giriş gerektiriyorsa **ve** kullanıcı giriş yapmamışsa giriş ekranına dönülür.

**Dosya: `resources/js/router.js`**

```js
auth.rol !== to.meta.role
```

`!==`, değer veya veri türü eşit değil demektir. JavaScript'te genellikle `==` yerine `===`, `!=` yerine `!==` kullanılır; çünkü bunlar veri türünü de kontrol eder.

#### Ternary operatörü

**Dosya: `resources/js/router.js`**

```js
auth.rol === 'teacher'
    ? 'ogretmen-panel'
    : 'dashboard'
```

Bu, kısa bir `if/else` yazımıdır:

**Sözdizimi örneği: JavaScript üçlü koşul operatörü**

```text
koşul ? doğruysaBu : yanlışsaBu
```

## 5. Pinia: Giriş Yapan Kullanıcı Nerede Tutuluyor?

**Dosya: `resources/js/stores/auth.js`**

```js
export const useAuthStore = defineStore('auth', {
    state: () => ({
        kullanici: null,
        kontrolEdildi: false,
    }),

    getters: {
        girisYapilmis: (state) => state.kullanici !== null,
        rol: (state) => state.kullanici?.role ?? null,
    },

    actions: {
        async kontrolEt() {
            try {
                const { data } = await api.get('/user');
                this.kullanici = data;
            } catch {
                this.kullanici = null;
            } finally {
                this.kontrolEdildi = true;
            }
        },
    },
});
```

Bir Pinia store üç ana bölümden oluşur:

- `state`: Saklanan veriler.
- `getters`: State'ten hesaplanan değerler.
- `actions`: State'i değiştiren işlemler.

### `state: () => ({ ... })`

**Dosya: `resources/js/stores/auth.js`**

```js
state: () => ({
    kullanici: null,
    kontrolEdildi: false,
})
```

Bu, nesne döndüren kısa bir fonksiyondur. Parantez, süslü parantezin fonksiyon gövdesi değil doğrudan döndürülen nesne olduğunu belirtir.

Başlangıçta `kullanici: null`, henüz kullanıcı bilgisi olmadığı anlamına gelir. `kontrolEdildi: false`, sunucuya “oturum açık mı?” sorusunun henüz sorulmadığını gösterir.

### Getter, optional chaining ve `??`

**Dosya: `resources/js/stores/auth.js`**

```js
getters: {
    girisYapilmis: (state) => state.kullanici !== null,
    rol: (state) => state.kullanici?.role ?? null,
}
```

`state.kullanici?.role` ifadesindeki `?.`, **optional chaining** operatörüdür. Kullanıcı varsa `role` alanını okur; kullanıcı null ise hata vermeden `undefined` üretir.

`??`, **nullish coalescing** operatörüdür. Sol taraf yalnızca `null` veya `undefined` ise sağ taraf kullanılır. Böylece kullanıcı yokken rol değeri `null` olur.

### `try / catch / finally`

**Dosya: `resources/js/stores/auth.js`**

```js
try {
    const { data } = await api.get('/user');
    this.kullanici = data;
} catch {
    this.kullanici = null;
} finally {
    this.kontrolEdildi = true;
}
```

- `try`: Başarıyla tamamlanabilecek işlem.
- `catch`: İşlem hata verirse çalışır.
- `finally`: Başarı veya hata fark etmeksizin çalışır.

API 200 dönerse kullanıcı kaydedilir. API 401 dönerse `catch` çalışır ve kullanıcı null kalır. Her iki durumda da kontrolün tamamlandığını göstermek için `finally` çalışır.

### Destructuring

**Dosya: `resources/js/stores/auth.js`**

```js
const { data } = await api.get('/user');
```

Axios; `data`, `status` ve `headers` gibi alanları olan bir cevap nesnesi döndürür. Burada yalnızca `data` alanı nesneden çıkarılır. Uzun karşılığı şöyledir:

**Dosya: `resources/js/stores/auth.js` satırının uzun karşılığı**

```js
const cevap = await api.get('/user');
const data = cevap.data;
```

## 6. Axios: Vue ile Laravel Nasıl Konuşuyor?

**Dosya: `resources/js/api.js`**

```js
const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});
```

Bu ortak bir Axios istemcisi oluşturur. `baseURL: '/api'` sayesinde `api.get('/seviyeler')` çağrısı gerçekte `/api/seviyeler` adresine gider.

`Accept: 'application/json'`, sunucuya HTML değil JSON beklediğimizi bildirir.

### CSRF çerezini bir kez kurma

**Dosya: `resources/js/api.js`**

```js
let csrfCerezKuruldu = false;

export async function csrfCerezGarantiEt() {
    if (csrfCerezKuruldu) {
        return;
    }

    await axios.get('/sanctum/csrf-cookie');
    csrfCerezKuruldu = true;
}
```

`let`, sonradan başka değer atanacak değişkeni tanımlar. `const` ise değişken referansının yeniden atanmasını engeller.

Vue'daki ref'ler yine `const` ile tanımlanabilir; çünkü değişkenin kendisi değil içindeki `.value` değiştirilir:

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
const mesaj = ref('');
mesaj.value = 'Kaydedildi';
```

`if (csrfCerezKuruldu) return` mantığı, işlem daha önce yapıldıysa fonksiyondan erken çıkmayı sağlar. Buna **early return** denir.

### Request interceptor

**Dosya: `resources/js/api.js`**

```js
api.interceptors.request.use(async (config) => {
    await csrfCerezGarantiEt();
    return config;
});
```

Bu fonksiyon her API isteğinden önce çalışır. Önce CSRF çerezini garanti eder, sonra `config` nesnesini geri vererek isteğin devam etmesini sağlar.

## 7. Vue Sayfasının Temel Yapısı

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
</script>
```

`<script setup>`, Vue 3 Composition API'nin kısa yazımıdır. Burada tanımlanan değişken ve fonksiyonlar template içinde otomatik kullanılabilir.

### `ref` nedir?

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
const seviyeler = ref([]);
const yukleniyor = ref(true);
const mesaj = ref('');
const duzenlenenId = ref(null);
```

`ref`, Vue tarafından izlenen reaktif bir değer oluşturur. Değer değişince Vue ekranın ilgili kısmını yeniler.

JavaScript bölümünde `.value` kullanılır:

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
seviyeler.value = data;
```

Template içinde `.value` yazılmaz; Vue ref'i otomatik açar:

**Dosya: `resources/js/pages/Seviyeler.vue`**

```vue
<tr v-for="seviye in seviyeler" :key="seviye.id">
    {{ seviye.name }}
</tr>
```

## 8. Sayfa Açılınca Veriyi Çekmek

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
async function yukle() {
    const { data } = await api.get('/seviyeler');
    seviyeler.value = data;
    yukleniyor.value = false;
}

onMounted(yukle);
```

`onMounted`, bileşen tarayıcıya yerleştirildikten sonra verilen fonksiyonu çalıştırır. `onMounted(yukle)` yazarken fonksiyonu hemen çağırmıyor, fonksiyonun kendisini veriyoruz.

`await`, yalnızca bulunduğu `async` fonksiyonun devamını Promise sonuçlanana kadar erteler; bütün tarayıcı arayüzünü kilitlemez.

Akış şöyledir:

**İlgili dosya: `resources/js/pages/Seviyeler.vue`**

```text
Seviyeler.vue oluşturulur
        ↓
Template ilk kez çizilir
        ↓
onMounted çalışır
        ↓
GET /api/seviyeler
        ↓
seviyeler.value güncellenir
        ↓
Vue tabloyu yeniden çizer
```

## 9. Form Açma: Varsayılan Parametre ve Optional Chaining

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
function formAc(seviye = null) {
    duzenlenenId.value = seviye?.id ?? null;
    gradeNumber.value = seviye?.grade_number ?? '';
    hata.value = '';
    formAcik.value = true;
}
```

Bu fonksiyon hem yeni kayıt hem düzenleme için kullanılır. `seviye = null`, fonksiyon parametresiz çağrılırsa seviye değerinin null olmasını sağlar.

`seviye?.id ?? null` ifadesi, seviye varsa id'yi alır; yoksa hata vermeden null kullanır.

## 10. Kaydetme: POST ve PUT Ayrımı

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
async function kaydet() {
    hata.value = '';

    try {
        if (duzenlenenId.value) {
            await api.put(
                `/seviyeler/${duzenlenenId.value}`,
                { grade_number: gradeNumber.value }
            );

            mesaj.value = 'Seviye güncellendi.';
        } else {
            await api.post('/seviyeler', {
                grade_number: gradeNumber.value,
            });

            mesaj.value = 'Seviye eklendi.';
        }

        formAcik.value = false;
        await yukle();
    } catch (e) {
        hata.value =
            e.response?.data?.errors?.grade_number?.[0]
            ?? 'Bir hata oluştu.';
    }
}
```

Yeni kayıtta `POST /api/seviyeler`, düzenlemede `PUT /api/seviyeler/{id}` kullanılır.

### Template literal

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
`/seviyeler/${duzenlenenId.value}`
```

Backtick ile yazılan metne `${...}` kullanarak değer yerleştirilir. Örneğin id 5 ise sonuç `/seviyeler/5` olur.

### Laravel doğrulama hatasını okumak

Laravel 422 cevabında alan hatalarını bir `errors` nesnesinde döndürür. Kod şu güvenli zincirle ilk mesajı alır:

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
e.response?.data?.errors?.grade_number?.[0]
    ?? 'Bir hata oluştu.'
```

Zincirde bir alan bulunmazsa `?.` hata oluşmasını önler. Özel mesaj yoksa `??` sonrasındaki genel mesaj kullanılır.

## 11. Silme İşlemi

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
async function sil(seviye) {
    if (! confirm(`${seviye.name} silinsin mi?`)) {
        return;
    }

    try {
        await api.delete(`/seviyeler/${seviye.id}`);
        mesaj.value = 'Seviye silindi.';
        await yukle();
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Silinemedi.';
    }
}
```

`confirm()` tarayıcının hazır onay penceresini açar. Kullanıcı vazgeçerse false döner. Baştaki `!` sonucu ters çevirir ve `return` ile fonksiyondan çıkılır.

Silme başarılı olunca `yukle()` yeniden çağrılır; liste sunucudaki güncel veriyle yenilenir.

## 12. Template Sözdizimi

**Dosya: `resources/js/pages/Seviyeler.vue`**

```vue
<Layout baslik="Seviyeler">
    <p v-if="yukleniyor">Yükleniyor...</p>

    <table v-else>
        <tr
            v-for="seviye in seviyeler"
            :key="seviye.id"
        >
            <td>{{ seviye.name }}</td>
            <td>{{ seviye.sections_count }}</td>

            <td>
                <button @click="formAc(seviye)">
                    Düzenle
                </button>
            </td>
        </tr>
    </table>
</Layout>
```

### `{{ ... }}`

`{{ seviye.name }}`, JavaScript değerini metin olarak HTML'e basar. Buna **interpolation** denir.

### `v-if` ve `v-else`

`yukleniyor` doğruysa paragraf, yanlışsa tablo oluşturulur. `v-if` yalnız gizleme yapmaz; koşul yanlışsa elementi DOM'dan kaldırır.

### `v-for`

`v-for="seviye in seviyeler"`, dizideki her kayıt için bir tablo satırı oluşturur. Blade'deki `@foreach` yapısının Vue karşılığıdır.

### `:key`

`:` işareti `v-bind:` kısaltmasıdır. `:key="seviye.id"`, Vue'ya her satırın benzersiz kimliğini verir.

### `@click`

`@`, `v-on:` kısaltmasıdır. `@click="formAc(seviye)"`, düğmeye tıklanınca fonksiyonu çalıştırır.

## 13. `v-model`: Input ile JavaScript Değişkenini Bağlamak

**Dosya: `resources/js/pages/Seviyeler.vue`**

```vue
<input
    v-model="gradeNumber"
    type="number"
    min="9"
    max="12"
>
```

`v-model`, çift yönlü bağlantı kurar. Kullanıcı input'a değer yazınca `gradeNumber` güncellenir; `gradeNumber` koddan değişince input da yeni değeri gösterir.

JavaScript bölümünde `gradeNumber.value`, template içinde `gradeNumber` kullanılır.

## 14. Layout ve Slot Mantığı

Her sayfada menüyü yeniden yazmak yerine ortak `Layout.vue` bileşeni kullanılır.

**Dosya: `resources/js/components/Layout.vue`**

```vue
<script setup>
defineProps({
    baslik: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <nav>
        <!-- Ortak menü -->
    </nav>

    <header v-if="baslik">
        <h2>{{ baslik }}</h2>
    </header>

    <main>
        <slot />
    </main>
</template>
```

`defineProps`, bileşenin dışarıdan hangi bilgileri alacağını belirler. Sayfa Layout'u şöyle kullanır:

**Dosya: `resources/js/pages/Seviyeler.vue`**

```vue
<Layout baslik="Seviyeler">
    <div>
        Seviyeler sayfasının içeriği
    </div>
</Layout>
```

İçerideki `<div>`, Layout içindeki `<slot />` konumuna yerleşir. Bu, Blade'deki `<x-app-layout>` ve `$slot` mantığıyla aynıdır.

## 15. `computed`: Bir Değerden Başka Değer Üretmek

**Dosya: `resources/js/components/Layout.vue`**

```js
const basHarfler = computed(() => {
    return (auth.kullanici?.name ?? '?')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((kelime) => kelime.charAt(0))
        .join('')
        .toLocaleUpperCase('tr');
});
```

`computed`, başka reaktif değerlerden türetilmiş değer oluşturur. Kullanıcının adı değişirse baş harfler otomatik yeniden hesaplanır.

Zincirde yapılanlar:

1. Kullanıcı adını al; yoksa `?` kullan.
2. `.trim()` ile kenar boşluklarını sil.
3. `.split(/\s+/)` ile kelimelere ayır.
4. `.slice(0, 2)` ile ilk iki kelimeyi al.
5. `.map(...)` ile her kelimenin ilk harfini al.
6. `.join('')` ile harfleri birleştir.
7. Türkçe büyük harf dönüşümü uygula.

`/\s+/` bir regular expression'dır. `\s` boşluk karakterini, `+` bir veya daha fazla tekrarı ifade eder.

## 16. Template İçinde Dinamik Menü ve CSS

**Dosya: `resources/js/components/Layout.vue`**

```vue
<router-link
    v-for="oge in (
        auth.rol === 'admin'
            ? yoneticiMenu
            : ogretmenMenu
    )"
    :key="oge.rota"
    :to="{ name: oge.rota }"
>
    {{ oge.ad }}
</router-link>
```

Ternary operatörü yöneticiye yönetici menüsünü, öğretmene öğretmen menüsünü seçer.

`:to`, düz bir metin yerine JavaScript nesnesi alır. `:` olmasaydı içerik JavaScript olarak değerlendirilmezdi.

Dinamik sınıf da aynı yöntemle verilir:

**Dosya: `resources/js/components/Layout.vue`**

```vue
:class="$route.name === oge.rota
    ? 'border-indigo-400 text-gray-900'
    : 'border-transparent text-gray-500'"
```

Rota aktifse bir, değilse başka Tailwind sınıfları uygulanır.

## 17. Dosya Yükleme: `FormData`

Fotoğraf veya PDF gibi ikili dosyaları form alanlarıyla aynı istekte göndermek için `FormData` kullanılır.

**Dosya: `resources/js/pages/Ogretmenler.vue`**

```js
const fd = new FormData();

Object.entries(form.value).forEach(([anahtar, deger]) => {
    fd.append(
        anahtar,
        anahtar === 'is_active'
            ? (deger ? 1 : 0)
            : deger
    );
});

if (fotografDosya.value) {
    fd.append('photo', fotografDosya.value);
}

if (cvDosya.value) {
    fd.append('cv', cvDosya.value);
}
```

### `Object.entries` ve array destructuring

`Object.entries(form.value)`, form nesnesini `[anahtar, değer]` çiftlerinden oluşan bir diziye çevirir.

**Dosya: `resources/js/pages/Ogretmenler.vue` içindeki veri dönüşümünün örneği**

```js
{
    first_name: 'Emre',
    email: 'emre@example.com'
}

// Object.entries sonucu:
[
    ['first_name', 'Emre'],
    ['email', 'emre@example.com']
]
```

`([anahtar, deger])` yazımı, iki elemanlı diziyi iki değişkene ayıran **array destructuring** sözdizimidir.

### Dosya güncellemede method spoofing

**Dosya: `resources/js/pages/Ogretmenler.vue`**

```js
if (duzenlenenId.value) {
    fd.append('_method', 'PUT');

    await api.post(
        `/ogretmenler/${duzenlenenId.value}`,
        fd
    );
} else {
    await api.post('/ogretmenler', fd);
}
```

HTTP isteği POST gider; gövdedeki `_method=PUT` nedeniyle Laravel bunu uygulama seviyesinde PUT kabul eder. Axios teknik olarak PUT ile FormData gönderebilir; bu desen PHP/Laravel multipart işleme hattında güvenilir uyumluluk için kullanılır.

`Content-Type` başlığı elle yazılmaz. Tarayıcı `multipart/form-data` başlığını gerekli `boundary` değeriyle birlikte üretir.

## 18. Dosya Seçme ve Anlık Önizleme

**Dosya: `resources/js/pages/Ogretmenler.vue`**

```vue
<input
    @change="fotografSecildi"
    type="file"
    accept="image/*"
>
```

Dosya seçilince `change` olayı çalışır.

**Dosya: `resources/js/pages/Ogretmenler.vue`**

```js
function fotografSecildi(olay) {
    const dosya = olay.target.files[0] ?? null;
    fotografDosya.value = dosya;

    if (yeniFotografOnizleme.value) {
        URL.revokeObjectURL(
            yeniFotografOnizleme.value
        );
    }

    yeniFotografOnizleme.value = dosya
        ? URL.createObjectURL(dosya)
        : null;
}
```

`olay.target`, olayı oluşturan input elementidir. `olay.target.files[0]`, seçilen ilk dosyadır.

`URL.createObjectURL(dosya)`, dosya sunucuya gitmeden tarayıcı içinde geçici bir adres üretir. Yeni dosya seçilince eski adres `URL.revokeObjectURL(...)` ile erken serbest bırakılır.

## 19. `computed` ve `watch`: Aynı Bileşende URL Değişmesi

Çizelge bileşeni hem şube hem öğretmen çizelgesini gösterir.

**Dosya: `resources/js/pages/CizelgeIzgara.vue`**

```js
const tur = computed(() =>
    route.name === 'cizelge-sube'
        ? 'sube'
        : 'ogretmen'
);
```

Rota adı değişince `tur` otomatik yeniden hesaplanır. JavaScript bölümünde `tur.value`, template içinde yalnız `tur` kullanılır.

**Dosya: `resources/js/pages/CizelgeIzgara.vue`**

```js
watch(
    () => [route.name, route.params.id],
    yukle
);
```

`watch`, rota adı veya id değişince `yukle` fonksiyonunu yeniden çalıştırır. Örneğin `/app/cizelge/sube/5` adresinden `/app/cizelge/sube/6` adresine geçildiğinde aynı bileşen kullanılmaya devam eder; `onMounted` tekrar çalışmayacağı için değişikliği `watch` yakalar.

## 20. İç İçe Veri ve Dinamik Anahtar Erişimi

**Dosya: `resources/js/pages/CizelgeIzgara.vue`**

```vue
<div v-if="veri.izgara[gunNo]?.[slot]">
    {{ veri.izgara[gunNo][slot].ust }}
</div>
```

Köşeli parantez dinamik anahtar erişimidir. `gunNo` değişkeni 2 ise `veri.izgara[gunNo]`, `veri.izgara[2]` alanını okur.

`veri.izgara[gunNo]?.[slot]`, önce gün kaydı var mı diye bakar. Gün yoksa optional chaining hata oluşmasını önler.

## 21. Faz 6: Blade ve Vue Neden Aynı Anda Duruyor?

Yeni SPA rotaları `/app/*` altında, eski Blade rotaları ise eski adreslerinde çalışır.

Laravel'in SPA kabuk rotası şöyledir:

**Dosya: `routes/web.php`**

```php
Route::get('/app/{any?}', fn () => view('spa'))
    ->where('any', '.*')
    ->name('spa');
```

`{any?}` opsiyonel dinamik rota bölümüdür. `.*`, `/app` altındaki bütün yolları kabul eder. Bu nedenle Laravel şu adreslerde aynı `spa.blade.php` kabuğunu döndürür:

**İlgili dosya: `routes/web.php`**

```text
/app
/app/seviyeler
/app/program
/app/cizelge/sube/5
```

Gerçek sayfa seçimini bundan sonra Vue Router yapar:

**İlgili dosyalar: `routes/web.php`, `resources/js/router.js`**

```text
Laravel Router
    → Sunucu isteğini karşılar
    → SPA kabuğunu veya API cevabını üretir

Vue Router
    → Tarayıcı içindeki /app/* geçişlerini yönetir
    → Hangi Vue bileşeninin görüneceğini belirler
```

## 22. Bir Düğmeye Basıldığında Uçtan Uca Ne Oluyor?

Yeni seviye ekleme akışını baştan sona izleyelim.

### 1. Kullanıcı input'a değer yazar

**Dosya: `resources/js/pages/Seviyeler.vue`**

```vue
<input v-model="gradeNumber">
```

Bu değer `gradeNumber` ref'ine aktarılır.

### 2. Kullanıcı Kaydet düğmesine basar

**Dosya: `resources/js/pages/Seviyeler.vue`**

```vue
<button @click="kaydet">
    Kaydet
</button>
```

### 3. `kaydet()` API isteğini gönderir

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
await api.post('/seviyeler', {
    grade_number: gradeNumber.value,
});
```

Axios'un `/api` base URL'si nedeniyle gerçek adres `/api/seviyeler` olur.

### 4. Laravel rotası isteği karşılar

**Dosya: `routes/api.php`**

```php
Route::apiResource(
    'seviyeler',
    ApiLevelController::class
);
```

POST isteği controller'ın `store` metoduna gider. Laravel veriyi doğrular; başarıda JSON, doğrulama hatasında 422 ve `errors` nesnesi döndürür.

### 5. Vue listeyi yeniden yükler

**Dosya: `resources/js/pages/Seviyeler.vue`**

```js
await yukle();
```

`seviyeler.value` güncellenince Vue tabloyu otomatik yeniden çizer. Elle `document.querySelector` veya `innerHTML` kullanmak gerekmez.

## 23. ODPYS'de Tekrar Eden Temel Kalıp

ODPYS'deki çoğu Vue ekranı şu kalıbın farklılaşmış halidir:

**İlgili dosyalar: `resources/js/pages/*.vue`**

```text
ref ile state tanımla
        ↓
onMounted ile ilk veriyi çek
        ↓
v-for ile listele
        ↓
v-model ile form alanlarını bağla
        ↓
api.post / api.put ile kaydet
        ↓
api.delete ile sil
        ↓
422 hatalarını catch içinde göster
        ↓
başarıdan sonra listeyi yeniden yükle
```

Bu kalıp Seviyeler, Derslikler, Şubeler, Dersler, Öğretmenler, Okullar ve İzinler ekranlarının temelini oluşturur. Program ve çizelge ekranları aynı temelin üzerine `computed`, `watch` ve daha karmaşık veri yapıları ekler.

## 24. Kod Okuma Sözlüğü

| Kod | Anlamı |
|---|---|
| `const` | Referansı yeniden atanmayacak değişken |
| `let` | Sonradan başka değer atanabilen değişken |
| `ref(...)` | Vue tarafından izlenen reaktif değer |
| `.value` | JavaScript içinde ref'in içindeki değer |
| `computed(...)` | Başka reaktif değerlerden hesaplanan değer |
| `watch(...)` | Bir değer değişince işlem çalıştırma |
| `async` | Promise döndüren/asenkron fonksiyon |
| `await` | Async fonksiyonun devamını sonuç gelene kadar erteleme |
| `?.` | Değer yoksa hata vermeden durma |
| `??` | Sol taraf null/undefined ise sağ tarafı kullanma |
| `===` | Değer ve tür eşitliği |
| `!==` | Değer veya tür eşitsizliği |
| `!` | Mantıksal ters çevirme |
| `&&` | Mantıksal “ve” |
| `a ? b : c` | Kısa if/else |
| `` `${deger}` `` | Metin içine değer yerleştirme |
| `v-if` | Koşullu element oluşturma |
| `v-for` | Listeyi tekrarlama |
| `v-model` | Input ve state'i çift yönlü bağlama |
| `:alan` | JavaScript değeri bind etme |
| `@olay` | Olay dinleme |
| `{{ deger }}` | Değeri metin olarak gösterme |
| `<slot />` | Üst bileşene verilen içeriğin yerleşeceği nokta |
| `<router-view />` | Aktif rota bileşeninin yerleşeceği nokta |
| `<router-link>` | Tam sayfa yenilemeden rota değiştirme |

## 25. Önerilen Dosya Okuma Sırası

Projeyi öğrenirken şu sırayla ilerlemek en anlaşılır akışı verir:

1. `resources/js/spa.js` — Vue uygulaması nasıl başlıyor?
2. `resources/js/App.vue` — Aktif sayfa nereye yerleşiyor?
3. `resources/js/router.js` — URL ve rol hangi sayfayı seçiyor?
4. `resources/js/stores/auth.js` — Oturum bilgisi nasıl tutuluyor?
5. `resources/js/api.js` — Laravel API ile nasıl konuşuluyor?
6. `resources/js/components/Layout.vue` — Ortak menü ve slot nasıl çalışıyor?
7. `resources/js/pages/Seviyeler.vue` — Basit bir CRUD ekranı nasıl kuruluyor?
8. `resources/js/pages/Ogretmenler.vue` — FormData ve dosya yükleme nasıl çalışıyor?
9. `resources/js/pages/CizelgeIzgara.vue` — computed, watch ve iç içe veri nasıl kullanılıyor?
10. `routes/api.php` ve ilgili Laravel controller'ları — Vue isteğinin backend karşılığı nedir?

Bu dosyaları bu sırayla okuyabildiğinde ODPYS'nin Vue tarafındaki ana yapıyı, tekrar eden sözdizimini ve Laravel ile veri alışverişini takip edebilir duruma gelirsin.
