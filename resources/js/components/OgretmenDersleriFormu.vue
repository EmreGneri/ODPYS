<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    action: String,
    method: { type: String, default: 'PUT' },
    dersler: { type: Array, default: () => [] },
    seviyeler: { type: Array, default: () => [] },
    initialSecili: { type: Array, default: () => [] },
    csrfToken: String,
});

// Seçimi "dersId-seviyeId" anahtarlı bir nesnede tutuyorum.
// Dizi kullansaydım her kontrolde indexOf ile tarama gerekirdi;
// nesnede arama O(1).
const secili = ref({});
props.initialSecili.forEach((anahtar) => {
    secili.value[anahtar] = true;
});

function anahtar(dersId, seviyeId) {
    return `${dersId}-${seviyeId}`;
}

function isaretli(dersId, seviyeId) {
    return secili.value[anahtar(dersId, seviyeId)] === true;
}

function degistir(dersId, seviyeId) {
    const k = anahtar(dersId, seviyeId);

    if (secili.value[k]) {
        delete secili.value[k];
    } else {
        secili.value[k] = true;
    }
}

// ── Satır: bir dersin tüm seviyeleri ──────────────────────
function satirTam(dersId) {
    return props.seviyeler.length > 0
        && props.seviyeler.every((s) => isaretli(dersId, s.id));
}

function satirDegistir(dersId) {
    const doluydu = satirTam(dersId);

    props.seviyeler.forEach((s) => {
        const k = anahtar(dersId, s.id);
        if (doluydu) {
            delete secili.value[k];
        } else {
            secili.value[k] = true;
        }
    });
}

// ── Sütun: bir seviyenin tüm dersleri ─────────────────────
function sutunTam(seviyeId) {
    return props.dersler.length > 0
        && props.dersler.every((d) => isaretli(d.id, seviyeId));
}

function sutunDegistir(seviyeId) {
    const doluydu = sutunTam(seviyeId);

    props.dersler.forEach((d) => {
        const k = anahtar(d.id, seviyeId);
        if (doluydu) {
            delete secili.value[k];
        } else {
            secili.value[k] = true;
        }
    });
}

function tumunuTemizle() {
    secili.value = {};
}

// ── Türetilmiş değerler ───────────────────────────────────
// Seçim değiştiğinde Vue bunları kendiliğinden yeniden hesaplıyor.
const seciliListesi = computed(() =>
    Object.keys(secili.value).map((k) => {
        const [dersId, seviyeId] = k.split('-');
        return { dersId, seviyeId };
    })
);

const yetkiSayisi = computed(() => seciliListesi.value.length);

const bransSayisi = computed(
    () => new Set(seciliListesi.value.map((x) => x.dersId)).size
);
</script>

<template>
    <form :action="action" method="POST" class="space-y-6">
        <input type="hidden" name="_token" :value="csrfToken">
        <input type="hidden" name="_method" :value="method">

        <!--
            Onay kutuları sadece görsel durumu tutuyor.
            Sunucuya giden veri bu gizli alanlar — controller'daki
            yetkiler[dersId][seviyeId] yapısı hiç değişmedi.
        -->
        <input
            v-for="satir in seciliListesi"
            :key="`${satir.dersId}-${satir.seviyeId}`"
            type="hidden"
            :name="`yetkiler[${satir.dersId}][${satir.seviyeId}]`"
            value="1">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm" :class="yetkiSayisi === 0 ? 'text-amber-600' : 'text-gray-600'">
                <template v-if="yetkiSayisi === 0">
                    Hiçbir yetki seçili değil — bu öğretmen program sihirbazında değerlendirilmez.
                </template>
                <template v-else>
                    <strong>{{ bransSayisi }}</strong> branşta
                    <strong>{{ yetkiSayisi }}</strong> ders-seviye yetkisi seçili.
                </template>
            </p>

            <button type="button" @click="tumunuTemizle"
                    class="text-sm text-gray-600 hover:text-gray-900 underline">
                Tümünü temizle
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-gray-500">
                        <th class="py-2 pe-4 text-left font-medium">Ders</th>
                        <th v-for="seviye in seviyeler" :key="seviye.id"
                            class="py-2 px-3 text-center font-medium">
                            <div class="flex flex-col items-center gap-1">
                                <span>{{ seviye.name }}</span>
                                <input type="checkbox"
                                       class="rounded border-gray-300"
                                       :checked="sutunTam(seviye.id)"
                                       @change="sutunDegistir(seviye.id)"
                                       :title="`${seviye.name} için tüm dersleri seç`">
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="ders in dersler" :key="ders.id"
                        class="border-b hover:bg-gray-50">
                        <td class="py-2 pe-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox"
                                       class="rounded border-gray-300"
                                       :checked="satirTam(ders.id)"
                                       @change="satirDegistir(ders.id)"
                                       :title="`${ders.name} için tüm seviyeleri seç`">
                                <span>{{ ders.name }}</span>
                            </label>
                        </td>

                        <td v-for="seviye in seviyeler" :key="seviye.id"
                            class="py-2 px-3 text-center">
                            <input type="checkbox"
                                   class="rounded border-gray-300"
                                   :checked="isaretli(ders.id, seviye.id)"
                                   @change="degistir(ders.id, seviye.id)">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                Kaydet
            </button>
            <a href="/ogretmenler" class="text-sm text-gray-600 hover:text-gray-900">Vazgeç</a>
        </div>
    </form>
</template>
