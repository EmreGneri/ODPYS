<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Layout from '../components/Layout.vue';
import KayitGezinme from '../components/KayitGezinme.vue';
import api from '../api';

const route = useRoute();

const ogretmen = ref(null);
const dersler = ref([]);
const seviyeler = ref([]);
const secili = ref({});
const yukleniyor = ref(true);
const mesaj = ref('');
const hata = ref('');

// Gezinme cubugu icin butun aktif ogretmenler - ogretmen degistikce
// degismeyen bir liste, bir kere cekiliyor.
const ogretmenler = ref([]);

// Sayfa basligi eskiden sabit "Ogretmen Ders Yetkileri" idi - kimin
// yetkilerini duzenledigin ekranda hic yazmiyordu. Ogretmenler arasi
// gezinme eklenince bu bilgi sart oldu.
const baslik = computed(() =>
    ogretmen.value ? `Ders Yetkileri — ${ogretmen.value.ad}` : 'Öğretmen Ders Yetkileri'
);

/**
 * Yetkiler "dersId-seviyeId" anahtarli duz bir nesnede tutuluyor -
 * OgretmenDersleriFormu.vue'daki (eski sistem) mantigin aynisi:
 * matriste her kutu icin O(1) arama saglıyor.
 */
function anahtar(dersId, seviyeId) {
    return `${dersId}-${seviyeId}`;
}

async function yukle() {
    yukleniyor.value = true;

    // Onceki ogretmenin "kaydedildi" mesaji yeni ogretmenin ekraninda
    // asili kalmasin.
    mesaj.value = '';
    hata.value = '';

    try {
        const { data } = await api.get(`/ogretmenler/${route.params.id}/dersler`);
        ogretmen.value = data.ogretmen;
        dersler.value = data.dersler;
        seviyeler.value = data.seviyeler;
        secili.value = { ...data.mevcut };
    } catch (e) {
        // Bkz. CizelgeIzgara.vue'daki ayni aciklama: 404 genellikle
        // "kayit baska okula ait" demek. Isaretli kutulari da
        // temizliyoruz - eski ogretmenin yetkileri ekranda kalip
        // yanlis kayda kaydedilmesin.
        ogretmen.value = null;
        dersler.value = [];
        seviyeler.value = [];
        secili.value = {};

        hata.value = e.response?.status === 404
            ? 'Bu öğretmen aktif okulda bulunamadı. Başka bir okula ait olabilir — "Okullar" ekranından aktif okulu değiştirip tekrar deneyin.'
            : 'Ders yetkileri yüklenemedi.';
    } finally {
        yukleniyor.value = false;
    }
}

async function ogretmenlerYukle() {
    const { data } = await api.get('/gezinme');
    ogretmenler.value = data.ogretmenler;
}

// Gezinme cubugu ayni bilesen icinde ogretmen degistiriyor (rota
// parametresi degisir ama bilesen yeniden olusturulmaz), bu yuzden
// veriyi elle tekrar cekmek gerekiyor - bu watch olmasa adres
// degisir ama tablo eski ogretmenin yetkilerini gostermeye devam
// ederdi, ki bu sessizce yanlis kayda yazmaya yol acardi.
watch(() => route.params.id, yukle);

onMounted(() => {
    yukle();
    ogretmenlerYukle();
});

function degistir(dersId, seviyeId) {
    const k = anahtar(dersId, seviyeId);
    secili.value[k] = ! secili.value[k];
}

function satirSec(dersId, deger) {
    seviyeler.value.forEach((s) => {
        secili.value[anahtar(dersId, s.id)] = deger;
    });
}

async function kaydet() {
    // Backend'in bekledigi sekil: yetkiler[dersId][seviyeId] = 1
    const yetkiler = {};

    Object.entries(secili.value).forEach(([k, v]) => {
        if (! v) return;
        const [dersId, seviyeId] = k.split('-');
        yetkiler[dersId] = yetkiler[dersId] ?? {};
        yetkiler[dersId][seviyeId] = 1;
    });

    const { data } = await api.put(`/ogretmenler/${route.params.id}/dersler`, { yetkiler });
    mesaj.value = data.mesaj;

    // Kaydettikten sonra listeye DONMUYORUZ (eskiden 1.2 sn sonra
    // donuyordu): gezinme cubugu eklendigi icin dogal akis artik
    // "kaydet -> sonraki ogretmen". Listeye donmek o akisi her
    // seferinde kesiyordu.
}
</script>

<template>
    <Layout :baslik="baslik">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <router-link :to="{ name: 'ogretmenler' }" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Öğretmenler
                </router-link>

                <div v-if="ogretmenler.length" class="bg-white shadow-sm sm:rounded-lg px-4 py-3">
                    <KayitGezinme
                        :kayitlar="ogretmenler"
                        :aktif-id="route.params.id"
                        rota="ogretmen-dersleri"
                        etiket="Öğretmen:"
                    />
                </div>

                <div v-if="mesaj" class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ mesaj }}
                </div>

                <div v-if="hata" class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ hata }}
                </div>

                <div v-else class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                    <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                    <template v-else>
                        <p class="text-sm text-gray-600 mb-4">
                            Şartname 2.4 — atama <strong>seviye bazlı</strong>: bir öğretmen aynı dersi
                            sadece işaretlenen seviyelerde verebilir.
                        </p>

                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="py-2 pr-4">Ders</th>
                                    <th v-for="s in seviyeler" :key="s.id" class="py-2 px-3 text-center">{{ s.name }}</th>
                                    <th class="py-2 px-3 text-center">Tümü</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in dersler" :key="d.id" class="border-b">
                                    <td class="py-2 pr-4">{{ d.name }}</td>
                                    <td v-for="s in seviyeler" :key="s.id" class="py-2 px-3 text-center">
                                        <input type="checkbox"
                                               :checked="!!secili[`${d.id}-${s.id}`]"
                                               @change="degistir(d.id, s.id)"
                                               class="rounded border-gray-300">
                                    </td>
                                    <td class="py-2 px-3 text-center space-x-2">
                                        <button @click="satirSec(d.id, true)" class="text-xs text-indigo-600 hover:underline">hepsi</button>
                                        <button @click="satirSec(d.id, false)" class="text-xs text-gray-500 hover:underline">temizle</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="mt-6 border-t pt-6">
                            <button @click="kaydet"
                                    class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                                Kaydet
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </Layout>
</template>
