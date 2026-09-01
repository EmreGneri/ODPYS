<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\UcretServisi;
use Illuminate\Http\Request;

/**
 * Aylik ucret raporunu JSON olarak sunar. Sartname 3.3 - API destegi.
 *
 * CizelgeController'daki gibi kaynaktan (sube/ogretmen) okul cikaramiyoruz
 * cunku bu uc noktanin URL'sinde belirli bir kayit yok - tum okulun
 * raporu isteniyor. Bu yuzden ?okul_id= sorgu parametresiyle hangi
 * okulun istendigini acikca aliyoruz; verilmezse ilk okul kullanilir.
 */
class UcretController extends Controller
{
    public function index(Request $request, UcretServisi $servis)
    {
        $okulId = $request->query('okul_id') ?? School::orderBy('id')->value('id');

        session(['aktif_okul_id' => $okulId]);

        return $servis->aylikUcretler();
    }
}
