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
     * withoutGlobalScopes() BILEREK var: bu bir KIMLIK kontrolu, "aktif
     * okul" oturumda kalmis eski/alakasiz bir deger yuzunden ogretmenin
     * KENDI kaydi gorunmez olmamali. Bug: coklu okulda, oturumda baska
     * bir okul aktifken (or. yonetici Ankara'dayken bir Performans Testi
     * Okulu ogretmenine hesap acmis, sonra Ankara'ya donmus) bu ogretmen
     * giris yapinca OkulScope onu filtreleyip NULL donduruyordu, 403
     * "hesabiniz bir ogretmen kaydina bagli degil" hatasi veriyordu -
     * kayit gercekten vardi, sadece yanlis okul scope'una takiliyordu.
     *
     * Kayit bulununca aktif okulu bu ogretmenin KENDI okuluna sabitliyoruz
     * - bir ogretmen zaten tek bir okula ait, "okul degistirme" diye bir
     * kavrami yok, bu yuzden onun oturumunda hep kendi okulu aktif olmali.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $kullanici = $request->user();

        if ($kullanici?->role !== 'teacher') {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        $ogretmen = $kullanici->teacher()->withoutGlobalScopes()->first();

        if (! $ogretmen) {
            abort(403, 'Kullanıcı hesabınız bir öğretmen kaydına bağlı değil. Yöneticinize başvurun.');
        }

        session(['aktif_okul_id' => $ogretmen->school_id]);

        return $next($request);
    }
}
