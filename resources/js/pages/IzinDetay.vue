<script setup>
import { computed, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Layout from '../components/Layout.vue';
import api from '../api';

const route = useRoute();
const router = useRouter();

const izin = ref(null);
const etkilenenDersler = ref([]);
const takaslar = ref([]);
const yukleniyor = ref(true);
const hata = ref('');
const mesaj = ref('');

// Takas paneli: hangi ders satiri icin acik ve adaylari neler.
const acikDers = ref(null);
const adaylar = ref([]);
const adaylarYukleniyor = ref(false);
const islemYapiliyor = ref(false);

const onayli = computed(() => izin.value?.status === 'approved');
const beklemede = computed(() => izin.value?.status === 'pending');

// Zaten takasa girmis dersler - ayni derse ikinci kez takas
// teklif etmemek icin.
const takastakiEntryIdleri = computed(() =>
    takaslar.value.map((t) => t.izinliEntryId).filter(Boolean)
);

function tarih(s) {
    return new Date(s).toLocaleString('tr-TR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

async function yukle() {
    const { data } = await api.get(`/izinler/${route.params.id}`);
    izin.value = data.izin;
    etkilenenDersler.value = data.etkilenenDersler;
    takaslar.value = data.takaslar ?? [];
    yukleniyor.value = false;
}

async function karar(tip) {
    hata.value = '';

    try {
        await api.post(`/izinler/${route.params.id}/${tip}`);

        // Onaydan sonra sayfada KALIYORUZ (eskiden listeye donuyordu):
        // takas ancak onaylanmis izinde yapilabildigi icin yoneticinin
        // dogal sonraki adimi burada.
        if (tip === 'onayla') {
            mesaj.value = 'İzin onaylandı. Aşağıdan ders takası yapabilirsiniz.';
            await yukle();
        } else {
            router.push({ name: 'izinler' });
        }
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'İşlem yapılamadı.';
    }
}

async function adaylariAc(ders) {
    // Ayni satira tekrar tiklanirsa panel kapansin.
    if (acikDers.value?.entryId === ders.entryId && acikDers.value?.tarihIso === ders.tarihIso) {
        acikDers.value = null;
        return;
    }

    acikDers.value = ders;
    adaylar.value = [];
    adaylarYukleniyor.value = true;
    hata.value = '';

    try {
        const { data } = await api.get(`/izinler/${route.params.id}/takas-adaylari`, {
            params: { entry_id: ders.entryId, tarih: ders.tarihIso },
        });

        adaylar.value = data.adaylar;
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Adaylar getirilemedi.';
    } finally {
        adaylarYukleniyor.value = false;
    }
}

async function takasYap(aday) {
    hata.value = '';
    mesaj.value = '';
    islemYapiliyor.value = true;

    try {
        const { data } = await api.post(`/izinler/${route.params.id}/takas`, {
            entry_id: acikDers.value.entryId,
            karsi_entry_id: aday.entryId,
            tarih: acikDers.value.tarihIso,
        });

        takaslar.value = data.takaslar;
        mesaj.value = data.mesaj;
        acikDers.value = null;
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Takas yapılamadı.';
    } finally {
        islemYapiliyor.value = false;
    }
}

async function takasGeriAl(takas) {
    if (! confirm('Bu takas geri alınsın mı? Ders eski saatine döner.')) {
        return;
    }

    hata.value = '';
    mesaj.value = '';
    islemYapiliyor.value = true;

    try {
        const { data } = await api.delete(`/izinler/${route.params.id}/takas/${takas.id}`);
        takaslar.value = data.takaslar;
        mesaj.value = data.mesaj;
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Geri alınamadı.';
    } finally {
        islemYapiliyor.value = false;
    }
}

onMounted(yukle);
</script>

<template>
    <Layout baslik="İzin Talebi">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <router-link :to="{ name: 'izinler' }" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; İzin Talepleri
                </router-link>

                <div v-if="mesaj" class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ mesaj }}
                </div>
                <div v-if="hata" class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ hata }}
                </div>

                <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                <template v-else>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500">Öğretmen</div>
                                <div class="text-gray-900">
                                    {{ izin.teacher?.first_name }} {{ izin.teacher?.last_name }}
                                </div>
                            </div>
                            <div>
                                <div class="text-gray-500">Durum</div>
                                <div class="text-gray-900">{{ izin.status }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Başlangıç</div>
                                <div class="text-gray-900 tabular-nums">{{ tarih(izin.starts_at) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Bitiş</div>
                                <div class="text-gray-900 tabular-nums">{{ tarih(izin.ends_at) }}</div>
                            </div>
                            <div class="col-span-2">
                                <div class="text-gray-500">Gerekçe</div>
                                <div class="text-gray-900">{{ izin.reason || '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sartname 2.7.2: "dersi var/yok gosterimi ile" -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-medium text-gray-900 mb-3">Etkilenen Ders Saatleri</h3>

                        <table v-if="etkilenenDersler.length" class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="py-2">Tarih</th>
                                    <th class="py-2">Gün</th>
                                    <th class="py-2">Saat</th>
                                    <th class="py-2">Şube</th>
                                    <th class="py-2">Ders</th>
                                    <th v-if="onayli" class="py-2 text-right">Takas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(d, i) in etkilenenDersler" :key="i">
                                    <tr class="border-b">
                                        <td class="py-2 tabular-nums">{{ d.tarih }}</td>
                                        <td class="py-2">{{ d.gunAdi }}</td>
                                        <td class="py-2 tabular-nums">{{ d.baslangic }}–{{ d.bitis }}</td>
                                        <td class="py-2">{{ d.sube }}</td>
                                        <td class="py-2">{{ d.ders }}</td>
                                        <td v-if="onayli" class="py-2 text-right">
                                            <span
                                                v-if="takastakiEntryIdleri.includes(d.entryId)"
                                                class="text-xs text-green-700"
                                            >
                                                takas yapıldı
                                            </span>
                                            <button
                                                v-else
                                                type="button"
                                                class="text-xs text-indigo-600 hover:underline"
                                                @click="adaylariAc(d)"
                                            >
                                                {{ acikDers?.entryId === d.entryId ? 'kapat' : 'takas yap' }}
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Aday paneli, ilgili satirin hemen altinda acilir -->
                                    <tr v-if="acikDers?.entryId === d.entryId && acikDers?.tarihIso === d.tarihIso">
                                        <td :colspan="onayli ? 6 : 5" class="bg-gray-50 px-3 py-4">
                                            <p class="mb-3 text-xs text-gray-600">
                                                Bu ders, <strong>{{ d.tarih }}</strong> günü aynı şubedeki başka bir
                                                ders saatiyle yer değiştirebilir. Takas <strong>yalnızca o güne
                                                özeldir</strong>; haftalık ders programı değişmez.
                                            </p>

                                            <p v-if="adaylarYukleniyor" class="text-xs text-gray-500">
                                                Adaylar getiriliyor...
                                            </p>

                                            <p v-else-if="!adaylar.length" class="text-xs text-gray-500">
                                                Bu gün için takas adayı yok.
                                            </p>

                                            <ul v-else class="space-y-1.5">
                                                <li
                                                    v-for="aday in adaylar"
                                                    :key="aday.entryId"
                                                    class="flex items-center gap-3 rounded border bg-white px-3 py-2"
                                                    :class="aday.uygun ? 'border-gray-200' : 'border-gray-100'"
                                                >
                                                    <span class="w-24 shrink-0 tabular-nums text-xs text-gray-500">
                                                        {{ aday.slot }}. ders · {{ aday.baslangic }}
                                                    </span>

                                                    <span class="min-w-0 flex-1">
                                                        <span class="text-sm" :class="aday.uygun ? 'text-gray-800' : 'text-gray-400'">
                                                            {{ aday.ders }}
                                                        </span>
                                                        <span class="block text-xs" :class="aday.uygun ? 'text-gray-500' : 'text-gray-400'">
                                                            {{ aday.ogretmen }}
                                                        </span>
                                                    </span>

                                                    <button
                                                        v-if="aday.uygun"
                                                        type="button"
                                                        :disabled="islemYapiliyor"
                                                        class="shrink-0 rounded bg-gray-800 px-3 py-1.5 text-xs text-white hover:bg-gray-700 disabled:opacity-50"
                                                        @click="takasYap(aday)"
                                                    >
                                                        Bu saatle takas et
                                                    </button>

                                                    <!-- Uygun olmayan aday GIZLENMIYOR, sebebiyle
                                                         gosteriliyor: yonetici listenin neden kisa
                                                         oldugunu anlasin, eksiklik saniyip
                                                         sistemi hatali zannetmesin. -->
                                                    <span v-else class="max-w-xs shrink-0 text-right text-xs text-gray-400">
                                                        {{ aday.sebep }}
                                                    </span>
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <p v-else class="text-sm text-gray-500">
                            Bu izin aralığında öğretmenin programında ders yok.
                        </p>
                    </div>

                    <!-- Yapilmis takaslar -->
                    <div v-if="takaslar.length" class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="mb-1 font-medium text-gray-900">Yapılan Ders Takasları</h3>
                        <p class="mb-3 text-xs text-gray-500">
                            Bu değişiklikler yalnızca belirtilen tarihte geçerlidir; haftalık ders programı
                            olduğu gibi kalır. Geri alındığında ders eski saatine döner.
                        </p>

                        <ul class="space-y-2">
                            <li
                                v-for="t in takaslar"
                                :key="t.id"
                                class="flex items-center gap-3 rounded border border-gray-200 px-3 py-2 text-sm"
                            >
                                <span class="shrink-0 tabular-nums text-xs text-gray-500">{{ t.tarih }}</span>

                                <span class="min-w-0 flex-1 text-gray-800">
                                    <strong>{{ t.sube }}</strong> ·
                                    {{ t.izinliDers }} ({{ t.izinliSlot }}. ders)
                                    &harr;
                                    {{ t.karsiDers }} ({{ t.karsiSlot }}. ders, {{ t.karsiOgretmen }})
                                </span>

                                <button
                                    type="button"
                                    :disabled="islemYapiliyor"
                                    class="shrink-0 text-xs text-red-600 hover:underline disabled:opacity-50"
                                    @click="takasGeriAl(t)"
                                >
                                    Geri al
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div v-if="beklemede" class="bg-white shadow-sm sm:rounded-lg p-6 flex gap-3">
                        <button @click="karar('onayla')"
                                class="px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                            Onayla
                        </button>
                        <button @click="karar('reddet')"
                                class="px-4 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                            Reddet
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </Layout>
</template>
