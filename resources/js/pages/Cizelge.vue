<script setup>
import { ref, computed, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const veri = ref(null);
const yukleniyor = ref(true);

const cakismaVar = computed(() => {
    if (! veri.value) return 0;
    const c = veri.value.cakismalar;
    return c.sube.length + c.ogretmen.length + c.derslik.length;
});

onMounted(async () => {
    const { data } = await api.get('/cizelge');
    veri.value = data;
    yukleniyor.value = false;
});
</script>

<template>
    <Layout baslik="Ders Programı">
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                <template v-else>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium text-gray-900">Program Durumu</h3>
                            <span class="text-sm text-gray-500">{{ veri.programSayisi }} ders saati</span>
                        </div>

                        <!-- Sartname 5.3: zaman cakismasi uyarilari -->
                        <div v-if="cakismaVar === 0"
                             class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">
                            Zaman çakışması yok — şube, öğretmen ve derslik bazında hiçbir çakışma bulunamadı.
                        </div>
                        <div v-else class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded text-sm">
                            <strong>{{ cakismaVar }} çakışma bulundu.</strong>
                            Şube: {{ veri.cakismalar.sube.length }},
                            öğretmen: {{ veri.cakismalar.ogretmen.length }},
                            derslik: {{ veri.cakismalar.derslik.length }}.
                        </div>

                        <router-link :to="{ name: 'cizelge-yuk' }" class="inline-block text-sm text-indigo-600 hover:underline">
                            Yük dağılımı raporu &rarr;
                        </router-link>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-medium text-gray-900 mb-3">Şube Bazlı Çizelge</h3>
                        <div class="flex flex-wrap gap-2">
                            <router-link v-for="s in veri.subeler" :key="s.id"
                                         :to="{ name: 'cizelge-sube', params: { id: s.id } }"
                                         class="px-3 py-1.5 rounded text-sm border border-gray-300 text-gray-700 hover:bg-gray-50">
                                {{ s.name }}
                            </router-link>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-medium text-gray-900 mb-3">Öğretmen Bazlı Program</h3>
                        <div class="flex flex-wrap gap-2">
                            <router-link v-for="o in veri.ogretmenler" :key="o.id"
                                         :to="{ name: 'cizelge-ogretmen', params: { id: o.id } }"
                                         class="px-3 py-1.5 rounded text-sm border border-gray-300 text-gray-700 hover:bg-gray-50">
                                {{ o.first_name }} {{ o.last_name }}
                            </router-link>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </Layout>
</template>
