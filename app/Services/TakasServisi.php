<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LessonSwap;
use App\Models\School;
use App\Models\ScheduleEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Ders takasi: bir ogretmenin izni yuzunden, o dersin BELIRLI BIR
 * TARIHTE ayni subenin baska bir ders saatiyle yer degistirmesi.
 *
 * Neden ayni sube icinde takas?
 * Bu sistemde her subenin SABIT bir dersligi var, ogretmenler
 * dolasiyor. Takas ayni sube icinde kaldigi surece derslik degismiyor,
 * dolayisiyla DERSLIK CAKISMASI YAPISAL OLARAK IMKANSIZ - ayrica
 * kontrol etmeye gerek yok. Farkli subeler arasi takasa izin verseydik
 * derslik, sinif mevcudu ve mufredat butunlugu de kontrol edilmek
 * zorunda kalirdi.
 *
 * Neden ayni gun icinde takas?
 * Takas kaydinin TEK bir tarihi var. Persembe dersini Cuma'ya tasimak
 * iki farkli tarihi ilgilendirirdi ve "hangi gunun programinda ne
 * gorunecek" sorusu ikiye bolunurdu. Izin genelde birkac saatlik
 * oldugu icin ayni gun icinde takas pratikte yeterli; izin butun gunu
 * kapsiyorsa zaten uygun aday cikmiyor ve yonetici takassiz onayliyor.
 *
 * Bu sinif HTTP bilmiyor.
 */
class TakasServisi
{
    public function __construct(private IzinServisi $izinServisi)
    {
    }

    /**
     * Bir izinli ders icin gecerli takas adaylari.
     *
     * @param  ScheduleEntry  $izinliDers  Izin yuzunden bosalan ders
     * @param  Carbon         $tarih       Takasin gecerli olacagi gun
     */
    public function adaylar(LeaveRequest $izin, ScheduleEntry $izinliDers, Carbon $tarih): array
    {
        $okul = School::aktif()?->load('days');

        if (! $okul) {
            return [];
        }

        $weekday = $tarih->dayOfWeekIso;

        // Takas ayni gune ait olmali: dersin sablondaki gunu ile
        // takasin tarihi ortusmuyorsa ortada tutarsiz bir istek var.
        if ($izinliDers->weekday !== $weekday) {
            return [];
        }

        $slotSaatleri = $okul->slotSaatleri($weekday);

        // Ayni sube, ayni gun, farkli slot.
        $adaylar = ScheduleEntry::with(['course', 'teacher'])
            ->where('section_id', $izinliDers->section_id)
            ->where('weekday', $weekday)
            ->where('id', '!=', $izinliDers->id)
            ->get();

        if ($adaylar->isEmpty()) {
            return [];
        }

        // O gun zaten takasa girmis dersler - bir ders ayni gunde
        // yalnizca bir takasin parcasi olabilir.
        $mesgulKayitIdleri = $this->gununTakaslari($tarih)
            ->flatMap(fn ($t) => [$t->entry_a_id, $t->entry_b_id])
            ->unique()
            ->all();

        // Ilgili butun ogretmenlerin o gunku izinleri - tek sorgu.
        $ogretmenIdleri = $adaylar->pluck('teacher_id')
            ->push($izinliDers->teacher_id)
            ->filter()
            ->unique()
            ->all();

        $izinler = $this->izinServisi->onayliIzinler(
            $ogretmenIdleri,
            $tarih->copy()->startOfDay(),
            $tarih->copy()->endOfDay()
        );

        // Cakisma kontrolu icin: bu iki ogretmenin o gunku TUM dersleri
        // (baska subelerdekiler dahil). Takastan sonra ogretmen baska
        // bir subede ayni saatte derse girmis olmamali.
        $ilgiliDersler = ScheduleEntry::whereIn('teacher_id', $ogretmenIdleri)
            ->where('weekday', $weekday)
            ->get();

        $sonuc = [];

        foreach ($adaylar as $aday) {
            if (in_array($izinliDers->id, $mesgulKayitIdleri, true)) {
                // Izinli dersin kendisi zaten takasta - hic aday yok.
                return [];
            }

            $engel = $this->engelBul(
                $izinliDers,
                $aday,
                $tarih,
                $slotSaatleri,
                $izinler,
                $ilgiliDersler,
                $mesgulKayitIdleri
            );

            $saat = $slotSaatleri[$aday->slot_no] ?? null;

            $sonuc[] = [
                'entryId' => $aday->id,
                'slot' => $aday->slot_no,
                'baslangic' => $saat['baslangic'] ?? '?',
                'bitis' => $saat['bitis'] ?? '?',
                'ders' => $aday->course->name ?? '?',
                'ogretmen' => $aday->teacher
                    ? $aday->teacher->first_name . ' ' . $aday->teacher->last_name
                    : '?',
                'uygun' => $engel === null,
                // Uygun olmayan adayi listeden gizlemek yerine SEBEBIYLE
                // gosteriyoruz: yonetici "neden bununla takas edemiyorum"
                // sorusunun cevabini gormeli, aksi halde eksik listeyi
                // hata saniyor.
                'sebep' => $engel,
            ];
        }

        usort($sonuc, fn ($a, $b) => [! $a['uygun'], $a['slot']] <=> [! $b['uygun'], $b['slot']]);

        return $sonuc;
    }

