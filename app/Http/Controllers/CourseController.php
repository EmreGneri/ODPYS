<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseLevel;
use App\Models\Level;
use App\Models\School;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name',
            'hourly_rate' => 'required|numeric|min:0|max:999999',
            'seviyeler' => 'nullable|array',
            'seviyeler.*.secili' => 'nullable|boolean',
            'seviyeler.*.weekly_hours' => 'nullable|integer|min:1|max:40',
        ]);

        $okul = School::first();

        $ders = $okul->courses()->create([
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

    public function update(Request $request, Course $ders)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name,' . $ders->id,
            'hourly_rate' => 'required|numeric|min:0|max:999999',
            'seviyeler' => 'nullable|array',
            'seviyeler.*.secili' => 'nullable|boolean',
            'seviyeler.*.weekly_hours' => 'nullable|integer|min:1|max:40',
        ]);

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
            $secili = isset($veri['secili']);
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
