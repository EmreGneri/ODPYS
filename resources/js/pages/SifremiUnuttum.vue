<script setup>
import { ref } from 'vue';
import api from '../api';

const email = ref('');
const mesaj = ref('');
const hata = ref('');
const gonderiliyor = ref(false);

/**
 * Api\PasswordController::forgot (Faz 1'de yazilmisti) - Password
 * facade'i uzerinden mevcut e-posta gonderim mekanizmasini (bugun
 * Gmail SMTP ile kurdugumuz) aynen kullaniyor, hic degismedi.
 */
async function gonder() {
    hata.value = '';
    mesaj.value = '';
    gonderiliyor.value = true;

    try {
        const { data } = await api.post('/forgot-password', { email: email.value });
        mesaj.value = data.message;
    } catch (e) {
        hata.value = e.response?.data?.errors?.email?.[0] ?? 'Bir hata oluştu.';
    } finally {
        gonderiliyor.value = false;
    }
}
</script>

<template>
    <div class="max-w-sm mx-auto mt-24 px-4">
        <h1 class="text-lg font-semibold text-gray-800 mb-2">Şifremi Unuttum</h1>
        <p class="text-sm text-gray-600 mb-6">
            E-posta adresinizi girin, şifre sıfırlama linki gönderelim.
        </p>

        <p v-if="mesaj" class="text-sm text-green-700 mb-4">{{ mesaj }}</p>
        <p v-if="hata" class="text-sm text-red-600 mb-4">{{ hata }}</p>

        <form @submit.prevent="gonder" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">E-posta</label>
                <input v-model="email" type="email" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <button type="submit" :disabled="gonderiliyor"
                    class="w-full px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700 disabled:opacity-50">
                {{ gonderiliyor ? 'Gönderiliyor...' : 'Sıfırlama Linki Gönder' }}
            </button>
        </form>

        <router-link :to="{ name: 'giris-secim' }" class="block mt-4 text-sm text-gray-600 hover:text-gray-900">
            &larr; Girişe dön
        </router-link>
    </div>
</template>
