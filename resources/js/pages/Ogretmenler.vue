<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const sonuc = ref({ data: [], current_page: 1, last_page: 1 });
const yukleniyor = ref(true);
const mesaj = ref('');
const hata = ref('');
const yeniHesap = ref(null);

const ara = ref('');
const durum = ref('');

const formAcik = ref(false);
const duzenlenenId = ref(null);
const form = ref({});
const fotografDosya = ref(null);
const cvDosya = ref(null);

async function yukle(sayfa = 1) {
    yukleniyor.value = true;

    const { data } = await api.get('/ogretmenler', {
        params: { ara: ara.value || undefined, durum: durum.value || undefined, page: sayfa },
    });

    sonuc.value = data;
    yukleniyor.value = false;
}

function formAc(ogretmen = null) {
    duzenlenenId.value = ogretmen?.id ?? null;
    form.value = {
        first_name: ogretmen?.first_name ?? '',
        last_name: ogretmen?.last_name ?? '',
        national_id: ogretmen?.national_id ?? '',
        birth_date: ogretmen?.birth_date?.substring(0, 10) ?? '',
        email: ogretmen?.email ?? '',
        max_weekly_hours: ogretmen?.max_weekly_hours ?? 30,
        is_active: ogretmen ? Boolean(ogretmen.is_active) : true,
    };
    fotografDosya.value = null;
    cvDosya.value = null;
    hata.value = '';
    formAcik.value = true;
}

/**
 * Dosya yuklemesi oldugu icin JSON degil FormData gonderiyoruz.
 * Guncellemede tarayicilar <input type=file> icerigini PUT ile native
 * gondermiyor - bu yuzden POST atip govdeye _method=PUT ekliyoruz
 * (Laravel'in "method spoofing" mekanizmasi, Blade formlarindaki
 * @method('PUT') ile ayni sey).
 */
async function kaydet() {
    hata.value = '';

    const fd = new FormData();
    Object.entries(form.value).forEach(([anahtar, deger]) => {
        fd.append(anahtar, anahtar === 'is_active' ? (deger ? 1 : 0) : deger);
    });

    if (fotografDosya.value) fd.append('photo', fotografDosya.value);
    if (cvDosya.value) fd.append('cv', cvDosya.value);

    try {
        if (duzenlenenId.value) {
            fd.append('_method', 'PUT');
            await api.post(`/ogretmenler/${duzenlenenId.value}`, fd);
            mesaj.value = 'Öğretmen güncellendi.';
        } else {
            await api.post('/ogretmenler', fd);
            mesaj.value = 'Öğretmen eklendi.';
        }

        formAcik.value = false;
        await yukle(sonuc.value.current_page);
    } catch (e) {
        const hatalar = e.response?.data?.errors ?? {};
        hata.value = Object.values(hatalar)[0]?.[0] ?? 'Bir hata oluştu.';
    }
}

async function sil(ogretmen) {
    if (! confirm(`${ogretmen.first_name} ${ogretmen.last_name} silinsin mi?`)) {
        return;
    }

    mesaj.value = '';
    hata.value = '';

    try {
        await api.delete(`/ogretmenler/${ogretmen.id}`);
        mesaj.value = 'Öğretmen silindi.';
        await yukle(sonuc.value.current_page);
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Silinemedi.';
    }
}

async function hesapAc(ogretmen) {
    mesaj.value = '';
    hata.value = '';
    yeniHesap.value = null;

    try {
        const { data } = await api.post(`/ogretmenler/${ogretmen.id}/hesap`);
        yeniHesap.value = data;
        await yukle(sonuc.value.current_page);
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Hesap açılamadı.';
    }
}

onMounted(() => yukle());
</script>

