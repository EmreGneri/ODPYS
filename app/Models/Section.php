<?php

namespace App\Models;

use App\Models\Scopes\SubeOkulScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $fillable = [
        'level_id',
        'name',
        'room_id',
    ];

    /**
     * sections tablosunda school_id sutunu yok - bilincli tasarim
     * karari (veri tekrari olmasin diye seviye uzerinden erisiliyor).
     * O yuzden OkulScope degil, level iliskisi uzerinden filtreleyen
     * SubeOkulScope kullaniliyor.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new SubeOkulScope);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
