<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\School;
use Illuminate\Http\Request;
use App\Models\CourseLevel;
use App\Models\TeacherCourseLevel;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $seviyeler = Level::withCount('sections')->orderBy('grade_number')->get();

        return view('levels.index', ['seviyeler' => $seviyeler]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('levels.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade_number' => 'required|integer|min:1|max:12|unique:levels,grade_number',
        ]);

        $okul = School::first();

        $okul->levels()->create([
            'name' => $validated['grade_number'] . '. Sınıf',
            'grade_number' => $validated['grade_number'],
        ]);

        return redirect()
            ->route('seviyeler.index')
            ->with('success', 'Seviye eklendi.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Level $seviye)
    {
        return view('levels.edit', ['seviye' => $seviye]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Level $seviye)
    {
        $validated = $request->validate([
            'grade_number' => 'required|integer|min:1|max:12|unique:levels,grade_number,' . $seviye->id,
        ]);

        $seviye->update([
            'name' => $validated['grade_number'] . '. Sınıf',
            'grade_number' => $validated['grade_number'],
        ]); 

        return redirect()
            ->route('seviyeler.index')
            ->with('success', 'Seviye güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Level $seviye)
    {
        $subeSayisi = $seviye->sections()->count();

        if ($subeSayisi > 0) {
            return redirect()
                ->route('seviyeler.index')
                ->with('error', "{$seviye->name} silinemez: bu seviyeye bağlı {$subeSayisi} şube var. Önce şubeleri silin.");
        }

        $mufredatSayisi = CourseLevel::where('level_id', $seviye->id)->count();

        if ($mufredatSayisi > 0) {
            return redirect()
                ->route('seviyeler.index')
                ->with('error', "{$seviye->name} silinemez: {$mufredatSayisi} dersin haftalık saati bu seviyeye tanımlı. Önce ders tanımlarından bu seviyeyi kaldırın.");
        }

        $yetkiSayisi = TeacherCourseLevel::where('level_id', $seviye->id)->count();

        if ($yetkiSayisi > 0) {
            return redirect()
                ->route('seviyeler.index')
                ->with('error', "{$seviye->name} silinemez: {$yetkiSayisi} öğretmen yetkisi bu seviyeye bağlı.");
        }

        $seviye->delete();

        return redirect()
            ->route('seviyeler.index')
            ->with('success', 'Seviye silindi.');
    }
}
