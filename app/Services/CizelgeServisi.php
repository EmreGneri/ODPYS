<?php

namespace App\Services;

use App\Models\Course;
use App\Models\ScheduleEntry;
use App\Models\School;
use App\Models\SchoolDay;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\TeacherCourseLevel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Uretilmis programi goruntuleme bicimlerine cevirir.
 * Sartname 5. bolum: olusturulacak ciktilar.
 *
 * Neden servis: uc ekran ayni veriyi farkli sekillerde istiyor
 * (sube bazli, ogretmen bazli, yuk raporu). Mantik controller'da
 * olsaydi ucunde de tekrarlanirdi.
 *
 * Bu sinif HTTP bilmiyor, dizi donduruyor. Blade de basabilir,
 * Inertia da prop olarak verebilir, API de JSON'a cevirebilir.
 */
class CizelgeServisi
{
    private const GUN_ADLARI = [
        1 => 'Pazartesi',
        2 => 'Salı',
        3 => 'Çarşamba',
        4 => 'Perşembe',
        5 => 'Cuma',
        6 => 'Cumartesi',
        7 => 'Pazar',
    ];

    public function __construct(
        private IzinServisi $izinServisi,
        private TakasServisi $takasServisi
    ) {
    }

    /**
     * Izgaranin hucrelerini kurar: takaslari uygular, sonra izin
     * isaretlemesini yapar.
     *
     * ONEMLI - SIRA: once TAKAS uygulanip dersin o hafta GERCEKTE
     * hangi slotta durdugu bulunuyor, izin kontrolu ondan SONRA
     * yapiliyor. Ters sirada olsaydi takasla kurtarilmis bir ders hala
     * "izinli" gorunurdu; yani takas ekranda hicbir sey degistirmemis
     * gibi olurdu.
     *
     * Iki gorunum (sube / ogretmen) hucrenin ust-alt metnini farkli
     * kuruyor, o yuzden bicimlendirme disaridan closure olarak geliyor.
     *
     * @param  callable  $bicimlendir  ScheduleEntry -> ['ust' => .., 'alt' => ..]
     */
    private function hucreleriKur(
        ?School $okul,
        Collection $kayitlar,
        Carbon $haftaBasi,
        callable $bicimlendir
    ): array {
        if (! $okul) {
            return [];
        }

        // pozisyon => o hafta orada GERCEKTE duran ders
        $yerlesim = [];

        foreach ($kayitlar as $k) {
            $yerlesim[$k->weekday][$k->slot_no] = ['kayit' => $k, 'takas' => null];
        }

        foreach ($this->takasServisi->haftaninTakaslari($haftaBasi) as $takas) {
            $a = $takas->entryA;
            $b = $takas->entryB;

            if (! $a || ! $b) {
                continue;
            }

            // Bu izgarada hangi taraflar var? Sube gorunumunde ikisi de
            // (takas ayni sube icinde kaliyor), ogretmen gorunumunde
            // genelde sadece biri - o zaman ders obur pozisyona TASINIYOR
            // ve eski pozisyonu bosaliyor.
            $aBizde = $kayitlar->contains('id', $a->id);
            $bBizde = $kayitlar->contains('id', $b->id);

            if (! $aBizde && ! $bBizde) {
                continue;
            }

            unset($yerlesim[$a->weekday][$a->slot_no], $yerlesim[$b->weekday][$b->slot_no]);

            if ($aBizde) {
                $yerlesim[$b->weekday][$b->slot_no] = ['kayit' => $a, 'takas' => $takas];
            }

            if ($bBizde) {
                $yerlesim[$a->weekday][$a->slot_no] = ['kayit' => $b, 'takas' => $takas];
            }
        }

        $izinler = $this->izinServisi->onayliIzinler(
            $kayitlar->pluck('teacher_id')->filter()->unique()->all(),
            $haftaBasi->copy()->startOfDay(),
            $haftaBasi->copy()->addDays(6)->endOfDay()
        );

        $izgara = [];

        foreach ($yerlesim as $weekday => $slotlar) {
            // haftaBasi Pazartesi (dayOfWeekIso = 1), yani weekday 1 => +0 gun.
            $gun = $haftaBasi->copy()->addDays($weekday - 1);
            $slotSaatleri = $okul->slotSaatleri($weekday);

            foreach ($slotlar as $slotNo => $hucre) {
                $k = $hucre['kayit'];
                $saat = $slotSaatleri[$slotNo] ?? null;

                $izin = ($saat && $k->teacher_id)
                    ? $this->izinServisi->cakisanIzin(
                        $izinler,
                        $k->teacher_id,
                        $gun->copy()->setTimeFromTimeString($saat['baslangic']),
                        $gun->copy()->setTimeFromTimeString($saat['bitis'])
                    )
                    : null;

                $izgara[$weekday][$slotNo] = $bicimlendir($k) + [
                    'izinli' => (bool) $izin,
                    'izinSebep' => $izin?->reason,
                    'takasli' => (bool) $hucre['takas'],
                    'takasNot' => $hucre['takas']
                        ? 'Öğretmen izni nedeniyle ders saati değişti (normalde ' . $k->slot_no . '. ders).'
                        : null,
                ];
            }
        }

        return $izgara;
    }

