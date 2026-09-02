<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { csrfCerezGarantiEt } from '../api';
import { useAuthStore } from '../stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const email = ref('');
const password = ref('');
const hata = ref('');
const gonderiliyor = ref(false);

const baslik = route.params.rol === 'yonetici' ? 'Yönetici Girişi' : 'Öğretmen Girişi';

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
    <div class="max-w-sm mx-auto mt-24 px-4">
        <h1 class="text-lg font-semibold text-gray-800 mb-6">{{ baslik }}</h1>

        <p v-if="hata" class="text-sm text-red-600 mb-4">{{ hata }}</p>

        <form @submit.prevent="girisYap" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">E-posta</label>
                <input v-model="email" type="email" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Şifre</label>
                <input v-model="password" type="password" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <button type="submit" :disabled="gonderiliyor"
                    class="w-full px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700 disabled:opacity-50">
                {{ gonderiliyor ? 'Giriş yapılıyor...' : 'Giriş Yap' }}
            </button>
        </form>

        <router-link :to="{ name: 'giris-secim' }" class="block mt-4 text-sm text-gray-600 hover:text-gray-900">
            &larr; Giriş türünü değiştir
        </router-link>
    </div>
</template>
