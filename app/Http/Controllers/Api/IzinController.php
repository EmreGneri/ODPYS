<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IzinRequest;
use App\Models\LeaveRequest;
use App\Services\IzinServisi;
use Illuminate\Http\Request;

/**
 * Sartname 2.7 - ogretmen izin yonetimi. Web tarafindaki IzinController
 * gibi iki tarafli: ogretmen kendi talebini olusturur (teacher grubu),
 * yonetici onaylar/reddeder (admin grubu). Ayni LeaveRequest tablosuna
 * baktiklari icin tek dosyada.
 */
class IzinController extends Controller
{
    // ───── ÖĞRETMEN TARAFI ─────────────────────────────────────

    public function benimIzinlerim(Request $request)
    {
        $ogretmen = $request->user()->teacher;

        return LeaveRequest::where('teacher_id', $ogretmen->id)
            ->orderByDesc('starts_at')
            ->get();
    }

    public function store(IzinRequest $request)
    {
        // teacher_id request'ten degil, oturum acmis kullanicinin kendi
        // ogretmen kaydindan aliniyor - kullanici baska bir ogretmen
        // adina talep olusturamamali.
        $ogretmen = $request->user()->teacher;

        $izin = LeaveRequest::create([
            'teacher_id' => $ogretmen->id,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json($izin, 201);
    }

    // ───── YÖNETİCİ TARAFI ─────────────────────────────────────

    public function index()
    {
        return LeaveRequest::whereHas('teacher')->with('teacher')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('starts_at')
            ->get();
    }

    public function goster(LeaveRequest $izin, IzinServisi $servis)
    {
        return [
            'izin' => $izin->load('teacher'),
            'etkilenenDersler' => $servis->etkilenenDersler($izin),
        ];
    }

    public function onayla(LeaveRequest $izin, Request $request)
    {
        if ($izin->status !== 'pending') {
            return response()->json(['message' => 'Bu talep zaten karara bağlanmış.'], 422);
        }

        $izin->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return $izin;
    }

    public function reddet(LeaveRequest $izin, Request $request)
    {
        if ($izin->status !== 'pending') {
            return response()->json(['message' => 'Bu talep zaten karara bağlanmış.'], 422);
        }

        $izin->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return $izin;
    }
}
