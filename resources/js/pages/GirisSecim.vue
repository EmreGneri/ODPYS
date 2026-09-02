<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();
</script>

<template>
    <div class="max-w-sm mx-auto mt-24 space-y-4 px-4">
        <h1 class="text-lg font-semibold text-gray-800 text-center mb-6">
            Nasıl giriş yapmak istiyorsunuz?
        </h1>

        <button
            class="block w-full text-center px-4 py-3 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700"
            @click="router.push({ name: 'giris', params: { rol: 'yonetici' } })"
        >
            Yönetici Girişi
        </button>

        <button
            class="block w-full text-center px-4 py-3 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50"
            @click="router.push({ name: 'giris', params: { rol: 'ogretmen' } })"
        >
            Öğretmen Girişi
        </button>
    </div>
</template>
