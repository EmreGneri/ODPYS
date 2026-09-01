<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CizelgeController as ApiCizelgeController;
use App\Http\Controllers\Api\UcretController as ApiUcretController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Sartname 3.3 - API destegi. Sanctum token'iyle kimlik dogrulama:
 * /api/login token uretiyor, sonraki istekler "Authorization: Bearer
 * <token>" basligiyla kimlik kanitliyor - tarayici oturum cerezi
 * (cookie) YOK, stateless.
 */
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Web tarafindaki cizelge/ucret ekranlariyla ayni yetki: sadece
    // yonetici. Ogretmenin kendi programi icin ayri, kendine ozel bir
    // uc nokta gerekir - bu ilk surumde yok.
    Route::middleware('admin')->group(function () {
        Route::get('/cizelge/sube/{sube}', [ApiCizelgeController::class, 'sube']);
        Route::get('/cizelge/ogretmen/{ogretmen}', [ApiCizelgeController::class, 'ogretmen']);
        Route::get('/ucretler', [ApiUcretController::class, 'index']);
    });
});
