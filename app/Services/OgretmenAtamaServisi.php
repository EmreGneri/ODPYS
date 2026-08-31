<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseLevel;
use App\Models\Level;
use App\Models\Section;
use App\Models\SectionCourseTeacher;
use App\Models\Teacher;
use App\Models\TeacherCourseLevel;
use Illuminate\Support\Facades\DB;

/**
 * Program üretiminin BİRİNCİ fazı: şube-ders çiftlerine öğretmen atar.
 * Saatlere yerleştirme ikinci fazın işi.
 *
 * Performans yaklaşımı: bütün veri 6 sorguyla bir kez belleğe alınıyor,
 * hesap bellekte yapılıyor, sonuç tek toplu yazma ile kaydediliyor.
 * Sorgu sayısı veri boyutundan BAĞIMSIZ (şartname 3.2).
 */
class OgretmenAtamaServisi
{
    public function calistir(): array
    {
        $baslangic = microtime(true);

        // ───── 1. Veriyi belleğe al ─────────────────────────────────

        $subeler = Section::orderBy('level_id')->orderBy('name')->get();
        $seviyeler = Level::all()->keyBy('id');
        $dersler = Course::all()->keyBy('id');

        // seviye_id => o seviyede okutulan dersler ve haftalık saatleri
        $mufredat = CourseLevel::all()->groupBy('level_id');

        // "dersId-seviyeId" => o dersi o seviyede verebilen öğretmen id'leri
        $yetkiler = TeacherCourseLevel::all()
            ->groupBy(fn ($y) => $y->course_id . '-' . $y->level_id)
            ->map(fn ($grup) => $grup->pluck('teacher_id')->all());

        $ogretmenler = Teacher::where('is_active', true)->get()->keyBy('id');

        // ───── 2. Bellekte hesapla ──────────────────────────────────

        $yuk = [];
        foreach ($ogretmenler as $ogretmen) {
            $yuk[$ogretmen->id] = 0;
        }

        $atamalar = [];
        $atanamayanlar = [];

        foreach ($subeler as $sube) {
            $seviyeAdi = $seviyeler[$sube->level_id]->name ?? '?';
            $dersListesi = $mufredat->get($sube->level_id, collect());

            foreach ($dersListesi as $mufredatSatiri) {
                $dersId = $mufredatSatiri->course_id;
                $saat = $mufredatSatiri->weekly_hours;
                $dersAdi = $dersler[$dersId]->name ?? "Ders #{$dersId}";

                $adaylar = $yetkiler[$dersId . '-' . $sube->level_id] ?? [];

                // Aktif VE kapasitesi yeten adayları süz (İş Kuralı 4.3)
                $uygun = [];
                foreach ($adaylar as $ogretmenId) {
                    if (! isset($ogretmenler[$ogretmenId])) {
                        continue;   // pasif öğretmen
                    }

                    if ($yuk[$ogretmenId] + $saat > $ogretmenler[$ogretmenId]->max_weekly_hours) {
                        continue;   // haftalık kapasitesi dolmuş
                    }

                    $uygun[] = $ogretmenId;
                }

                // Şartname 2.6.2/2: uygun öğretmen yoksa HATA ÜRETİLMELİ
                if (empty($uygun)) {
                    $atanamayanlar[] = [
                        'sube' => $sube->name,
                        'seviye' => $seviyeAdi,
                        'ders' => $dersAdi,
                        'saat' => $saat,
                        'sebep' => empty($adaylar)
                            ? 'Bu dersi bu seviyede verebilen öğretmen tanımlı değil.'
                            : 'Yetkili öğretmenlerin tamamı haftalık kapasitesini doldurmuş.',
                    ];
                    continue;
                }

                // Şartname 2.6.2/3 HAKKANİYET: en az yüklü öğretmeni seç.
                // Eşitlikte küçük id kazanır — tekrar çalıştırınca aynı
                // sonuç çıksın diye (deterministik).
                usort($uygun, fn ($a, $b) => [$yuk[$a], $a] <=> [$yuk[$b], $b]);

                $secilen = $uygun[0];

                $atamalar[] = [
                    'section_id' => $sube->id,
                    'course_id' => $dersId,
                    'teacher_id' => $secilen,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $yuk[$secilen] += $saat;
            }
        }

        // ───── 3. Toplu yaz ─────────────────────────────────────────

        DB::transaction(function () use ($atamalar) {
            // Tekrarlı sihirbaz (2.6.2/5): önce eskisini temizle
            SectionCourseTeacher::whereHas('section')->delete();

            foreach (array_chunk($atamalar, 500) as $parca) {
                SectionCourseTeacher::insert($parca);
            }
        });

        // ───── 4. Raporla ───────────────────────────────────────────

        $calisanYukler = array_filter($yuk);

        return [
            'atanan' => count($atamalar),
            'atanamayanlar' => $atanamayanlar,
            'toplam_saat' => array_sum($yuk),
            'calisan_ogretmen' => count($calisanYukler),
            'en_yuklu' => $calisanYukler ? max($calisanYukler) : 0,
            'en_az_yuklu' => $calisanYukler ? min($calisanYukler) : 0,
            'sure_ms' => round((microtime(true) - $baslangic) * 1000),
        ];
    }
}
