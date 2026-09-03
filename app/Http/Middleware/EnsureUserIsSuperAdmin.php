<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sadece superadmin: okul ekleme, okullar arasi gecis ve yonetici
 * hesabi atama gibi SISTEM seviyesindeki islemler.
 *
 * Okula bagli bir yonetici bu islemleri yapabilseydi kendini baska
 * bir okula atayarak kisitlamayi asabilirdi - bu yuzden ayri bir
 * middleware, sadece rol kontrolu yeterli degil.
 */
class EnsureUserIsSuperAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->superAdminMi()) {
            abort(403, 'Bu işlem için süper yönetici yetkisi gerekiyor.');
        }

        return $next($request);
    }
}
