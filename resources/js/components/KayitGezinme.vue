<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';

/**
 * Detay ekranlarinda "listeye don -> baskasina tikla -> tekrar gir"
 * dongusunu ortadan kaldiran gezinme cubugu: onceki / sonraki
 * dugmeleri, dogrudan atlamak icin acilir liste ve "3 / 13" sayaci.
 *
 * Bileşen kendi verisini CEKMIYOR - listeyi ve aktif kaydi disaridan
 * aliyor. Boylece cizelge ekrani da (sube ve ogretmen), ogretmen ders
 * yetkileri ekrani da ayni bilesen ile calisiyor; yarin bir ucuncu
 * ekran gerekirse sadece prop vermek yetiyor.
 */
const props = defineProps({
    // [{ id, ad }] - kullaniciya GOSTERILDIGI sirayla
    kayitlar: { type: Array, default: () => [] },
    // Su an acik olan kaydin id'si (rota parametresinden geliyor)
    aktifId: { type: [String, Number], required: true },
    // Gidilecek rotanin adi; { id } parametresiyle cagriliyor
    rota: { type: String, required: true },
    etiket: { type: String, default: '' },
});

const router = useRouter();

/**
 * Rota parametreleri her zaman STRING gelir ("153"), listedeki id'ler
 * ise NUMBER (153). Karsilastirmayi bozmasin diye ikisini de String'e
 * ceviriyoruz - aksi halde sira hep -1 cikar ve dugmeler hic acilmaz.
 */
const sira = computed(() =>
    props.kayitlar.findIndex((kayit) => String(kayit.id) === String(props.aktifId))
);

const onceki = computed(() => (sira.value > 0 ? props.kayitlar[sira.value - 1] : null));

const sonraki = computed(() =>
    sira.value >= 0 && sira.value < props.kayitlar.length - 1
        ? props.kayitlar[sira.value + 1]
        : null
);

function git(id) {
    if (id !== null && id !== undefined && id !== '') {
        router.push({ name: props.rota, params: { id } });
    }
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <span v-if="etiket" class="text-sm text-gray-500">{{ etiket }}</span>

        <button
            type="button"
            :disabled="! onceki"
            :title="onceki ? onceki.ad : 'İlk kayıt'"
            class="rounded border border-gray-300 px-2.5 py-1.5 text-sm text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
            @click="git(onceki?.id)"
        >
            &larr;
        </button>

        <!--
            :value ile kontrol ediyoruz, v-model ile degil: secim
            dogrudan bir rota degisikligine donusuyor, ara bir yerel
            durum tutmuyoruz. Rota degisince aktifId de degisiyor ve
            secili secenek kendiliginden guncelleniyor.
        -->
        <select
            :value="String(aktifId)"
            class="max-w-[16rem] rounded border-gray-300 py-1.5 text-sm shadow-sm focus:border-gray-800 focus:ring-gray-800"
            @change="git($event.target.value)"
        >
            <option v-if="sira === -1" :value="String(aktifId)">— listede yok —</option>
            <option v-for="kayit in kayitlar" :key="kayit.id" :value="String(kayit.id)">
                {{ kayit.ad }}
            </option>
        </select>

        <button
            type="button"
            :disabled="! sonraki"
            :title="sonraki ? sonraki.ad : 'Son kayıt'"
            class="rounded border border-gray-300 px-2.5 py-1.5 text-sm text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
            @click="git(sonraki?.id)"
        >
            &rarr;
        </button>

        <span v-if="sira >= 0" class="text-xs tabular-nums text-gray-400">
            {{ sira + 1 }} / {{ kayitlar.length }}
        </span>
    </div>
</template>
