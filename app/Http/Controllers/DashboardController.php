<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Level;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\School;
use App\Models\Section;
use App\Models\Teacher;

class DashboardController extends Controller
{
    public function index()
    {
        $okul = School::first();

        return view('dashboard', [
            'okul' => $okul,
            'seviyeSayisi' => Level::count(),
            'subeSayisi' => Section::count(),
            'derslikSayisi' => Room::count(),
            'dersSayisi' => Course::count(),
            'ogretmenSayisi' => Teacher::where('is_active', true)->count(),
            'programKayitSayisi' => ScheduleEntry::count(),
        ]);
    }
}
