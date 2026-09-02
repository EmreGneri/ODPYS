<script setup>
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

async function cikisYap() {
    await axios.post('/logout', {}, { headers: { Accept: 'application/json' } });
    auth.cikisYap();
    router.push({ name: 'giris-secim' });
}
</script>

<template>
    <div class="max-w-2xl mx-auto mt-12 px-4">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h1 class="text-lg font-semibold text-gray-800 mb-2">SPA çalışıyor</h1>
            <p class="text-sm text-gray-600">
                Giriş yapan: <strong>{{ auth.kullanici?.name }}</strong>
                ({{ auth.kullanici?.email }}) — rol: <strong>{{ auth.rol }}</strong>
            </p>
            <p class="text-xs text-gray-400 mt-4">
                Bu, Faz 2'nin altyapı testi için konmuş geçici bir sayfa.
                Gerçek yönetici/öğretmen ekranları Faz 4'te buraya gelecek.
            </p>
            <button @click="cikisYap" class="mt-6 text-sm text-red-600 hover:text-red-800">
                Çıkış yap
            </button>
        </div>
    </div>
</template>
