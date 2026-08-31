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
 *
 * @param  Closure(Request): (Response)  $next
 */
class EnsureAktifOkul
{
    public function handle(Request $request, Closure $next): Response
    {
        School::aktif();

        return $next($request);
    }
}
