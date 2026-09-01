<?php

namespace App\Http\Controllers;

use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Teacher;
use App\Services\CizelgeServisi;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Uretilmis programin goruntulenmesi. Sartname 5. bolum ciktilari.
 *
 * Controller sadece kimin cizelgesi istendigini belirleyip servise
 * soruyor; veri sekillendirme CizelgeServisi'nde.
 */
class CizelgeController extends Controller
{
    public function index(CizelgeServisi $servis)
    {
        return view('cizelge.index', [
            'programSayisi' => ScheduleEntry::whereHas('section')->count(),
            'subeler' => $this->subeler(),
            'ogretmenler' => $this->ogretmenler(),
            'cakismalar' => $servis->cakismaKontrolu(),
        ]);
    }

    public function sube(Section $sube, CizelgeServisi $servis)
    {
        return view('cizelge.izgara', $servis->subeCizelgesi($sube) + [
            'geriUrl' => route('cizelge.index'),
            'tur' => 'sube',
            'subeId' => $sube->id,
            'gezinme' => $this->gezinmeKur(
                'Şubeler',
                $this->subeler(),
                $sube->id,
                fn ($s) => $s->name,
                fn ($s) => route('cizelge.sube', $s)
            ),
        ]);
    }

    public function ogretmen(Teacher $ogretmen, CizelgeServisi $servis)
    {
        return view('cizelge.izgara', $servis->ogretmenCizelgesi($ogretmen) + [
            'geriUrl' => route('cizelge.index'),
            'tur' => 'ogretmen',
            'ogretmenId' => $ogretmen->id,
            'gezinme' => $this->gezinmeKur(
                'Öğretmenler',
                $this->ogretmenler(),
                $ogretmen->id,
                fn ($o) => $o->first_name . ' ' . $o->last_name,
                fn ($o) => route('cizelge.ogretmen', $o)
            ),
        ]);
    }

    public function yuk(CizelgeServisi $servis)
    {
        return view('cizelge.yuk', $servis->yukDagilimi());
    }

    /**
     * Subeler 9A, 9B, 10A sirasinda.
     *
     * join: levels.grade_number'a gore siralayabilmek icin.
     * select: join sonrasi id ve name sutunlari cakisiyor; olmasaydi
     * levels.id sections.id'yi ezerdi ve baglantilar yanlis subeye giderdi.
     */
    private function subeler(): Collection
    {
        return Section::with('level')
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->orderBy('levels.grade_number')
            ->orderBy('sections.name')
            ->select('sections.*')
            ->get();
    }

    private function ogretmenler(): Collection
    {
        return Teacher::where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Cizelge sayfasindaki gezinme seridi.
     *
     * Sube ve ogretmen icin ayni yapiyi uretiyor; gorunum hangisi
     * oldugunu bilmiyor. Boylece tek bir izgara.blade.php iki ekrani
     * da basiyor ve gezinme kodu iki yerde tekrarlanmiyor.
     *
     * @param  string    $baslik   Serit basligi
     * @param  Collection $liste   Gezilecek kayitlar (sirali)
     * @param  int       $aktifId  Su an gosterilen kaydin id'si
     * @param  callable  $ad       Kayittan gorunen adi ureten islev
     * @param  callable  $url      Kayittan baglantiyi ureten islev
     */
    private function gezinmeKur(
        string $baslik,
        Collection $liste,
        int $aktifId,
        callable $ad,
        callable $url
    ): array {
        $ogeler = $liste->map(fn ($k) => [
            'ad' => $ad($k),
            'url' => $url($k),
            'aktif' => $k->id === $aktifId,
        ])->all();

        // Onceki/sonraki icin aktif kaydin sirasini buluyorum.
        // search() bulamazsa false doner, o yuzden === false kontrolu.
        $sira = $liste->search(fn ($k) => $k->id === $aktifId);

        $onceki = null;
        $sonraki = null;

        if ($sira !== false) {
            $oncekiKayit = $liste->values()->get($sira - 1);
            $sonrakiKayit = $liste->values()->get($sira + 1);

            // $sira 0 ise get(-1) son elemani DONMEZ, null doner — Laravel
            // Collection negatif indeksi desteklemiyor. Yine de acik olsun
            // diye kosulu yaziyorum.
            $onceki = ($sira > 0 && $oncekiKayit) ? [
                'ad' => $ad($oncekiKayit),
                'url' => $url($oncekiKayit),
            ] : null;

            $sonraki = $sonrakiKayit ? [
                'ad' => $ad($sonrakiKayit),
                'url' => $url($sonrakiKayit),
            ] : null;
        }

        return [
            'baslik' => $baslik,
            'ogeler' => $ogeler,
            'onceki' => $onceki,
            'sonraki' => $sonraki,
        ];
    }

    public function subePdf(Section $sube, CizelgeServisi $servis)
    {
        $veri = $servis->subeCizelgesi($sube);

        return Pdf::loadView('cizelge.pdf', $veri)
            ->download(Str::slug($veri['baslik']) . '-cizelge.pdf');
    }

    public function ogretmenPdf(Teacher $ogretmen, CizelgeServisi $servis)
    {
        $veri = $servis->ogretmenCizelgesi($ogretmen);

        return Pdf::loadView('cizelge.pdf', $veri)
            ->download(Str::slug($veri['baslik']) . '-cizelge.pdf');
    }

    public function subeExcel(Section $sube, CizelgeServisi $servis)
    {
        return $this->excelIndir($servis->subeCizelgesi($sube));
    }

    public function ogretmenExcel(Teacher $ogretmen, CizelgeServisi $servis)
    {
        return $this->excelIndir($servis->ogretmenCizelgesi($ogretmen));
    }

    /**
     * Cizelgeyi Excel'de acilan bir dosya olarak indirir. Gercek .xlsx
     * degil - Excel, HTML tablosunu ".xls" uzantisiyla ve dogru
     * Content-Type ile gonderilince kendi formatina ceviriyor. Boylece
     * kutuphane eklemeden (maatwebsite/excel gibi) hem sutun genislikleri
     * hem Turkce karakterler dogru goruntuleniyor - duz CSV'de sutun
     * genisligi bilgisi hic tasinamiyordu, her acilista elle ayarlamak
     * gerekiyordu.
     *
     * pdf.blade.php'yi tekrar kullaniyoruz - zaten temiz, basit bir HTML
     * tablosu, Excel'in eski HTML-okuyucusu boyle bir yapiyi sorunsuz
     * yorumluyor.
     */
    private function excelIndir(array $veri): \Illuminate\Http\Response
    {
        $html = "\xEF\xBB\xBF" . view('cizelge.pdf', $veri)->render();
        $dosyaAdi = Str::slug($veri['baslik']) . '-cizelge.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $dosyaAdi . '"',
        ]);
    }
}
