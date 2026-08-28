<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProgramController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Giriş yapmış herkes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Sadece yönetici
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/okul', [SchoolController::class, 'index'])->name('okul.index');
    Route::get('/okul/duzenle', [SchoolController::class, 'edit'])->name('okul.edit');
    Route::put('/okul', [SchoolController::class, 'update'])->name('okul.update');

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

    Route::get('/ogretmenler/{ogretmen}/dersler', [TeacherController::class, 'dersler'])
        ->name('ogretmenler.dersler');

    Route::put('/ogretmenler/{ogretmen}/dersler', [TeacherController::class, 'derslerKaydet'])
        ->name('ogretmenler.dersler.kaydet');

    Route::get('/program', [ProgramController::class, 'index'])->name('program.index');
    Route::post('/program/atama', [ProgramController::class, 'atama'])->name('program.atama');
});

require __DIR__.'/auth.php';
