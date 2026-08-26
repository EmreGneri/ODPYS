<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ScheduleEntry extends Model
{
    protected $fillable = [
        'section_id',
        'course_id',
        'teacher_id',
        'room_id',
        'weekday',
        'slot_no',
    ];

    public function section():BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

     public function course():BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

     public function teacher():BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

     public function room():BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

}
