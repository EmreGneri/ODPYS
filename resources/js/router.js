import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

/**
 * Faz 4: tum ekranlar. Her rota, karsiligi olan Blade sayfasinin
 * yerini aliyor - eski Blade rotalari (/seviyeler, /cizelge ...) hala
 * calisir durumda, paralel yayindalar (plan Faz 6).
 *
 * meta.auth  -> giris zorunlu
 * meta.role  -> sadece bu roldeki kullanici girebilir (backend'deki
 *               admin/teacher middleware'lerinin UX karsiligi; asil
 *               kilit her zaman API tarafinda)
 */
const routes = [
    // ───── Giris akisi (giris gerektirmez) ─────
    {
        path: '/app/giris',
        name: 'giris-secim',
        component: () => import('./pages/GirisSecim.vue'),
    },
    {
        path: '/app/giris/:rol(yonetici|ogretmen)',
        name: 'giris',
        component: () => import('./pages/Giris.vue'),
    },
    {
        path: '/app/sifremi-unuttum',
        name: 'sifremi-unuttum',
        component: () => import('./pages/SifremiUnuttum.vue'),
    },
    {
        // :token URL'in kendisinde, e-posta ?email= sorgu parametresinde -
        // AppServiceProvider::boot()'taki ResetPassword::createUrlUsing
        // e-posta linkini bu formatta uretiyor.
        path: '/app/sifre-sifirla/:token',
        name: 'sifre-sifirla',
        component: () => import('./pages/SifreSifirla.vue'),
    },

    // ───── Yonetici ekranlari ─────
    {
        path: '/app/dashboard',
        name: 'dashboard',
        component: () => import('./pages/Dashboard.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/okul',
        name: 'okul-ayarlari',
        component: () => import('./pages/OkulAyarlari.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/okullar',
        name: 'okullar',
        component: () => import('./pages/Okullar.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/seviyeler',
        name: 'seviyeler',
        component: () => import('./pages/Seviyeler.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/subeler',
        name: 'subeler',
        component: () => import('./pages/Subeler.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/derslikler',
        name: 'derslikler',
        component: () => import('./pages/Derslikler.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/dersler',
        name: 'dersler',
        component: () => import('./pages/Dersler.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/ogretmenler',
        name: 'ogretmenler',
        component: () => import('./pages/Ogretmenler.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/ogretmenler/:id/dersler',
        name: 'ogretmen-dersleri',
        component: () => import('./pages/OgretmenDersleri.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/program',
        name: 'program',
        component: () => import('./pages/Program.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/program/duzenle/:sube/:weekday/:slot',
        name: 'program-hucre',
        component: () => import('./pages/ProgramHucre.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/cizelge',
        name: 'cizelge',
        component: () => import('./pages/Cizelge.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/cizelge/yuk',
        name: 'cizelge-yuk',
        component: () => import('./pages/CizelgeYuk.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/cizelge/sube/:id',
        name: 'cizelge-sube',
        component: () => import('./pages/CizelgeIzgara.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/cizelge/ogretmen/:id',
        name: 'cizelge-ogretmen',
        component: () => import('./pages/CizelgeIzgara.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/ucretler',
        name: 'ucretler',
        component: () => import('./pages/Ucretler.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/izinler',
        name: 'izinler',
        component: () => import('./pages/Izinler.vue'),
        meta: { auth: true, role: 'admin' },
    },
    {
        path: '/app/izinler/:id',
        name: 'izin-detay',
        component: () => import('./pages/IzinDetay.vue'),
        meta: { auth: true, role: 'admin' },
    },

    // ───── Ogretmen ekranlari (sartname 2.7.4) ─────
    {
        path: '/app/ogretmen/panel',
        name: 'ogretmen-panel',
        component: () => import('./pages/OgretmenPanel.vue'),
        meta: { auth: true, role: 'teacher' },
    },
    {
        path: '/app/ogretmen/izinler',
        name: 'ogretmen-izinler',
        component: () => import('./pages/OgretmenIzinler.vue'),
        meta: { auth: true, role: 'teacher' },
    },
    {
        path: '/app/ogretmen/izinler/olustur',
        name: 'izin-olustur',
        component: () => import('./pages/IzinOlustur.vue'),
        meta: { auth: true, role: 'teacher' },
    },

    // ───── Her iki rol icin ortak ─────
    // meta.role YOK: profil, hem yoneticinin hem ogretmenin erisebilmesi
    // gereken tek ekran (herkes kendi adini/sifresini degistirebilmeli).
    // Arka uctaki rotalar da (routes/api.php) ayni sebeple admin/teacher
    // alt gruplarinin disinda duruyor.
    {
        path: '/app/profil',
        name: 'profil',
        component: () => import('./pages/Profil.vue'),
        meta: { auth: true },
    },
    {
        // Sartname 3.4 - "Nasil Kullanilir". meta.role YOK ama sayfanin
        // KENDISI role gore farkli icerik gosteriyor (yonetici kurulum
        // sirasini, ogretmen sadece kendi iki ekranini).
        path: '/app/nasil-kullanilir',
        name: 'nasil-kullanilir',
        component: () => import('./pages/NasilKullanilir.vue'),
        meta: { auth: true },
    },

    // ───── /app: role gore dogru anasayfaya yonlendir ─────
    // Burada 'redirect' KULLANMIYORUM: Vue Router'da redirect, guard'dan
    // ONCE calisir - yani auth.kontrolEt() henuz calismamis olur ve
    // kullanicinin rolunu bilemeyiz. Bunun yerine sade bir bilesen
    // veriyoruz, yonlendirmeyi asagidaki guard yapiyor (rol belli olduktan
    // sonra). Bilesen pratikte hic gorunmuyor.
    {
        path: '/app',
        name: 'anasayfa',
        component: () => import('./pages/Anasayfa.vue'),
        meta: { auth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (! auth.kontrolEdildi) {
        await auth.kontrolEt();
    }

    if (to.meta.auth && ! auth.girisYapilmis) {
        return { name: 'giris-secim' };
    }

    // Rolu uymayan kullanici kendi anasayfasina gidiyor. Bu sadece UX -
    // gercek yetki kontrolu API'nin admin/teacher middleware'lerinde.
    if (to.meta.role && auth.rol !== to.meta.role) {
        return { name: auth.rol === 'teacher' ? 'ogretmen-panel' : 'dashboard' };
    }

    // /app: rol belli oldugu icin artik dogru anasayfaya yollayabiliriz.
    if (to.name === 'anasayfa') {
        return { name: auth.rol === 'teacher' ? 'ogretmen-panel' : 'dashboard' };
    }
});

export default router;
