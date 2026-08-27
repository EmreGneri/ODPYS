<script setup>
import { ref } from 'vue';

const props = defineProps({
    action: String,
    method: { type: String, default: 'POST' },
    initialGradeNumber: { type: [String, Number], default: '' },
    csrfToken: String,
});

const gradeNumber = ref(props.initialGradeNumber);
const hata = ref('');

function gonder(e) {
    if (gradeNumber.value < 9 || gradeNumber.value > 12) {
        e.preventDefault();
        hata.value = 'Sınıf numarası 9-12 arasında olmalıdır.';
    } else {
        hata.value = '';
    }
}
</script>

<template>
    <form :action="action" method="POST" @submit="gonder" class="space-y-6">
        <input type="hidden" name="_token" :value="csrfToken">
        <input v-if="method !== 'POST'" type="hidden" name="_method" :value="method">

        <div>
            <label for="grade_number" class="block text-sm font-medium text-gray-700">
                Sınıf Numarası (9-12)
            </label>
            <input
                id="grade_number"
                name="grade_number"
                type="number"
                v-model="gradeNumber"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"
                required
            >
            <p v-if="hata" class="mt-2 text-sm text-red-600">{{ hata }}</p>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                Kaydet
            </button>
            <a href="/seviyeler" class="text-sm text-gray-600 hover:text-gray-900">Vazgeç</a>
        </div>
    </form>
</template>
