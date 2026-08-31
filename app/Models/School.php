<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'city',
        'district',
        'lessons_per_day',
        'lesson_duration',
        'break_duration',
    ];

    /**
     * Eğitim verilen gün sayısı.
     */
    public function aktifGunSayisi(): int
    {
        // İlişki koleksiyonu üzerinden: bir kez yükleniyor,
        // tekrar çağrılarda yeniden sorgu atılmıyor.
        return $this->days->where('is_active', true)->count();
    }

    /**
     * Bir şubenin (ve bir öğretmenin) haftada girebileceği azami ders saati.
     *
     * Üç yerde kullanılıyor: öğretmen max_weekly_hours üst sınırı,
     * ders saati doğrulaması, program sihirbazının slot sayısı.
     * Sabit yazılmıyor — okul ayarı değişince hepsi birlikte değişsin diye.
     */
    public function haftalikKapasite(): int
    {
        return $this->lessons_per_day * $this->aktifGunSayisi();
    }

    /**
     * Bir günün slot numaralarını gerçek saatlere çevirir.
     * Şartname 2.1.8 — ders saatleri programı.
     *
     * Saatler veritabanında saklanmıyor; ders süresi değişince
     * bütün program kendiliğinden kaysın diye buradan hesaplanıyor.
     */
    public function slotSaatleri(int $weekday): array
    {
        $gun = $this->days->firstWhere('weekday', $weekday);

        if (! $gun || ! $gun->is_active) {
            return [];
        }

        $saatler = [];
        $baslangic = Carbon::parse($gun->start_time);

        for ($slot = 1; $slot <= $this->lessons_per_day; $slot++) {
            $bitis = $baslangic->copy()->addMinutes($this->lesson_duration);

            $saatler[$slot] = [
                'baslangic' => $baslangic->format('H:i'),
                'bitis' => $bitis->format('H:i'),
            ];

            $baslangic = $bitis->copy()->addMinutes($this->break_duration);
        }

        return $saatler;
    }

    public function days(): HasMany
    {
        return $this->hasMany(SchoolDay::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }
}
