<?php

namespace App\Http\Controllers;

use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\SectionCourseTeacher;
use App\Services\OgretmenAtamaServisi;
use App\Services\SlotYerlestirmeServisi;
use App\Http\Requests\ProgramDuzenleRequest;
use App\Models\Course;
use App\Models\Teacher;

class ProgramController extends Controller
{
    public function index()
    {
        return view('program.index', [
            'atamaSayisi' => SectionCourseTeacher::whereHas('section')->count(),
            'programSayisi' => ScheduleEntry::whereHas('section')->count(),

            // Ön kontrol: dersliksiz şubenin programı üretilemez,
            // çünkü schedule_entries.room_id NOT NULL.
            // Kullanıcı çalıştırmadan ÖNCE görsün.
            'dersliksizSubeler' => Section::whereNull('room_id')->pluck('name')->all(),

            'atamaSonuc' => session('atamaSonuc'),
            'yerlesimSonuc' => session('yerlesimSonuc'),
        ]);
    }

    public function atama(OgretmenAtamaServisi $servis)
    {
        $sonuc = $servis->calistir();

        return redirect()
            ->route('program.index')
            ->with('atamaSonuc', $sonuc)
            ->with('success', "1. adım tamamlandı: {$sonuc['atanan']} atama yapıldı ({$sonuc['sure_ms']} ms).");
    }

    public function yerlestir(SlotYerlestirmeServisi $servis)
    {
        $sonuc = $servis->calistir();

        if ($sonuc['hata']) {
            return redirect()
                ->route('program.index')
                ->with('error', $sonuc['hata']);
        }

        return redirect()
            ->route('program.index')
            ->with('yerlesimSonuc', $sonuc)
            ->with('success', "2. adım tamamlandı: {$sonuc['yerlesen']} ders saati yerleştirildi ({$sonuc['sure_ms']} ms).");
    }

        /**
     * Bir hucreyi duzenleme formu. Bos veya dolu hucre icin ayni form,
     * mevcut kayit varsa alanlar onceden dolduruluyor.
     */
    public function hucreDuzenle(Section $sube, int $weekday, int $slot)
    {
        if (! $sube->room_id) {
            return redirect()
                ->route('cizelge.sube', $sube)
                ->with('error', 'Bu şubenin dersliği yok, program düzenlenemez.');
        }

        $mevcut = ScheduleEntry::where('section_id', $sube->id)
            ->where('weekday', $weekday)
            ->where('slot_no', $slot)
            ->first();

        // Bu subenin seviyesindeki mufredata gore secenekler.
        $dersler = Course::whereHas('courseLevels', fn ($q) => $q->where('level_id', $sube->level_id))
            ->orderBy('name')
            ->get();

        $ogretmenler = Teacher::whereHas('courseLevels', fn ($q) => $q->where('level_id', $sube->level_id))
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $gunAdlari = [
            1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe',
            5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar',
        ];

        return view('program.hucre-duzenle', [
            'sube' => $sube,
            'weekday' => $weekday,
            'slot' => $slot,
            'gunAdi' => $gunAdlari[$weekday] ?? '?',
            'mevcut' => $mevcut,
            'dersler' => $dersler,
            'ogretmenler' => $ogretmenler,
        ]);
    }

    /**
     * Hucreyi kaydeder. Oda her zaman subenin kendi dersligi -
     * kullanicidan hic sorulmuyor (sartname: her subenin sabit
     * dersligi var).
     */
    public function hucreGuncelle(ProgramDuzenleRequest $request, Section $sube, int $weekday, int $slot)
    {
        ScheduleEntry::updateOrCreate(
            ['section_id' => $sube->id, 'weekday' => $weekday, 'slot_no' => $slot],
            [
                'course_id' => $request->course_id,
                'teacher_id' => $request->teacher_id,
                'room_id' => $sube->room_id,
            ]
        );

        return redirect()
            ->route('cizelge.sube', $sube)
            ->with('success', 'Ders saati güncellendi.');
    }

    /**
     * Hucreyi bosaltir - kayit silinir, cizelgede "bos" gorunur.
     */
    public function hucreBosalt(Section $sube, int $weekday, int $slot)
    {
        ScheduleEntry::where('section_id', $sube->id)
            ->where('weekday', $weekday)
            ->where('slot_no', $slot)
            ->delete();

        return redirect()
            ->route('cizelge.sube', $sube)
            ->with('success', 'Ders saati boşaltıldı.');
    }

}
