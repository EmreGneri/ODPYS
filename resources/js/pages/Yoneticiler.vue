<script setup>
import { computed, ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../api';
import { useAuthStore } from '../stores/auth';

/**
 * Yonetici hesaplari - sadece super yonetici gorur (rota
 * meta.role: 'superadmin', asil kilit API'deki superadmin
 * middleware'inde).
 */
const auth = useAuthStore();

const yoneticiler = ref([]);
const okullar = ref([]);
const yukleniyor = ref(true);
const mesaj = ref('');
const hatalar = ref({});

const formAcik = ref(false);
const duzenlenenId = ref(null);
const form = ref({ name: '', email: '', password: '', role: 'admin', school_id: '' });

// Rol 'admin' ise okul zorunlu, 'superadmin' ise anlamsiz - form da
// backend de ayni kurala uyuyor.
const okulGerekli = computed(() => form.value.role === 'admin');

function rolAdi(rol) {
    return rol === 'superadmin' ? 'Süper Yönetici' : 'Okul Yöneticisi';
}

async function yukle() {
    const { data } = await api.get('/yoneticiler');
    yoneticiler.value = data.yoneticiler;
    okullar.value = data.okullar;
    yukleniyor.value = false;
}

function formAc(yonetici = null) {
    hatalar.value = {};
    mesaj.value = '';
    duzenlenenId.value = yonetici?.id ?? null;

    form.value = {
        name: yonetici?.ad ?? '',
        email: yonetici?.eposta ?? '',
        password: '',
        role: yonetici?.rol ?? 'admin',
        school_id: yonetici?.okulId ?? '',
    };

    formAcik.value = true;
}

async function kaydet() {
    hatalar.value = {};
    mesaj.value = '';

    try {
        if (duzenlenenId.value) {
            // Duzenlemede sadece rol ve okul degisiyor; ad/e-posta/sifre
            // kisinin kendi Profil ekraninda.
            const { data } = await api.put(`/yoneticiler/${duzenlenenId.value}`, {
                role: form.value.role,
                school_id: okulGerekli.value ? form.value.school_id : null,
            });
            mesaj.value = data.mesaj;
        } else {
            const { data } = await api.post('/yoneticiler', {
                ...form.value,
                school_id: okulGerekli.value ? form.value.school_id : null,
            });
            mesaj.value = data.mesaj;
        }

        formAcik.value = false;
        await yukle();
    } catch (e) {
        hatalar.value = e.response?.data?.errors ?? {};

        if (! Object.keys(hatalar.value).length) {
            hatalar.value = { genel: [e.response?.data?.message ?? 'Kaydedilemedi.'] };
        }
    }
}

async function sil(yonetici) {
    if (! confirm(`${yonetici.ad} adlı yönetici silinsin mi?`)) {
        return;
    }

    hatalar.value = {};
    mesaj.value = '';

    try {
        await api.delete(`/yoneticiler/${yonetici.id}`);
        mesaj.value = 'Yönetici silindi.';
        await yukle();
    } catch (e) {
        hatalar.value = { genel: [e.response?.data?.message ?? 'Silinemedi.'] };
    }
}

onMounted(yukle);
</script>

<template>
    <Layout baslik="Yöneticiler">
        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm leading-relaxed text-gray-600">
                        <strong>Süper yönetici</strong> bütün okulları görür, okul ekleyebilir ve
                        buradan yönetici atayabilir. <strong>Okul yöneticisi</strong> ise yalnızca
                        atandığı okulun verilerine erişir; okullar arasında geçiş yapamaz.
                    </p>
                </div>

                <div v-if="mesaj" class="rounded border border-green-300 bg-green-100 px-4 py-3 text-sm text-green-800">
                    {{ mesaj }}
                </div>
                <div v-if="hatalar.genel" class="rounded border border-red-300 bg-red-100 px-4 py-3 text-sm text-red-800">
                    {{ hatalar.genel[0] }}
                </div>

                <div>
                    <button
                        class="rounded bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-700"
                        @click="formAc()"
                    >
                        Yeni Yönetici Ekle
                    </button>
                </div>

                <div v-if="formAcik" class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-medium text-gray-900">
                        {{ duzenlenenId ? 'Yetkileri Düzenle' : 'Yeni Yönetici' }}
                    </h3>

                    <template v-if="! duzenlenenId">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ad Soyad</label>
                            <input v-model="form.name" type="text"
                                   class="mt-1 block w-full max-w-md rounded-md border-gray-300 text-sm shadow-sm">
                            <p v-if="hatalar.name" class="mt-1 text-xs text-red-600">{{ hatalar.name[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">E-posta</label>
                            <input v-model="form.email" type="email"
                                   class="mt-1 block w-full max-w-md rounded-md border-gray-300 text-sm shadow-sm">
                            <p v-if="hatalar.email" class="mt-1 text-xs text-red-600">{{ hatalar.email[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Şifre</label>
                            <input v-model="form.password" type="password" autocomplete="new-password"
                                   class="mt-1 block w-full max-w-md rounded-md border-gray-300 text-sm shadow-sm">
                            <p v-if="hatalar.password" class="mt-1 text-xs text-red-600">{{ hatalar.password[0] }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                En az 8 karakter. Yönetici sonradan kendi Profil ekranından değiştirebilir.
                            </p>
                        </div>
                    </template>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Yetki</label>
                        <select v-model="form.role"
                                class="mt-1 block w-full max-w-md rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="admin">Okul Yöneticisi — sadece seçilen okul</option>
                            <option value="superadmin">Süper Yönetici — bütün okullar</option>
                        </select>
                        <p v-if="hatalar.role" class="mt-1 text-xs text-red-600">{{ hatalar.role[0] }}</p>
                    </div>

                    <div v-if="okulGerekli">
                        <label class="block text-sm font-medium text-gray-700">Okul</label>
                        <select v-model="form.school_id"
                                class="mt-1 block w-full max-w-md rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">— seçiniz —</option>
                            <option v-for="o in okullar" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                        <p v-if="hatalar.school_id" class="mt-1 text-xs text-red-600">{{ hatalar.school_id[0] }}</p>
                    </div>

                    <div class="flex gap-3">
                        <button class="rounded bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-700"
                                @click="kaydet">
                            Kaydet
                        </button>
                        <button class="text-sm text-gray-600 hover:text-gray-900" @click="formAcik = false">
                            Vazgeç
                        </button>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p v-if="yukleniyor" class="text-sm text-gray-500">Yükleniyor...</p>

                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="py-2">Ad</th>
                                <th class="py-2">E-posta</th>
                                <th class="py-2">Yetki</th>
                                <th class="py-2">Okul</th>
                                <th class="py-2 text-right">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="y in yoneticiler" :key="y.id" class="border-b">
                                <td class="py-2">
                                    {{ y.ad }}
                                    <span v-if="y.id === auth.kullanici?.id" class="text-xs text-gray-400">(siz)</span>
                                </td>
                                <td class="py-2">{{ y.eposta }}</td>
                                <td class="py-2">
                                    <span
                                        class="rounded px-2 py-0.5 text-xs"
                                        :class="y.rol === 'superadmin'
                                            ? 'bg-indigo-100 text-indigo-800'
                                            : 'bg-gray-100 text-gray-700'"
                                    >
                                        {{ rolAdi(y.rol) }}
                                    </span>
                                </td>
                                <td class="py-2">{{ y.okul ?? '— bütün okullar —' }}</td>
                                <td class="space-x-3 py-2 text-right">
                                    <button class="text-blue-600 hover:underline" @click="formAc(y)">Yetki</button>
                                    <button
                                        v-if="y.id !== auth.kullanici?.id"
                                        class="text-red-600 hover:underline"
                                        @click="sil(y)"
                                    >
                                        Sil
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Layout>
</template>
