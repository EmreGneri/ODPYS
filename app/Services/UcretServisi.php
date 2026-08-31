<?php

namespace App\Services;

use App\Models\ScheduleEntry;
use App\Models\Teacher;

/**
 * Ogretmenlerin aylik ders ucretini hesaplar. Sartname 2.2.4.
 *
 * Kaynak veri: uretilmis program (schedule_entries) + derslerin
 * saatlik ucreti (courses.hourly_rate). Ayni ogretmen farkli
 * dersler veriyorsa (farkli ucretler), her ders ayri satirda
 * kiriliyor - "neden bu kadar tutuyor" sorusuna cevap verebilsin.
 *
 * Haftadan aya gecis: schedule_entries HAFTALIK BIR SABLON (tarihsiz,
 * bkz. IzinServisi'nin ayni notu). Takvimde kac hafta oldugu, resmi
 * tatiller vb. bilgisi sistemde yok. AYLIK_HAFTA_SAYISI ile sabit bir
 * varsayim kullaniliyor - bordro degil, tahmini/gosterge niteliginde
 * bir rakam. Gercek bordro icin okulun muhasebesi kesin hesabi yapar.
 */
class UcretServisi
{
    private const AYLIK_HAFTA_SAYISI = 4;

    public function aylikUcretler(): array
    {
        // whereHas('teacher'): coklu okulda sadece aktif okulun
        // ogretmenlerine ait kayitlar gelsin diye.
        $kayitlar = ScheduleEntry::whereHas('teacher')
            ->with('course')
            ->get()
            ->groupBy('teacher_id');

        $satirlar = Teacher::orderBy('first_name')->orderBy('last_name')->get()
            ->map(function ($ogretmen) use ($kayitlar) {
                $dersBazinda = ($kayitlar[$ogretmen->id] ?? collect())
                    ->groupBy('course_id')
                    ->map(function ($grup) {
                        $ders = $grup->first()->course;
                        $saat = $grup->count();
                        $saatlikUcret = (float) ($ders->hourly_rate ?? 0);

                        return [
                            'ders' => $ders->name ?? '?',
                            'saat' => $saat,
                            'saatlikUcret' => $saatlikUcret,
                            'haftalikTutar' => $saat * $saatlikUcret,
                        ];
                    })
                    ->sortBy('ders')
                    ->values();

                $haftalikSaat = (int) $dersBazinda->sum('saat');
                $haftalikTutar = (float) $dersBazinda->sum('haftalikTutar');
                $aylikTutar = $haftalikTutar * self::AYLIK_HAFTA_SAYISI;

                return [
                    'id' => $ogretmen->id,
                    'ad' => $ogretmen->first_name . ' ' . $ogretmen->last_name,
                    'dersler' => $dersBazinda->all(),
                    'haftalikSaat' => $haftalikSaat,
                    'haftalikTutar' => $haftalikTutar,
                    'aylikTutar' => $aylikTutar,
                    'aktif' => (bool) $ogretmen->is_active,
                ];
            });

        $gorevli = $satirlar->where('haftalikSaat', '>', 0);

        return [
            'satirlar' => $satirlar->all(),
            'aylikHafta' => self::AYLIK_HAFTA_SAYISI,
            'toplamAylikTutar' => (float) $gorevli->sum('aylikTutar'),
            'gorevliSayisi' => $gorevli->count(),
            'ortalamaAylikTutar' => $gorevli->count() > 0
                ? (float) $gorevli->avg('aylikTutar')
                : 0.0,
        ];
    }
}
