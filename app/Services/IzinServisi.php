<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\School;
use App\Models\ScheduleEntry;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Ogretmen izin talebini uretilmis program SABLONUYLA eslestirir.
 *
 * Sartname 2.7.3: "izni onaylanirsa ders programinda SADECE O DERS
 * icin degisiklik yapilmalidir." Bunu su sekilde yorumluyorum:
 * schedule_entries HAFTALIK BIR SABLON - tarihsiz, "her Cuma 1. ders"
 * gibi. Izin ise TARIHLI bir olay. Izin onaylandiginda schedule_entries
 * tablosuna HIC dokunmuyorum. Sadece leave_requests.status degisiyor.
 *
 * Degisen sey GORUNUM: bu servis sablonu belirli bir tarihe/haftaya
 * baglayip, onayli izinle cakisan hucreleri isaretliyor. Boylece:
 *   - izin iptal edilirse program kendiliginden geri geliyor
 *   - sadece o tarihteki o ders saati etkileniyor, sonraki haftalar degil
 *   - "aslinda bu dersi kim veriyordu" bilgisi hic kaybolmuyor
 *
 * Ayni fikir ders takasinda da surüyor (bkz. TakasServisi).
 *
 * Bu sinif HTTP bilmiyor, dizi donduruyor.
 */