<template>
    <Layout baslik="Öğretmenler">
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-if="mesaj" class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ mesaj }}
                </div>
                <div v-if="hata" class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ hata }}
                </div>

                <div v-if="yeniHesap" class="bg-blue-50 border border-blue-300 text-blue-900 px-4 py-3 rounded text-sm">
                    <strong>{{ yeniHesap.ad }}</strong> için giriş hesabı açıldı.<br>
                    E-posta: <code>{{ yeniHesap.eposta }}</code><br>
                    Geçici parola: <code class="font-mono">{{ yeniHesap.parola }}</code><br>
                    <span class="text-xs">Bu parola bir daha gösterilmeyecek, öğretmene iletin.</span>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs text-gray-500">Ara</label>
                        <input v-model="ara" @keyup.enter="yukle()" type="text" placeholder="ad, e-posta, TC"
                               class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Durum</label>
                        <select v-model="durum" @change="yukle()" class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">Hepsi</option>
                            <option value="aktif">Aktif</option>
                            <option value="pasif">Pasif</option>
                        </select>
                    </div>
                    <button @click="yukle()" class="px-4 py-2 border border-gray-300 text-sm rounded hover:bg-gray-50">
                        Filtrele
                    </button>

                    <button @click="formAc()"
                            class="ml-auto px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                        Yeni Öğretmen Ekle
                    </button>
                </div>

                <div v-if="formAcik" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-medium text-gray-900">
                        {{ duzenlenenId ? 'Öğretmeni Düzenle' : 'Yeni Öğretmen' }}
                    </h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ad</label>
                            <input v-model="form.first_name" type="text"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Soyad</label>
                            <input v-model="form.last_name" type="text"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">TC Kimlik No</label>
                            <input v-model="form.national_id" type="text" maxlength="11"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Doğum Tarihi</label>
                            <input v-model="form.birth_date" type="date"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">E-posta</label>
                            <input v-model="form.email" type="email"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Haftalık Azami Saat</label>
                            <input v-model="form.max_weekly_hours" type="number" min="1"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fotoğraf</label>
                            <input @change="fotografDosya = $event.target.files[0]" type="file" accept="image/*"
                                   class="mt-1 block w-full text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Özgeçmiş (PDF)</label>
                            <input @change="cvDosya = $event.target.files[0]" type="file" accept="application/pdf"
                                   class="mt-1 block w-full text-sm">
                        </div>
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700">Aktif</span>
                    </label>

                    <div class="flex gap-3 border-t pt-4">
                        <button @click="kaydet"
                                class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                            Kaydet
                        </button>
                        <button @click="formAcik = false" class="text-sm text-gray-600 hover:text-gray-900">
                            Vazgeç
                        </button>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                    <p v-if="yukleniyor" class="p-6 text-sm text-gray-500">Yükleniyor...</p>

                    <table v-else class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="border-b text-left text-gray-500">
                                <th class="px-6 py-3 font-medium">Ad Soyad</th>
                                <th class="px-6 py-3 font-medium">E-posta</th>
                                <th class="px-6 py-3 font-medium">Haftalık Azami</th>
                                <th class="px-6 py-3 font-medium">Durum</th>
                                <th class="px-6 py-3 font-medium">Hesap</th>
                                <th class="px-6 py-3 text-right font-medium">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="o in sonuc.data" :key="o.id">
                                <td class="px-6 py-3">{{ o.first_name }} {{ o.last_name }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ o.email }}</td>
                                <td class="px-6 py-3 tabular-nums">{{ o.max_weekly_hours }}</td>
                                <td class="px-6 py-3">
                                    <span v-if="o.is_active" class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">Aktif</span>
                                    <span v-else class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs">Pasif</span>
                                </td>
                                <td class="px-6 py-3">
                                    <span v-if="o.user_id" class="text-xs text-gray-500">Var</span>
                                    <button v-else @click="hesapAc(o)" class="text-xs text-indigo-600 hover:underline">
                                        Hesap Aç
                                    </button>
                                </td>
                                <td class="px-6 py-3 text-right space-x-3">
                                    <router-link :to="{ name: 'ogretmen-dersleri', params: { id: o.id } }"
                                                 class="text-indigo-600 hover:underline">Dersleri</router-link>
                                    <button @click="formAc(o)" class="text-blue-600 hover:underline">Düzenle</button>
                                    <button @click="sil(o)" class="text-red-600 hover:underline">Sil</button>
                                </td>
                            </tr>
                            <tr v-if="sonuc.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Öğretmen bulunamadı.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="sonuc.last_page > 1" class="flex items-center gap-2">
                    <button v-for="s in sonuc.last_page" :key="s" @click="yukle(s)"
                            class="px-3 py-1 text-sm rounded border"
                            :class="s === sonuc.current_page ? 'bg-gray-800 text-white border-gray-800' : 'border-gray-300 hover:bg-gray-50'">
                        {{ s }}
                    </button>
                </div>
            </div>
        </div>
    </Layout>
</template>
