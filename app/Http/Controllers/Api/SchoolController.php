<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OkulOlusturRequest;
use App\Http\Requests\SchoolRequest;
use App\Models\Level;
use App\Models\School;
use App\Models\Teacher;
use App\Services\OkulAyarServisi;

class SchoolController extends Controller
{
    private const GUN_ADLARI = [
        1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe',
        5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar',
    ];

    /**
     * Aktif okulun ayarlari + hesaplanan ders saatleri cizelgesi
     * (sartname 2.1.8-9). Web tarafindaki SchoolController@index ile
     * ayni veri, JSON olarak.
     */
    public function index()
    {
        $okul = School::aktif()?->load('days');

        if (! $okul) {
            return response()->json(['message' => 'Henüz hiç okul tanımlanmamış.'], 404);
        }

        $gunler = [];

        foreach (self::GUN_ADLARI as $no => $ad) {
            $gun = $okul->days->firstWhere('weekday', $no);

            $gunler[$no] = [
                'ad' => $ad,
                'aktif' => (bool) ($gun?->is_active ?? false),
                'saat' => $gun ? substr($gun->start_time, 0, 5) : '08:00',
                'dersSaatleri' => ($gun && $gun->is_active) ? $okul->slotSaatleri($no) : [],
            ];
        }

        return [
            'okul' => $okul,
            'gunler' => $gunler,
            'haftalikKapasite' => $okul->haftalikKapasite(),
        ];
    }

    public function update(SchoolRequest $request, OkulAyarServisi $servis)
    {
        $servis->guncelle(School::aktif(), $request->validated());

        return ['mesaj' => 'Okul ayarları güncellendi.'];
    }

    /**
     * Tum okullar + her birinin seviye/ogretmen sayisi.
     *
     * withCount KULLANMIYORUM: Level/Teacher modellerinde OkulScope var,
     * withCount'un urettigi alt sorguya scope'un kosulu da eklenir ve
     * aktif okul disindaki her okul 0 gorunur. withoutGlobalScopes ile
     * her okul icin ayri sayiyorum.
     */
    public function okullar()
    {
        $okullar = School::orderBy('name')->get()->map(function ($okul) {
            $okul->seviye_sayisi = Level::withoutGlobalScopes()
                ->where('school_id', $okul->id)->count();
            $okul->ogretmen_sayisi = Teacher::withoutGlobalScopes()
                ->where('school_id', $okul->id)->count();

            return $okul;
        });

        return [
            'okullar' => $okullar,
            'aktifId' => School::aktif()?->id,
        ];
    }

    public function okulEkle(OkulOlusturRequest $request)
    {
        $okul = School::create($request->validated());

        // Yeni okul hemen aktif yapiliyor - yonetici ekledigi okulu
        // dogrudan yapilandirmaya devam edebilsin diye.
        session(['aktif_okul_id' => $okul->id]);

        return response()->json($okul, 201);
    }

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