    /**
     * Iki ders takaslanabilir mi? Engel varsa sebebini, yoksa null doner.
     *
     * Takas sonrasi durum: A dersi B'nin slotuna, B dersi A'nin slotuna
     * gecer. Dolayisiyla her iki ogretmen icin de YENI slotta musaitlik
     * kontrol edilmeli.
     */
    private function engelBul(
        ScheduleEntry $a,
        ScheduleEntry $b,
        Carbon $tarih,
        array $slotSaatleri,
        Collection $izinler,
        Collection $ilgiliDersler,
        array $mesgulKayitIdleri
    ): ?string {
        if (in_array($b->id, $mesgulKayitIdleri, true)) {
            return 'Bu ders o gün zaten başka bir takasa dahil.';
        }

        $aSaat = $slotSaatleri[$a->slot_no] ?? null;
        $bSaat = $slotSaatleri[$b->slot_no] ?? null;

        if (! $aSaat || ! $bSaat) {
            return 'Ders saati hesaplanamadı.';
        }

        $aBaslangic = $tarih->copy()->setTimeFromTimeString($aSaat['baslangic']);
        $aBitis = $tarih->copy()->setTimeFromTimeString($aSaat['bitis']);
        $bBaslangic = $tarih->copy()->setTimeFromTimeString($bSaat['baslangic']);
        $bBitis = $tarih->copy()->setTimeFromTimeString($bSaat['bitis']);

        // 1) Izinli ogretmen, tasinacagi yeni saatte de izinliyse takas
        //    hicbir sey cozmez.
        if ($a->teacher_id && $this->izinServisi->cakisanIzin($izinler, $a->teacher_id, $bBaslangic, $bBitis)) {
            return 'İzinli öğretmen bu saatte de izinli.';
        }

        // 2) Karsi tarafin ogretmeni, tasinacagi saatte izinliyse yeni
        //    bir bosluk yaratmis oluruz.
        if ($b->teacher_id && $this->izinServisi->cakisanIzin($izinler, $b->teacher_id, $aBaslangic, $aBitis)) {
            return 'Karşı dersin öğretmeni o saatte izinli.';
        }

        // 3) Izinli ogretmenin yeni slotta BASKA bir subede dersi var mi?
        if ($a->teacher_id && $this->baskaDersVar($ilgiliDersler, $a->teacher_id, $b->slot_no, [$a->id, $b->id])) {
            return 'İzinli öğretmenin o saatte başka şubede dersi var.';
        }

        // 4) Karsi ogretmenin yeni slotta baska bir subede dersi var mi?
        if ($b->teacher_id && $this->baskaDersVar($ilgiliDersler, $b->teacher_id, $a->slot_no, [$a->id, $b->id])) {
            return 'Karşı dersin öğretmeninin o saatte başka şubede dersi var.';
        }

        // Derslik kontrolu YOK - takas ayni sube icinde oldugu icin
        // derslik degismiyor (sinif docblock'una bakin).

        return null;
    }

