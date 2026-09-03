<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CizelgeController as ApiCizelgeController;
use App\Http\Controllers\Api\CourseController as ApiCourseController;
use App\Http\Controllers\Api\DashboardController as ApiDashboardController;
use App\Http\Controllers\Api\GezinmeController as ApiGezinmeController;
use App\Http\Controllers\Api\IzinController as ApiIzinController;
use App\Http\Controllers\Api\LevelController as ApiLevelController;
use App\Http\Controllers\Api\OgretmenPanelController as ApiOgretmenPanelController;
use App\Http\Controllers\Api\PasswordController as ApiPasswordController;
use App\Http\Controllers\Api\ProfileController as ApiProfileController;
use App\Http\Controllers\Api\ProgramController as ApiProgramController;
use App\Http\Controllers\Api\RoomController as ApiRoomController;
use App\Http\Controllers\Api\SchoolController as ApiSchoolController;
use App\Http\Controllers\Api\SectionController as ApiSectionController;
use App\Http\Controllers\Api\TeacherController as ApiTeacherController;
use App\Http\Controllers\Api\UcretController as ApiUcretController;
use App\Http\Controllers\Api\YoneticiController as ApiYoneticiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Sartname 3.3 - API destegi. Sanctum token'iyle kimlik dogrulama:
 * /api/login token uretiyor, sonraki istekler "Authorization: Bearer
 * <token>" basligiyla kimlik kanitliyor - tarayici oturum cerezi
 * (cookie) YOK, stateless.
 */
// KABA KUVVET KORUMASI - iki katman:
//
//   1. Buradaki throttle middleware'i IP bazli calisiyor: bir
//      adresten dakikada kac istek gelebilecegini sinirliyor, yani
//      uc noktanin dovulmesini engelliyor.
//   2. AuthController icindeki RateLimiter ise KIMLIK bazli:
//      e-posta+IP anahtariyla, tek bir hesaba yapilan sifre
//      denemelerini 5'te durduruyor.
//
// Ikisi farkli seyi koruyor, biri digerinin yerini tutmuyor: sadece
// IP sinirlamasi olsaydi saldirgan farkli IP'lerden ayni hesaba
// saldirabilirdi; sadece kimlik sinirlamasi olsaydi rastgele
// e-postalarla uc noktayi yine dovebilirdi.
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

// Sifre sifirlama - giris yapmamis kullanicilar icin, guest.
// Bunlar e-POSTA GONDERIYOR: sinirsiz birakilirsa hem posta kotasi
// tuketilir hem de bir kullaniciya spam gonderilebilir.
Route::post('/forgot-password', [ApiPasswordController::class, 'forgot'])
    ->middleware('throttle:6,1');
Route::post('/reset-password', [ApiPasswordController::class, 'reset'])
    ->middleware('throttle:6,1');

