<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTeacher
{
    /**
     * Ogretmen ekranlarina erisim kontrolu.
     *
     * Iki kosul birden gerekiyor:
     *   1. Kullanicinin rolu 'teacher' olmali
     *   2. teachers tablosunda ona bagli bir kayit olmali
     *
     * Ikincisi sart cunku ogretmen paneli "kendi programini" gosteriyor;
     * teachers kaydi yoksa gosterecek bir sey yok.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $kullanici = $request->user();

        if ($kullanici?->role !== 'teacher') {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        if (! $kullanici->teacher) {
            abort(403, 'Kullanıcı hesabınız bir öğretmen kaydına bağlı değil. Yöneticinize başvurun.');
        }

        return $next($request);
    }
}
