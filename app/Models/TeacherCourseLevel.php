<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherCourseLevel extends Model
{
    protected $table = 'teacher_course_level';

    protected $fillable = [
        'teacher_id',
        'course_id',
        'level_id'
    ];

    public function level():BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
