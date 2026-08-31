<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Level;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\School;
use App\Models\Section;
use App\Models\Teacher;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Ogretmen bu sayfaya gelirse kendi paneline yonlendiriyorum.
        // Asagidaki icerik (sube/ders/derslik sayilari, admin
        // ekranlarina baglantilar) tamamen yonetici icin - sartname
        // 2.7.4 "ogretmen yalnizca izin alma ve kendi bilgilerine
        // erisim saglayabilmelidir" diyor, bu kartlar o siniri asardi.
        if ($request->user()->role === 'teacher') {
            return redirect()->route('ogretmen.panel');
        }

        $okul = School::aktif();

        return view('dashboard', [
            'okul' => $okul,
            'seviyeSayisi' => Level::count(),
            'subeSayisi' => Section::count(),
            'derslikSayisi' => Room::count(),
            'dersSayisi' => Course::count(),
            'ogretmenSayisi' => Teacher::where('is_active', true)->count(),
            'programKayitSayisi' => ScheduleEntry::whereHas('section')->count(),
        ]);
    }
}
