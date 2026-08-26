<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Course extends Model
{
    protected $fillable =[
        'school_id',
        'name',
        'hourly_rate',

    ];
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
