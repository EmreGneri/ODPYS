<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Models\CourseLevel;
use App\Models\Level;
use App\Models\School;
use App\Models\SectionCourseTeacher;
use App\Services\DersServisi;

class CourseController extends Controller
{
    public function index()
    {
        // courseLevels.level ic ice eager loading: with() olmasaydi
        // 10 ders icin 1 + 10 + 10 = 21 sorgu olurdu, boyle 3 sorgu.
        $dersler = Course::with('courseLevels.level')->orderBy('name')->get();

        return view('courses.index', ['dersler' => $dersler]);
    }

    public function create()
    {
        return view('courses.create', [
            'seviyeler' => Level::orderBy('grade_number')->get(),
        ]);
    }

    public function store(CourseRequest $request, DersServisi $servis)
    {
        $servis->olustur(School::aktif(), $request->validated());

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

    public function update(CourseRequest $request, Course $ders, DersServisi $servis)
    {
        $servis->guncelle($ders, $request->validated());

        return redirect()
            ->route('dersler.index')
            ->with('success', 'Ders güncellendi.');
    }

    public function destroy(Course $ders)
    {
        // Sadece URETILMIS program korunuyor. Mufredat tanimi ve ogretmen
        // yetkisi dersin kendi parcasi; onlari da korusaydim kullanici
        // dersi silmek icin once seviyeleri kaldirmak zorunda kalirdi ve
        // form en az bir seviye istedigi icin kilitlenirdi.
        $atamaVar = SectionCourseTeacher::where('course_id', $ders->id)->exists();

        if ($atamaVar) {
            return redirect()
                ->route('dersler.index')
                ->with('error', "{$ders->name} silinemez: bu ders üretilmiş programda kullanılıyor. Önce programı sıfırlayın.");
        }

        $ders->delete();

        return redirect()
            ->route('dersler.index')
            ->with('success', 'Ders silindi.');
    }
}
