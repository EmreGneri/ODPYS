<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    action: String,
    method: { type: String, default: 'POST' },
    seviyeler: { type: Array, default: () => [] },
    initialName: { type: String, default: '' },
    initialHourlyRate: { type: [String, Number], default: '' },
    initialSeviyeSaatleri: { type: Object, default: () => ({}) },
    csrfToken: String,
});

const name = ref(props.initialName);
const hourlyRate = ref(props.initialHourlyRate);
const hata = ref('');

const HAFTA_SAYISI = 4;

const satirlar = ref(
    props.seviyeler.map((seviye) => ({
        id: seviye.id,
        name: seviye.name,
        secili: Object.prototype.hasOwnProperty.call(props.initialSeviyeSaatleri, seviye.id),
        saat: props.initialSeviyeSaatleri[seviye.id] ?? '',
    }))
);

const toplamHaftalikSaat = computed(() =>
    satirlar.value
        .filter((s) => s.secili && s.saat)
        .reduce((toplam, s) => toplam + Number(s.saat), 0)
);

function aylikUcret(saat) {
    if (!saat || !hourlyRate.value) return 0;
    return Number(saat) * HAFTA_SAYISI * Number(hourlyRate.value);
}

function paraFormatla(sayi) {
    return new Intl.NumberFormat('tr-TR').format(sayi);
}

function gonder(e) {
    if (!name.value.trim()) {
        e.preventDefault();
        hata.value = 'Ders adı boş olamaz.';
        return;
    }

    const seciliVar = satirlar.value.some((s) => s.secili);
    if (!seciliVar) {
        e.preventDefault();
        hata.value = 'En az bir seviye seçmelisiniz.';
        return;
    }

    const eksikSaat = satirlar.value.some((s) => s.secili && !s.saat);
    if (eksikSaat) {
        e.preventDefault();
        hata.value = 'Seçtiğiniz her seviye için haftalık saat girmelisiniz.';
        return;
    }

    hata.value = '';
}
</script>

<template>
    <form :action="action" method="POST" @submit="gonder" class="space-y-6">
        <input type="hidden" name="_token" :value="csrfToken">
        <input v-if="method !== 'POST'" type="hidden" name="_method" :value="method">

        <p v-if="hata" class="text-sm text-red-600">{{ hata }}</p>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Ders Adı</label>
            <input id="name" name="name" type="text" v-model="name"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label for="hourly_rate" class="block text-sm font-medium text-gray-700">
                Saatlik Ücret (TL)
            </label>
            <input id="hourly_rate" name="hourly_rate" type="number" step="0.01" v-model="hourlyRate"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        </div>

        <div class="border-t pt-6">
            <h3 class="font-medium text-gray-900 mb-1">Seviye Bazlı Haftalık Saatler</h3>
            <p class="text-sm text-gray-500 mb-4">
                Dersin okutulduğu seviyeleri işaretleyin ve haftalık ders saatini girin.
            </p>

            <div class="space-y-3">
                <div v-for="(satir, i) in satirlar" :key="satir.id"
                     class="flex items-center gap-4">

                    <label class="flex items-center gap-2 w-32">
                        <input type="checkbox"
                               v-model="satir.secili"
                               :name="`seviyeler[${satir.id}][secili]`"
                               value="1"
                               class="rounded border-gray-300">
                        <span class="text-sm">{{ satir.name }}</span>
                    </label>

                    <input type="number"
                           v-model="satir.saat"
                           :name="`seviyeler[${satir.id}][weekly_hours]`"
                           :disabled="!satir.secili"
                           min="1"
                           max="40"
                           placeholder="saat"
                           class="w-24 rounded-md border-gray-300 shadow-sm text-sm disabled:bg-gray-100">

                    <span class="text-sm text-gray-500">saat/hafta</span>

                    <span v-if="satir.secili && satir.saat && hourlyRate"
                          class="text-sm text-gray-700">
                        → aylık {{ paraFormatla(aylikUcret(satir.saat)) }} TL
                    </span>
                </div>
            </div>

            <p class="mt-4 text-sm text-gray-600">
                Toplam haftalık saat: <strong>{{ toplamHaftalikSaat }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-4 border-t pt-6">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                Kaydet
            </button>
            <a href="/dersler" class="text-sm text-gray-600 hover:text-gray-900">Vazgeç</a>
        </div>
    </form>
</template>
