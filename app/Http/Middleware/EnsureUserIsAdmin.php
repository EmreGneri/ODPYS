<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Yonetici ekranlari icin. SUPERADMIN DE GECER - superadmin,
 * yoneticinin yapabildigi her seyi yapabilir, ustune okullar arasi
 * yetkisi vardir.
 *
 * Hangi OKULUN verisini gorecegi burada belirlenmiyor; onu
 * School::aktif() cozuyor (okula bagli yonetici kendi okuluna
 * kilitleniyor). Bu middleware sadece "yonetici mi" sorusuna bakiyor.
 */
class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->yoneticiMi()) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        return $next($request);
    }
}
