<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\School;
use App\Models\ScheduleEntry;
use App\Models\Teacher;
use Carbon\Carbon;

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

                    // Cakisma formulu: A.baslangic < B.bitis VE B.baslangic < A.bitis
                    // < kullaniyorum, <= degil: ders 08:40'ta bitip izin
                    // 08:40'ta basliyorsa sinir temasi var, cakisma yok.
                    if ($dersBaslangic->lt($izin->ends_at) && $izin->starts_at->lt($dersBitis)) {
                        $etkilenen[] = [
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

        // Bu haftayla KESISEN onayli izinler. Izin haftadan tasabilir
        // (Persembe baslayip haftaya Pazartesi bitebilir), o yuzden
        // sinir kontrolu starts_at/ends_at'in ikisine de bakiyor.
        $izinler = LeaveRequest::where('teacher_id', $ogretmen->id)
            ->where('status', 'approved')
            ->where('starts_at', '<=', $haftaSonu)
            ->where('ends_at', '>=', $haftaBasi)
            ->get();

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

                $dersBaslangic = $gun->copy()->setTimeFromTimeString($saat['baslangic']);
                $dersBitis = $gun->copy()->setTimeFromTimeString($saat['bitis']);

                foreach ($izinler as $izin) {
                    if ($dersBaslangic->lt($izin->ends_at) && $izin->starts_at->lt($dersBitis)) {
                        $izinliHucreler[$weekday][$ders->slot_no] = [
                            'tarih' => $gun->format('d.m.Y'),
                            'sebep' => $izin->reason,
                        ];

                        break;
                    }
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
