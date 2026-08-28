<?php

namespace App\Http\Controllers;

use App\Models\SectionCourseTeacher;
use App\Services\OgretmenAtamaServisi;

class ProgramController extends Controller
{
    public function index()
    {
        return view('program.index', [
            'atamaSayisi' => SectionCourseTeacher::count(),
            'sonuc' => session('sonuc'),
        ]);
    }

    public function atama(OgretmenAtamaServisi $servis)
    {
        $sonuc = $servis->calistir();

        return redirect()
            ->route('program.index')
            ->with('sonuc', $sonuc)
            ->with('success', "{$sonuc['atanan']} atama yapıldı ({$sonuc['sure_ms']} ms).");
    }
}
