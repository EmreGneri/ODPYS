<script setup>
import { computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

defineProps({
    baslik: { type: String, default: '' },
});

const auth = useAuthStore();
const router = useRouter();

// navigation.blade.php'nin birebir karsiligi - rol'e gore menu.
const yoneticiMenu = [
    { ad: 'Dashboard', rota: 'dashboard' },
    { ad: 'Okul Ayarları', rota: 'okul-ayarlari' },
    { ad: 'Okullar', rota: 'okullar' },
    { ad: 'Seviyeler', rota: 'seviyeler' },
    { ad: 'Şubeler', rota: 'subeler' },
    { ad: 'Öğretmenler', rota: 'ogretmenler' },
    { ad: 'Derslikler', rota: 'derslikler' },
    { ad: 'Dersler', rota: 'dersler' },
    { ad: 'Program', rota: 'program' },
    { ad: 'Çizelge', rota: 'cizelge' },
    { ad: 'Ücretler', rota: 'ucretler' },
    { ad: 'İzin Talepleri', rota: 'izinler' },
];

const ogretmenMenu = [
    { ad: 'Panelim', rota: 'ogretmen-panel' },
    { ad: 'İzinlerim', rota: 'ogretmen-izinler' },
];

/**
 * Gosterilecek menu. Yonetici menusune, SADECE super yoneticide
 * "Yoneticiler" ekleniyor - okula bagli yonetici o ekrani hic
 * gormuyor (asil kilit API'nin superadmin middleware'inde, bu
 * calismayacak bir link gostermemek icin).
 */
const menu = computed(() => {
    if (! auth.yonetici) {
        return ogretmenMenu;
    }

    return auth.superAdmin
        ? [...yoneticiMenu, { ad: 'Yöneticiler', rota: 'yoneticiler' }]
        : yoneticiMenu;
});

/**
 * Kullanicinin bas harfleri - profil butonundaki kucuk yuvarlak
 * icin. Ogretmen listesindeki avatar ile ayni fikir: fotograf yok,
 * o yuzden isim uzerinden bir simge uretiyoruz.
 */
const basHarfler = computed(() => {
    return (auth.kullanici?.name ?? '?')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((kelime) => kelime.charAt(0))
        .join('')
        .toLocaleUpperCase('tr');
});

async function cikisYap() {
    await axios.post('/logout', {}, { headers: { Accept: 'application/json' } });
    auth.cikisYap();
    router.push({ name: 'giris-secim' });
}
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-4 overflow-x-auto">
                        <router-link
                            v-for="oge in menu"
                            :key="oge.rota"
                            :to="{ name: oge.rota }"
                            class="text-sm whitespace-nowrap pt-1 border-b-2 h-16 flex items-center transition"
                            :class="$route.name === oge.rota
                                ? 'border-indigo-400 text-gray-900'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        >
                            {{ oge.ad }}
                        </router-link>
                    </div>

                    <div class="flex items-center gap-4 shrink-0 pl-4">
                        <!-- Ana menuye DEGIL buraya konuldu: yonetici menusu
                             zaten 12 ogeyle dolu, 13.'su onu tasirirdi.
                             Yardim, profil gibi "hesap/destek" tarafina ait. -->
                        <router-link
                            :to="{ name: 'nasil-kullanilir' }"
                            class="flex h-7 w-7 items-center justify-center rounded-full border text-sm transition"
                            :class="$route.name === 'nasil-kullanilir'
                                ? 'border-gray-800 text-gray-900'
                                : 'border-gray-300 text-gray-500 hover:border-gray-500 hover:text-gray-800'"
                            title="Nasıl kullanılır?"
                            aria-label="Nasıl kullanılır?"
                        >
                            ?
                        </router-link>

                        <router-link
                            :to="{ name: 'profil' }"
                            class="flex items-center gap-2 text-sm transition"
                            :class="$route.name === 'profil' ? 'text-gray-900' : 'text-gray-500 hover:text-gray-800'"
                            title="Profil ve şifre"
                        >
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-medium text-gray-700">
                                {{ basHarfler }}
                            </span>
                            <span class="hidden whitespace-nowrap sm:inline">{{ auth.kullanici?.name }}</span>
                        </router-link>

                        <button @click="cikisYap" class="text-sm text-gray-500 hover:text-gray-700">
                            Çıkış
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="baslik" class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ baslik }}</h2>
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
