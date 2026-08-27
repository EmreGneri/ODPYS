<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
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