    /**
     * Bir subenin haftalik cizelgesi. Sartname 5.1
     *
     * Onayli izinle cakisan hucreler isaretlenir (sartname 2.7.3).
     * $haftaBasi verilmezse bugunun haftasi kullanilir - bu ekranda
     * hafta secici yok, "su an" gorunumu.
     */
    public function subeCizelgesi(Section $sube, ?Carbon $haftaBasi = null): array
    {
        $okul = School::aktif()?->load('days');
        $haftaBasi = $haftaBasi ?? Carbon::now()->startOfWeek(Carbon::MONDAY);

        // Tek sorgu, iliskiler onden yukleniyor. Izgarada 35 hucre var
        // ve her hucrede ders ile ogretmen adi gosteriliyor; with()
        // olmasaydi hucre basina iki sorgu atilirdi.
        $kayitlar = ScheduleEntry::with(['course', 'teacher'])
            ->where('section_id', $sube->id)
            ->get();

        // Takas uygulama ve izin isaretlemesi hucreleriKur()'da -
        // ogretmen cizelgesiyle ortak, cunku ikisi de ayni kurallara
        // tabi (bkz. o metodun docblock'u).
        $izgara = $this->hucreleriKur($okul, $kayitlar, $haftaBasi, fn ($k) => [
            'ust' => $k->course->name ?? '?',
            'alt' => $k->teacher
                ? $k->teacher->first_name . ' ' . $k->teacher->last_name
                : '?',
        ]);

        return $this->izgarayaCevir($okul, $izgara) + [
            'baslik' => $sube->name . ' Şubesi',
            'altBaslik' => $sube->room?->name
                ? 'Derslik: ' . $sube->room->name
                : 'Derslik atanmamış',
            'toplamSaat' => $kayitlar->count(),
        ];
    }

    /**
     * Bir ogretmenin haftalik programi. Sartname 5.2
     *
     * Onayli izinle cakisan hucreler isaretlenir (sartname 2.7.3).
     */
    public function ogretmenCizelgesi(Teacher $ogretmen, ?Carbon $haftaBasi = null): array
    {
        $okul = School::aktif()?->load('days');
        $haftaBasi = $haftaBasi ?? Carbon::now()->startOfWeek(Carbon::MONDAY);

        $kayitlar = ScheduleEntry::with(['course', 'section', 'room'])
            ->where('teacher_id', $ogretmen->id)
            ->get();

        $izgara = $this->hucreleriKur($okul, $kayitlar, $haftaBasi, fn ($k) => [
            'ust' => $k->course->name ?? '?',
            'alt' => ($k->section->name ?? '?') . ' · ' . ($k->room->name ?? '?'),
        ]);

        return $this->izgarayaCevir($okul, $izgara) + [
            'baslik' => $ogretmen->first_name . ' ' . $ogretmen->last_name,
            'altBaslik' => $kayitlar->count() . ' / ' . $ogretmen->max_weekly_hours . ' saat',
            'toplamSaat' => $kayitlar->count(),
        ];
    }

