<script setup>
import { ref } from 'vue';

const props = defineProps({
    action: String,
    method: { type: String, default: 'POST' },
    initialName: { type: String, default: '' },
    csrfToken: String,
});

const name = ref(props.initialName);
const hata = ref('');

function gonder(e) {
    if (!name.value.trim()) {
        e.preventDefault();
        hata.value = 'Derslik adı boş olamaz.';
        return;
    }
    hata.value = '';
}
</script>

<template>
    <form :action="action" method="POST" @submit="gonder" class="space-y-6">
        <input type="hidden" name="_token" :value="csrfToken">
        <input v-if="method !== 'POST'" type="hidden" name="_method" :value="method">

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
                Derslik Adı (örn. D-9A, Fizik Lab)
            </label>
            <input id="name" name="name" type="text" v-model="name"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            <p v-if="hata" class="mt-2 text-sm text-red-600">{{ hata }}</p>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                Kaydet
            </button>
            <a href="/derslikler" class="text-sm text-gray-600 hover:text-gray-900">Vazgeç</a>
        </div>
    </form>
</template>
