<script setup>
import { onMounted, ref } from 'vue';
import api from '../api';
import Layout from '../components/Layout.vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

// ───── Bilgi formu ─────
const ad = ref('');
const eposta = ref('');
const bilgiHatalari = ref({});
const bilgiMesaji = ref('');
const bilgiKaydediliyor = ref(false);

// ───── Sifre formu ─────
const mevcutSifre = ref('');
const yeniSifre = ref('');
const yeniSifreTekrar = ref('');
const sifreHatalari = ref({});
const sifreMesaji = ref('');
const sifreKaydediliyor = ref(false);

// Kullanici bilgisi zaten store'da (router guard'i acilista
// /api/user'a sorup doldurmustu) - tekrar sunucuya sormaya gerek yok.
onMounted(() => {
    ad.value = auth.kullanici?.name ?? '';
    eposta.value = auth.kullanici?.email ?? '';
});

async function bilgiKaydet() {
    bilgiHatalari.value = {};
    bilgiMesaji.value = '';
    bilgiKaydediliyor.value = true;

    try {
        const { data } = await api.put('/profil', {
            name: ad.value,
            email: eposta.value,
        });

        bilgiMesaji.value = data.mesaj;

        // Store'u tazele - ust menudeki isim de aninda degissin.
        auth.girisYapildi(data.kullanici);
    } catch (e) {
        // Laravel 422'de { errors: { name: [...], email: [...] } }
        // donuyor - FormRequest bunu kendiligiden yapiyor.
        bilgiHatalari.value = e.response?.data?.errors ?? {};

        if (! Object.keys(bilgiHatalari.value).length) {
            bilgiHatalari.value = { genel: ['Kaydedilemedi.'] };
        }
    } finally {
        bilgiKaydediliyor.value = false;
    }
}

async function sifreKaydet() {
    sifreHatalari.value = {};
    sifreMesaji.value = '';
    sifreKaydediliyor.value = true;

    try {
        const { data } = await api.put('/profil/sifre', {
            current_password: mevcutSifre.value,
            password: yeniSifre.value,
            password_confirmation: yeniSifreTekrar.value,
        });

        sifreMesaji.value = data.mesaj;

        // Sifreler ekranda asili kalmasin.
        mevcutSifre.value = '';
        yeniSifre.value = '';
        yeniSifreTekrar.value = '';
    } catch (e) {
        sifreHatalari.value = e.response?.data?.errors ?? {};

        if (! Object.keys(sifreHatalari.value).length) {
            sifreHatalari.value = { genel: ['Şifre değiştirilemedi.'] };
        }
    } finally {
        sifreKaydediliyor.value = false;
    }
}
</script>

<template>
    <Layout baslik="Profil">
        <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <!-- ───── Hesap bilgileri ───── -->
            <section class="rounded-lg bg-white p-6 shadow-sm">
                <header class="mb-6">
                    <h3 class="text-base font-semibold text-gray-800">Hesap Bilgileri</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Adınızı ve giriş yaptığınız e-posta adresini güncelleyin.
                    </p>
                </header>

                <p v-if="bilgiMesaji" class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
                    {{ bilgiMesaji }}
                </p>

                <form class="max-w-lg space-y-4" @submit.prevent="bilgiKaydet">
                    <div>
                        <label for="ad" class="block text-sm font-medium text-gray-700">Ad Soyad</label>
                        <input
                            id="ad"
                            v-model="ad"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-800 focus:ring-gray-800"
                        >
                        <p v-if="bilgiHatalari.name" class="mt-1 text-xs text-red-600">{{ bilgiHatalari.name[0] }}</p>
                    </div>

                    <div>
                        <label for="eposta" class="block text-sm font-medium text-gray-700">E-posta</label>
                        <input
                            id="eposta"
                            v-model="eposta"
                            type="email"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-800 focus:ring-gray-800"
                        >
                        <p v-if="bilgiHatalari.email" class="mt-1 text-xs text-red-600">{{ bilgiHatalari.email[0] }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            Bu adres aynı zamanda giriş kullanıcı adınızdır.
                        </p>
                    </div>

                    <p v-if="bilgiHatalari.genel" class="text-xs text-red-600">{{ bilgiHatalari.genel[0] }}</p>

                    <button
                        type="submit"
                        :disabled="bilgiKaydediliyor"
                        class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700 disabled:opacity-50"
                    >
                        {{ bilgiKaydediliyor ? 'Kaydediliyor...' : 'Kaydet' }}
                    </button>
                </form>
            </section>

            <!-- ───── Sifre ───── -->
            <section class="rounded-lg bg-white p-6 shadow-sm">
                <header class="mb-6">
                    <h3 class="text-base font-semibold text-gray-800">Şifre Değiştir</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Güvenlik için önce mevcut şifrenizi doğrulamanız gerekir.
                    </p>
                </header>

                <p v-if="sifreMesaji" class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
                    {{ sifreMesaji }}
                </p>

                <form class="max-w-lg space-y-4" @submit.prevent="sifreKaydet">
                    <div>
                        <label for="mevcut-sifre" class="block text-sm font-medium text-gray-700">Mevcut Şifre</label>
                        <input
                            id="mevcut-sifre"
                            v-model="mevcutSifre"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-800 focus:ring-gray-800"
                        >
                        <p v-if="sifreHatalari.current_password" class="mt-1 text-xs text-red-600">
                            {{ sifreHatalari.current_password[0] }}
                        </p>
                    </div>

                    <div>
                        <label for="yeni-sifre" class="block text-sm font-medium text-gray-700">Yeni Şifre</label>
                        <input
                            id="yeni-sifre"
                            v-model="yeniSifre"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-800 focus:ring-gray-800"
                        >
                        <p v-if="sifreHatalari.password" class="mt-1 text-xs text-red-600">
                            {{ sifreHatalari.password[0] }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">En az 8 karakter.</p>
                    </div>

                    <div>
                        <label for="yeni-sifre-tekrar" class="block text-sm font-medium text-gray-700">Yeni Şifre (Tekrar)</label>
                        <input
                            id="yeni-sifre-tekrar"
                            v-model="yeniSifreTekrar"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-800 focus:ring-gray-800"
                        >
                    </div>

                    <p v-if="sifreHatalari.genel" class="text-xs text-red-600">{{ sifreHatalari.genel[0] }}</p>

                    <button
                        type="submit"
                        :disabled="sifreKaydediliyor"
                        class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700 disabled:opacity-50"
                    >
                        {{ sifreKaydediliyor ? 'Değiştiriliyor...' : 'Şifreyi Değiştir' }}
                    </button>
                </form>
            </section>
        </div>
    </Layout>
</template>
