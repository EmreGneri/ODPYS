<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Teacher;
use App\Services\CizelgeServisi;

/**
 * Cizelge verisini JSON olarak sunar. Sartname 3.3 - API destegi.
 *
 * CizelgeServisi'ye HIC dokunulmadi - kendi docblock'unda zaten
 * "HTTP bilmiyor, dizi donduruyor" diyor, Blade'e bastigi ayni diziyi
 * burada dogrudan JSON'a ceviriyoruz.
 *
 * Onemli fark - coklu okul: web tarafinda "aktif okul" oturumda
 * (session) tutuluyor. API cerez/oturum modunda da ayni session
 * paylasiliyor; yine de sube/ogretmen bazli uc noktalarda aktif okulu
 * istenen kaynagin KENDI okulundan cozuyoruz - boylece token ile
 * gelen (oturumsuz) harici istemcilerde de dogru okula filtreleniyor.
 */
class CizelgeController extends Controller
{
    /**
     * Cizelge ana ekrani: program durumu, gezinme listeleri ve
     * zaman cakismasi uyarilari (sartname 5.3).
     */
    public function index(CizelgeServisi $servis)
    {
        return [
            'programSayisi' => ScheduleEntry::whereHas('section')->count(),
            'subeler' => $this->subeler(),
            'ogretmenler' => $this->ogretmenler(),
            'cakismalar' => $servis->cakismaKontrolu(),
        ];
    }

    public function sube(Section $sube, CizelgeServisi $servis)
    {
        session(['aktif_okul_id' => $sube->level->school_id]);

        return $servis->subeCizelgesi($sube);
    }

    public function ogretmen(Teacher $ogretmen, CizelgeServisi $servis)
    {
        session(['aktif_okul_id' => $ogretmen->school_id]);

        return $servis->ogretmenCizelgesi($ogretmen);
    }

    /**
     * Yuk dagilimi raporu - sartname 5.5.
     */
    public function yuk(CizelgeServisi $servis)
    {
        return $servis->yukDagilimi();
    }

    /**
     * Subeler 9A, 9B, 10A sirasinda - web tarafindaki
     * CizelgeController ile ayni sorgu.
     */
    private function subeler()
    {
        return Section::with('level')
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->orderBy('levels.grade_number')
            ->orderBy('sections.name')
            ->select('sections.*')
            ->get();
    }

    private function ogretmenler()
    {
        return Teacher::where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }
}
