<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseLevel;
use App\Models\ScheduleEntry;
use App\Models\School;
use App\Models\SchoolDay;
use App\Models\Section;
use App\Models\SectionCourseTeacher;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

/**
 * Program üretiminin İKİNCİ fazı: faz 1'in belirlediği
 * şube-ders-öğretmen atamalarını gün ve slotlara yerleştirir.
 *
 * Faz 1 "kim verecek" sorusuna cevap verdi, bu faz "ne zaman"
 * sorusuna cevap veriyor.
 *
 * İki kısıt var (şartname 2.6.2/1):
 *   - Bir şube aynı slotta iki ders göremez
 *   - Bir öğretmen aynı slotta iki yerde olamaz
 *
 * Derslik kısıtı YOK: her şubenin sabit dersliği var, şube aynı anda
 * tek ders gördüğü için oda çakışması yapısal olarak imkânsız.
 * Oda şubeden kopyalanıyor.
 *
 * Performans: veri 7 sorguyla bir kez belleğe alınıyor, yerleştirme
 * bellekte yapılıyor, sonuç tek işlemde toplu yazılıyor.
 */
class SlotYerlestirmeServisi
{
    public function calistir(): array
    {
        $baslangic = microtime(true);

        // ───── 1. Veriyi belleğe al ─────────────────────────────

        $okul = School::aktif();

        if (! $okul) {
            return $this->bosRapor('Okul tanımlı değil.');
        }

        $gunler = SchoolDay::where('is_active', true)
            ->orderBy('weekday')
            ->pluck('weekday')
            ->all();

        if (empty($gunler)) {
            return $this->bosRapor('Aktif eğitim günü tanımlı değil.');
        }

        $slotSayisi = $okul->lessons_per_day;

        $subeler = Section::all()->keyBy('id');
        $dersler = Course::all()->keyBy('id');
        $ogretmenler = Teacher::all()->keyBy('id');

        // "dersId-seviyeId" => haftalık saat
        $mufredat = CourseLevel::all()
            ->mapWithKeys(fn ($cl) => [$cl->course_id . '-' . $cl->level_id => $cl->weekly_hours])
            ->all();

        // Faz 1'in ürettiği atamalar. orderBy determinizm için:
        // ORDER BY olmadan PostgreSQL satır sırası garantisi vermiyor.
        $atamalar = SectionCourseTeacher::orderBy('section_id')
            ->orderBy('course_id')
            ->get();

        if ($atamalar->isEmpty()) {
            return $this->bosRapor('Önce 1. adımı çalıştırın: öğretmen ataması yapılmamış.');
        }

        // ───── 2. Hazırlık: yük hesabı ve sıralama ──────────────

        // Her atamanın kaç saat olduğunu ve öğretmenin toplam yükünü bul.
        $ogretmenYuku = [];
        $isler = [];

        foreach ($atamalar as $atama) {
            $sube = $subeler[$atama->section_id] ?? null;

            if (! $sube) {
                continue;
            }

            $saat = $mufredat[$atama->course_id . '-' . $sube->level_id] ?? 0;

            if ($saat < 1) {
                continue;   // bu ders bu seviyede okutulmuyor
            }

            $isler[] = [
                'section_id' => $atama->section_id,
                'course_id' => $atama->course_id,
                'teacher_id' => $atama->teacher_id,
                'saat' => $saat,
            ];

            $ogretmenYuku[$atama->teacher_id] = ($ogretmenYuku[$atama->teacher_id] ?? 0) + $saat;
        }

        // SEZGİSEL 1: en yüklü öğretmenin işleri önce.
        // 20 saatlik bir öğretmen 35 slotun 20'sini dolduracak; ızgara
        // boşken yerleştirmek kolay, sona bırakırsam yer kalmaz.
        // Eşitlikte saat, sonra id — sonuç deterministik olsun diye.
        usort($isler, function ($a, $b) use ($ogretmenYuku) {
            return [
                $ogretmenYuku[$b['teacher_id']], $b['saat'], $a['section_id'], $a['course_id'],
            ] <=> [
                $ogretmenYuku[$a['teacher_id']], $a['saat'], $b['section_id'], $b['course_id'],
            ];
        });

        // ───── 3. Yerleştir ─────────────────────────────────────

        $subeDolu = [];        // [subeId][gun][slot] = true
        $ogretmenDolu = [];    // [ogretmenId][gun][slot] = true
        $subeGunYuku = [];     // [subeId][gun] = adet  — güne yayma için
        $dersGunu = [];        // [subeId][dersId][gun] = true — aynı ders aynı güne yığılmasın

        $satirlar = [];
        $atanamayanlar = [];
        $hedefSaat = 0;

        foreach ($isler as $is) {
            $hedefSaat += $is['saat'];

            $sube = $subeler[$is['section_id']];
            $subeAdi = $sube->name;
            $dersAdi = $dersler[$is['course_id']]->name ?? "Ders #{$is['course_id']}";
            $ogretmen = $ogretmenler[$is['teacher_id']] ?? null;
            $ogretmenAdi = $ogretmen ? $ogretmen->first_name . ' ' . $ogretmen->last_name : '?';

            // schedule_entries.room_id NOT NULL — dersliksiz şubenin
            // programı üretilemez. Sessizce atlamak yerine raporluyorum.
            if (! $sube->room_id) {
                $atanamayanlar[] = [
                    'sube' => $subeAdi,
                    'ders' => $dersAdi,
                    'ogretmen' => $ogretmenAdi,
                    'saat' => $is['saat'],
                    'sebep' => 'Şubeye derslik atanmamış. Şube düzenleme ekranından derslik seçin.',
                ];

                continue;
            }

            $yerlesen = 0;

            for ($sira = 1; $sira <= $is['saat']; $sira++) {
                $slot = $this->bosSlotBul(
                    $is, $gunler, $slotSayisi,
                    $subeDolu, $ogretmenDolu, $subeGunYuku, $dersGunu
                );

                if ($slot === null) {
                    break;
                }

                [$gun, $slotNo] = $slot;

                $satirlar[] = [
                    'section_id' => $is['section_id'],
                    'course_id' => $is['course_id'],
                    'teacher_id' => $is['teacher_id'],
                    'room_id' => $sube->room_id,
                    'weekday' => $gun,
                    'slot_no' => $slotNo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $subeDolu[$is['section_id']][$gun][$slotNo] = true;
                $ogretmenDolu[$is['teacher_id']][$gun][$slotNo] = true;
                $subeGunYuku[$is['section_id']][$gun] = ($subeGunYuku[$is['section_id']][$gun] ?? 0) + 1;
                $dersGunu[$is['section_id']][$is['course_id']][$gun] = true;

                $yerlesen++;
            }

            // Şartname 2.6.2/2: yerleştirilemeyen sessizce düşmemeli
            if ($yerlesen < $is['saat']) {
                $atanamayanlar[] = [
                    'sube' => $subeAdi,
                    'ders' => $dersAdi,
                    'ogretmen' => $ogretmenAdi,
                    'saat' => $is['saat'] - $yerlesen,
                    'sebep' => "Uygun boş slot bulunamadı. {$ogretmenAdi} ile {$subeAdi} birlikte boş olduğu saat kalmadı.",
                ];
            }
        }

        // ───── 4. Toplu yaz ─────────────────────────────────────

        DB::transaction(function () use ($satirlar) {
            // Tekrarlı sihirbaz (2.6.2/5): önce eskisini temizle
            // whereHas('section'): Section modeli SubeOkulScope tasidigi
            // icin bu, sadece AKTIF OKULUN program kayitlarini siler.
            // Bilerek eklendi - filtre olmadan sihirbaz her calistiginda
            // TUM okullarin programini siliyordu (kritik veri kaybi).
            ScheduleEntry::whereHas('section')->delete();

            foreach (array_chunk($satirlar, 500) as $parca) {
                ScheduleEntry::insert($parca);
            }
        });

        // ───── 5. Raporla ───────────────────────────────────────

        $kapasite = count($subeler) * $slotSayisi * count($gunler);

        $gunDagilimi = [];
        foreach ($satirlar as $s) {
            $gunDagilimi[$s['weekday']] = ($gunDagilimi[$s['weekday']] ?? 0) + 1;
        }
        ksort($gunDagilimi);

        return [
            'hata' => null,
            'hedef' => $hedefSaat,
            'yerlesen' => count($satirlar),
            'atanamayanlar' => $atanamayanlar,
            'kapasite' => $kapasite,
            'doluluk' => $kapasite > 0 ? round(count($satirlar) / $kapasite * 100, 1) : 0,
            'gun_dagilimi' => $gunDagilimi,
            'sure_ms' => (int) round((microtime(true) - $baslangic) * 1000),
        ];
    }

    /**
     * Bir ders saati için uygun boş slot arar.
     *
     * SEZGİSEL 2: önce bu dersin o şubede henüz olmadığı günlere bakıyor.
     * Matematik haftada 6 saat; hepsi Pazartesi'ye düşerse program
     * kullanılamaz olur. Şartname blok dersi opsiyonel bıraktığı için
     * bu bir tercih, zorunluluk değil — bulamazsa herhangi bir boş slota
     * yerleştiriyor.
     *
     * Günler o şubede en az dolu olandan başlayarak taranıyor: yük
     * haftaya dengeli yayılsın, Cuma boş kalıp Pazartesi tıkanmasın.
     */
    private function bosSlotBul(
        array $is,
        array $gunler,
        int $slotSayisi,
        array $subeDolu,
        array $ogretmenDolu,
        array $subeGunYuku,
        array $dersGunu
    ): ?array {
        $subeId = $is['section_id'];
        $dersId = $is['course_id'];
        $ogretmenId = $is['teacher_id'];

        // Günleri o şubedeki doluluğa göre sırala (az dolu önce).
        // Eşitlikte gün numarası — deterministik olsun diye.
        $siraliGunler = $gunler;
        usort($siraliGunler, function ($a, $b) use ($subeGunYuku, $subeId) {
            return [$subeGunYuku[$subeId][$a] ?? 0, $a]
               <=> [$subeGunYuku[$subeId][$b] ?? 0, $b];
        });

        // 1. tur: bu dersin henüz olmadığı günler
        foreach ($siraliGunler as $gun) {
            if (isset($dersGunu[$subeId][$dersId][$gun])) {
                continue;
            }

            for ($slot = 1; $slot <= $slotSayisi; $slot++) {
                if (isset($subeDolu[$subeId][$gun][$slot])) {
                    continue;
                }

                if (isset($ogretmenDolu[$ogretmenId][$gun][$slot])) {
                    continue;
                }

                return [$gun, $slot];
            }
        }

        // 2. tur: herhangi bir boş slot
        foreach ($siraliGunler as $gun) {
            for ($slot = 1; $slot <= $slotSayisi; $slot++) {
                if (isset($subeDolu[$subeId][$gun][$slot])) {
                    continue;
                }

                if (isset($ogretmenDolu[$ogretmenId][$gun][$slot])) {
                    continue;
                }

                return [$gun, $slot];
            }
        }

        return null;
    }

    private function bosRapor(string $hata): array
    {
        return [
            'hata' => $hata,
            'hedef' => 0,
            'yerlesen' => 0,
            'atanamayanlar' => [],
            'kapasite' => 0,
            'doluluk' => 0,
            'gun_dagilimi' => [],
            'sure_ms' => 0,
        ];
    }
}
