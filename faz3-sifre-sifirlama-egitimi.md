# Faz 3: Şifre Sıfırlama Akışı — Sıfırdan Eğitim + Uygulama

Bu doküman, Faz 3'te yapılanları **kullanılan her kavramı sıfırdan** anlatarak açıklıyor. Faz 2'de kurulan altyapının (Vue Router, Pinia, axios) üzerine inşa ediyor — o dokümanı okuduysan buradaki terimler tanıdık gelecek.

---

## 1. Neden Faz 3 Bu Kadar Kısaydı?

Bir önceki soruda söylediğim gibi: giriş akışının (giriş formu, 401 yönlendirmesi, route guard) **büyük kısmı zaten Faz 2'de** birlikte yapılmıştı — çünkü SPA'nın altyapısını kurarken zaten çalışan bir giriş ekranına ihtiyacımız vardı, test etmek için. Faz 3'te gerçekten **eksik olan tek şey** şifremi unuttum / şifre sıfırlama akışıydı — API tarafı (`Api\PasswordController`) zaten Faz 1'de yazılmıştı ama onu çağıran bir SPA sayfası yoktu.

---

## 2. Şifre Sıfırlama Nasıl Çalışır (Laravel'in Genel Mantığı)

Adımlar sırayla:

1. Kullanıcı e-postasını girer, "gönder" der.
2. Laravel, veritabanına **rastgele, tahmin edilemez bir token** kaydeder (bu token'ın kendisi değil, **hash'lenmiş** hâli saklanır — şifreler gibi).
3. Kullanıcıya bir e-posta gider, içinde `token`'ın **düz hâli** olan bir link vardır: `https://site.com/reset-password/abc123...?email=kullanici@site.com`
4. Kullanıcı linke tıklar, yeni şifresini girer.
5. Form, `token` + `email` + yeni şifreyi sunucuya gönderir. Sunucu, gelen token'ı hash'leyip veritabanındakiyle karşılaştırır — eşleşirse şifre değişir.

**Neden token e-postada düz, veritabanında hash'li?** Aynı sebep şifrelerle aynı: veritabanı bir şekilde ele geçirilirse, saldırgan token'ların **hash'ini** görür, gerçek token'ı üretemez, kimsenin şifresini sıfırlayamaz.

---

## 3. `AppServiceProvider` Nedir, `createUrlUsing` Ne İşe Yarıyor?

Laravel, her uygulamanın açılışında bazı "kurulum" kodlarının çalışmasına ihtiyaç duyar (örn. "bu paketi şu ayarla kullan"). Bu kodun yazıldığı standart yer **Service Provider**'lardır. `AppServiceProvider`, Laravel projelerinde **varsayılan olarak gelen**, en genel amaçlı olanı — "başka hiçbir yere ait olmayan küçük kurulum kodları buraya" mantığıyla.

**Sorun neydi?** Laravel'in hazır şifre sıfırlama e-postası (`ResetPassword` bildirimi), varsayılan olarak linki **eski web sayfasına** (`/reset-password/{token}`) yönlendiriyordu — bizim yeni SPA sayfamıza değil. Bu davranışı değiştirmek için Laravel'in bize verdiği resmi bir "kanca" (hook) var: `ResetPassword::createUrlUsing(...)`.

**Dosya: `app/Providers/AppServiceProvider.php`** (değişen kısım)

```php
use Illuminate\Auth\Notifications\ResetPassword;

public function boot(): void
{
    // Sifre sifirlama e-postasindaki link artik SPA'nin sayfasina
    // gidiyor (eski /reset-password/{token} web rotasi hala calisir,
    // sadece yeni e-postalar artik SPA'ya isaret ediyor).
    ResetPassword::createUrlUsing(function ($notifiable, string $token) {
        return config('app.url') . '/app/sifre-sifirla/' . $token
            . '?email=' . urlencode($notifiable->getEmailForPasswordReset());
    });
}
```

**Satır satır:**

- `ResetPassword::createUrlUsing(function (...) { ... })` — Laravel'e "e-posta linkini oluştururken artık şu fonksiyonu kullan" diyoruz. Bu, **statik bir metot çağrısı** — `ResetPassword` sınıfının kendisine "bundan sonra hep bu kuralı kullan" demek, her kullanıcı için ayrı ayrı çağrılmıyor, **bir kere** kaydediliyor (uygulama her açıldığında).
- `$notifiable` — bildirim kimin için gönderiliyorsa o kullanıcı (`User` modeli).
- `$token` — Laravel'in az önce ürettiği, düz (hash'lenmemiş) token.
- `config('app.url')` — `.env`'deki `APP_URL` değeri, yani sitenin adresi.
- `urlencode(...)` — e-posta adresini URL'e güvenle koyabilmek için özel karakterleri (`@`, boşluk gibi) kodluyor. `admin@okul.edu.tr` → `admin%40okul.edu.tr` oluyor URL'de.

