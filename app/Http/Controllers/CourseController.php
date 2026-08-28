<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Models\CourseLevel;
use App\Models\Level;
use App\Models\School;
use App\Models\TeacherCourseLevel;

class CourseController extends Controller
{
    public function index()
    {
        $dersler = Course::with('courseLevels.level')->orderBy('name')->get();

        return view('courses.index', ['dersler' => $dersler]);
    }

    public function create()
    {
        return view('courses.create', [
            'seviyeler' => Level::orderBy('grade_number')->get(),
        ]);
    }

    public function store(CourseRequest $request)
    {
        $validated = $request->validated();

        $ders = School::first()->courses()->create([
            'name' => $validated['name'],
            'hourly_rate' => $validated['hourly_rate'],
        ]);

        $this->seviyeSaatleriniKaydet($ders, $validated['seviyeler'] ?? []);

        return redirect()
            ->route('dersler.index')
            ->with('success', 'Ders eklendi.');
    }

    public function edit(Course $ders)
    {
        $ders->load('courseLevels');

        return view('courses.edit', [
            'ders' => $ders,
            'seviyeler' => Level::orderBy('grade_number')->get(),
        ]);
    }

    public function update(CourseRequest $request, Course $ders)
    {
        $validated = $request->validated();

        $ders->update([
            'name' => $validated['name'],
            'hourly_rate' => $validated['hourly_rate'],
        ]);

        $this->seviyeSaatleriniKaydet($ders, $validated['seviyeler'] ?? []);

        return redirect()
            ->route('dersler.index')
            ->with('success', 'Ders güncellendi.');
    }

    public function destroy(Course $ders)
    {
        $seviyeSayisi = CourseLevel::where('course_id', $ders->id)->count();

        if ($seviyeSayisi > 0) {
            return redirect()
                ->route('dersler.index')
                ->with('error', "{$ders->name} silinemez: {$seviyeSayisi} seviyeye haftalık saat tanımlı. Önce düzenleme ekranından seviye işaretlerini kaldırın.");
        }

        $yetkiSayisi = TeacherCourseLevel::where('course_id', $ders->id)->count();

        if ($yetkiSayisi > 0) {
            return redirect()
                ->route('dersler.index')
                ->with('error', "{$ders->name} silinemez: {$yetkiSayisi} öğretmen yetkisi bu derse bağlı.");
        }

        $ders->delete();

        return redirect()
            ->route('dersler.index')
            ->with('success', 'Ders silindi.');
    }

    private function seviyeSaatleriniKaydet(Course $ders, array $seviyeVerileri): void
    {
        foreach ($seviyeVerileri as $levelId => $veri) {
            // isset yerine ! empty: gizli alan "0" gönderirse isset true döner
            $secili = ! empty($veri['secili']);
            $saat = $veri['weekly_hours'] ?? null;

            if ($secili && $saat) {
                CourseLevel::updateOrCreate(
                    ['course_id' => $ders->id, 'level_id' => $levelId],
                    ['weekly_hours' => $saat]
                );
            } else {
                CourseLevel::where('course_id', $ders->id)
                    ->where('level_id', $levelId)
                    ->delete();
            }
        }
    }
}
