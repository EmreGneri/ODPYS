<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const derslikler = ref([]);
const yukleniyor = ref(true);
const mesaj = ref('');
const hata = ref('');

const formAcik = ref(false);
const duzenlenenId = ref(null);
const ad = ref('');

async function yukle() {
    const { data } = await api.get('/derslikler');
    derslikler.value = data;
    yukleniyor.value = false;
}

function formAc(derslik = null) {
    duzenlenenId.value = derslik?.id ?? null;
    ad.value = derslik?.name ?? '';
    hata.value = '';
    formAcik.value = true;
}

async function kaydet() {
    hata.value = '';

    try {
        if (duzenlenenId.value) {
            await api.put(`/derslikler/${duzenlenenId.value}`, { name: ad.value });
            mesaj.value = 'Derslik güncellendi.';
        } else {
            await api.post('/derslikler', { name: ad.value });
            mesaj.value = 'Derslik eklendi.';
        }

        formAcik.value = false;
        await yukle();
    } catch (e) {
        hata.value = e.response?.data?.errors?.name?.[0] ?? 'Bir hata oluştu.';
    }
}

async function sil(derslik) {
    if (! confirm('Bu dersliği silmek istediğinize emin misiniz?')) {
        return;
    }

    mesaj.value = '';
    hata.value = '';

    try {
        await api.delete(`/derslikler/${derslik.id}`);
        mesaj.value = 'Derslik silindi.';
        await yukle();
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Silinemedi.';
    }
}

onMounted(yukle);
</script>

<template>
    <Layout baslik="Derslikler">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-if="mesaj" class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ mesaj }}
                </div>
                <div v-if="hata" class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ hata }}
                </div>

                <div>
                    <button @click="formAc()"
                            class="inline-block px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                        Yeni Derslik Ekle
                    </button>
                </div>

                <div v-if="formAcik" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-medium text-gray-900">
                        {{ duzenlenenId ? 'Dersliği Düzenle' : 'Yeni Derslik' }}
                    </h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Derslik Adı</label>
                        <input v-model="ad" type="text"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>

                    <div class="flex gap-3">
                        <button @click="kaydet"
                                class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                            Kaydet
                        </button>
                        <button @click="formAcik = false" class="text-sm text-gray-600 hover:text-gray-900">
                            Vazgeç
                        </button>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="py-2">Derslik</th>
                                <th class="py-2">Durum</th>
                                <th class="py-2 text-right">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="derslik in derslikler" :key="derslik.id" class="border-b">
                                <td class="py-2">{{ derslik.name }}</td>
                                <td class="py-2">
                                    <span v-if="derslik.section_count > 0" class="text-gray-500">Kullanımda</span>
                                    <span v-else class="text-green-600">Boşta</span>
                                </td>
                                <td class="py-2 text-right space-x-3">
                                    <button @click="formAc(derslik)" class="text-blue-600 hover:underline">Düzenle</button>
                                    <button @click="sil(derslik)" class="text-red-600 hover:underline">Sil</button>
                                </td>
                            </tr>
                            <tr v-if="derslikler.length === 0">
                                <td colspan="3" class="py-6 text-center text-gray-500">
                                    Henüz derslik tanımlanmamış.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Layout>
</template>
