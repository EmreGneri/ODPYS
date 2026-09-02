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
 * BUG (bulundu ve duzeltildi): burada onceden 401 gorunce
 * window.location.href ile TAM SAYFA yenilemesi yapiliyordu. Bu,
 * router guard'in kendi rutin "giris yapilmis mi?" kontrolunun
 * (GET /api/user - giris yapilmamisken 401 donmesi zaten NORMAL ve
 * BEKLENEN) her calisisinda tetikleniyordu: /app/giris'e her gidiste
 * guard tekrar kontrol ediyor -> 401 -> tam sayfa yenileme -> Pinia
 * hafizasi sifirlaniyor -> guard yeniden calisiyor -> tekrar 401 ->
 * SONSUZ DONGU.
 *
 * Yonlendirme sorumlulugu zaten router.js'deki beforeEach guard'da -
 * o, client-side (sayfa yenilemeden) yonlendiriyor ve sadece auth
 * gerektiren sayfalarda calisiyor. Burada AYRICA bir yonlendirme
 * yapmaya hic gerek yok, sadece hatayi oldugu gibi yukari birakiyoruz.
 * 422 (dogrulama hatasi) zaten hic yakalanmiyordu - onu her form kendi
 * catch bloguyla ele aliyor.
 */
api.interceptors.response.use(
    (response) => response,
    (error) => Promise.reject(error)
);

export default api;
