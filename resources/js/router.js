import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

/**
 * Faz 2'de sadece iskelet: giris akisi + tek bir "anasayfa" test
 * sayfasi. Gercek yonetici/ogretmen ekranlari (seviyeler, subeler,
 * dersler, ogretmenler, program, cizelge, izinler...) Faz 4'te,
 * ayni desenle (route + sayfa bileseni) teker teker eklenecek.
 */
const routes = [
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

/**
 * Web'deki 'admin'/'teacher' middleware'lerinin frontend karsiligi -
 * ama bu sadece UX icin erken yonlendirme. Gercek yetki kontrolu
 * HER ZAMAN backend'de (API'nin admin/teacher middleware'leri)
 * yapiliyor; bu guard'i bypass etmek hicbir veriye erisim saglamaz.
 */
router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.kontrolEdildi) {
        await auth.kontrolEt();
    }

    if (to.meta.auth && !auth.girisYapilmis) {
        return { name: 'giris-secim' };
    }

    // Faz 4'te ekranlar meta:{ role: 'admin' } gibi isaretlenecek -
    // rolu uymayan kullanici kendi anasayfasina donuyor. Simdiden
    // altyapiyi hazirliyoruz, henuz kullanan bir rota yok.
    if (to.meta.role && auth.rol !== to.meta.role) {
        return { name: 'anasayfa' };
    }
});

export default router;
