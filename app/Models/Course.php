<?php

namespace App\Models;

use App\Models\Scopes\OkulScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'hourly_rate',
        'block_ders',
    ];

    protected $casts = [
        'block_ders' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new OkulScope);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function courseLevels(): HasMany
    {
        return $this->hasMany(CourseLevel::class);
    }
}
