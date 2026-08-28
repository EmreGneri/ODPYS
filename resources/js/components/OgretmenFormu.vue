<script setup>
import { ref } from 'vue';

const props = defineProps({
    action: String,
    method: { type: String, default: 'POST' },
    azamiSaat: { type: [String, Number], default: 35 },
    initialFirstName: { type: String, default: '' },
    initialLastName: { type: String, default: '' },
    initialNationalId: { type: String, default: '' },
    initialBirthDate: { type: String, default: '' },
    initialEmail: { type: String, default: '' },
    initialMaxWeeklyHours: { type: [String, Number], default: 30 },
    initialIsActive: { type: [String, Number, Boolean], default: true },
    initialPhotoUrl: { type: String, default: '' },
    initialCvUrl: { type: String, default: '' },
    csrfToken: String,
});

const firstName = ref(props.initialFirstName);
const lastName = ref(props.initialLastName);
const nationalId = ref(props.initialNationalId);
const birthDate = ref(props.initialBirthDate);
const email = ref(props.initialEmail);
const maxWeeklyHours = ref(props.initialMaxWeeklyHours);
const isActive = ref(
    props.initialIsActive === true ||
    props.initialIsActive === 1 ||
    props.initialIsActive === '1'
);
const hata = ref('');

// Dosya alanlarına ref/v-model bağlanmıyor: tarayıcı güvenlik gereği
// <input type="file"> değerinin JavaScript'ten yazılmasına izin vermiyor.
// Form normal POST attığı için dosyayı tarayıcı kendisi taşıyor.

function gonder(e) {
    if (!firstName.value.trim() || !lastName.value.trim()) {
        e.preventDefault();
        hata.value = 'Ad ve soyad zorunludur.';
        return;
    }
    if (!/^\d{11}$/.test(nationalId.value)) {
        e.preventDefault();
        hata.value = 'TC kimlik numarası 11 rakam olmalıdır.';
        return;
    }
    if (!birthDate.value) {
        e.preventDefault();
        hata.value = 'Doğum tarihi zorunludur.';
        return;
    }
    if (!email.value.trim()) {
        e.preventDefault();
        hata.value = 'E-posta zorunludur.';
        return;
    }
    const saat = Number(maxWeeklyHours.value);
    if (!saat || saat < 1 || saat > Number(props.azamiSaat)) {
        e.preventDefault();
        hata.value = `Haftalık azami saat 1 ile ${props.azamiSaat} arasında olmalıdır.`;
        return;
    }
    hata.value = '';
}
</script>

<template>
    <form :action="action" method="POST" enctype="multipart/form-data"
          @submit="gonder" class="space-y-6">

        <input type="hidden" name="_token" :value="csrfToken">
        <input v-if="method !== 'POST'" type="hidden" name="_method" :value="method">

        <p v-if="hata" class="text-sm text-red-600">{{ hata }}</p>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700">Ad</label>
                <input id="first_name" name="first_name" type="text" v-model="firstName"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700">Soyad</label>
                <input id="last_name" name="last_name" type="text" v-model="lastName"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="national_id" class="block text-sm font-medium text-gray-700">TC Kimlik No</label>
                <input id="national_id" name="national_id" type="text" inputmode="numeric" maxlength="11"
                       v-model="nationalId"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <div>
                <label for="birth_date" class="block text-sm font-medium text-gray-700">Doğum Tarihi</label>
                <input id="birth_date" name="birth_date" type="date" v-model="birthDate"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">E-posta</label>
            <input id="email" name="email" type="email" v-model="email"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label for="max_weekly_hours" class="block text-sm font-medium text-gray-700">
                Haftalık Azami Ders Saati
            </label>
            <input id="max_weekly_hours" name="max_weekly_hours" type="number"
                   min="1" :max="azamiSaat" v-model="maxWeeklyHours"
                   class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm text-sm">
            <p class="mt-1 text-xs text-gray-500">
                Okulun haftalık ders kapasitesi {{ azamiSaat }} saat.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="photo" class="block text-sm font-medium text-gray-700">Fotoğraf</label>

                <img v-if="initialPhotoUrl" :src="initialPhotoUrl" alt=""
                     class="mt-2 h-20 w-20 rounded object-cover border border-gray-200">

                <input id="photo" name="photo" type="file" accept="image/*"
                       class="mt-2 block w-full text-sm text-gray-600
                              file:mr-3 file:py-2 file:px-3 file:rounded file:border-0
                              file:bg-gray-100 file:text-sm hover:file:bg-gray-200">
                <p class="mt-1 text-xs text-gray-500">
                    JPG, PNG veya WEBP — en fazla 2 MB.
                    <span v-if="initialPhotoUrl">Yeni dosya seçilirse eskisi silinir.</span>
                </p>
            </div>

            <div>
                <label for="cv" class="block text-sm font-medium text-gray-700">Özgeçmiş (PDF)</label>

                <a v-if="initialCvUrl" :href="initialCvUrl" target="_blank"
                   class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-900">
                    Mevcut özgeçmişi görüntüle
                </a>

                <input id="cv" name="cv" type="file" accept="application/pdf"
                       class="mt-2 block w-full text-sm text-gray-600
                              file:mr-3 file:py-2 file:px-3 file:rounded file:border-0
                              file:bg-gray-100 file:text-sm hover:file:bg-gray-200">
                <p class="mt-1 text-xs text-gray-500">
                    PDF — en fazla 5 MB.
                    <span v-if="initialCvUrl">Yeni dosya seçilirse eskisi silinir.</span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input id="is_active" name="is_active" type="checkbox" value="1" v-model="isActive"
                   class="rounded border-gray-300">
            <label for="is_active" class="text-sm text-gray-700">Aktif (programa dahil edilsin)</label>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                Kaydet
            </button>
            <a href="/ogretmenler" class="text-sm text-gray-600 hover:text-gray-900">Vazgeç</a>
        </div>
    </form>
</template>
