<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * API girisi. Web tarafindaki oturum (session/cookie) bazli giristen
 * farkli - burada bir TOKEN uretilip donduruluyor, istemci sonraki
 * isteklerde "Authorization: Bearer <token>" basligiyla kimligini
 * kanitliyor. Sanctum bu token'lari personal_access_tokens tablosunda
 * tutuyor.
 *
 * KABA KUVVET KORUMASI (sonradan eklendi - gercek bir aciti):
 * Web tarafinda Breeze'in korumasi zaten vardi
 * (app/Http/Requests/Auth/LoginRequest.php icindeki
 * ensureIsNotRateLimited), ama bu controller o sinifi kullanmiyor,
 * kendi akisini calistiriyor - dolayisiyla /api/login uzerinden
 * SINIRSIZ sifre denemesi yapilabiliyordu. Web'deki 5 deneme siniri
 * bu yoldan tamamen atlaniyordu.
 *
 * Simdi ayni kural burada da uygulaniyor. Bilerek web tarafiyla
 * BIREBIR ayni desen (5 deneme, e-posta+IP anahtari, Lockout olayi):
 * iki giris yolunun farkli davranmasi, birinin gozden kacmasi
 * demektir.
 *
 * Ikinci bir katman da rota tarafinda var (routes/api.php ->
 * throttle middleware'i). Ikisi farkli seyi koruyor:
 *   - buradaki RateLimiter KIMLIK bazli: tek bir hesaba yapilan
 *     sifre denemelerini durduruyor
 *   - rotadaki throttle IP bazli: uc noktanin genel olarak
 *     dovulmesini durduruyor
 */
class AuthController extends Controller
{
    /**
     * Kac basarisiz denemeden sonra kilitlenecek.
     */
    private const AZAMI_DENEME = 5;

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $anahtar = $this->denemeAnahtari($request);

        $this->kilitliMi($anahtar);

        $kullanici = User::where('email', $request->email)->first();

        if (! $kullanici || ! Hash::check($request->password, $kullanici->password)) {
            // Basarisiz deneme sayaci artiyor. 60 saniye sonra
            // kendiliginden dusuyor.
            RateLimiter::hit($anahtar);

            // Kullanici var mi yok mu BELLI ETMIYORUZ - ayni mesaj.
            // Farkli mesaj verseydik saldirgan hangi e-postalarin
            // kayitli oldugunu ogrenirdi (kullanici numaralandirma).
            throw ValidationException::withMessages([
                'email' => 'Bilgiler kayıtlarımızla eşleşmiyor.',
            ]);
        }

        // Basarili giris sayaci sifirliyor - dogru sifreyi bilen biri
        // onceki hatalarindan dolayi cezalandirilmasin.
        RateLimiter::clear($anahtar);

        return [
            'token' => $kullanici->createToken('api')->plainTextToken,
            'kullanici' => [
                'ad' => $kullanici->name,
                'email' => $kullanici->email,
                'rol' => $kullanici->role,
            ],
        ];
    }

    /**
     * Sayacin anahtari: e-posta + IP.
     *
     * Neden ikisi birden? Sadece IP olsaydi, ayni agdan cikan
     * (okul, ofis) butun kullanicilar tek sayaci paylasir ve biri
     * yuzunden hepsi kilitlenirdi. Sadece e-posta olsaydi saldirgan
     * farkli IP'lerden ayni hesaba saldirmaya devam ederdi.
     *
     * transliterate: "İSMAİL@..." gibi Turkce karakterli e-postalarin
     * kucuk harfe cevrilirken farkli anahtar uretmesini engelliyor.
     */
    private function denemeAnahtari(Request $request): string
    {
        return 'api-login|' . Str::transliterate(Str::lower((string) $request->input('email')))
            . '|' . $request->ip();
    }

    /**
     * Sinir asilmissa istegi kesip kalan sureyi bildiriyor.
     */
    private function kilitliMi(string $anahtar): void
    {
        if (! RateLimiter::tooManyAttempts($anahtar, self::AZAMI_DENEME)) {
            return;
        }

        // Web tarafi da bu olayi firlatiyor - ileride "hesabiniza
        // giris denemesi yapildi" bildirimi eklenirse iki yol da
        // ayni yerden dinlenebilir.
        event(new Lockout(request()));

        $saniye = RateLimiter::availableIn($anahtar);

        throw ValidationException::withMessages([
            'email' => "Çok fazla giriş denemesi yapıldı. {$saniye} saniye sonra tekrar deneyin.",
        ]);
    }
}
