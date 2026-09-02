<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;

class SchoolController extends Controller
{
    /**
     * Aktif okulu degistirir - web tarafindaki SchoolController@aktifYap
     * ile birebir ayni mantik, sadece JSON donuyor.
     */
    public function aktifYap(School $okul)
    {
        session(['aktif_okul_id' => $okul->id]);

        return ['mesaj' => "\"{$okul->name}\" aktif okul olarak seçildi."];
    }
}