**Neden eski web rotasını silmedik?** Çünkü daha önce gönderilmiş e-postalardaki linkler hâlâ eski adrese işaret ediyor — o rotayı silersek, o e-postaları alan insanlar kırık bir link bulur. Sadece **bundan sonra üretilecek yeni** e-postaların adresini değiştirdik, geçmişe dokunmadık. Bu, "paralel çalışma" stratejimizin (Faz 6) doğal bir uzantısı.

**Doğruladığım şey:** Laravel'in içindeki `createUrlUsing` callback'ini doğrudan çağırıp, ürettiği URL'in gerçekten beklediğimiz formatta olduğunu kanıtladım:

```
http://localhost:8000/app/sifre-sifirla/test-token-123?email=admin%40okul.edu.tr
```

---

## 4. Şifremi Unuttum Sayfası

**Dosya: `resources/js/pages/SifremiUnuttum.vue`** (yeni)

```vue
<script setup>
import { ref } from 'vue';
import api from '../api';

const email = ref('');
const mesaj = ref('');
const hata = ref('');

async function gonder() {
    hata.value = '';
    mesaj.value = '';

    try {
        const { data } = await api.post('/forgot-password', { email: email.value });
        mesaj.value = data.message;
    } catch (e) {
        hata.value = e.response?.data?.errors?.email?.[0] ?? 'Bir hata oluştu.';
    }
}
</script>
```

Burada yeni bir kavram yok — Faz 2'de öğrendiğin `api` instance'ı (merkezi axios, `/api` önekiyle, CSRF/çerez otomatik) aynen kullanılıyor. `api.post('/forgot-password', ...)` gerçekte `POST /api/forgot-password`'a gidiyor — bu, **Faz 1'de** yazdığımız `Api\PasswordController::forgot()` metodunu tetikliyor. O metot da Laravel'in hazır `Password::sendResetLink(...)` fonksiyonunu çağırıyor — e-postayı gönderen kod **hiç değişmedi**, biz sadece JSON bir cevap ekledik üstüne.

**`e.response?.data?.errors?.email?.[0]` ifadesi ne yapıyor?** Laravel'in doğrulama hatası formatı şöyledir:

```json
{ "errors": { "email": ["Bu e-posta adresiyle kayıtlı kullanıcı bulunamadı."] } }
```

`errors.email` bir **dizi** (birden fazla hata olabilir aynı alan için), `[0]` ilkini alıyor. `?.` işaretleri ("optional chaining") — zincirdeki herhangi bir parça `undefined`/`null` ise hata fırlatmak yerine sonucu `undefined` yapıyor, JavaScript çökmüyor. Yani "hata objesinde `errors.email` yoksa patlama, sadece boş dön" demek.

---

## 5. Şifre Sıfırlama Sayfası

**Dosya: `resources/js/pages/SifreSifirla.vue`** (yeni)

```vue
<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api';

const route = useRoute();
const router = useRouter();

// E-postadaki link buraya token'i URL'in kendisinde, email'i sorgu
// parametresinde tasiyor.
const email = ref(route.query.email ?? '');
const password = ref('');
const passwordConfirmation = ref('');

async function gonder() {
    try {
        await api.post('/reset-password', {
            token: route.params.token,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });

        setTimeout(() => router.push({ name: 'giris-secim' }), 2000);
    } catch (e) {
        // ...
    }
}
</script>
```

**`route.params.token` ile `route.query.email` arasındaki fark — önemli bir Vue Router kavramı:**

- **`route.params`** — URL'in **yol** kısmındaki değişkenler. `router.js`'de rotayı `/app/sifre-sifirla/:token` diye tanımladık — `:token` bir parametre, `route.params.token` onu okuyor. URL `/app/sifre-sifirla/abc123` ise, `route.params.token === 'abc123'`.
- **`route.query`** — URL'in `?` sonrasındaki **sorgu parametreleri**. URL `...?email=admin@okul.edu.tr` ise, `route.query.email === 'admin@okul.edu.tr'`.

Bu ayrım, tam olarak `AppServiceProvider`'da ürettiğimiz URL formatıyla eşleşiyor: `/app/sifre-sifirla/{token}?email=...` — token yol'da, email sorguda.

`setTimeout(..., 2000)` — 2 saniye bekleyip kullanıcıyı giriş ekranına yönlendiriyor, böylece "şifreniz güncellendi" mesajını okuyacak zamanı oluyor, aniden sayfa değişmiyor.

---

## 6. `router.js`'e Eklenen Rotalar

**Dosya: `resources/js/router.js`** (eklenen kısım)

```js
{
    path: '/app/sifremi-unuttum',
    name: 'sifremi-unuttum',
    component: () => import('./pages/SifremiUnuttum.vue'),
},
{
    path: '/app/sifre-sifirla/:token',
    name: 'sifre-sifirla',
    component: () => import('./pages/SifreSifirla.vue'),
},
```