    private function baskaDersVar(Collection $dersler, int $ogretmenId, int $slotNo, array $harictutulanIdler): bool
    {
        return $dersler->contains(
            fn ($d) => $d->teacher_id === $ogretmenId
                && $d->slot_no === $slotNo
                && ! in_array($d->id, $harictutulanIdler, true)
        );
    }

    /**
     * Takasi olusturur.
     *
     * Transaction icinde: kontrolu yaptiktan sonra kayit atilana kadar
     * baska bir yoneticinin ayni dersi takasa sokmasi ihtimalini
     * kapatiyor.
     */
    public function takasYap(
        LeaveRequest $izin,
        ScheduleEntry $izinliDers,
        ScheduleEntry $karsiDers,
        Carbon $tarih,
        ?int $kullaniciId
    ): LessonSwap {
        return DB::transaction(function () use ($izin, $izinliDers, $karsiDers, $tarih, $kullaniciId) {
            $adaylar = $this->adaylar($izin, $izinliDers, $tarih);

            $secilen = collect($adaylar)->firstWhere('entryId', $karsiDers->id);

            if (! $secilen) {
                throw new RuntimeException('Bu ders takas adayı değil.');
            }

            if (! $secilen['uygun']) {
                throw new RuntimeException($secilen['sebep'] ?? 'Bu takas yapılamaz.');
            }

            return LessonSwap::create([
                'leave_request_id' => $izin->id,
                'swap_date' => $tarih->toDateString(),
                'entry_a_id' => $izinliDers->id,
                'entry_b_id' => $karsiDers->id,
                'created_by' => $kullaniciId,
            ]);
        });
    }

    public function geriAl(LessonSwap $takas): void
    {
        // Takas kaydini silmek yeterli: sablona hic dokunmadigimiz icin
        // program kendiliginden eski haline donuyor.
        $takas->delete();
    }

    /**
     * Belirli bir gundeki takaslar.
     *
     * whereHas ile okul kapsami: lesson_swaps'ta school_id yok, global
     * scope da yok. Kapsam, sube uzerinden (SubeOkulScope) uygulaniyor -
     * aksi halde coklu okulda baska okulun takasi da gelirdi.
     */
    public function gununTakaslari(Carbon $tarih): Collection
    {
        return LessonSwap::with(['entryA', 'entryB'])
            ->whereHas('entryA.section')
            ->whereDate('swap_date', $tarih->toDateString())
            ->get();
    }

    /**
     * Bir haftadaki takaslar - cizelge gosterimi icin.
     */
    public function haftaninTakaslari(Carbon $haftaBasi): Collection
    {
        $haftaBasi = $haftaBasi->copy()->startOfDay();

        return LessonSwap::with(['entryA', 'entryB'])
            ->whereHas('entryA.section')
            ->whereBetween('swap_date', [
                $haftaBasi->toDateString(),
                $haftaBasi->copy()->addDays(6)->toDateString(),
            ])
            ->get();
    }

    /**
     * Bir izne bagli takaslar - onay ekraninda listelemek icin.
     */
    public function izninTakaslari(LeaveRequest $izin): Collection
    {
        return LessonSwap::with(['entryA.course', 'entryB.course', 'entryB.teacher', 'entryA.section'])
            ->where('leave_request_id', $izin->id)
            ->orderBy('swap_date')
            ->get();
    }
}
