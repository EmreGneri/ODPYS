<script setup>
import { ref } from 'vue';

const props = defineProps({
    action: String,
    method: { type: String, default: 'POST' },
    seviyeler: { type: Array, default: () => [] },
    derslikler: { type: Array, default: () => [] },
    initialLevelId: { type: [String, Number], default: '' },
    initialName: { type: String, default: '' },
    initialRoomId: { type: [String, Number], default: '' },
    csrfToken: String,
});

const levelId = ref(props.initialLevelId);
const name = ref(props.initialName);
const roomId = ref(props.initialRoomId);
const hata = ref('');

function gonder(e) {
    if (!levelId.value) {
        e.preventDefault();
        hata.value = 'Seviye seçmelisiniz.';
        return;
    }
    if (!name.value.trim()) {
        e.preventDefault();
        hata.value = 'Şube adı boş olamaz.';
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
            <label for="level_id" class="block text-sm font-medium text-gray-700">Seviye</label>
            <select id="level_id" name="level_id" v-model="levelId"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">Seçiniz</option>
                <option v-for="seviye in seviyeler" :key="seviye.id" :value="seviye.id">
                    {{ seviye.name }}
                </option>
            </select>
        </div>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Şube Adı (örn. 9A)</label>
            <input id="name" name="name" type="text" v-model="name"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label for="room_id" class="block text-sm font-medium text-gray-700">Derslik</label>
            <select id="room_id" name="room_id" v-model="roomId"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">Derslik atanmadı</option>
                <option v-for="derslik in derslikler" :key="derslik.id" :value="derslik.id">
                    {{ derslik.name }}
                </option>
            </select>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                Kaydet
            </button>
            <a href="/subeler" class="text-sm text-gray-600 hover:text-gray-900">Vazgeç</a>
        </div>
    </form>
</template>
