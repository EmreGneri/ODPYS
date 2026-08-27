<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;

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
        ->parameters(['seviyeler' => 'seviye']);

    Route::resource('subeler', SectionController::class)
        ->parameters(['subeler' => 'sube']);
    Route::resource('derslikler', RoomController::class)
        ->parameters(['derslikler' => 'derslik']);
    Route::resource('dersler', CourseController::class)
        ->parameters(['dersler' => 'ders']);
});

require __DIR__.'/auth.php';
