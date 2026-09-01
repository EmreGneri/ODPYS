<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
 * (session) tutuluyor, bir sonraki istekte de hatirlaniyor. API
 * STATELESS - her istek oturumsuz basliyor, session'a guvenilemez.
 * Bu yuzden her metot, aktif okulu istenen kaynagin (sube/ogretmen)
 * KENDI okulundan cozup session'a bu istek suresince yaziyor - servis
 * katmanina hic dokunmadan dogru okula filtrelenmesini sagliyor.
 */
class CizelgeController extends Controller
{
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
}
