<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Section extends Model
{
    protected $fillable =[
        'level_id',
        'name',
        'room_id',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function room():BelongsTo
    {
        return $this->belongsTo(Room::class);

    }
}
