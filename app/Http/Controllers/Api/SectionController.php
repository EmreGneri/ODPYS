<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SectionRequest;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\SectionCourseTeacher;

class SectionController extends Controller
{
    public function index()
    {
        return Section::with(['level', 'room'])
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->orderBy('levels.grade_number')
            ->orderBy('sections.name')
            ->select('sections.*')
            ->get();
    }

    public function store(SectionRequest $request)
    {
        $sube = Section::create($request->validated());

        return response()->json($sube, 201);
    }

    public function update(SectionRequest $request, Section $sube)
    {
        $sube->update($request->validated());

        return $sube;
    }

    public function destroy(Section $sube)
    {
        // Uretilmis program korunuyor: schedule_entries ve
        // section_course_teacher CASCADE ile tanimli, sube silinince
        // o subenin butun cizelgesi sessizce ucardi.
        $programdaVar = ScheduleEntry::where('section_id', $sube->id)->exists()
            || SectionCourseTeacher::where('section_id', $sube->id)->exists();

        if ($programdaVar) {
            return response()->json([
                'message' => "{$sube->name} silinemez: üretilmiş programda kullanılıyor. Önce programı sıfırlayın.",
            ], 422);
        }

        $sube->delete();

        return response()->json(null, 204);
    }
}
