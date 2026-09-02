<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Layout from '../components/Layout.vue';
import api from '../api';

const route = useRoute();
const router = useRouter();

const izin = ref(null);
const etkilenenDersler = ref([]);
const yukleniyor = ref(true);
const hata = ref('');

function tarih(s) {
    return new Date(s).toLocaleString('tr-TR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

async function yukle() {
    const { data } = await api.get(`/izinler/${route.params.id}`);
    izin.value = data.izin;
    etkilenenDersler.value = data.etkilenenDersler;
    yukleniyor.value = false;
}

async function karar(tip) {
    hata.value = '';

    try {
        await api.post(`/izinler/${route.params.id}/${tip}`);
        router.push({ name: 'izinler' });
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'İşlem yapılamadı.';
    }
}

onMounted(yukle);
</script>

<template>
    <Layout baslik="İzin Talebi">
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <router-link :to="{ name: 'izinler' }" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; İzin Talepleri
                </router-link>

                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>
                <p v-if="hata" class="text-sm text-red-600">{{ hata }}</p>

                <template v-if="izin">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500">Öğretmen</div>
                                <div class="text-gray-900">{{ izin.teacher?.first_name }} {{ izin.teacher?.last_name }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Durum</div>
                                <div class="text-gray-900">{{ izin.status }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Başlangıç</div>
                                <div class="text-gray-900 tabular-nums">{{ tarih(izin.starts_at) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Bitiş</div>
                                <div class="text-gray-900 tabular-nums">{{ tarih(izin.ends_at) }}</div>
                            </div>
                            <div class="col-span-2">
                                <div class="text-gray-500">Gerekçe</div>
                                <div class="text-gray-900">{{ izin.reason || '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sartname 2.7.2: "dersi var/yok gosterimi ile" -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-medium text-gray-900 mb-3">Etkilenen Ders Saatleri</h3>

                        <table v-if="etkilenenDersler.length" class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="py-2">Tarih</th>
                                    <th class="py-2">Gün</th>
                                    <th class="py-2">Saat</th>
                                    <th class="py-2">Şube</th>
                                    <th class="py-2">Ders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(d, i) in etkilenenDersler" :key="i" class="border-b">
                                    <td class="py-2 tabular-nums">{{ d.tarih }}</td>
                                    <td class="py-2">{{ d.gunAdi }}</td>
                                    <td class="py-2 tabular-nums">{{ d.baslangic }}–{{ d.bitis }}</td>
                                    <td class="py-2">{{ d.sube }}</td>
                                    <td class="py-2">{{ d.ders }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <p v-else class="text-sm text-gray-500">
                            Bu izin aralığında öğretmenin programında ders yok.
                        </p>
                    </div>

                    <div v-if="izin.status === 'pending'" class="bg-white shadow-sm sm:rounded-lg p-6 flex gap-3">
                        <button @click="karar('onayla')"
                                class="px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                            Onayla
                        </button>
                        <button @click="karar('reddet')"
                                class="px-4 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                            Reddet
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </Layout>
</template>
