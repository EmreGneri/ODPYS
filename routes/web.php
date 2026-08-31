<?php

use App\Http\Controllers\CizelgeController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IzinController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\OgretmenPanelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'aktifokul'])
    ->name('dashboard');

// Giriş yapmış herkes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Sadece öğretmen — sartname 2.7.4: yalnizca izin alma ve kendi
// bilgilerine erisim. EnsureUserIsTeacher hem role='teacher' hem
// teachers kaydina bagli olma sartlarinin ikisini birden ariyor.
// aktifokul: bu sube/ders/ogretmen sorgulari coklu okulda dogru
// okula filtrelensin diye.
Route::middleware(['auth', 'teacher', 'aktifokul'])->prefix('ogretmen')->name('ogretmen.')->group(function () {
    Route::get('/panel', [OgretmenPanelController::class, 'index'])->name('panel');

    Route::get('/izinler', [IzinController::class, 'index'])->name('izinler.index');
    Route::get('/izinler/olustur', [IzinController::class, 'create'])->name('izinler.create');
    Route::post('/izinler', [IzinController::class, 'store'])->name('izinler.store');
});

// Sadece yönetici
Route::middleware(['auth', 'admin', 'aktifokul'])->group(function () {
    Route::get('/okul', [SchoolController::class, 'index'])->name('okul.index');
    Route::get('/okul/duzenle', [SchoolController::class, 'edit'])->name('okul.edit');
    Route::put('/okul', [SchoolController::class, 'update'])->name('okul.update');

    // Sartname 2.1.7 — birden fazla okul eklenebilmeli
    Route::get('/okullar', [SchoolController::class, 'okullar'])->name('okullar.index');
    Route::get('/okullar/ekle', [SchoolController::class, 'okulEkleForm'])->name('okullar.create');
    Route::post('/okullar', [SchoolController::class, 'okulEkle'])->name('okullar.store');
    Route::post('/okullar/{okul}/aktif-yap', [SchoolController::class, 'aktifYap'])->name('okullar.aktif');

    Route::resource('seviyeler', LevelController::class)
        ->parameters(['seviyeler' => 'seviye'])
        ->except(['show']);

    Route::resource('subeler', SectionController::class)
        ->parameters(['subeler' => 'sube'])
        ->except(['show']);

    Route::resource('derslikler', RoomController::class)
        ->parameters(['derslikler' => 'derslik'])
        ->except(['show']);

    Route::resource('dersler', CourseController::class)
        ->parameters(['dersler' => 'ders'])
        ->except(['show']);

    Route::resource('ogretmenler', TeacherController::class)
        ->parameters(['ogretmenler' => 'ogretmen'])
        ->except(['show']);

    Route::post('/ogretmenler/{ogretmen}/hesap', [TeacherController::class, 'hesapAc'])
        ->name('ogretmenler.hesap');

    Route::get('/ogretmenler/{ogretmen}/dersler', [TeacherController::class, 'dersler'])
        ->name('ogretmenler.dersler');

    Route::put('/ogretmenler/{ogretmen}/dersler', [TeacherController::class, 'derslerKaydet'])
        ->name('ogretmenler.dersler.kaydet');

    Route::get('/program', [ProgramController::class, 'index'])->name('program.index');
    Route::post('/program/atama', [ProgramController::class, 'atama'])->name('program.atama');
    Route::post('/program/yerlestir', [ProgramController::class, 'yerlestir'])->name('program.yerlestir');

    // Sartname 5. bolum ciktilari
    Route::get('/cizelge', [CizelgeController::class, 'index'])->name('cizelge.index');
    Route::get('/cizelge/yuk', [CizelgeController::class, 'yuk'])->name('cizelge.yuk');
    Route::get('/cizelge/sube/{sube}', [CizelgeController::class, 'sube'])->name('cizelge.sube');
    Route::get('/cizelge/ogretmen/{ogretmen}', [CizelgeController::class, 'ogretmen'])->name('cizelge.ogretmen');

    // Sartname 2.7.2 — izin onay ekrani
    Route::get('/izinler', [IzinController::class, 'yonetimIndex'])->name('izinler.index');
    Route::get('/izinler/{izin}', [IzinController::class, 'goster'])->name('izinler.goster');
    Route::post('/izinler/{izin}/onayla', [IzinController::class, 'onayla'])->name('izinler.onayla');
    Route::post('/izinler/{izin}/reddet', [IzinController::class, 'reddet'])->name('izinler.reddet');
});

require __DIR__.'/auth.php';
