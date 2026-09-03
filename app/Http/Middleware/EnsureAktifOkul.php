<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Her istekte oturumda gecerli bir aktif okul id'si olmasini garanti
 * ediyor. OkulScope/SubeOkulScope session('aktif_okul_id') bos ise
 * HICBIR SEY filtrelemez - yani bu middleware calismazsa coklu okulda
 * tum okullarin verisi karisir. Bu yuzden admin ve teacher route
 * gruplarinin ikisine de uygulaniyor.
 */
class EnsureAktifOkul
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $okul = School::aktif();

        // Okula bagli yoneticinin okulu yoksa (hic atanmamis ya da okulu
        // silinmis) istegi BURADA kesiyoruz.
        //
        // Devam etseydik School::aktif() null dondugu icin oturumda okul
        // id'si olmaz, OkulScope da hicbir sey filtrelemez ve kullanici
        // BUTUN okullarin verisini gorurdu - kisitlamanin tam tersi.
        // Yetki belirsizse istek calismasin.
        if (! $okul && $request->user()?->okulaBagliYoneticiMi()) {
            abort(403, 'Hesabınıza bir okul atanmamış. Süper yönetici ile iletişime geçin.');
        }

        return $next($request);
    }
}
