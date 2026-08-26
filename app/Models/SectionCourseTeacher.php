<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionCourseTeacher extends Model
{
    protected $table = 'section_course_teacher';

    protected $fillable = [
        'teacher_id',
        'section_id',
        'course_id',
    ];

    public function teacher():BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function section():BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function course():BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
