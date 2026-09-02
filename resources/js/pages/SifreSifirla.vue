<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api';
import GirisKarti from '../components/GirisKarti.vue';

const route = useRoute();
const router = useRouter();

// E-postadaki link buraya token'i URL'in kendisinde, email'i sorgu
// parametresinde tasiyor - AppServiceProvider::boot() bu formatta
// uretiyor (bkz. ResetPassword::createUrlUsing).
const email = ref(route.query.email ?? '');
const password = ref('');
const passwordConfirmation = ref('');
const hata = ref('');
const basarili = ref(false);
const gonderiliyor = ref(false);

async function gonder() {
    hata.value = '';
    gonderiliyor.value = true;

    try {
        await api.post('/reset-password', {
            token: route.params.token,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });

        basarili.value = true;
        setTimeout(() => router.push({ name: 'giris-secim' }), 2000);
    } catch (e) {
        hata.value = e.response?.data?.errors?.password?.[0]
            ?? e.response?.data?.errors?.email?.[0]
            ?? 'Bir hata oluştu.';
    } finally {
        gonderiliyor.value = false;
    }
}
</script>

<template>
    <GirisKarti baslik="Yeni Şifre Belirle" aciklama="Hesabınız için yeni bir şifre oluşturun.">
        <p v-if="basarili" class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
            Şifreniz güncellendi, giriş ekranına yönlendiriliyorsunuz...
        </p>

        <form v-else class="space-y-4" @submit.prevent="gonder">
            <p v-if="hata" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ hata }}
            </p>

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
                <label for="password" class="block text-sm font-medium text-gray-700">Yeni Şifre</label>
                <input
                    id="password"
                    v-model="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-800 focus:ring-gray-800"
                >
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Yeni Şifre (Tekrar)</label>
                <input
                    id="password_confirmation"
                    v-model="passwordConfirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-800 focus:ring-gray-800"
                >
            </div>

            <button
                type="submit"
                :disabled="gonderiliyor"
                class="w-full rounded-md bg-gray-800 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 disabled:opacity-50"
            >
                {{ gonderiliyor ? 'Kaydediliyor...' : 'Şifreyi Güncelle' }}
            </button>
        </form>
    </GirisKarti>
</template>
