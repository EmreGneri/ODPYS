<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LeaveRequest;
use App\Services\CizelgeServisi;
use Illuminate\Http\Request;

/**
 * Sartname 2.7.4 - "ogretmen yalnizca izin alma ve kendi bilgilerine
 * erisim saglayabilmelidir." Bu controller o "kendi bilgileri" ekrani:
 * kendi haftalik programi + kendi izin durumu ozeti.
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

        return view('ogretmen.panel', $servis->ogretmenCizelgesi($ogretmen) + [
            'ogretmen' => $ogretmen,
            'branslar' => $branslar,
            'bekleyenIzinSayisi' => LeaveRequest::where('teacher_id', $ogretmen->id)
                ->where('status', 'pending')
                ->count(),
        ]);
    }
}
