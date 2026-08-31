<?php

namespace App\Http\Controllers;

use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\SectionCourseTeacher;
use App\Services\OgretmenAtamaServisi;
use App\Services\SlotYerlestirmeServisi;

class ProgramController extends Controller
{
    public function index()
    {
        return view('program.index', [
            'atamaSayisi' => SectionCourseTeacher::count(),
            'programSayisi' => ScheduleEntry::count(),

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
}
