<?php

namespace App\Models;

use App\Models\Scopes\OkulScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Bu iki alan veritabaninda YOK - photo_path/cv_path'ten
     * turetiliyor ama her JSON cevabina otomatik ekleniyor.
     *
     * Neden model'de: Blade tarafinda her gosterim yerinde ayri ayri
     * Storage::url(...) cagriliyordu (listede, formda, duzenleme
     * ekraninda). Frontend'in "dosya yolundan URL nasil uretilir"
     * bilgisini tasimasi gerekmiyor - model kendi verisinin nasil
     * adreslendigini bilsin, tuketen taraf hazir URL alsin.
     */
    protected $appends = ['photo_url', 'cv_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function getCvUrlAttribute(): ?string
    {
        return $this->cv_path ? Storage::disk('public')->url($this->cv_path) : null;
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OkulScope);
    }

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

    /**
     * Öğretmenin hangi dersi hangi seviyede verebildiği (yetki).
     * Şartname 2.4 — "Ahmet Hoca → Türkçe → 9. sınıf"
     */
    public function courseLevels(): HasMany
    {
        return $this->hasMany(TeacherCourseLevel::class);
    }

    /**
     * Sihirbazın ürettiği şube-ders-öğretmen atamaları.
     */
    public function sectionCourses(): HasMany
    {
        return $this->hasMany(SectionCourseTeacher::class);
    }

    /**
     * Listede ve başlıklarda kullanmak için: "Ahmet Yılmaz"
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
