import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    /**
     * Tailwind, sinif isimlerini bu listedeki dosyalari TARAYARAK
     * bulur ve sadece gercekten kullanilanlarin CSS'ini uretir
     * ("purge"). Burada listelenmeyen bir dosyadaki sinif, yazilmis
     * olsa bile CSS'e hic girmez - tarayicida sessizce hicbir sey
     * yapmaz, hata da vermez.
     *
     * SPA'ya (Faz 2+) gecerken bu liste guncellenmemisti: butun Vue
     * sayfalari '.vue' dosyalarinda yasadigi icin, SADECE orada gecen
     * siniflar (mt-24, max-w-sm, grid-cols-7 ...) uretilmiyordu.
     * Blade'de de tesaduf eseri gecen siniflar (bg-gray-800, rounded-md)
     * calisiyordu - bu yuzden sayfalar "tamamen bicimsiz" degil,
     * "yarim bicimli" goruluyordu ve tespiti zordu.
     */
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