class IzinServisi
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

    // ─────────────────────────────────────────────────────────────
    // Ortak cakisma mantigi
    //
    // Cakisma formulu eskiden bu dosyada iki ayri yerde tekrar
    // ediyordu; takas ozelligiyle birlikte ucuncu bir kullanan daha
    // cikinca tek yere toplandi. Formulun tek bir dogru tanimi olmali:
    // birinde < obruinde <= kullanilsa iki ekran farkli sonuc verirdi.
    // ─────────────────────────────────────────────────────────────

    /**
     * Verilen ogretmenlerin, belirtilen zaman araligiyla KESISEN
     * onayli izinleri. Tek sorgu - cagiran taraf sonra bellekte
     * kontrol ediyor, hucre basina sorgu atmiyor.
     */
    public function onayliIzinler(array $ogretmenIdleri, Carbon $baslangic, Carbon $bitis): Collection
    {
        if (empty($ogretmenIdleri)) {
            return collect();
        }

        // Izin araliktan tasabilir (once baslayip sonra bitebilir),
        // o yuzden kesisim kontrolu iki sinira da bakiyor.
        return LeaveRequest::whereIn('teacher_id', $ogretmenIdleri)
            ->where('status', 'approved')
            ->where('starts_at', '<=', $bitis)
            ->where('ends_at', '>=', $baslangic)
            ->get();
    }

    /**
     * Belirli bir ders saati, elimizdeki izinlerden birine denk
     * geliyor mu? Geliyorsa o izni doner.
     *
     * Cakisma formulu: A.baslangic < B.bitis VE B.baslangic < A.bitis
     * < kullaniyorum, <= degil: ders 08:40'ta bitip izin 08:40'ta
     * basliyorsa sinir temasi var, cakisma yok.
     */
    public function cakisanIzin(
        Collection $izinler,
        int $ogretmenId,
        Carbon $dersBaslangic,
        Carbon $dersBitis
    ): ?LeaveRequest {
        foreach ($izinler as $izin) {
            if ($izin->teacher_id !== $ogretmenId) {
                continue;
            }

            if ($dersBaslangic->lt($izin->ends_at) && $izin->starts_at->lt($dersBitis)) {
                return $izin;
            }
        }

        return null;
    }

    /**
     * Tek bir ogretmen/zaman icin izin kontrolu - kendi sorgusunu
     * atiyor. Takas adayi degerlendirirken kullaniliyor (orada
     * kontrol edilecek hucre sayisi az, toplu yuklemeye deger).
     */
    public function musaitMi(int $ogretmenId, Carbon $dersBaslangic, Carbon $dersBitis): bool
    {
        $izinler = $this->onayliIzinler([$ogretmenId], $dersBaslangic, $dersBitis);

        return $this->cakisanIzin($izinler, $ogretmenId, $dersBaslangic, $dersBitis) === null;
    }

    // ─────────────────────────────────────────────────────────────

    /**
     * Bir izin talebinin hangi ders saatlerine denk geldigini bulur.
     * Sartname 2.7.2: onay ekrani "dersi var/yok gosterimi ile" olmali.
     *
     * Izin birden fazla gunu kapsayabilir (starts_at ve ends_at farkli
     * tarihlerde olabilir), o yuzden aralikta gun gun geziyorum.
     */
    public function etkilenenDersler(LeaveRequest $izin): array
    {
        $okul = School::aktif()?->load('days');

        if (! $okul) {
            return [];
        }

        // Bu ogretmenin butun haftalik seviyesi - tek sorgu, gun gun
        // tekrar sorgu atmiyorum. 35 satirdan fazla olmayacagi icin
        // bellekte tutmak ucuz.
        $tumDersler = ScheduleEntry::with(['section', 'course'])
            ->where('teacher_id', $izin->teacher_id)
            ->get();

        if ($tumDersler->isEmpty()) {
            return [];
        }

        $izinler = collect([$izin]);
        $etkilenen = [];
        $gun = $izin->starts_at->copy()->startOfDay();
        $sonGun = $izin->ends_at->copy()->startOfDay();

        // Guvenlik siniri: kullanici formu kurcalayip ends_at'i yillar
        // sonrasina tasirsa dongu sonsuza kadar surmesin.
        $azamiGun = 366;

        while ($gun->lte($sonGun) && $azamiGun-- > 0) {
            // Carbon'da Pazartesi=1 ... Pazar=7 - sema ile birebir ortusuyor.
            $weekday = $gun->dayOfWeekIso;

            $oGunkuDersler = $tumDersler->where('weekday', $weekday);

            if ($oGunkuDersler->isNotEmpty()) {
                $slotSaatleri = $okul->slotSaatleri($weekday);

                foreach ($oGunkuDersler as $ders) {
                    $saat = $slotSaatleri[$ders->slot_no] ?? null;

                    if (! $saat) {
                        continue;
                    }

                    // Slot saatleri sadece "H:i" metni; bu gunun tarihiyle
                    // birlestirip GERCEK bir zaman noktasi kuruyorum.
                    $dersBaslangic = $gun->copy()->setTimeFromTimeString($saat['baslangic']);
                    $dersBitis = $gun->copy()->setTimeFromTimeString($saat['bitis']);

                    if ($this->cakisanIzin($izinler, $izin->teacher_id, $dersBaslangic, $dersBitis)) {
                        $etkilenen[] = [
                            // Takas ekraninin ihtiyaci: hangi ders kaydi,
                            // hangi tarih. ISO tarih makine icin, format
                            // edilmis olan ekranda gosterilmek icin.
                            'entryId' => $ders->id,
                            'tarihIso' => $gun->toDateString(),
                            'tarih' => $gun->format('d.m.Y'),
                            'gunAdi' => self::GUN_ADLARI[$weekday] ?? '?',
                            'slot' => $ders->slot_no,
                            'baslangic' => $saat['baslangic'],
                            'bitis' => $saat['bitis'],
                            'sube' => $ders->section->name ?? '?',
                            'ders' => $ders->course->name ?? '?',
                        ];
                    }
                }
            }

            $gun->addDay();
        }

        // Tarih, sonra slot sirasinda - kullanici kronolojik okusun.
        usort($etkilenen, fn ($a, $b) => [$a['tarih'], $a['slot']] <=> [$b['tarih'], $b['slot']]);

        return $etkilenen;
    }

    /**
     * Belirli bir haftada, bir ogretmenin hangi hucrelerinin izinli
     * gorunmesi gerektigini bulur. Sartname 2.7.3 - cizelge gosterimi.
     *
     * NOT: CizelgeServisi artik bunu kullanmiyor - takas ozelligiyle
     * birlikte izin isaretlemesi "sablondaki yerine" degil "dersin
     * GERCEKTE durdugu yere" gore yapilmak zorunda kaldi (takas dersi
     * baska bir slota tasiyor). Bu metot, ogretmen paneli gibi takas
     * uygulamayan basit gorunumler icin duruyor.
     *
     * @param  Carbon  $haftaBasi  O haftanin Pazartesi'si
     * @return array   [weekday][slot_no] => izin bilgisi
     */
    public function haftaninIzinleri(Teacher $ogretmen, Carbon $haftaBasi): array
    {
        $okul = School::aktif()?->load('days');

        if (! $okul) {
            return [];
        }

        $haftaBasi = $haftaBasi->copy()->startOfDay();
        $haftaSonu = $haftaBasi->copy()->addDays(6)->endOfDay();

        $izinler = $this->onayliIzinler([$ogretmen->id], $haftaBasi, $haftaSonu);

        if ($izinler->isEmpty()) {
            return [];
        }

        $dersler = ScheduleEntry::where('teacher_id', $ogretmen->id)->get();
        $izinliHucreler = [];

        for ($i = 0; $i < 7; $i++) {
            $gun = $haftaBasi->copy()->addDays($i);
            $weekday = $gun->dayOfWeekIso;

            $oGunkuDersler = $dersler->where('weekday', $weekday);

            if ($oGunkuDersler->isEmpty()) {
                continue;
            }

            $slotSaatleri = $okul->slotSaatleri($weekday);

            foreach ($oGunkuDersler as $ders) {
                $saat = $slotSaatleri[$ders->slot_no] ?? null;

                if (! $saat) {
                    continue;
                }

                $izin = $this->cakisanIzin(
                    $izinler,
                    $ogretmen->id,
                    $gun->copy()->setTimeFromTimeString($saat['baslangic']),
                    $gun->copy()->setTimeFromTimeString($saat['bitis'])
                );

                if ($izin) {
                    $izinliHucreler[$weekday][$ders->slot_no] = [
                        'tarih' => $gun->format('d.m.Y'),
                        'sebep' => $izin->reason,
                    ];
                }
            }
        }

        return $izinliHucreler;
    }

    /**
     * Bekleyen izin talebi sayisi. Dashboard'da kullanilabilir.
     */
    public function bekleyenSayisi(): int
    {
        return LeaveRequest::where('status', 'pending')->count();
    }
}
