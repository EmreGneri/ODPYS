<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'school_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Bu kullaniciya bagli ogretmen kaydi.
     *
     * hasOne cunku yabanci anahtar teachers.user_id sutununda duruyor,
     * users tablosunda degil. belongsTo yazsaydik Laravel users.teacher_id
     * arardi, oyle bir sutun yok.
     *
     * Yonetici kullanicilarinda null doner.
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Okula bagli yoneticinin okulu. Superadmin ve ogretmenlerde null.
     *
     * withoutGlobalScopes YOK cunku School modelinde OkulScope zaten
     * yok (scope'lar okula BAGLI tablolar icin - okulun kendisi icin
     * degil).
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Butun okullara erisen yonetici.
     */
    public function superAdminMi(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Yonetici mi? Superadmin de yoneticidir - yoneticinin yapabildigi
     * her seyi yapabilir, ustune okullar arasi yetkisi vardir.
     */
    public function yoneticiMi(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }

    /**
     * Tek bir okula kilitli yonetici mi?
     *
     * Bu kontrol School::aktif() icinde kullaniliyor ve butun coklu
     * okul filtrelemesinin dayanagi: dogru donerse kullanicinin aktif
     * okulu oturumdan DEGIL, kendi kaydindan belirleniyor.
     *
     * DIKKAT - burada school_id'nin dolu olmasi ARANMIYOR, sadece rol
     * bakiliyor. Sebep: okul silindiginde school_id nullOnDelete ile
     * NULL'a duser. "role=admin VE school_id dolu" deseydik, okulu
     * silinen yonetici birden "okula bagli degil" sayilip School::aktif()
     * icindeki eski akisa duser ve SISTEMDEKI ILK OKULU gormeye
     * baslardi - yani okul silmek sessizce yetki genisletirdi.
     *
     * Simdi tam tersi: rolu admin olan herkes okuluna bagli sayiliyor;
     * okulu yoksa hicbir seye erisemiyor (School::aktif() null doner).
     * Yetki hatasi olacaksa kapali tarafta olsun.
     */
    public function okulaBagliYoneticiMi(): bool
    {
        return $this->role === 'admin';
    }
}
