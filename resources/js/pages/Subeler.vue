<script setup>
import { ref, computed, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';

const subeler = ref([]);
const seviyeler = ref([]);
const derslikler = ref([]);
const yukleniyor = ref(true);
const mesaj = ref('');
const hata = ref('');

const formAcik = ref(false);
const duzenlenenId = ref(null);
const form = ref({ level_id: '', name: '', room_id: '' });

/**
 * Blade tarafinda controller "bos derslikler" listesini kendisi
 * hazirliyordu (Room::whereDoesntHave('section')). API'de ayri bir
 * endpoint acmak yerine, /derslikler zaten her derslik icin
 * section_count donduruyor - bos olanlari burada suzuyoruz. Duzenleme
 * modunda subenin KENDI dersligi de listede kalmali, yoksa kullanici
 * kendi dersligini goremez.
 */
const secilebilirDerslikler = computed(() =>
    derslikler.value.filter(
        (d) => d.section_count === 0 || d.id === form.value.room_id
    )
);

async function yukle() {
    const [s, sv, d] = await Promise.all([
        api.get('/subeler'),
        api.get('/seviyeler'),
        api.get('/derslikler'),
    ]);

    subeler.value = s.data;
    seviyeler.value = sv.data;
    derslikler.value = d.data;
    yukleniyor.value = false;
}

function formAc(sube = null) {
    duzenlenenId.value = sube?.id ?? null;
    form.value = {
        level_id: sube?.level_id ?? '',
        name: sube?.name ?? '',
        room_id: sube?.room_id ?? '',
    };
    hata.value = '';
    formAcik.value = true;
}

async function kaydet() {
    hata.value = '';

    const gonderilecek = {
        level_id: form.value.level_id,
        name: form.value.name,
        room_id: form.value.room_id || null,
    };

    try {
        if (duzenlenenId.value) {
            await api.put(`/subeler/${duzenlenenId.value}`, gonderilecek);
            mesaj.value = 'Şube güncellendi.';
        } else {
            await api.post('/subeler', gonderilecek);
            mesaj.value = 'Şube eklendi.';
        }

        formAcik.value = false;
        await yukle();
    } catch (e) {
        const hatalar = e.response?.data?.errors ?? {};
        hata.value = hatalar.name?.[0] ?? hatalar.level_id?.[0] ?? hatalar.room_id?.[0] ?? 'Bir hata oluştu.';
    }
}

async function sil(sube) {
    if (! confirm(`${sube.name} silinsin mi?`)) {
        return;
    }

    mesaj.value = '';
    hata.value = '';

    try {
        await api.delete(`/subeler/${sube.id}`);
        mesaj.value = 'Şube silindi.';
        await yukle();
    } catch (e) {
        hata.value = e.response?.data?.message ?? 'Silinemedi.';
    }
}

onMounted(yukle);
</script>

<template>
    <Layout baslik="Şubeler">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-if="mesaj" class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ mesaj }}
                </div>
                <div v-if="hata" class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ hata }}
                </div>

                <div>
                    <button @click="formAc()"
                            class="inline-block px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                        Yeni Şube Ekle
                    </button>
                </div>

                <div v-if="formAcik" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-medium text-gray-900">
                        {{ duzenlenenId ? 'Şubeyi Düzenle' : 'Yeni Şube' }}
                    </h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Seviye</label>
                        <select v-model="form.level_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">— Seçin —</option>
                            <option v-for="s in seviyeler" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Şube Adı</label>
                        <input v-model="form.name" type="text" placeholder="9A"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Derslik</label>
                        <select v-model="form.room_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">— Derslik atanmamış —</option>
                            <option v-for="d in secilebilirDerslikler" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            Sadece boştaki derslikler listelenir; bir derslik yalnızca bir şubeye ait olabilir.
                        </p>
                    </div>

                    <div class="flex gap-3">
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
                                <th class="py-2">Şube</th>
                                <th class="py-2">Seviye</th>
                                <th class="py-2">Derslik</th>
                                <th class="py-2 text-right">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sube in subeler" :key="sube.id" class="border-b">
                                <td class="py-2">{{ sube.name }}</td>
                                <td class="py-2">{{ sube.level?.name ?? '—' }}</td>
                                <td class="py-2">{{ sube.room?.name ?? '—' }}</td>
                                <td class="py-2 text-right space-x-3">
                                    <button @click="formAc(sube)" class="text-blue-600 hover:underline">Düzenle</button>
                                    <button @click="sil(sube)" class="text-red-600 hover:underline">Sil</button>
                                </td>
                            </tr>
                            <tr v-if="subeler.length === 0">
                                <td colspan="4" class="py-6 text-center text-gray-500">
                                    Henüz şube tanımlanmamış.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Layout>
</template>
