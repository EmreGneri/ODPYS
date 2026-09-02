<script setup>
/**
 * Giris akisindaki DORT sayfanin (giris turu secimi, giris formu,
 * sifremi unuttum, sifre sifirlama) ortak kabugu: marka basligi +
 * ortalanmis beyaz kart.
 *
 * Neden ayri bir bilesen? Ayni kart isaretlemesini dort dosyaya
 * kopyalamak yerine tek yerde tutuyoruz - yarin kart tasarimi
 * degisirse tek dosya degisiyor. Ayni fikir Layout.vue'da da var
 * (giris YAPMIS sayfalarin ortak kabugu); bu, onun giris ekrani
 * karsiligi.
 *
 * <slot /> -> kartin govdesi (form)
 * <slot name="alt" /> -> kartin ALTINDA kalan kucuk baglantilar
 */
defineProps({
    baslik: { type: String, required: true },
    aciklama: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center px-4 py-12">
        <div class="mb-6 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gray-800 text-base font-semibold tracking-tight text-white">
                OD
            </div>
            <h1 class="mt-3 text-xl font-semibold text-gray-800">ODPYS</h1>
            <p class="mt-0.5 text-xs text-gray-500">Okul Ders Programı Yönetim Sistemi</p>
        </div>

        <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-base font-semibold text-gray-800">{{ baslik }}</h2>
            <p v-if="aciklama" class="mt-1 text-sm text-gray-500">{{ aciklama }}</p>

            <div class="mt-6">
                <slot />
            </div>
        </div>

        <div class="mt-6 text-center text-sm">
            <slot name="alt" />
        </div>
    </div>
</template>
