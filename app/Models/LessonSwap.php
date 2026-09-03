<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bir izin yuzunden iki dersin belirli bir tarihte yer degistirmesi.
 * Gerekce ve tasarim notu icin migration dosyasina bakin.
 *
 * DIKKAT - okul kapsami: bu tabloda school_id YOK, dolayisiyla
 * OkulScope gibi bir global scope da yok. Coklu okulda yanlislikla
 * baska okulun takasini gormemek icin sorgular entry uzerinden
 * kapsanmali:
 *
 *     LessonSwap::whereHas('entryA.section')->...
 *
 * (section zaten SubeOkulScope tasiyor.) Ayni tuzak daha once
 * schedule_entries ve section_course_teacher'da gercek hatalara yol
 * acmisti - TakasServisi bu yuzden hep whereHas ile sorguluyor.
 */
class LessonSwap extends Model
{
    protected $fillable = [
        'leave_request_id',
        'swap_date',
        'entry_a_id',
        'entry_b_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'swap_date' => 'date',
        ];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /** Izinli olan ders. */
    public function entryA(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntry::class, 'entry_a_id');
    }

    /** Onunla yer degisen ders. */
    public function entryB(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntry::class, 'entry_b_id');
    }
}
