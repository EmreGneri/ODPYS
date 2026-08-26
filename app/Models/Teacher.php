<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'first_name',
        'last_name',
        'national_id',
        'birth_date',
        'email',
        'photo_path',
        'cv_path',
        'is_active',
        'max_weekly_hours',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
