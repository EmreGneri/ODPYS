<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Yonetici hesaplarinin yonetimi - SADECE SUPERADMIN.
 *
 * Rotalari 'superadmin' middleware'i altinda; okula bagli bir yonetici
 * buraya erisebilseydi kendini baska bir okula atayarak ya da kendini
 * superadmin yaparak kisitlamayi asardi.
 *
 * Ogretmen hesaplari buradan acilmiyor - onlarin kendi akisi var
 * (Ogretmenler ekranindaki "Hesap Aç", sartname 2.8).
 */
class YoneticiController extends Controller
{
    /**
     * Yonetici hesaplari + bagli olduklari okul.
     *
     * Ogretmen rolundekiler listelenmiyor: bu ekran okul yetkisi
     * daitmak icin, ogretmenlerin okulu zaten kendi kayitlarindan
     * geliyor.
     */
    public function index()
    {
        $yoneticiler = User::with('school')
            ->whereIn('role', ['admin', 'superadmin'])
            ->orderByRaw("CASE WHEN role = 'superadmin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(fn ($k) => [
                'id' => $k->id,
                'ad' => $k->name,
                'eposta' => $k->email,
                'rol' => $k->role,
                'okulId' => $k->school_id,
                'okul' => $k->school?->name,
            ]);

        return [
            'yoneticiler' => $yoneticiler,
            'okullar' => School::orderBy('name')->get(['id', 'name']),
        ];
    }

    public function store(Request $request)
    {
        $veri = $request->validate($this->kurallar(), $this->mesajlar());

        $kullanici = User::create([
            'name' => $veri['name'],
            'email' => $veri['email'],
            'password' => Hash::make($veri['password']),
            'role' => $veri['role'],
            // Superadmin hicbir okula bagli degil - butun okullara
            // erisiyor. Bagli birakmak kafa karistirici olurdu.
            'school_id' => $veri['role'] === 'superadmin' ? null : $veri['school_id'],
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'mesaj' => $kullanici->name . ' yönetici olarak eklendi.',
        ], 201);
    }

    /**
     * Rol ve okul degistirme. Sifre burada degistirilmiyor - herkes
     * kendi sifresini Profil ekranindan degistiriyor.
     */
    public function update(Request $request, User $yonetici)
    {
        if (! $yonetici->yoneticiMi()) {
            return response()->json(['message' => 'Bu kullanıcı bir yönetici değil.'], 422);
        }

        $veri = $request->validate([
            'role' => ['required', Rule::in(['admin', 'superadmin'])],
            'school_id' => ['nullable', 'exists:schools,id', 'required_if:role,admin'],
        ], $this->mesajlar());

        // Son superadmin kendini normal yonetici yaparsa sistemde okul
        // ekleyebilen/yonetici atayabilen kimse kalmaz - geri donusu
        // olmayan bir kilitlenme.
        if ($yonetici->superAdminMi() && $veri['role'] !== 'superadmin' && $this->superAdminSayisi() <= 1) {
            return response()->json([
                'message' => 'Sistemdeki son süper yönetici bu yetkiden çıkarılamaz.',
            ], 422);
        }

        $yonetici->update([
            'role' => $veri['role'],
            'school_id' => $veri['role'] === 'superadmin' ? null : $veri['school_id'],
        ]);

        return ['mesaj' => $yonetici->name . ' güncellendi.'];
    }

    public function destroy(Request $request, User $yonetici)
    {
        if ($yonetici->id === $request->user()->id) {
            return response()->json(['message' => 'Kendi hesabınızı silemezsiniz.'], 422);
        }

        if ($yonetici->superAdminMi() && $this->superAdminSayisi() <= 1) {
            return response()->json([
                'message' => 'Sistemdeki son süper yönetici silinemez.',
            ], 422);
        }

        $yonetici->delete();

        return response()->json(null, 204);
    }

    private function superAdminSayisi(): int
    {
        return User::where('role', 'superadmin')->count();
    }

    private function kurallar(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'superadmin'])],
            // Okula bagli yonetici icin okul ZORUNLU: school_id bos
            // kalirsa School::aktif() onu okula bagli saymaz ve
            // kullanici butun okullari gormeye baslar.
            'school_id' => ['nullable', 'exists:schools,id', 'required_if:role,admin'],
        ];
    }

    private function mesajlar(): array
    {
        return [
            'school_id.required_if' => 'Okul yöneticisi için okul seçilmesi zorunludur.',
            'email.unique' => 'Bu e-posta adresi zaten kullanılıyor.',
        ];
    }
}
