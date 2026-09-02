<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Layout from '../components/Layout.vue';
import KayitGezinme from '../components/KayitGezinme.vue';
import api from '../api';

const route = useRoute();
const veri = ref(null);
const yukleniyor = ref(true);
const hata = ref('');

// Gezinme cubugunun listesi. Cizelge verisinden AYRI cekiliyor cunku
// sube/ogretmen degistikce cizelge yeniden yukleniyor ama liste ayni
// kaliyor - bir kere alip saklamak yeterli.
const gezinme = ref({ subeler: [], ogretmenler: [] });

// 'sube' ya da 'ogretmen' - rota adindan cikariyoruz, iki ekran da
// ayni izgarayi kullaniyor (Blade'deki tek izgara.blade.php gibi).
const tur = computed(() => (route.name === 'cizelge-sube' ? 'sube' : 'ogretmen'));

const gezinmeListesi = computed(() =>
    tur.value === 'sube' ? gezinme.value.subeler : gezinme.value.ogretmenler
);

async function yukle() {
    yukleniyor.value = true;
    hata.value = '';

    try {
        const { data } = await api.get(`/cizelge/${tur.value}/${route.params.id}`);
        veri.value = data;
    } catch (e) {
        veri.value = null;

        // 404 = kayit AKTIF OKULDA yok. Coklu okulda cok olasi bir
        // durum: baska okul aktifken alinmis bir yer imi ya da elle
        // yazilan bir adres. Rota modeli baglama okul kapsamindan
        // gectigi icin kayit "yok" sayiliyor - dogru davranis, ama
        // kullaniciya ne oldugunu soylemek gerekiyor. Bu yakalama
        // olmadan sayfa sonsuza kadar "Yukleniyor..." kaliyordu.
        hata.value = e.response?.status === 404
            ? 'Bu kayıt aktif okulda bulunamadı. Başka bir okula ait olabilir — "Okullar" ekranından aktif okulu değiştirip tekrar deneyin.'
            : 'Çizelge yüklenemedi.';
    } finally {
        yukleniyor.value = false;
    }
}

async function gezinmeYukle() {
    const { data } = await api.get('/gezinme');
    gezinme.value = data;
}

// Ayni bilesende sube 9A -> 9B gecisi olursa (rota parametresi degisir
// ama bilesen yeniden olusturulmaz) veriyi tekrar cekmemiz gerekiyor.
// Gezinme cubugu tam olarak bu gecisi yapiyor, yani bu watch olmadan
// dugmeler adres cubugunu degistirir ama tablo eski kalirdi.
watch(() => [route.name, route.params.id], yukle);

onMounted(() => {
    yukle();
    gezinmeYukle();
});
</script>

<template>
    <Layout :baslik="veri?.baslik ?? 'Çizelge'">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
                <div class="flex items-center justify-between">
                    <router-link :to="{ name: 'cizelge' }" class="text-sm text-gray-600 hover:text-gray-900">
                        &larr; Ders Programı
                    </router-link>

                    <div v-if="veri" class="flex items-center gap-4">
                        <span class="text-sm text-gray-500">{{ veri.altBaslik }} · Toplam {{ veri.toplamSaat }} ders saati</span>

                        <!-- PDF/Excel indirme: cerez tabanli kimlik dogrulama
                             kullandigimiz icin duz link yeterli, tarayici
                             oturum cerezini otomatik gonderiyor. -->
                        <a :href="`/cizelge/${tur}/${route.params.id}/pdf`"
                           class="text-sm text-indigo-600 hover:text-indigo-900">PDF indir</a>
                        <a :href="`/cizelge/${tur}/${route.params.id}/excel`"
                           class="text-sm text-indigo-600 hover:text-indigo-900">Excel indir</a>
                    </div>
                </div>

                <div v-if="gezinmeListesi.length"
                     class="bg-white shadow-sm sm:rounded-lg px-4 py-3">
                    <KayitGezinme
                        :kayitlar="gezinmeListesi"
                        :aktif-id="route.params.id"
                        :rota="route.name"
                        :etiket="tur === 'sube' ? 'Şube:' : 'Öğretmen:'"
                    />
                </div>

                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                <div v-else-if="hata"
                     class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ hata }}
                </div>

                <div v-else-if="!Object.keys(veri.gunler).length"
                     class="bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded">
                    Aktif eğitim günü tanımlı değil.
                </div>

                <div v-else class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="px-3 py-3 text-left font-medium text-gray-500 w-28">Saat</th>
                                <th v-for="(gunAdi, gunNo) in veri.gunler" :key="gunNo"
                                    class="px-3 py-3 text-left font-medium text-gray-500">
                                    {{ gunAdi }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="slot in veri.slotlar" :key="slot">
                                <td class="px-3 py-2 align-top">
                                    <div class="font-medium text-gray-700">{{ slot }}. ders</div>
                                </td>

                                <td v-for="(gunAdi, gunNo) in veri.gunler" :key="gunNo" class="px-3 py-2 align-top">
                                    <div v-if="veri.izgara[gunNo]?.[slot]"
                                         class="rounded border px-2 py-1.5"
                                         :class="veri.izgara[gunNo][slot].izinli
                                            ? 'border-amber-300 bg-amber-50'
                                            : 'border-gray-200 bg-gray-50'">
                                        <template v-if="veri.izgara[gunNo][slot].izinli">
                                            <div class="text-xs font-medium text-amber-800">İzinli</div>
                                            <div class="text-xs text-amber-700">
                                                Normalde: {{ veri.izgara[gunNo][slot].ust }} · {{ veri.izgara[gunNo][slot].alt }}
                                            </div>
                                            <div v-if="veri.izgara[gunNo][slot].izinSebep"
                                                 class="text-xs text-amber-600 italic">
                                                {{ veri.izgara[gunNo][slot].izinSebep }}
                                            </div>
                                        </template>
                                        <template v-else>
                                            <div class="font-medium text-gray-800">{{ veri.izgara[gunNo][slot].ust }}</div>
                                            <div class="text-xs text-gray-500">{{ veri.izgara[gunNo][slot].alt }}</div>
                                        </template>

                                        <div class="text-xs text-gray-400 tabular-nums mt-0.5">
                                            {{ veri.saatler[gunNo]?.[slot]?.baslangic }}-{{ veri.saatler[gunNo]?.[slot]?.bitis }}
                                        </div>

                                        <router-link v-if="tur === 'sube'"
                                                     :to="{ name: 'program-hucre', params: { sube: route.params.id, weekday: gunNo, slot } }"
                                                     class="text-xs text-indigo-600 hover:text-indigo-900 mt-1 inline-block">
                                            Düzenle
                                        </router-link>
                                    </div>

                                    <div v-else class="text-gray-300 text-xs px-2 py-1.5">
                                        boş
                                        <router-link v-if="tur === 'sube'"
                                                     :to="{ name: 'program-hucre', params: { sube: route.params.id, weekday: gunNo, slot } }"
                                                     class="block text-indigo-600 hover:text-indigo-900 mt-1">
                                            + Ders Ekle
                                        </router-link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Layout>
</template>
