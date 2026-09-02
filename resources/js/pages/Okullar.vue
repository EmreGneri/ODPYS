<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const okullar = ref([]);
const aktifId = ref(null);
const yukleniyor = ref(true);
const mesaj = ref('');
const hata = ref('');

const formAcik = ref(false);
const form = ref({
    name: '', city: '', district: '',
    lessons_per_day: 7, lesson_duration: 40, break_duration: 10,
});

async function yukle() {
    const { data } = await api.get('/okullar');
    okullar.value = data.okullar;
    aktifId.value = data.aktifId;
    yukleniyor.value = false;
}

async function aktifYap(okul) {
    const { data } = await api.post(`/okullar/${okul.id}/aktif-yap`);
    mesaj.value = data.mesaj;
    await yukle();
}

async function ekle() {
    hata.value = '';

    try {
        await api.post('/okullar', form.value);
        mesaj.value = 'Okul eklendi ve aktif okul olarak seçildi.';
        formAcik.value = false;
        await yukle();
    } catch (e) {
        const hatalar = e.response?.data?.errors ?? {};
        hata.value = Object.values(hatalar)[0]?.[0] ?? 'Bir hata oluştu.';
    }
}

onMounted(yukle);
</script>

<template>
    <Layout baslik="Okullar">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-if="mesaj" class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ mesaj }}
                </div>
                <div v-if="hata" class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ hata }}
                </div>

                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        Aktif okul, tüm ekranlarda (öğretmenler, dersler, program) görülen ve değiştirilen okuldur.
                    </p>
                    <button @click="formAcik = !formAcik"
                            class="shrink-0 px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                        Yeni Okul Ekle
                    </button>
                </div>

                <div v-if="formAcik" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <p class="text-sm text-gray-600">
                        Okul eklendikten sonra otomatik olarak aktif okul yapılır. Eğitim günlerini
                        "Okul Ayarları" ekranından tanımlayabilirsiniz.
                    </p>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Okul Adı</label>
                            <input v-model="form.name" type="text"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">İl</label>
                            <input v-model="form.city" type="text"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">İlçe</label>
                            <input v-model="form.district" type="text"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Günde Ders</label>
                            <input v-model="form.lessons_per_day" type="number"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ders Süresi (dk)</label>
                            <input v-model="form.lesson_duration" type="number"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mola Süresi (dk)</label>
                            <input v-model="form.break_duration" type="number"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>

                    <div class="flex gap-3 border-t pt-4">
                        <button @click="ekle" class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                            Okulu Ekle
                        </button>
                        <button @click="formAcik = false" class="text-sm text-gray-600 hover:text-gray-900">Vazgeç</button>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                    <p v-if="yukleniyor" class="p-6 text-sm text-gray-500">Yükleniyor...</p>

                    <table v-else class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="border-b text-left text-gray-500">
                                <th class="px-6 py-3 font-medium">Okul</th>
                                <th class="px-6 py-3 font-medium">İl / İlçe</th>
                                <th class="px-6 py-3 font-medium">Seviye</th>
                                <th class="px-6 py-3 font-medium">Öğretmen</th>
                                <th class="px-6 py-3 font-medium">Durum</th>
                                <th class="px-6 py-3 text-right font-medium">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="okul in okullar" :key="okul.id" :class="okul.id === aktifId ? 'bg-indigo-50' : ''">
                                <td class="px-6 py-3 font-medium">{{ okul.name }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ okul.city }} / {{ okul.district }}</td>
                                <td class="px-6 py-3 tabular-nums">{{ okul.seviye_sayisi }}</td>
                                <td class="px-6 py-3 tabular-nums">{{ okul.ogretmen_sayisi }}</td>
                                <td class="px-6 py-3">
                                    <span v-if="okul.id === aktifId"
                                          class="inline-flex px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-xs">Aktif</span>
                                    <span v-else class="text-gray-400 text-xs">—</span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button v-if="okul.id !== aktifId" @click="aktifYap(okul)"
                                            class="text-indigo-600 hover:text-indigo-900 text-sm">Aktif Yap</button>
                                    <span v-else class="text-gray-300 text-sm">Şu an aktif</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Layout>
</template>
