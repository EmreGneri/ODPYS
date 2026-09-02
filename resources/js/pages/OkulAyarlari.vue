<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const okul = ref(null);
const gunler = ref({});
const haftalikKapasite = ref(0);
const yukleniyor = ref(true);
const mesaj = ref('');
const hata = ref('');
const duzenleModu = ref(false);

async function yukle() {
    const { data } = await api.get('/okul');
    okul.value = data.okul;
    gunler.value = data.gunler;
    haftalikKapasite.value = data.haftalikKapasite;
    yukleniyor.value = false;
}

async function kaydet() {
    hata.value = '';

    // Backend SchoolRequest'in bekledigi sekil: days[gunNo][is_active|start_time]
    const days = {};
    Object.entries(gunler.value).forEach(([no, g]) => {
        days[no] = { is_active: g.aktif ? 1 : 0, start_time: g.saat };
    });

    try {
        const { data } = await api.put('/okul', {
            name: okul.value.name,
            city: okul.value.city,
            district: okul.value.district,
            lessons_per_day: okul.value.lessons_per_day,
            lesson_duration: okul.value.lesson_duration,
            break_duration: okul.value.break_duration,
            days,
        });

        mesaj.value = data.mesaj;
        duzenleModu.value = false;
        await yukle();
    } catch (e) {
        const hatalar = e.response?.data?.errors ?? {};
        hata.value = Object.values(hatalar)[0]?.[0] ?? 'Bir hata oluştu.';
    }
}

onMounted(yukle);
</script>

<template>
    <Layout :baslik="okul?.name ?? 'Okul Ayarları'">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-if="mesaj" class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ mesaj }}
                </div>
                <div v-if="hata" class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ hata }}
                </div>

                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                <template v-else-if="okul">
                    <div class="flex justify-end">
                        <button @click="duzenleModu = !duzenleModu"
                                class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                            {{ duzenleModu ? 'Görüntülemeye Dön' : 'Düzenle' }}
                        </button>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                        <h3 class="font-medium text-gray-900">Temel Bilgiler</h3>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                            <div>
                                <label class="block text-gray-500">Okul Adı</label>
                                <input v-if="duzenleModu" v-model="okul.name" type="text"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <div v-else class="text-gray-900">{{ okul.name }}</div>
                            </div>
                            <div>
                                <label class="block text-gray-500">İl</label>
                                <input v-if="duzenleModu" v-model="okul.city" type="text"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <div v-else class="text-gray-900">{{ okul.city }}</div>
                            </div>
                            <div>
                                <label class="block text-gray-500">İlçe</label>
                                <input v-if="duzenleModu" v-model="okul.district" type="text"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <div v-else class="text-gray-900">{{ okul.district }}</div>
                            </div>
                            <div>
                                <label class="block text-gray-500">Günde ders</label>
                                <input v-if="duzenleModu" v-model="okul.lessons_per_day" type="number" min="1" max="20"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <div v-else class="text-gray-900">{{ okul.lessons_per_day }}</div>
                            </div>
                            <div>
                                <label class="block text-gray-500">Ders süresi (dk)</label>
                                <input v-if="duzenleModu" v-model="okul.lesson_duration" type="number" min="5" max="120"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <div v-else class="text-gray-900">{{ okul.lesson_duration }}</div>
                            </div>
                            <div>
                                <label class="block text-gray-500">Mola süresi (dk)</label>
                                <input v-if="duzenleModu" v-model="okul.break_duration" type="number" min="0" max="60"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <div v-else class="text-gray-900">{{ okul.break_duration }}</div>
                            </div>
                        </div>

                        <p v-if="!duzenleModu" class="text-sm text-gray-600 border-t pt-4">
                            Haftalık kapasite: <strong>{{ haftalikKapasite }}</strong> ders saati
                            (günde {{ okul.lessons_per_day }} ders × aktif gün sayısı).
                        </p>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                        <h3 class="font-medium text-gray-900">Eğitim Günleri ve Ders Saatleri</h3>

                        <div v-for="(gun, no) in gunler" :key="no" class="border-b last:border-b-0 pb-4 last:pb-0">
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 w-40">
                                    <input type="checkbox" v-model="gun.aktif" :disabled="!duzenleModu"
                                           class="rounded border-gray-300">
                                    <span class="text-sm font-medium">{{ gun.ad }}</span>
                                </label>

                                <input v-if="duzenleModu" v-model="gun.saat" type="time"
                                       class="rounded-md border-gray-300 shadow-sm text-sm">
                                <span v-else-if="gun.aktif" class="text-sm text-gray-600">Başlangıç: {{ gun.saat }}</span>
                                <span v-else class="text-sm text-gray-400">Eğitim yok</span>
                            </div>

                            <div v-if="!duzenleModu && gun.aktif && Object.keys(gun.dersSaatleri).length"
                                 class="mt-2 ml-40 flex flex-wrap gap-2">
                                <span v-for="(saat, slot) in gun.dersSaatleri" :key="slot"
                                      class="text-xs text-gray-500 tabular-nums bg-gray-50 rounded px-2 py-1">
                                    {{ slot }}. {{ saat.baslangic }}–{{ saat.bitis }}
                                </span>
                            </div>
                        </div>

                        <div v-if="duzenleModu" class="border-t pt-4">
                            <button @click="kaydet"
                                    class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                                Kaydet
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </Layout>
</template>
