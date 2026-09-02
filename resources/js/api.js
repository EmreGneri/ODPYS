import axios from 'axios';

/**
 * Sanctum SPA (cerez/oturum) modu icin gerekli iki ayar:
 * - withCredentials: her istekte tarayicinin oturum cerezini gonder
 * - withXSRFToken: Laravel'in CSRF korumasini otomatik uygula
 *   (axios, tarayicidaki XSRF-TOKEN cerezini okuyup X-XSRF-TOKEN
 *   basligina kendisi ekliyor)
 *
 * Global axios.defaults'a yaziyoruz cunku hem /api/* cagrilari
 * (asagidaki 'api' instance) hem web'in kendi giris/cikis rotalarina
 * (/giris/{rol}, /logout - Faz 3'te kullanilacak) atilan istekler
 * ayni cerez/CSRF davranisini paylasmali.
 */
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

let csrfCerezKuruldu = false;

/**
 * Sanctum SPA modunun zorunlu ilk adimi: ilk kimlik dogrulamali
 * istekten once bir kere bu adrese gidilip XSRF-TOKEN cerezinin
 * kurulmus olmasi lazim. Tekrar tekrar cagirmamak icin bir bayrakla
 * koruyoruz - her istekte bunu tekrar yapmaya gerek yok.
 */
export async function csrfCerezGarantiEt() {
    if (csrfCerezKuruldu) {
        return;
    }

    await axios.get('/sanctum/csrf-cookie');
    csrfCerezKuruldu = true;
}

/**
 * Butun /api/* cagrilari icin merkezi axios instance'i. Her yeni
 * sayfa/bilesen bunu import edip kullaniyor - token/cerez/hata
 * mantigini tek yerde tutmak icin.
 */
const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});

api.interceptors.request.use(async (config) => {
    await csrfCerezGarantiEt();
    return config;
});

/**
 * 401 (kimlik dogrulanmamis) her yerde ayni sekilde ele alinsin diye
 * merkezi bir yakalayici: kullaniciyi giris sayfasina yonlendiriyor.
 * 422 (dogrulama hatasi) burada YAKALANMIYOR - onu her form kendi
 * catch bloguyla, kullaniciya hangi alanin yanlis oldugunu gostermek
 * icin ayri ele alacak.
 */
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            window.location.href = '/app/giris';
        }

        return Promise.reject(error);
    }
);

export default api;
