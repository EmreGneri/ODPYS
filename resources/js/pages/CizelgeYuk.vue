<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const veri = ref(null);
const yukleniyor = ref(true);

onMounted(async () => {
    const { data } = await api.get('/cizelge/yuk');
    veri.value = data;
    yukleniyor.value = false;
});
</script>

<template>
    <Layout baslik="Yük Dağılımı Raporu">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <router-link :to="{ name: 'cizelge' }" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Ders Programı
                </router-link>

                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                <template v-else>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500">Toplam ders saati</div>
                                <div class="text-2xl font-semibold">{{ veri.toplamSaat }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">İş düşen öğretmen</div>
                                <div class="text-2xl font-semibold">{{ veri.calisanSayisi }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Boştaki öğretmen</div>
                                <div class="text-2xl font-semibold">{{ veri.bostaSayisi }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">En yüklü</div>
                                <div class="text-2xl font-semibold">{{ veri.enYuklu }} saat</div>
                            </div>
                            <div>
                                <div class="text-gray-500">En az yüklü</div>
                                <div class="text-2xl font-semibold">{{ veri.enAzYuklu }} saat</div>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 mt-4">
                            Fark <strong>{{ veri.enYuklu - veri.enAzYuklu }} saat</strong>.
                            Hakkaniyet branş içinde sağlanıyor: bir branştaki öğretmen sayısı o dersin
                            toplam saatini eşit bölemiyorsa fark kaçınılmaz olur.
                        </p>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr class="border-b text-left text-gray-500">
                                    <th class="px-6 py-3 font-medium">Öğretmen</th>
                                    <th class="px-6 py-3 font-medium">Branş</th>
                                    <th class="px-6 py-3 font-medium">Yük</th>
                                    <th class="px-6 py-3 font-medium w-48">Doluluk</th>
                                    <th class="px-6 py-3 font-medium">Durum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="s in veri.satirlar" :key="s.id">
                                    <td class="px-6 py-3">
                                        <router-link :to="{ name: 'cizelge-ogretmen', params: { id: s.id } }"
                                                     class="text-indigo-600 hover:text-indigo-900">
                                            {{ s.ad }}
                                        </router-link>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">
                                        <span v-if="s.branslar.length">{{ s.branslar.join(', ') }}</span>
                                        <span v-else class="text-amber-600 text-xs">Ders atanmamış</span>
                                    </td>
                                    <td class="px-6 py-3 tabular-nums">{{ s.saat }} / {{ s.kapasite }}</td>
                                    <td class="px-6 py-3">
                                        <div class="h-2 w-full rounded bg-gray-100 overflow-hidden">
                                            <div class="h-2 rounded"
                                                 :class="s.oran >= 90 ? 'bg-red-400' : (s.oran >= 60 ? 'bg-green-500' : 'bg-gray-400')"
                                                 :style="{ width: Math.min(s.oran, 100) + '%' }"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1 tabular-nums">%{{ s.oran }}</div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span v-if="!s.aktif" class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs">Pasif</span>
                                        <span v-else-if="s.saat === 0" class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs">Boşta</span>
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
