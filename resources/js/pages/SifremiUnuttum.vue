<script setup>
import { ref } from 'vue';
import api from '../api';
import GirisKarti from '../components/GirisKarti.vue';

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
    <GirisKarti
        baslik="Şifremi Unuttum"
        aciklama="E-posta adresinizi girin, şifre sıfırlama linki gönderelim."
    >
        <template #default>
            <p v-if="mesaj" class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
                {{ mesaj }}
            </p>
            <p v-if="hata" class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ hata }}
            </p>

            <form class="space-y-4" @submit.prevent="gonder">
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

                <button
                    type="submit"
                    :disabled="gonderiliyor"
                    class="w-full rounded-md bg-gray-800 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 disabled:opacity-50"
                >
                    {{ gonderiliyor ? 'Gönderiliyor...' : 'Sıfırlama Linki Gönder' }}
                </button>
            </form>
        </template>

        <template #alt>
            <router-link :to="{ name: 'giris-secim' }" class="text-gray-500 hover:text-gray-800 hover:underline">
                &larr; Girişe dön
            </router-link>
        </template>
    </GirisKarti>
</template>
