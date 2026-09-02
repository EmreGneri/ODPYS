<script setup>
import { ref, computed, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const dersler = ref([]);
const seviyeler = ref([]);
const yukleniyor = ref(true);
const mesaj = ref('');
const hata = ref('');

const formAcik = ref(false);
const duzenlenenId = ref(null);
const ad = ref('');
const saatlikUcret = ref('');
const blockDers = ref(false);
const satirlar = ref([]);

const HAFTA_SAYISI = 4;

// DersFormu.vue'daki (eski Blade sistemi) hesabin birebir aynisi -
// sartname 2.2/4: saatlik ucretten aylik ucret otomatik hesaplaniyor.
const toplamHaftalikSaat = computed(() =>
    satirlar.value.filter((s) => s.secili && s.saat).reduce((t, s) => t + Number(s.saat), 0)
);

function aylikUcret(saat) {
    if (! saat || ! saatlikUcret.value) return 0;
    return Number(saat) * HAFTA_SAYISI * Number(saatlikUcret.value);
}

function paraFormatla(sayi) {
    return new Intl.NumberFormat('tr-TR').format(sayi);
}

async function yukle() {
    const [d, s] = await Promise.all([api.get('/dersler'), api.get('/seviyeler')]);
    dersler.value = d.data;
    seviyeler.value = s.data;
    yukleniyor.value = false;
}

function formAc(ders = null) {
    duzenlenenId.value = ders?.id ?? null;
    ad.value = ders?.name ?? '';
    saatlikUcret.value = ders?.hourly_rate ?? '';
    blockDers.value = Boolean(ders?.block_ders);

    // Mevcut mufredat: seviye id -> haftalik saat
    const mevcut = {};
    (ders?.course_levels ?? []).forEach((cl) => {
        mevcut[cl.level_id] = cl.weekly_hours;
    });

    satirlar.value = seviyeler.value.map((s) => ({
        id: s.id,
        name: s.name,
        secili: Object.prototype.hasOwnProperty.call(mevcut, s.id),
        saat: mevcut[s.id] ?? '',
    }));

    hata.value = '';
    formAcik.value = true;
}

async function kaydet() {
    hata.value = '';

    // Backend CourseRequest'in bekledigi sekil:
    // seviyeler[levelId][secili] ve [weekly_hours]
    const seviyelerVerisi = {};
    satirlar.value.forEach((s) => {
        seviyelerVerisi[s.id] = {
            secili: s.secili ? 1 : 0,
            weekly_hours: s.saat || null,
        };
    });

    const gonderilecek = {
        name: ad.value,
        hourly_rate: saatlikUcret.value,
        block_ders: blockDers.value ? 1 : 0,
        seviyeler: seviyelerVerisi,
    };

    try {
        if (duzenlenenId.value) {
            await api.put(`/dersler/${duzenlenenId.value}`, gonderilecek);
            mesaj.value = 'Ders güncellendi.';
        } else {
            await api.post('/dersler', gonderilecek);
            mesaj.value = 'Ders eklendi.';
        }

        formAcik.value = false;
        await yukle();
    } catch (e) {
        const hatalar = e.response?.data?.errors ?? {};
        // Seviye bazli hatalar "seviyeler.3.weekly_hours" gibi anahtarlarla
        // geliyor - ilk bulunani gosteriyoruz.
        hata.value = hatalar.name?.[0]
            ?? hatalar.hourly_rate?.[0]
            ?? Object.values(hatalar)[0]?.[0]
            ?? 'Bir hata oluştu.';
    }
}

async function sil(ders) {
    if (! confirm(`${ders.name} dersi silinsin mi?`)) {
        return;
    }

    mesaj.value = '';
    hata.value = '';

    try {
        await api.delete(`/dersler/${ders.id}`);
        mesaj.value = 'Ders silindi.';
        await yukle();
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Silinemedi.';
    }
}

onMounted(yukle);
</script>

<template>
    <Layout baslik="Dersler">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-if="mesaj" class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ mesaj }}
                </div>
                <div v-if="hata" class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ hata }}
                </div>

                <div>
                    <button @click="formAc()"
                            class="inline-block px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                        Yeni Ders Ekle
                    </button>
                </div>

                <div v-if="formAcik" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <h3 class="font-medium text-gray-900">
                        {{ duzenlenenId ? 'Dersi Düzenle' : 'Yeni Ders' }}
                    </h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ders Adı</label>
                        <input v-model="ad" type="text"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Saatlik Ücret (TL)</label>
                        <input v-model="saatlikUcret" type="number" step="0.01"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="blockDers" class="rounded border-gray-300">
                            <span class="text-sm font-medium text-gray-700">Blok ders</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1">
                            İşaretlenirse, sihirbaz bu dersin haftalık saatlerini mümkünse aynı günde
                            art arda (blok halinde) yerleştirmeye çalışır.
                        </p>
                    </div>

                    <div class="border-t pt-6">
                        <h4 class="font-medium text-gray-900 mb-1">Seviye Bazlı Haftalık Saatler</h4>
                        <p class="text-sm text-gray-500 mb-4">
                            Dersin okutulduğu seviyeleri işaretleyin ve haftalık ders saatini girin.
                        </p>

                        <div class="space-y-3">
                            <div v-for="satir in satirlar" :key="satir.id" class="flex items-center gap-4">
                                <label class="flex items-center gap-2 w-32">
                                    <input type="checkbox" v-model="satir.secili" class="rounded border-gray-300">
                                    <span class="text-sm">{{ satir.name }}</span>
                                </label>

                                <input type="number" v-model="satir.saat" :disabled="!satir.secili"
                                       min="1" placeholder="saat"
                                       class="w-24 rounded-md border-gray-300 shadow-sm text-sm disabled:bg-gray-100">

                                <span class="text-sm text-gray-500">saat/hafta</span>

                                <span v-if="satir.secili && satir.saat && saatlikUcret" class="text-sm text-gray-700">
                                    → aylık {{ paraFormatla(aylikUcret(satir.saat)) }} TL
                                </span>
                            </div>
                        </div>

                        <p class="mt-4 text-sm text-gray-600">
                            Toplam haftalık saat: <strong>{{ toplamHaftalikSaat }}</strong>
                        </p>
                    </div>

                    <div class="flex gap-3 border-t pt-6">
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
                                <th class="py-2">Ders</th>
                                <th class="py-2">Saatlik Ücret</th>
                                <th class="py-2">Seviyeler ve Haftalık Saatler</th>
                                <th class="py-2 text-right">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ders in dersler" :key="ders.id" class="border-b">
                                <td class="py-2 align-top">
                                    {{ ders.name }}
                                    <span v-if="ders.block_ders"
                                          class="inline-flex px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-xs ml-1">
                                        Blok
                                    </span>
                                </td>
                                <td class="py-2 align-top">
                                    {{ Number(ders.hourly_rate).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) }} TL
                                </td>
                                <td class="py-2 align-top">
                                    <div v-for="cl in ders.course_levels" :key="cl.id" class="text-gray-700">
                                        {{ cl.level?.name }} — {{ cl.weekly_hours }} saat
                                    </div>
                                    <span v-if="!ders.course_levels?.length" class="text-gray-400">Seviye atanmamış</span>
                                </td>
                                <td class="py-2 text-right align-top space-x-3">
                                    <button @click="formAc(ders)" class="text-blue-600 hover:underline">Düzenle</button>
                                    <button @click="sil(ders)" class="text-red-600 hover:underline">Sil</button>
                                </td>
                            </tr>
                            <tr v-if="dersler.length === 0">
                                <td colspan="4" class="py-6 text-center text-gray-500">
                                    Henüz ders tanımlanmamış.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Layout>
</template>
