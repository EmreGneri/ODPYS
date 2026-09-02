<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const veri = ref(null);
const yukleniyor = ref(true);

function para(sayi) {
    return Number(sayi).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

onMounted(async () => {
    const { data } = await api.get('/ucretler');
    veri.value = data;
    yukleniyor.value = false;
});
</script>

<template>
    <Layout baslik="Aylık Ücretler">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                <template v-else>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500">Görevli öğretmen</div>
                                <div class="text-2xl font-semibold">{{ veri.gorevliSayisi }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Toplam aylık ödeme</div>
                                <div class="text-2xl font-semibold">{{ para(veri.toplamAylikTutar) }} TL</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Ortalama (öğretmen başı)</div>
                                <div class="text-2xl font-semibold">{{ para(veri.ortalamaAylikTutar) }} TL</div>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 mt-4">
                            Aylık tutar, haftalık ders programındaki saatlerin {{ veri.aylikHafta }} hafta ile
                            çarpılmasından hesaplanır. Bu bir <strong>tahmini gösterge</strong>dir — resmi tatil,
                            yarıyıl/yaz tatili gibi takvim farkları hesaba katılmaz.
                        </p>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr class="border-b text-left text-gray-500">
                                    <th class="px-6 py-3 font-medium">Öğretmen</th>
                                    <th class="px-6 py-3 font-medium">Ders Kırılımı</th>
                                    <th class="px-6 py-3 font-medium">Haftalık Saat</th>
                                    <th class="px-6 py-3 font-medium">Haftalık Tutar</th>
                                    <th class="px-6 py-3 font-medium">Aylık Tutar</th>
                                    <th class="px-6 py-3 font-medium">Durum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="s in veri.satirlar" :key="s.id">
                                    <td class="px-6 py-3">
                                        <router-link :to="{ name: 'cizelge-ogretmen', params: { id: s.id } }"
                                                     class="text-indigo-600 hover:text-indigo-900">{{ s.ad }}</router-link>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">
                                        <ul v-if="s.dersler.length" class="space-y-0.5">
                                            <li v-for="(d, i) in s.dersler" :key="i" class="text-xs">
                                                {{ d.ders }}: {{ d.saat }} saat × {{ para(d.saatlikUcret) }} TL
                                            </li>
                                        </ul>
                                        <span v-else class="text-amber-600 text-xs">Programda dersi yok</span>
                                    </td>
                                    <td class="px-6 py-3 tabular-nums">{{ s.haftalikSaat }}</td>
                                    <td class="px-6 py-3 tabular-nums">{{ para(s.haftalikTutar) }} TL</td>
                                    <td class="px-6 py-3 tabular-nums font-medium">{{ para(s.aylikTutar) }} TL</td>
                                    <td class="px-6 py-3">
                                        <span v-if="!s.aktif" class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs">Pasif</span>
                                        <span v-else-if="s.haftalikSaat === 0" class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs">Boşta</span>
                                        <span v-else class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">Görevli</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>
    </Layout>
</template>
