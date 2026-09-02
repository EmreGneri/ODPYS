<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const seviyeler = ref([]);
const yukleniyor = ref(true);
const mesaj = ref('');
const hata = ref('');

// Blade'de ayri bir /seviyeler/create sayfasi vardi; SPA'da tam sayfa
// gecisine gerek yok, ayni sayfada acilip kapanan bir form yeterli.
const formAcik = ref(false);
const duzenlenenId = ref(null);
const gradeNumber = ref('');

async function yukle() {
    const { data } = await api.get('/seviyeler');
    seviyeler.value = data;
    yukleniyor.value = false;
}

function formAc(seviye = null) {
    duzenlenenId.value = seviye?.id ?? null;
    gradeNumber.value = seviye?.grade_number ?? '';
    hata.value = '';
    formAcik.value = true;
}

async function kaydet() {
    hata.value = '';

    try {
        if (duzenlenenId.value) {
            await api.put(`/seviyeler/${duzenlenenId.value}`, { grade_number: gradeNumber.value });
            mesaj.value = 'Seviye güncellendi.';
        } else {
            await api.post('/seviyeler', { grade_number: gradeNumber.value });
            mesaj.value = 'Seviye eklendi.';
        }

        formAcik.value = false;
        await yukle();
    } catch (e) {
        // Laravel'in 422 dogrulama hatasi - Blade'deki $errors bag'inin
        // JSON karsiligi.
        hata.value = e.response?.data?.errors?.grade_number?.[0] ?? 'Bir hata oluştu.';
    }
}

async function sil(seviye) {
    if (! confirm(`${seviye.name} silinsin mi?`)) {
        return;
    }

    mesaj.value = '';
    hata.value = '';

    try {
        await api.delete(`/seviyeler/${seviye.id}`);
        mesaj.value = 'Seviye silindi.';
        await yukle();
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Silinemedi.';
    }
}

onMounted(yukle);
</script>

<template>
    <Layout baslik="Seviyeler">
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
                        Yeni Seviye Ekle
                    </button>
                </div>

                <div v-if="formAcik" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-medium text-gray-900">
                        {{ duzenlenenId ? 'Seviyeyi Düzenle' : 'Yeni Seviye' }}
                    </h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sınıf Numarası (9-12)</label>
                        <input v-model="gradeNumber" type="number" min="9" max="12"
                               class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm text-sm">
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
                                <th class="py-2">Ad</th>
                                <th class="py-2">Şube Sayısı</th>
                                <th class="py-2 text-right">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="seviye in seviyeler" :key="seviye.id" class="border-b">
                                <td class="py-2">{{ seviye.name }}</td>
                                <td class="py-2">{{ seviye.sections_count }}</td>
                                <td class="py-2 text-right space-x-3">
                                    <button @click="formAc(seviye)" class="text-blue-600 hover:underline">Düzenle</button>
                                    <button @click="sil(seviye)" class="text-red-600 hover:underline">Sil</button>
                                </td>
                            </tr>
                            <tr v-if="seviyeler.length === 0">
                                <td colspan="3" class="py-6 text-center text-gray-500">
                                    Henüz seviye tanımlanmamış.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Layout>
</template>
