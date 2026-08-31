<?php

namespace App\Models;

use App\Models\Scopes\OkulScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'grade_number',
    ];

    /**
     * Coklu okul: her sorgu otomatik aktif okula filtreleniyor.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new OkulScope);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
