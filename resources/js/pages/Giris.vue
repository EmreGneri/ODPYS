<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { csrfCerezGarantiEt } from '../api';
import { useAuthStore } from '../stores/auth';
import GirisKarti from '../components/GirisKarti.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const email = ref('');
const password = ref('');
const hata = ref('');
const gonderiliyor = ref(false);

// computed: rol URL'den geliyor, kullanici "giris turunu degistir"
// linkiyle geri gidip obur role gecebiliyor - o zaman baslik da
// kendiliginden guncellensin diye sabit degil, hesaplanan deger.
const yonetici = computed(() => route.params.rol === 'yonetici');
const baslik = computed(() => (yonetici.value ? 'Yönetici Girişi' : 'Öğretmen Girişi'));
const harf = computed(() => (yonetici.value ? 'Y' : 'Ö'));

/**
 * Bu, /api/* degil, mevcut WEB giris rotasina (/giris/{rol}) atilan
 * duz bir istek - bugun eklenen AuthenticatedSessionController hic
 * degismeden calisiyor. Basarisizlikta (yanlis sifre, yanlis
 * sayfadan giris) Laravel'in ValidationException'i, "Accept:
 * application/json" basligini gorunce OTOMATIK 422 + JSON hatasi
 * donduruyor - controller kodunda hicbir degisiklik gerekmedi.
 * Basarida ise controller bir redirect donuyor; axios bunu sessizce
 * takip ediyor, biz onu kullanmiyoruz - gercek dogrulamayi asagida
 * auth.kontrolEt() ile /api/user'a sorarak yapiyoruz.
 */
async function girisYap() {
    hata.value = '';
    gonderiliyor.value = true;

    try {
        await csrfCerezGarantiEt();

        await axios.post(`/giris/${route.params.rol}`, {
            email: email.value,
            password: password.value,
        }, {
            headers: { Accept: 'application/json' },
        });

        await auth.kontrolEt();
        router.push({ name: 'anasayfa' });
    } catch (e) {
        hata.value = e.response?.data?.errors?.email?.[0]
            ?? e.response?.data?.message
            ?? 'Giriş başarısız.';
    } finally {
        gonderiliyor.value = false;
    }
}
</script>

<template>
    <GirisKarti :baslik="baslik" aciklama="Hesap bilgilerinizle devam edin.">
        <template #default>
            <div class="mb-6 flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-gray-800 text-xs font-semibold text-white">
                    {{ harf }}
                </span>
                <span class="text-xs text-gray-500">
                    {{ yonetici ? 'Yönetici hesabı' : 'Öğretmen hesabı' }}
                </span>
            </div>

            <p v-if="hata" class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ hata }}
            </p>

            <form class="space-y-4" @submit.prevent="girisYap">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">E-posta</label>
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        required
                        autocomplete="username"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-800 focus:ring-gray-800"
                    >
                </div>

                <div>
                    <div class="flex items-baseline justify-between">
                        <label for="password" class="block text-sm font-medium text-gray-700">Şifre</label>
                        <router-link
                            :to="{ name: 'sifremi-unuttum' }"
                            class="text-xs text-gray-500 hover:text-gray-800 hover:underline"
                        >
                            Şifremi unuttum
                        </router-link>
                    </div>
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-800 focus:ring-gray-800"
                    >
                </div>

                <button
                    type="submit"
                    :disabled="gonderiliyor"
                    class="w-full rounded-md bg-gray-800 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 disabled:opacity-50"
                >
                    {{ gonderiliyor ? 'Giriş yapılıyor...' : 'Giriş Yap' }}
                </button>
            </form>
        </template>

        <template #alt>
            <router-link :to="{ name: 'giris-secim' }" class="text-gray-500 hover:text-gray-800 hover:underline">
                &larr; Giriş türünü değiştir
            </router-link>
        </template>
    </GirisKarti>
</template>
