<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const veri = ref(null);
const yukleniyor = ref(true);

onMounted(async () => {
    const { data } = await api.get('/ogretmen/panel');
    veri.value = data;
    yukleniyor.value = false;
});
</script>

<template>
    <Layout :baslik="veri ? `Merhaba, ${veri.ogretmen.first_name}` : 'Panelim'">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                <template v-else>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500">Ad Soyad</div>
                                <div class="font-medium">{{ veri.ogretmen.first_name }} {{ veri.ogretmen.last_name }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">E-posta</div>
                                <div class="font-medium">{{ veri.ogretmen.email }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Branş</div>
                                <div class="font-medium">
                                    {{ veri.branslar.length ? veri.branslar.join(', ') : 'Atanmamış' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-gray-500">Haftalık Yük</div>
                                <div class="font-medium tabular-nums">
                                    {{ veri.toplamSaat }} / {{ veri.ogretmen.max_weekly_hours }} saat
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-6 flex items-center justify-between flex-wrap gap-4">
                        <div class="text-sm text-gray-600">
                            <span v-if="veri.bekleyenIzinSayisi > 0" class="text-amber-700 font-medium">
                                {{ veri.bekleyenIzinSayisi }} bekleyen izin talebiniz var.
                            </span>
                            <span v-else>Bekleyen izin talebiniz yok.</span>
                        </div>

                        <div class="flex gap-3">
                            <router-link :to="{ name: 'ogretmen-izinler' }"
                                         class="text-sm text-indigo-600 hover:text-indigo-900">İzin Taleplerim</router-link>
                            <router-link :to="{ name: 'izin-olustur' }"
                                         class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                                Yeni İzin Talebi
                            </router-link>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-medium text-gray-900 mb-4">Haftalık Ders Programım</h3>

                        <p v-if="!Object.keys(veri.gunler).length" class="text-sm text-gray-500">
                            Program henüz üretilmemiş.
                        </p>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="px-3 py-3 text-left font-medium text-gray-500 w-28">Saat</th>
                                        <th v-for="(gunAdi, gunNo) in veri.gunler" :key="gunNo"
                                            class="px-3 py-3 text-left font-medium text-gray-500">{{ gunAdi }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="slot in veri.slotlar" :key="slot">
                                        <td class="px-3 py-2 align-top">
                                            <div class="font-medium text-gray-700">{{ slot }}. ders</div>
                                        </td>
                                        <td v-for="(gunAdi, gunNo) in veri.gunler" :key="gunNo" class="px-3 py-2 align-top">
                                            <!-- Renkler cizelge ekraniyla ayni: kirmizi =
                                                 izinli, sari = takasla saati degismis,
                                                 gri = normal. -->
                                            <div v-if="veri.izgara[gunNo]?.[slot]"
                                                 class="rounded border px-2 py-1.5"
                                                 :class="veri.izgara[gunNo][slot].izinli
                                                    ? 'border-red-300 bg-red-50'
                                                    : (veri.izgara[gunNo][slot].takasli
                                                        ? 'border-amber-300 bg-amber-50'
                                                        : 'border-gray-200 bg-gray-50')">
                                                <template v-if="veri.izgara[gunNo][slot].izinli">
                                                    <div class="text-xs font-medium text-red-800">İzinli</div>
                                                    <div class="text-xs text-red-700">
                                                        Normalde: {{ veri.izgara[gunNo][slot].ust }} · {{ veri.izgara[gunNo][slot].alt }}
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <div class="font-medium text-gray-800">{{ veri.izgara[gunNo][slot].ust }}</div>
                                                    <div class="text-xs text-gray-500">{{ veri.izgara[gunNo][slot].alt }}</div>

                                                    <div v-if="veri.izgara[gunNo][slot].takasli"
                                                         class="mt-1 text-xs text-amber-800">
                                                        {{ veri.izgara[gunNo][slot].takasNot }}
                                                    </div>
                                                </template>
                                                <div class="text-xs text-gray-400 tabular-nums mt-0.5">
                                                    {{ veri.saatler[gunNo]?.[slot]?.baslangic }}-{{ veri.saatler[gunNo]?.[slot]?.bitis }}
                                                </div>
                                            </div>
                                            <div v-else class="text-gray-300 text-xs px-2 py-1.5">boş</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-xs text-gray-500 mt-3">
                            Bu haftanın onaylı izinleri, ilgili ders saatlerinde "İzinli" olarak işaretlenir.
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </Layout>
</template>
