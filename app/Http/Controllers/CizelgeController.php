<?php

namespace App\Http\Controllers;

use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Teacher;
use App\Services\CizelgeServisi;
use Illuminate\Support\Collection;

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
}
