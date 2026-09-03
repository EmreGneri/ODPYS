<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IzinRequest;
use App\Models\LeaveRequest;
use App\Models\LessonSwap;
use App\Models\ScheduleEntry;
use App\Services\IzinServisi;
use App\Services\TakasServisi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RuntimeException;

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

    public function goster(LeaveRequest $izin, IzinServisi $servis, TakasServisi $takasServisi)
    {
        return [
            'izin' => $izin->load('teacher'),
            'etkilenenDersler' => $servis->etkilenenDersler($izin),
            'takaslar' => $this->takasListesi($takasServisi, $izin),
        ];
    }

    // ───── TAKAS (sartname 2.7.3 uzerine) ──────────────────────

    /**
     * Bir izinli ders icin takas adaylari.
     *
     * Aday listesi UYGUN OLMAYANLARI DA iceriyor (sebebiyle birlikte) -
     * yonetici "neden bununla takas edemiyorum" sorusunun cevabini
     * gormeli, aksi halde eksik listeyi hata saniyor.
     */
    public function takasAdaylari(LeaveRequest $izin, Request $request, TakasServisi $servis)
    {
        $veri = $request->validate([
            'entry_id' => ['required', 'integer'],
            'tarih' => ['required', 'date'],
        ]);

        // Route model binding kullanmiyorum cunku dersin GERCEKTEN bu
        // iznin ogretmenine ait oldugunu da dogrulamam lazim - baska
        // bir ogretmenin dersini bu izin uzerinden takasa sokamamali.
        $ders = ScheduleEntry::whereHas('section')->find($veri['entry_id']);

        if (! $ders || $ders->teacher_id !== $izin->teacher_id) {
            return response()->json(['message' => 'Bu ders bu izne ait değil.'], 422);
        }

        return [
            'adaylar' => $servis->adaylar($izin, $ders, Carbon::parse($veri['tarih'])),
        ];
    }

    public function takasYap(LeaveRequest $izin, Request $request, TakasServisi $servis)
    {
        if ($izin->status !== 'approved') {
            return response()->json([
                'message' => 'Takas yalnızca onaylanmış izinler için yapılabilir.',
            ], 422);
        }

        $veri = $request->validate([
            'entry_id' => ['required', 'integer'],
            'karsi_entry_id' => ['required', 'integer', 'different:entry_id'],
            'tarih' => ['required', 'date'],
        ]);

        $ders = ScheduleEntry::whereHas('section')->find($veri['entry_id']);
        $karsiDers = ScheduleEntry::whereHas('section')->find($veri['karsi_entry_id']);

        if (! $ders || ! $karsiDers) {
            return response()->json(['message' => 'Ders bulunamadı.'], 422);
        }

        if ($ders->teacher_id !== $izin->teacher_id) {
            return response()->json(['message' => 'Bu ders bu izne ait değil.'], 422);
        }

        try {
            $servis->takasYap($izin, $ders, $karsiDers, Carbon::parse($veri['tarih']), $request->user()->id);
        } catch (RuntimeException $e) {
            // TakasServisi'nin kendi dogrulamasi - kullaniciya
            // gosterilebilir, anlasilir bir sebep tasiyor.
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return [
            'mesaj' => 'Ders takası yapıldı.',
            'takaslar' => $this->takasListesi($servis, $izin),
        ];
    }

    public function takasGeriAl(LeaveRequest $izin, LessonSwap $takas, TakasServisi $servis)
    {
        if ($takas->leave_request_id !== $izin->id) {
            return response()->json(['message' => 'Bu takas bu izne ait değil.'], 422);
        }

        $servis->geriAl($takas);

        return [
            'mesaj' => 'Takas geri alındı, ders eski saatine döndü.',
            'takaslar' => $this->takasListesi($servis, $izin),
        ];
    }

    /**
     * Takaslari ekranda gosterilecek sekle cevirir.
     */
    private function takasListesi(TakasServisi $servis, LeaveRequest $izin): array
    {
        return $servis->izninTakaslari($izin)->map(fn ($t) => [
            'id' => $t->id,
            'tarih' => $t->swap_date->format('d.m.Y'),
            // Ekran, zaten takasa girmis derse tekrar "takas yap"
            // teklif etmesin diye bu id'yi kullaniyor.
            'izinliEntryId' => $t->entry_a_id,
            'sube' => $t->entryA?->section?->name ?? '?',
            'izinliDers' => $t->entryA?->course?->name ?? '?',
            'izinliSlot' => $t->entryA?->slot_no,
            'karsiDers' => $t->entryB?->course?->name ?? '?',
            'karsiSlot' => $t->entryB?->slot_no,
            'karsiOgretmen' => $t->entryB?->teacher
                ? $t->entryB->teacher->first_name . ' ' . $t->entryB->teacher->last_name
                : '?',
        ])->all();
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
