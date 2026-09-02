<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Teacher;

/**
 * Detay ekranlarindaki "onceki / sonraki kayit" cubugunu doldurmak
 * icin sade listeler: sadece id + etiket, baska hicbir alan yok.
 *
 * Neden ayri bir uc nokta?
 * - /api/cizelge ayni iki listeyi zaten donuyor, ama yaninda cakisma
 *   kontrolu (uc adet gruplu sorgu) da calistiriyor. Sadece gezinme
 *   cubugunu doldurmak icin o bedeli odemek gereksiz.
 * - /api/ogretmenler ise SAYFALANMIS (20'ser). Gezinme icin butun
 *   liste lazim, yoksa 21. ogretmene "sonraki" ile gecilemez.
 *
 * Siralamalar, kullanicinin listelerde gordugu sirayla AYNI - yoksa
 * "sonraki" beklenmedik bir kayda giderdi.
 */
class GezinmeController extends Controller
{
    public function index(): array
    {
        return [
            'subeler' => $this->subeler(),
            'ogretmenler' => $this->ogretmenler(),
        ];
    }

    /**
     * 9A, 9B, 10A sirasinda - Api\CizelgeController ve web
     * tarafindaki CizelgeController ile ayni sorgu.
     */
    private function subeler(): array
    {
        return Section::query()
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->orderBy('levels.grade_number')
            ->orderBy('sections.name')
            ->select('sections.id', 'sections.name')
            ->get()
            ->map(fn ($sube) => ['id' => $sube->id, 'ad' => $sube->name])
            ->all();
    }

    /**
     * Sadece AKTIF ogretmenler - cizelge ana ekranindaki listeyle
     * ayni. Pasif bir ogretmenin cizelgesine adresten girilirse
     * gezinme cubugu onu listede bulamaz; bileşen bu durumu
     * (sira = -1) zaten sessizce karsiliyor.
     */
    private function ogretmenler(): array
    {
        return Teacher::where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($ogretmen) => ['id' => $ogretmen->id, 'ad' => $ogretmen->full_name])
            ->all();
    }
}
