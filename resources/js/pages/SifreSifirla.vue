<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api';

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
    <div class="max-w-sm mx-auto mt-24 px-4">
        <h1 class="text-lg font-semibold text-gray-800 mb-6">Yeni Şifre Belirle</h1>

        <p v-if="basarili" class="text-sm text-green-700">
            Şifreniz güncellendi, giriş ekranına yönlendiriliyorsunuz...
        </p>

        <form v-else @submit.prevent="gonder" class="space-y-4">
            <p v-if="hata" class="text-sm text-red-600">{{ hata }}</p>

            <div>
                <label class="block text-sm font-medium text-gray-700">E-posta</label>
                <input v-model="email" type="email" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Yeni Şifre</label>
                <input v-model="password" type="password" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Yeni Şifre (Tekrar)</label>
                <input v-model="passwordConfirmation" type="password" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <button type="submit" :disabled="gonderiliyor"
                    class="w-full px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700 disabled:opacity-50">
                {{ gonderiliyor ? 'Kaydediliyor...' : 'Şifreyi Güncelle' }}
            </button>
        </form>
    </div>
</template>
