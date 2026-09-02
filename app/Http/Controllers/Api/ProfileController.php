<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Web tarafindaki ProfileController + Auth\PasswordController'in
 * JSON karsiligi. Ikisi de "giris yapmis HERKES" icin - hem yonetici
 * hem ogretmen kendi adini/e-postasini ve sifresini degistirebilmeli
 * (bu yuzden rotalari admin/teacher alt gruplarinin DISINDA duruyor).
 *
 * Hesap silme (web'deki ProfileController::destroy) bilerek
 * tasinmadi - bkz. routes/api.php'deki not.
 */
class ProfileController extends Controller
{
    /**
     * Ad + e-posta guncelleme.
     *
     * Dogrulama kurallari web tarafiyla AYNI FormRequest'ten geliyor
     * (ProfileUpdateRequest): 'email' benzersiz olmali ama kendi
     * kaydini es geciyor (Rule::unique()->ignore), yoksa kullanici
     * kendi e-postasini degistirmeden kaydedemezdi. Kural tek yerde
     * durdugu icin web ile API zamanla ayrisamaz.
     */
    public function guncelle(ProfileUpdateRequest $request): JsonResponse
    {
        $kullanici = $request->user();
        $kullanici->fill($request->validated());

        // E-posta degistiyse dogrulanmis isareti dusuyor - web
        // tarafindaki davranisin birebir aynisi.
        if ($kullanici->isDirty('email')) {
            $kullanici->email_verified_at = null;
        }

        $kullanici->save();

        return response()->json([
            'mesaj' => 'Profil bilgileriniz güncellendi.',
            'kullanici' => $kullanici,
        ]);
    }

    /**
     * Sifre degistirme.
     *
     * 'current_password' Laravel'in hazir kurali: girilen degeri
     * GIRIS YAPMIS kullanicinin mevcut sifresiyle karsilastirir.
     * Boylece acik birakilmis bir tarayicinin basina gecen biri,
     * eski sifreyi bilmeden sifreyi degistiremiyor.
     *
     * 'confirmed' -> 'password_confirmation' alaniyla ayni olmali.
     * Password::defaults() -> uygulamanin global sifre politikasi;
     * boylece kural burada elle yazilmiyor, sifre sifirlama akisiyla
     * ayni politikaya bagli kaliyor.
     */
    public function sifreDegistir(Request $request): JsonResponse
    {
        $dogrulanmis = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.current_password' => 'Mevcut şifreniz hatalı.',
            'password.confirmed' => 'Yeni şifre tekrarı eşleşmiyor.',
        ]);

        $request->user()->update([
            'password' => Hash::make($dogrulanmis['password']),
        ]);

        return response()->json(['mesaj' => 'Şifreniz değiştirildi.']);
    }
}
