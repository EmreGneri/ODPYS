<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Layout from '../components/Layout.vue';
import api from '../api';

const route = useRoute();
const router = useRouter();

const dersler = ref([]);
const seviyeler = ref([]);
const secili = ref({});
const yukleniyor = ref(true);
const mesaj = ref('');

/**
 * Yetkiler "dersId-seviyeId" anahtarli duz bir nesnede tutuluyor -
 * OgretmenDersleriFormu.vue'daki (eski sistem) mantigin aynisi:
 * matriste her kutu icin O(1) arama saglıyor.
 */
function anahtar(dersId, seviyeId) {
    return `${dersId}-${seviyeId}`;
}

async function yukle() {
    const { data } = await api.get(`/ogretmenler/${route.params.id}/dersler`);
    dersler.value = data.dersler;
    seviyeler.value = data.seviyeler;
    secili.value = { ...data.mevcut };
    yukleniyor.value = false;
}

function degistir(dersId, seviyeId) {
    const k = anahtar(dersId, seviyeId);
    secili.value[k] = ! secili.value[k];
}

function satirSec(dersId, deger) {
    seviyeler.value.forEach((s) => {
        secili.value[anahtar(dersId, s.id)] = deger;
    });
}

async function kaydet() {
    // Backend'in bekledigi sekil: yetkiler[dersId][seviyeId] = 1
    const yetkiler = {};

    Object.entries(secili.value).forEach(([k, v]) => {
        if (! v) return;
        const [dersId, seviyeId] = k.split('-');
        yetkiler[dersId] = yetkiler[dersId] ?? {};
        yetkiler[dersId][seviyeId] = 1;
    });

    const { data } = await api.put(`/ogretmenler/${route.params.id}/dersler`, { yetkiler });
    mesaj.value = data.mesaj;

    setTimeout(() => router.push({ name: 'ogretmenler' }), 1200);
}

onMounted(yukle);
</script>

<template>
    <Layout baslik="Öğretmen Ders Yetkileri">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <router-link :to="{ name: 'ogretmenler' }" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Öğretmenler
                </router-link>

                <div v-if="mesaj" class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ mesaj }}
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                    <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                    <template v-else>
                        <p class="text-sm text-gray-600 mb-4">
                            Şartname 2.4 — atama <strong>seviye bazlı</strong>: bir öğretmen aynı dersi
                            sadece işaretlenen seviyelerde verebilir.
                        </p>

                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="py-2 pr-4">Ders</th>
                                    <th v-for="s in seviyeler" :key="s.id" class="py-2 px-3 text-center">{{ s.name }}</th>
                                    <th class="py-2 px-3 text-center">Tümü</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in dersler" :key="d.id" class="border-b">
                                    <td class="py-2 pr-4">{{ d.name }}</td>
                                    <td v-for="s in seviyeler" :key="s.id" class="py-2 px-3 text-center">
                                        <input type="checkbox"
                                               :checked="!!secili[`${d.id}-${s.id}`]"
                                               @change="degistir(d.id, s.id)"
                                               class="rounded border-gray-300">
                                    </td>
                                    <td class="py-2 px-3 text-center space-x-2">
                                        <button @click="satirSec(d.id, true)" class="text-xs text-indigo-600 hover:underline">hepsi</button>
                                        <button @click="satirSec(d.id, false)" class="text-xs text-gray-500 hover:underline">temizle</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="mt-6 border-t pt-6">
                            <button @click="kaydet"
                                    class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                                Kaydet
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </Layout>
</template>
