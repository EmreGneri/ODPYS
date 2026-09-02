<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Models\School;
use App\Models\SectionCourseTeacher;
use App\Services\DersServisi;

class CourseController extends Controller
{
    public function index()
    {
        // courseLevels.level ic ice eager loading: with() olmasaydi
        // 10 ders icin 1 + 10 + 10 = 21 sorgu olurdu, boyle 3 sorgu.
        return Course::with('courseLevels.level')->orderBy('name')->get();
    }

    public function store(CourseRequest $request, DersServisi $servis)
    {
        $ders = $servis->olustur(School::aktif(), $request->validated());

        return response()->json($ders->load('courseLevels.level'), 201);
    }

    public function update(CourseRequest $request, Course $ders, DersServisi $servis)
    {
        $servis->guncelle($ders, $request->validated());

        return $ders->fresh()->load('courseLevels.level');
    }

    public function destroy(Course $ders)
    {
        // Sadece URETILMIS program korunuyor - dersin kendi mufredat/
        // yetki verisi degil, bkz. web tarafindaki ayni kontrol.
        $atamaVar = SectionCourseTeacher::where('course_id', $ders->id)->exists();

        if ($atamaVar) {
            return response()->json([
                'message' => "{$ders->name} silinemez: bu ders üretilmiş programda kullanılıyor. Önce programı sıfırlayın.",
            ], 422);
        }

        $ders->delete();

        return response()->json(null, 204);
    }
}
