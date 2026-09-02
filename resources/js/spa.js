import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';

/**
 * SPA'nin tek kok giris noktasi. Eski app.js'ten farki: orada her
 * [data-vue-component] div'i icin AYRI bir mini Vue app'i mount
 * ediliyordu (bkz. resources/js/app.js) - burada TEK bir Vue app,
 * TEK bir #app div'ine mount edilip, sayfa gecisini Vue Router
 * tarayici tarafinda yonetiyor (tam sayfa yenilenmeden).
 */
createApp(App)
    .use(createPinia())
    .use(router)
    .mount('#app');
