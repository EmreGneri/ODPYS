<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sartname: "Yonetici ve ogretmen icin ayrik kullanici girisleri
 * saglanmalidir." Tek bir /login formu yerine iki ayri URL var:
 * /giris/yonetici ve /giris/ogretmen. Kimlik dogrulama ayni (Breeze'in
 * hazir LoginRequest'i), ustune tek bir kontrol ekleniyor: sifre dogru
 * olsa bile kullanicinin gercek rolu o sayfanin beklediginden farkliysa
 * giris reddediliyor. Boylece bir ogretmen yanlislikla yonetici giris
 * sayfasindan girmeyi denerse (sifresi dogru olsa bile) giremez.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Hangi giris sayfasindan hangi roller girebilir.
     *
     * "yonetici" sayfasi ikisini de kabul ediyor: superadmin de bir
     * yoneticidir, ayri bir giris sayfasi olmasi anlamsiz olurdu.
     * Dizi (tek deger degil) tutuluyor cunku superadmin eklendiginde
     * tek degerli karsilastirma superadmin'i KENDI SISTEMINE
     * sokamiyordu - mevcut yonetici hesabi dahil hicbir superadmin
     * giris yapamaz hale gelmisti.
     */
    private const ROL_ESLESTIRME = [
        'yonetici' => ['admin', 'superadmin'],
        'ogretmen' => ['teacher'],
    ];

    /**
     * Giris turu secim ekrani - eski /login adresi artik burasi.
     */
    public function secim(): View
    {
        return view('auth.login-secim');
    }

    /**
     * Rol'e ozel giris formu.
     */
    public function create(string $rol): View
    {
        return view('auth.login', [
            'rol' => $rol,
            'baslik' => $rol === 'yonetici' ? 'Yönetici Girişi' : 'Öğretmen Girişi',
        ]);
    }

    /**
     * Kimlik dogrulama + rol kontrolu.
     */
    public function store(LoginRequest $request, string $rol): RedirectResponse
    {
        $request->authenticate();

        $kabulEdilenRoller = self::ROL_ESLESTIRME[$rol];

        if (! in_array(Auth::user()->role, $kabulEdilenRoller, true)) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => $rol === 'yonetici'
                    ? 'Bu giriş sayfası sadece yöneticiler içindir.'
                    : 'Bu giriş sayfası sadece öğretmenler içindir.',
            ]);
        }

        $request->session()->regenerate();

        // Faz 6: varsayilan arayuz artik SPA. Eski Blade ekranlari
        // silinmedi - URL'i elle yazan (ya da giris oncesi bir eski
        // sayfaya gitmeye calisan, yani intended() dolu olan) kullanici
        // oraya gitmeye devam edebiliyor; sadece VARSAYILAN hedef degisti.
        //
        // SPA'nin kendi giris formu (Giris.vue) bu yonlendirmeyi zaten
        // kullanmiyor - istegi atip cevabi yok sayiyor, sonra kendi
        // router.push'unu yapiyor. Yani bu degisiklik SPA'yi etkilemiyor,
        // sadece eski Blade giris formundan gelenleri SPA'ya tasiyor.
        return redirect()->intended('/app');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
