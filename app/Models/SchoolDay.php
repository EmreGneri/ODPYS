<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SchoolDay extends Model
{
    protected $fillable = [
    'school_id',
    'weekday',
    'start_time',
    'is_active',
];

public function school(): BelongsTo
{
    return $this->belongsTo(School::class);
}
}
