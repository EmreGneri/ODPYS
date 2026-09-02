<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const veri = ref(null);
const yukleniyor = ref(true);

onMounted(async () => {
    const { data } = await api.get('/dashboard');
    veri.value = data;
    yukleniyor.value = false;
});

const kartlar = [
    { alan: 'seviyeSayisi', etiket: 'Seviye', rota: 'seviyeler' },
    { alan: 'subeSayisi', etiket: 'Şube', rota: 'subeler' },
    { alan: 'derslikSayisi', etiket: 'Derslik', rota: 'derslikler' },
    { alan: 'dersSayisi', etiket: 'Ders', rota: 'dersler' },
];
</script>

<template>
    <Layout :baslik="veri?.okul?.name ?? 'ODPYS'">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                <template v-else>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <router-link
                            v-for="kart in kartlar" :key="kart.alan"
                            :to="{ name: kart.rota }"
                            class="block bg-white rounded-lg shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition"
                        >
                            <div class="text-3xl font-semibold text-gray-900">{{ veri[kart.alan] }}</div>
                            <div class="mt-1 text-sm text-gray-500">{{ kart.etiket }}</div>
                        </router-link>

                        <div class="block bg-white rounded-lg shadow-sm p-5">
                            <div class="text-3xl font-semibold text-gray-900">{{ veri.ogretmenSayisi }}</div>
                            <div class="mt-1 text-sm text-gray-500">Aktif Öğretmen</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Haftalık Ders Programı</h3>

                        <div class="flex items-center gap-3">
                            <span v-if="veri.programKayitSayisi > 0"
                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Oluşturuldu
                            </span>
                            <span v-else
                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                Henüz oluşturulmadı
                            </span>

                            <span class="text-sm text-gray-600">
                                {{ veri.programKayitSayisi > 0
                                    ? `${veri.programKayitSayisi} ders saati yerleştirildi.`
                                    : 'Tüm tanımlamalar tamamlandıktan sonra program oluşturulabilir.' }}
                            </span>
                        </div>
                    </div>

                    <div v-if="veri.okul" class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Okul Ayarları</h3>
                            <router-link :to="{ name: 'okul-ayarlari' }" class="text-sm text-blue-600 hover:underline">
                                Detaylar
                            </router-link>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500">Konum</div>
                                <div class="text-gray-900">{{ veri.okul.city }} / {{ veri.okul.district }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Günde ders</div>
                                <div class="text-gray-900">{{ veri.okul.lessons_per_day }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Ders süresi</div>
                                <div class="text-gray-900">{{ veri.okul.lesson_duration }} dk</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Mola süresi</div>
                                <div class="text-gray-900">{{ veri.okul.break_duration }} dk</div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </Layout>
</template>