Route::middleware(['auth:sanctum', 'aktifokul'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ───── HER İKİ ROL — kendi hesabı ─────
    // Bilerek admin/teacher alt gruplarinin DISINDA: yonetici de
    // ogretmen de kendi adini/e-postasini ve sifresini
    // degistirebilmeli. Yetki zaten dogal olarak siniri ciziyor -
    // controller her zaman $request->user() uzerinde calisiyor,
    // yani kimse baskasinin hesabina dokunamiyor.
    //
    // Hesap SILME (web'de ProfileController::destroy var) bilerek
    // tasinmadi: bu bir okul sistemi, tek yonetici hesabi kendini
    // silerse sisteme girilemez hale geliyor. Gerekirse ayri bir is
    // olarak, "baska bir yonetici tarafindan silme" seklinde
    // eklenmeli.
    Route::put('/profil', [ApiProfileController::class, 'guncelle']);
    Route::put('/profil/sifre', [ApiProfileController::class, 'sifreDegistir']);

    // ───── ÖĞRETMEN TARAFI — sartname 2.7.4: sadece kendi bilgileri ─────
    Route::middleware('teacher')->prefix('ogretmen')->group(function () {
        Route::get('/panel', [ApiOgretmenPanelController::class, 'index']);
        Route::get('/izinler', [ApiIzinController::class, 'benimIzinlerim']);
        Route::post('/izinler', [ApiIzinController::class, 'store']);
    });

    // ───── YÖNETİCİ TARAFI ─────
    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [ApiDashboardController::class, 'index']);

        // Detay ekranlarindaki "onceki/sonraki kayit" cubugu icin sade
        // listeler (sadece id + etiket). Ayrintili gerekce: controller.
        Route::get('/gezinme', [ApiGezinmeController::class, 'index']);

        // Cizelge ciktilari - sartname 5. bolum
        Route::get('/cizelge', [ApiCizelgeController::class, 'index']);
        Route::get('/cizelge/yuk', [ApiCizelgeController::class, 'yuk']);
        Route::get('/cizelge/sube/{sube}', [ApiCizelgeController::class, 'sube']);
        Route::get('/cizelge/ogretmen/{ogretmen}', [ApiCizelgeController::class, 'ogretmen']);

        Route::get('/ucretler', [ApiUcretController::class, 'index']);

        // Okul ayarlari + coklu okul
        // Bunlar HER yonetici icin: okula bagli yonetici de kendi
        // okulunun ayarlarini duzenleyebilmeli. Hangi okulun ayarlari
        // geldigi School::aktif() tarafindan belirleniyor.
        Route::get('/okul', [ApiSchoolController::class, 'index']);
        Route::put('/okul', [ApiSchoolController::class, 'update']);
        Route::get('/okullar', [ApiSchoolController::class, 'okullar']);

        // ───── SADECE SÜPER YÖNETİCİ ─────
        // Okul ekleme ve okullar arasi gecis sistem seviyesinde
        // islemler. Okula bagli bir yonetici bunlari yapabilseydi
        // kendini baska okula gecirerek kisitlamayi asardi.
        Route::middleware('superadmin')->group(function () {
            Route::post('/okullar', [ApiSchoolController::class, 'okulEkle']);
            Route::post('/okullar/{okul}/aktif-yap', [ApiSchoolController::class, 'aktifYap']);

            Route::get('/yoneticiler', [ApiYoneticiController::class, 'index']);
            Route::post('/yoneticiler', [ApiYoneticiController::class, 'store']);
            Route::put('/yoneticiler/{yonetici}', [ApiYoneticiController::class, 'update']);
            Route::delete('/yoneticiler/{yonetici}', [ApiYoneticiController::class, 'destroy']);
        });

        Route::apiResource('seviyeler', ApiLevelController::class)
            ->parameters(['seviyeler' => 'seviye'])
            ->names('api.seviyeler')
            ->except(['show']);

        Route::apiResource('derslikler', ApiRoomController::class)
            ->parameters(['derslikler' => 'derslik'])
            ->names('api.derslikler')
            ->except(['show']);

        Route::apiResource('subeler', ApiSectionController::class)
            ->parameters(['subeler' => 'sube'])
            ->names('api.subeler')
            ->except(['show']);

        Route::apiResource('dersler', ApiCourseController::class)
            ->parameters(['dersler' => 'ders'])
            ->names('api.dersler')
            ->except(['show']);

        Route::apiResource('ogretmenler', ApiTeacherController::class)
            ->parameters(['ogretmenler' => 'ogretmen'])
            ->names('api.ogretmenler')
            ->except(['show']);

        Route::post('/ogretmenler/{ogretmen}/hesap', [ApiTeacherController::class, 'hesapAc']);
        Route::get('/ogretmenler/{ogretmen}/dersler', [ApiTeacherController::class, 'dersler']);
        Route::put('/ogretmenler/{ogretmen}/dersler', [ApiTeacherController::class, 'derslerKaydet']);

        Route::get('/program', [ApiProgramController::class, 'index']);
        Route::post('/program/atama', [ApiProgramController::class, 'atama']);
        Route::post('/program/yerlestir', [ApiProgramController::class, 'yerlestir']);
        Route::get('/program/duzenle/{sube}/{weekday}/{slot}', [ApiProgramController::class, 'hucreDuzenle'])
            ->whereNumber(['weekday', 'slot']);
        Route::put('/program/duzenle/{sube}/{weekday}/{slot}', [ApiProgramController::class, 'hucreGuncelle'])
            ->whereNumber(['weekday', 'slot']);
        Route::delete('/program/duzenle/{sube}/{weekday}/{slot}', [ApiProgramController::class, 'hucreBosalt'])
            ->whereNumber(['weekday', 'slot']);

        Route::get('/izinler', [ApiIzinController::class, 'index']);
        Route::get('/izinler/{izin}', [ApiIzinController::class, 'goster']);
        Route::post('/izinler/{izin}/onayla', [ApiIzinController::class, 'onayla']);
        Route::post('/izinler/{izin}/reddet', [ApiIzinController::class, 'reddet']);

        // Ders takasi - izin yuzunden bosalan dersin ayni gun icinde
        // baska bir ders saatiyle yer degistirmesi (bkz. TakasServisi).
        Route::get('/izinler/{izin}/takas-adaylari', [ApiIzinController::class, 'takasAdaylari']);
        Route::post('/izinler/{izin}/takas', [ApiIzinController::class, 'takasYap']);
        Route::delete('/izinler/{izin}/takas/{takas}', [ApiIzinController::class, 'takasGeriAl']);
    });
});
