<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * API girisi. Web tarafindaki oturum (session/cookie) bazli giristen
 * farkli - burada bir TOKEN uretilip donduruluyor, istemci sonraki
 * isteklerde "Authorization: Bearer <token>" basligiyla kimligini
 * kanitliyor. Sanctum bu token'lari personal_access_tokens tablosunda
 * tutuyor.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $kullanici = User::where('email', $request->email)->first();

        if (! $kullanici || ! Hash::check($request->password, $kullanici->password)) {
            throw ValidationException::withMessages([
                'email' => 'Bilgiler kayıtlarımızla eşleşmiyor.',
            ]);
        }

        return [
            'token' => $kullanici->createToken('api')->plainTextToken,
            'kullanici' => [
                'ad' => $kullanici->name,
                'email' => $kullanici->email,
                'rol' => $kullanici->role,
            ],
        ];
    }
}
