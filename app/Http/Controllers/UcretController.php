<?php

namespace App\Http\Controllers;

use App\Services\UcretServisi;

/**
 * Aylik ucret raporu. Sartname 2.2.4.
 *
 * CizelgeController ile ayni desen: controller sadece servise soruyor,
 * hesaplama mantigi UcretServisi'nde.
 */
class UcretController extends Controller
{
    public function index(UcretServisi $servis)
    {
        return view('ucret.index', $servis->aylikUcretler());
    }
}
