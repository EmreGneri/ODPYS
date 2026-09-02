<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const izinler = ref([]);
const yukleniyor = ref(true);

function tarih(s) {
    return new Date(s).toLocaleString('tr-TR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

const durumSinifi = {
    pending: 'bg-amber-100 text-amber-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
};

const durumAdi = { pending: 'Bekliyor', approved: 'Onaylandı', rejected: 'Reddedildi' };

onMounted(async () => {
    const { data } = await api.get('/izinler');
    izinler.value = data;
    yukleniyor.value = false;
});
</script>

<template>
    <Layout baslik="İzin Talepleri">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                <div v-else class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="border-b text-left text-gray-500">
                                <th class="px-6 py-3 font-medium">Öğretmen</th>
                                <th class="px-6 py-3 font-medium">Başlangıç</th>
                                <th class="px-6 py-3 font-medium">Bitiş</th>
                                <th class="px-6 py-3 font-medium">Gerekçe</th>
                                <th class="px-6 py-3 font-medium">Durum</th>
                                <th class="px-6 py-3 text-right font-medium">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="izin in izinler" :key="izin.id"
                                :class="izin.status === 'pending' ? 'bg-amber-50' : ''">
                                <td class="px-6 py-3">
                                    {{ izin.teacher?.first_name }} {{ izin.teacher?.last_name }}
                                </td>
                                <td class="px-6 py-3 tabular-nums">{{ tarih(izin.starts_at) }}</td>
                                <td class="px-6 py-3 tabular-nums">{{ tarih(izin.ends_at) }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ izin.reason || '—' }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs"
                                          :class="durumSinifi[izin.status]">
                                        {{ durumAdi[izin.status] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <router-link :to="{ name: 'izin-detay', params: { id: izin.id } }"
                                                 class="text-indigo-600 hover:text-indigo-900 text-sm">
                                        {{ izin.status === 'pending' ? 'İncele' : 'Detay' }}
                                    </router-link>
                                </td>
                            </tr>
                            <tr v-if="izinler.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Henüz izin talebi yok.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Layout>
</template>
