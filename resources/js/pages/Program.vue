<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const durum = ref(null);
const yukleniyor = ref(true);
const calisiyor = ref(false);
const hata = ref('');
const atamaSonuc = ref(null);
const yerlesimSonuc = ref(null);

async function yukle() {
    const { data } = await api.get('/program');
    durum.value = data;
    yukleniyor.value = false;
}

async function atamaCalistir() {
    hata.value = '';
    calisiyor.value = true;

    try {
        const { data } = await api.post('/program/atama');
        atamaSonuc.value = data;
        await yukle();
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Bir hata oluştu.';
    } finally {
        calisiyor.value = false;
    }
}

async function yerlestirCalistir() {
    hata.value = '';
    calisiyor.value = true;

    try {
        const { data } = await api.post('/program/yerlestir');
        yerlesimSonuc.value = data;
        await yukle();
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Bir hata oluştu.';
    } finally {
        calisiyor.value = false;
    }
}

onMounted(yukle);
</script>

<template>
    <Layout baslik="Program Sihirbazı">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-if="hata" class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ hata }}
                </div>

                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                <template v-else>
                    <div v-if="durum.dersliksizSubeler.length"
                         class="bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded text-sm">
                        <strong>Dersliksiz şubeler var:</strong> {{ durum.dersliksizSubeler.join(', ') }}.
                        Bu şubelerin programı üretilemez, önce derslik atayın.
                    </div>

                    <!-- 1. ADIM -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-800 text-white text-sm">1</span>
                            <h3 class="font-medium text-gray-900">Öğretmen Ataması</h3>
                            <span class="ml-auto text-sm text-gray-500">{{ durum.atamaSayisi }} atama</span>
                        </div>

                        <p class="text-sm text-gray-600">
                            Her şube-ders çiftine, o dersi o seviyede verebilen en az yüklü öğretmen atanır
                            (şartname 2.6.2/3 — hakkaniyet).
                        </p>

                        <button @click="atamaCalistir" :disabled="calisiyor"
                                class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700 disabled:opacity-50">
                            {{ calisiyor ? 'Çalışıyor...' : '1. Adımı Çalıştır' }}
                        </button>

                        <div v-if="atamaSonuc" class="border-t pt-3 text-sm space-y-2">
                            <p class="text-gray-700">
                                <strong>{{ atamaSonuc.atanan }}</strong> atama yapıldı
                                ({{ atamaSonuc.sure_ms }} ms), toplam {{ atamaSonuc.toplam_saat }} ders saati,
                                {{ atamaSonuc.calisan_ogretmen }} öğretmen görevlendirildi.
                            </p>

                            <div v-if="atamaSonuc.atanamayanlar.length">
                                <p class="text-amber-800 font-medium">
                                    Öğretmen atanamayan dersler ({{ atamaSonuc.atanamayanlar.length }})
                                </p>
                                <table class="w-full text-xs mt-1">
                                    <tbody>
                                        <tr v-for="(satir, i) in atamaSonuc.atanamayanlar" :key="i" class="border-b">
                                            <td class="py-1">{{ satir.sube }}</td>
                                            <td class="py-1">{{ satir.ders }}</td>
                                            <td class="py-1 text-gray-600">{{ satir.sebep }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 2. ADIM -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-800 text-white text-sm">2</span>
                            <h3 class="font-medium text-gray-900">Gün / Saat Yerleştirme</h3>
                            <span class="ml-auto text-sm text-gray-500">{{ durum.programSayisi }} ders saati</span>
                        </div>

                        <p class="text-sm text-gray-600">
                            1. adımda belirlenen atamalar, çakışma olmadan gün ve slotlara yerleştirilir.
                            Tekrar çalıştırmak mevcut programı siler ve yeniden üretir (şartname 2.6.2/5).
                        </p>

                        <button @click="yerlestirCalistir" :disabled="calisiyor || durum.atamaSayisi === 0"
                                class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700 disabled:opacity-50">
                            {{ calisiyor ? 'Çalışıyor...' : '2. Adımı Çalıştır' }}
                        </button>

                        <div v-if="yerlesimSonuc" class="border-t pt-3 text-sm space-y-2">
                            <p class="text-gray-700">
                                <strong>{{ yerlesimSonuc.yerlesen }}</strong> / {{ yerlesimSonuc.hedef }} ders saati
                                yerleştirildi ({{ yerlesimSonuc.sure_ms }} ms). Doluluk: %{{ yerlesimSonuc.doluluk }}
                                ({{ yerlesimSonuc.kapasite }} slot kapasite).
                            </p>

                            <div v-if="yerlesimSonuc.atanamayanlar.length">
                                <p class="text-amber-800 font-medium">
                                    Yerleştirilemeyen ders saatleri ({{ yerlesimSonuc.atanamayanlar.length }} kalem)
                                </p>
                                <table class="w-full text-xs mt-1">
                                    <tbody>
                                        <tr v-for="(satir, i) in yerlesimSonuc.atanamayanlar" :key="i" class="border-b">
                                            <td class="py-1">{{ satir.sube }}</td>
                                            <td class="py-1">{{ satir.ders }}</td>
                                            <td class="py-1">{{ satir.saat }} saat</td>
                                            <td class="py-1 text-gray-600">{{ satir.sebep }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <router-link :to="{ name: 'cizelge' }" class="text-sm text-indigo-600 hover:underline">
                            Üretilen programı görüntüle (Çizelge) &rarr;
                        </router-link>
                    </div>
                </template>
            </div>
        </div>
    </Layout>
</template>
