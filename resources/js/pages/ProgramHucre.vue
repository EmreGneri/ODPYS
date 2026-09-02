<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Layout from '../components/Layout.vue';
import api from '../api';

const route = useRoute();
const router = useRouter();

const dersler = ref([]);
const ogretmenler = ref([]);
const mevcut = ref(null);
const courseId = ref('');
const teacherId = ref('');
const yukleniyor = ref(true);
const hata = ref('');

const GUN_ADLARI = {
    1: 'Pazartesi', 2: 'Salı', 3: 'Çarşamba', 4: 'Perşembe',
    5: 'Cuma', 6: 'Cumartesi', 7: 'Pazar',
};

const yol = () => `/program/duzenle/${route.params.sube}/${route.params.weekday}/${route.params.slot}`;

async function yukle() {
    try {
        const { data } = await api.get(yol());
        dersler.value = data.dersler;
        ogretmenler.value = data.ogretmenler;
        mevcut.value = data.mevcut;
        courseId.value = data.mevcut?.course_id ?? '';
        teacherId.value = data.mevcut?.teacher_id ?? '';
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Yüklenemedi.';
    } finally {
        yukleniyor.value = false;
    }
}

async function kaydet() {
    hata.value = '';

    try {
        await api.put(yol(), { course_id: courseId.value, teacher_id: teacherId.value });
        router.push({ name: 'cizelge-sube', params: { id: route.params.sube } });
    } catch (e) {
        const hatalar = e.response?.data?.errors ?? {};
        // ProgramDuzenleRequest'in uc asamali kontrolu (mufredat ->
        // yetki -> cakisma) buradan geliyor.
        hata.value = hatalar.teacher_id?.[0] ?? hatalar.course_id?.[0] ?? 'Kaydedilemedi.';
    }
}

async function bosalt() {
    if (! confirm('Bu ders saatini boşaltmak istediğinize emin misiniz?')) {
        return;
    }

    await api.delete(yol());
    router.push({ name: 'cizelge-sube', params: { id: route.params.sube } });
}

onMounted(yukle);
</script>

<template>
    <Layout :baslik="`${GUN_ADLARI[route.params.weekday]} · ${route.params.slot}. ders`">
        <div class="py-12">
            <div class="max-w-lg mx-auto sm:px-6 lg:px-8 space-y-6">
                <router-link :to="{ name: 'cizelge-sube', params: { id: route.params.sube } }"
                             class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Şube Çizelgesi
                </router-link>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                    <template v-else>
                        <p v-if="hata" class="text-sm text-red-600">{{ hata }}</p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ders</label>
                            <select v-model="courseId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">— Seçin —</option>
                                <option v-for="d in dersler" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Öğretmen</label>
                            <select v-model="teacherId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">— Seçin —</option>
                                <option v-for="o in ogretmenler" :key="o.id" :value="o.id">
                                    {{ o.first_name }} {{ o.last_name }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                Sadece bu şubenin seviyesinde ders vermeye yetkili öğretmenler listelenir.
                            </p>
                        </div>

                        <div class="flex items-center gap-4 border-t pt-6">
                            <button @click="kaydet"
                                    class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                                Kaydet
                            </button>
                            <router-link :to="{ name: 'cizelge-sube', params: { id: route.params.sube } }"
                                         class="text-sm text-gray-600 hover:text-gray-900">
                                Vazgeç
                            </router-link>
                        </div>

                        <div v-if="mevcut" class="border-t pt-6">
                            <button @click="bosalt" class="text-sm text-red-600 hover:text-red-800">
                                Bu ders saatini boşalt
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </Layout>
</template>
