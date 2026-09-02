<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sifre sifirlama e-postasindaki link artik SPA'nin sayfasina
        // gidiyor (eski /reset-password/{token} web rotasi hala calisir,
        // sadece yeni e-postalar artik SPA'ya isaret ediyor - eski
        // gonderilmis e-postalardaki linkler bozulmuyor).
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return config('app.url') . '/app/sifre-sifirla/' . $token
                . '?email=' . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
