<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LeaveRequest;
use App\Services\CizelgeServisi;
use Illuminate\Http\Request;

/**
 * Sartname 2.7.4 - ogretmenin "kendi bilgileri" ekrani: kendi haftalik
 * programi + kendi izin durumu ozeti. Web tarafindaki
 * OgretmenPanelController ile ayni veri, JSON olarak.
 */
class OgretmenPanelController extends Controller
{
    public function index(Request $request, CizelgeServisi $servis)
    {
        $ogretmen = $request->user()->teacher->load('courseLevels');

        $dersAdlari = Course::pluck('name', 'id');
        $branslar = $ogretmen->courseLevels->pluck('course_id')->unique()
            ->map(fn ($id) => $dersAdlari[$id] ?? '?')
            ->sort()->values()->all();

        return $servis->ogretmenCizelgesi($ogretmen) + [
            'ogretmen' => $ogretmen,
            'branslar' => $branslar,
            'bekleyenIzinSayisi' => LeaveRequest::where('teacher_id', $ogretmen->id)
                ->where('status', 'pending')
                ->count(),
        ];
    }
}
