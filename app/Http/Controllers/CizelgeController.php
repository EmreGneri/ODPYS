<?php

namespace App\Http\Controllers;

use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Teacher;
use App\Services\CizelgeServisi;

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
            'programSayisi' => ScheduleEntry::count(),

            // join + select: subeler 9A, 9B, 10A sirasinda cikmali.
            // select olmasaydi levels.id sections.id'yi ezerdi ve
            // baglantilar yanlis subeye giderdi.
            'subeler' => Section::with('level')
                ->join('levels', 'sections.level_id', '=', 'levels.id')
                ->orderBy('levels.grade_number')
                ->orderBy('sections.name')
                ->select('sections.*')
                ->get(),

            'ogretmenler' => Teacher::where('is_active', true)
                ->orderBy('first_name')->orderBy('last_name')->get(),

            'cakismalar' => $servis->cakismaKontrolu(),
        ]);
    }

    public function sube(Section $sube, CizelgeServisi $servis)
    {
        return view('cizelge.izgara', $servis->subeCizelgesi($sube) + [
            'geriUrl' => route('cizelge.index'),
            'tur' => 'sube',
        ]);
    }

    public function ogretmen(Teacher $ogretmen, CizelgeServisi $servis)
    {
        return view('cizelge.izgara', $servis->ogretmenCizelgesi($ogretmen) + [
            'geriUrl' => route('cizelge.index'),
            'tur' => 'ogretmen',
        ]);
    }

    public function yuk(CizelgeServisi $servis)
    {
        return view('cizelge.yuk', $servis->yukDagilimi());
    }
}
