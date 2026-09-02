<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Layout from '../components/Layout.vue';
import api from '../api';

const router = useRouter();

const startsAt = ref('');
const endsAt = ref('');
const reason = ref('');
const hata = ref('');
const gonderiliyor = ref(false);

async function gonder() {
    hata.value = '';
    gonderiliyor.value = true;

    try {
        // teacher_id GONDERILMIYOR - backend onu oturum acmis
        // kullanicinin kendi ogretmen kaydindan aliyor (IzinController),
        // boylece kimse baskasi adina talep olusturamaz.
        await api.post('/ogretmen/izinler', {
            starts_at: startsAt.value,
            ends_at: endsAt.value,
            reason: reason.value || null,
        });

        router.push({ name: 'ogretmen-izinler' });
    } catch (e) {
        const hatalar = e.response?.data?.errors ?? {};
        hata.value = Object.values(hatalar)[0]?.[0] ?? 'Talep oluşturulamadı.';
    } finally {
        gonderiliyor.value = false;
    }
}
</script>

<template>
    <Layout baslik="Yeni İzin Talebi">
        <div class="py-12">
            <div class="max-w-lg mx-auto sm:px-6 lg:px-8 space-y-6">
                <router-link :to="{ name: 'ogretmen-izinler' }" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; İzin Taleplerim
                </router-link>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <p v-if="hata" class="text-sm text-red-600">{{ hata }}</p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Başlangıç</label>
                        <input v-model="startsAt" type="datetime-local"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bitiş</label>
                        <input v-model="endsAt" type="datetime-local"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gerekçe (opsiyonel)</label>
                        <textarea v-model="reason" rows="3" maxlength="500"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"></textarea>
                    </div>

                    <div class="flex gap-3 border-t pt-4">
                        <button @click="gonder" :disabled="gonderiliyor"
                                class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700 disabled:opacity-50">
                            {{ gonderiliyor ? 'Gönderiliyor...' : 'Talebi Gönder' }}
                        </button>
                        <router-link :to="{ name: 'ogretmen-izinler' }" class="text-sm text-gray-600 hover:text-gray-900">
                            Vazgeç
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
