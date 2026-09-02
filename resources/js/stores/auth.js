import { defineStore } from 'pinia';
import api from '../api';

/**
 * Kimlik/oturum durumu - TOKEN TUTMUYOR (cerez tabanli kimlik
 * dogrulamada buna gerek yok, tarayici otomatik yonetir). Sadece:
 * giris yapmis kullanici bilgisi, rolu, ve arayuzun bilmesi gereken
 * "aktif okul" id'si.
 */
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
        /**
         * "Hala giris yapilmis mi?" kontrolu. GET /api/user cerez
         * gecerliyse kullaniciyi doner, degilse 401 - o zaman
         * kullanici null kalir (giris yapmamis sayilir).
         */
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

        /**
         * Basarili girisden sonra cagrilir - kullanici bilgisini
         * tekrar sunucudan cekmek yerine dogrudan set ediyoruz.
         */
        girisYapildi(kullanici) {
            this.kullanici = kullanici;
            this.kontrolEdildi = true;
        },

        cikisYap() {
            this.kullanici = null;
        },
    },
});
