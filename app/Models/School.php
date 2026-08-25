<?php

namespace App\Models;

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