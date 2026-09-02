<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Level;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\School;
use App\Models\Section;
use App\Models\Teacher;

/**
 * Yonetici dashboard ozet kartlari. Rol kontrolu (ogretmen buraya
 * gelirse yonlendir) web'e ozgu bir gezinme kaygisiydi - burada
 * gerek yok, bu endpoint zaten sadece 'admin' rota grubunda.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $okul = School::aktif();

        return [
            'okul' => $okul,
            'seviyeSayisi' => Level::count(),
            'subeSayisi' => Section::count(),
            'derslikSayisi' => Room::count(),
            'dersSayisi' => Course::count(),
            'ogretmenSayisi' => Teacher::where('is_active', true)->count(),
            'programKayitSayisi' => ScheduleEntry::whereHas('section')->count(),
        ];
    }
}
