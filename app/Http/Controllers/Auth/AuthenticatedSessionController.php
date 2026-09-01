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
    private const ROL_ESLESTIRME = [
        'yonetici' => 'admin',
        'ogretmen' => 'teacher',
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

        $beklenenRol = self::ROL_ESLESTIRME[$rol];

        if (Auth::user()->role !== $beklenenRol) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => $rol === 'yonetici'
                    ? 'Bu giriş sayfası sadece yöneticiler içindir.'
                    : 'Bu giriş sayfası sadece öğretmenler içindir.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
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
