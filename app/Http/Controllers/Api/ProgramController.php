<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProgramDuzenleRequest;
use App\Models\Course;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\SectionCourseTeacher;
use App\Models\Teacher;
use App\Services\OgretmenAtamaServisi;
use App\Services\SlotYerlestirmeServisi;

class ProgramController extends Controller
{
    public function index()
    {
        return [
            'atamaSayisi' => SectionCourseTeacher::whereHas('section')->count(),
            'programSayisi' => ScheduleEntry::whereHas('section')->count(),

            // On kontrol: dersliksiz subenin programi uretilemez, cunku
            // schedule_entries.room_id NOT NULL. Frontend calistirmadan
            // ONCE gostersin diye.
            'dersliksizSubeler' => Section::whereNull('room_id')->pluck('name')->all(),
        ];
    }

    public function atama(OgretmenAtamaServisi $servis)
    {
        return $servis->calistir();
    }

    public function yerlestir(SlotYerlestirmeServisi $servis)
    {
        $sonuc = $servis->calistir();

        if ($sonuc['hata']) {
            return response()->json(['message' => $sonuc['hata']], 422);
        }

        return $sonuc;
    }

    public function hucreDuzenle(Section $sube, int $weekday, int $slot)
    {
        if (! $sube->room_id) {
            return response()->json(['message' => 'Bu şubenin dersliği yok, program düzenlenemez.'], 422);
        }

        $mevcut = ScheduleEntry::where('section_id', $sube->id)
            ->where('weekday', $weekday)
            ->where('slot_no', $slot)
            ->first();

        $dersler = Course::whereHas('courseLevels', fn ($q) => $q->where('level_id', $sube->level_id))
            ->orderBy('name')
            ->get();

        $ogretmenler = Teacher::whereHas('courseLevels', fn ($q) => $q->where('level_id', $sube->level_id))
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return [
            'mevcut' => $mevcut,
            'dersler' => $dersler,
            'ogretmenler' => $ogretmenler,
        ];
    }

    public function hucreGuncelle(ProgramDuzenleRequest $request, Section $sube, int $weekday, int $slot)
    {
        return ScheduleEntry::updateOrCreate(
            ['section_id' => $sube->id, 'weekday' => $weekday, 'slot_no' => $slot],
            [
                'course_id' => $request->course_id,
                'teacher_id' => $request->teacher_id,
                'room_id' => $sube->room_id,
            ]
        );
    }

    public function hucreBosalt(Section $sube, int $weekday, int $slot)
    {
        ScheduleEntry::where('section_id', $sube->id)
            ->where('weekday', $weekday)
            ->where('slot_no', $slot)
            ->delete();

        return response()->json(null, 204);
    }
}