Faz 2'den hatırla: `component: () => import(...)` **tembel yükleme** demekti — bu iki sayfanın kodu, kullanıcı gerçekten bu adrese gelene kadar hiç indirilmiyor. `npm run build` çıktısında bunu görebilirsin: `SifremiUnuttum-DHo1upiP.js` (1.69 kB) ve `SifreSifirla-MM0GaSK8.js` (2.16 kB) — **ayrı, küçük** dosyalar, ana `spa.js` paketine (83 kB) dahil değiller.

---

## 7. Küçük Bir Ek: Rol Bazlı Yönlendirme Altyapısı

Faz 3 sırasında, gelecekte (Faz 4'te) işimize yarayacak küçük bir iyileştirme daha yaptım — henüz kullanan bir ekran yok ama altyapı hazır.

**Dosya: `resources/js/router.js`** (guard'a eklenen kısım)

```js
router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.kontrolEdildi) {
        await auth.kontrolEt();
    }

    if (to.meta.auth && !auth.girisYapilmis) {
        return { name: 'giris-secim' };
    }

    // Faz 4'te ekranlar meta:{ role: 'admin' } gibi isaretlenecek -
    // rolu uymayan kullanici kendi anasayfasina donuyor.
    if (to.meta.role && auth.rol !== to.meta.role) {
        return { name: 'anasayfa' };
    }
});
```

**Neden şimdiden ekledik?** Faz 4'te "Seviyeler" gibi bir sayfa yazınca, onu `meta: { auth: true, role: 'admin' }` diye işaretleyeceğiz — bir öğretmen o URL'e (JavaScript konsolundan bile) gitmeye çalışsa, guard onu otomatik olarak kendi anasayfasına geri gönderecek. Bu satırı şimdi eklemek, Faz 4'te her yeni sayfada tekrar hatırlamamıza gerek bırakmıyor — mekanizma zaten hazır, sadece her sayfaya `role` etiketini eklemek yeterli olacak.

**Yine hatırlatma (Faz 2'den de bildiğin ilke):** bu bir **kullanıcı deneyimi** kolaylığı, güvenlik burada değil. Gerçek kilit, API'deki `admin`/`teacher` middleware'lerinde — onlar zaten Faz 1'de her endpoint'e uygulanmış durumda.

---

## 8. Uçtan Uca Doğrulama — Gerçekten Çalıştığının Kanıtı

Kod yazmak yetmez, gerçekten işlediğini kanıtlamak gerekir. Şunu yaptım (gerçek veritabanı işlemi, ama bir "transaction" içinde — yani test bitince otomatik geri alındı, hiçbir gerçek veri değişmedi):

1. Gerçek bir öğretmen kullanıcısı için **gerçek bir sıfırlama token'ı** ürettim (Laravel'in kendi `Password::createToken()` fonksiyonuyla — e-posta gönderme adımını atlayıp, token üretme + doğrulama mekanizmasının kendisini test ettim).
2. `Api\PasswordController::reset()` metoduna bu token'ı, e-postayı ve yeni bir şifreyi gönderdim.
3. Sonucu kontrol ettim: **kullanıcının veritabanındaki şifre hash'i gerçekten değişti**, VE yeni şifreyle `Hash::check()` yapınca **doğru** çıktı.

Bu, sadece "kod hatasız çalıştı" değil, "**gerçekten doğru işi yaptı**" demek — sahte bir başarı mesajı değil, gerçek bir yan etki (şifre değişikliği) doğrulandı.

---

## 9. Özet Tablo

| Ne | Dosya | Durum |
|---|---|---|
| Sıfırlama e-posta linkinin hedefi | `app/Providers/AppServiceProvider.php` | Değişti |
| Şifremi unuttum formu | `resources/js/pages/SifremiUnuttum.vue` | Yeni |
| Yeni şifre belirleme formu | `resources/js/pages/SifreSifirla.vue` | Yeni |
| İki yeni rota + rol-guard altyapısı | `resources/js/router.js` | Değişti |
| "Şifremi unuttum" linki | `resources/js/pages/Giris.vue` | Değişti |
| Backend (forgot/reset endpoint'leri) | `app/Http/Controllers/Api/PasswordController.php` | Faz 1'de zaten vardı, dokunulmadı |

---

## 10. Sırada Ne Var

Faz 0-1-2-3 tamamlandı — SPA'nın **temeli** (kimlik doğrulama, API, routing altyapısı) artık sağlam. Sırada **Faz 4**: gerçek yönetici/öğretmen ekranlarının (Seviyeler, Şubeler, Dersler, Öğretmenler, Program, Çizelge, İzinler) hepsinin, bugüne kadar öğrendiğimiz **aynı kalıpla** (route + sayfa bileşeni + `api` ile veri çekme) tek tek yazılması. Bu, planın en büyük ve en uzun fazı.