    /**
     * Yuk dagilimi raporu. Sartname 5.5
     * "Hangi ogretmen haftada kac saat ders almis?"
     */
    public function yukDagilimi(): array
    {
        // Her schedule_entries satiri bir ders saati; saymak yeterli.
        $saatler = ScheduleEntry::whereHas('teacher')
            ->selectRaw('teacher_id, COUNT(*) AS saat')
            ->groupBy('teacher_id')
            ->pluck('saat', 'teacher_id');

        $dersAdlari = Course::pluck('name', 'id');

        $branslar = TeacherCourseLevel::whereHas('teacher')
            ->select('teacher_id', 'course_id')
            ->distinct()
            ->get()
            ->groupBy('teacher_id')
            ->map(fn ($g) => $g->pluck('course_id')->unique()
                ->map(fn ($id) => $dersAdlari[$id] ?? '?')
                ->sort()->values()->all());

        $satirlar = Teacher::orderBy('first_name')->orderBy('last_name')->get()
            ->map(function ($o) use ($saatler, $branslar) {
                $saat = (int) ($saatler[$o->id] ?? 0);

                return [
                    'id' => $o->id,
                    'ad' => $o->first_name . ' ' . $o->last_name,
                    'branslar' => $branslar[$o->id] ?? [],
                    'saat' => $saat,
                    'kapasite' => $o->max_weekly_hours,
                    'oran' => $o->max_weekly_hours > 0
                        ? (int) round($saat / $o->max_weekly_hours * 100)
                        : 0,
                    'aktif' => (bool) $o->is_active,
                ];
            });

        $calisan = $satirlar->where('saat', '>', 0);

        return [
            'satirlar' => $satirlar->all(),
            'toplamSaat' => $satirlar->sum('saat'),
            'calisanSayisi' => $calisan->count(),
            'bostaSayisi' => $satirlar->count() - $calisan->count(),
            'enYuklu' => $calisan->max('saat') ?? 0,
            'enAzYuklu' => $calisan->min('saat') ?? 0,
        ];
    }

    /**
     * Zaman cakismasi kontrolu. Sartname 5.3
     *
     * schedule_entries uzerinde uc unique indeks var, yani cakisma
     * fiziksel olarak olusamaz. Bu metot o garantiyi DOGRULUYOR:
     * bos donmesi veritabani korumasinin calistigi anlamina geliyor.
     */
    public function cakismaKontrolu(): array
    {
        $bul = function (string $sutun): array {
            return DB::table('schedule_entries')
                ->selectRaw($sutun . ' AS anahtar, weekday, slot_no, COUNT(*) AS adet')
                ->groupBy($sutun, 'weekday', 'slot_no')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->all();
        };

        return [
            'sube' => $bul('section_id'),
            'ogretmen' => $bul('teacher_id'),
            'derslik' => $bul('room_id'),
        ];
    }

    /**
     * Ortak izgara iskeleti: aktif gunler, slot numaralari ve
     * her gunun gercek saatleri.
     *
     * Saatler veritabaninda saklanmiyor; School::slotSaatleri()
     * okul ayarlarindan hesapliyor. Ders suresi degisince butun
     * cizelge kendiliginden kaiyor.
     */
    private function izgarayaCevir(?School $okul, array $izgara): array
    {
        if (! $okul) {
            return ['gunler' => [], 'slotlar' => [], 'saatler' => [], 'izgara' => []];
        }

        $aktifGunler = SchoolDay::where('school_id', $okul->id)->where('is_active', true)
            ->orderBy('weekday')
            ->pluck('weekday')
            ->all();

        $gunler = [];
        $saatler = [];

        foreach ($aktifGunler as $g) {
            $gunler[$g] = self::GUN_ADLARI[$g] ?? 'Gün ' . $g;
            $saatler[$g] = $okul->slotSaatleri($g);
        }

        return [
            'gunler' => $gunler,
            'slotlar' => range(1, $okul->lessons_per_day),
            'saatler' => $saatler,
            'izgara' => $izgara,
        ];
    }
}
