<script setup>
import { useRouter } from 'vue-router';
import GirisKarti from '../components/GirisKarti.vue';

const router = useRouter();

/**
 * Sartname: yonetici ve ogretmen icin AYRIK girisler. Iki secenek de
 * ayni bilesene (Giris.vue) gidiyor, farki :rol parametresi -
 * arka uctaki AuthenticatedSessionController, sifre dogru olsa bile
 * rolu tutmayan kullaniciyi geri ceviriyor.
 */
const secenekler = [
    {
        rol: 'yonetici',
        harf: 'Y',
        ad: 'Yönetici Girişi',
        aciklama: 'Okul, program, çizelge ve izin yönetimi',
    },
    {
        rol: 'ogretmen',
        harf: 'Ö',
        ad: 'Öğretmen Girişi',
        aciklama: 'Kendi programınız ve izin talepleriniz',
    },
];
</script>

<template>
    <GirisKarti
        baslik="Nasıl giriş yapmak istiyorsunuz?"
        aciklama="Yönetici ve öğretmen girişleri ayrıdır."
    >
        <div class="space-y-3">
            <button
                v-for="secenek in secenekler"
                :key="secenek.rol"
                type="button"
                class="group flex w-full items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-left transition hover:border-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2"
                @click="router.push({ name: 'giris', params: { rol: secenek.rol } })"
            >
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-gray-800 text-sm font-semibold text-white">
                    {{ secenek.harf }}
                </span>

                <span class="min-w-0">
                    <span class="block text-sm font-medium text-gray-800">{{ secenek.ad }}</span>
                    <span class="block text-xs text-gray-500">{{ secenek.aciklama }}</span>
                </span>

                <span class="ml-auto shrink-0 text-gray-300 transition group-hover:text-gray-600" aria-hidden="true">
                    &rarr;
                </span>
            </button>
        </div>
    </GirisKarti>
</template>
