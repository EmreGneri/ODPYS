<?php

namespace App\Http\Controllers;

use App\Http\Requests\IzinRequest;
use App\Models\LeaveRequest;
use App\Services\IzinServisi;
use Illuminate\Http\Request;

/**
 * Sartname 2.7 - ogretmen izin yonetimi.
 *
 * Bu controller iki taraflI: metotlarin bir kismi ogretmenin kendi
 * talebini yonetmesi icin (teacher middleware), bir kismi yoneticinin
 * onaylamasi icin (admin middleware). Ikisi ayni LeaveRequest tablosuna
 * bakiyor, o yuzden tek controller'da tuttum.
 */
class IzinController extends Controller
{
    // ───── ÖĞRETMEN TARAFI ─────────────────────────────────────

    /**
     * Ogretmenin kendi izin taleplerinin listesi.
     */
    public function index(Request $request)
    {
        $ogretmen = $request->user()->teacher;

        $izinler = LeaveRequest::where('teacher_id', $ogretmen->id)
            ->orderByDesc('starts_at')
            ->get();

        return view('ogretmen.izinler.index', ['izinler' => $izinler]);
    }

    public function create()
    {
        return view('ogretmen.izinler.create');
    }

    public function store(IzinRequest $request)
    {
        // teacher_id'yi request'ten degil, oturum acmis kullanicinin
        // kendi ogretmen kaydindan aliyorum. Formda teacher_id alani
        // hic yok - olsa bile kullanici kurcalarsa baska ogretmen
        // adina talep olusturabilirdi.
        $ogretmen = $request->user()->teacher;

        LeaveRequest::create([
            'teacher_id' => $ogretmen->id,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('ogretmen.izinler.index')
            ->with('success', 'İzin talebiniz oluşturuldu, yönetici onayı bekleniyor.');
    }

    // ───── YÖNETİCİ TARAFI ─────────────────────────────────────

    /**
     * Butun izin taleplerinin listesi, bekleyenler once.
     * Sartname 2.7.2 - onay ekrani.
     */
    public function yonetimIndex()
    {
        $izinler = LeaveRequest::whereHas('teacher')->with('teacher')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('starts_at')
            ->get();

        return view('izinler.index', ['izinler' => $izinler]);
    }

    /**
     * Tek bir talebin detayi: hangi derslere denk geldigi ("dersi
     * var/yok gosterimi ile" - sartname 2.7.2) burada gosteriliyor.
     */
    public function goster(LeaveRequest $izin, IzinServisi $servis)
    {
        return view('izinler.goster', [
            'izin' => $izin->load('teacher'),
            'etkilenenDersler' => $servis->etkilenenDersler($izin),
        ]);
    }

    public function onayla(LeaveRequest $izin, Request $request)
    {
        if ($izin->status !== 'pending') {
            return redirect()
                ->route('izinler.index')
                ->with('error', 'Bu talep zaten karara bağlanmış.');
        }

        $izin->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return redirect()
            ->route('izinler.index')
            ->with('success', 'İzin talebi onaylandı.');
    }

    public function reddet(LeaveRequest $izin, Request $request)
    {
        if ($izin->status !== 'pending') {
            return redirect()
                ->route('izinler.index')
                ->with('error', 'Bu talep zaten karara bağlanmış.');
        }

        $izin->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return redirect()
            ->route('izinler.index')
            ->with('success', 'İzin talebi reddedildi.');
    }
}
