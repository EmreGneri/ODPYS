<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolRequest;
use App\Models\School;
use App\Services\OkulAyarServisi;

class SchoolController extends Controller
{
    /**
     * Haftanin gunleri. Sabit veri oldugu icin tek yerde tutuluyor.
     */
    private const GUN_ADLARI = [
        1 => 'Pazartesi',
        2 => 'Salı',
        3 => 'Çarşamba',
        4 => 'Perşembe',
        5 => 'Cuma',
        6 => 'Cumartesi',
        7 => 'Pazar',
    ];

    public function index()
    {
        $okul = School::with('days')->first();

        $aktifGunler = [];
        $dersSaatleri = [];

        foreach (self::GUN_ADLARI as $no => $ad) {
            $gun = $okul->days->firstWhere('weekday', $no);

            if ($gun && $gun->is_active) {
                $aktifGunler[$no] = $ad;
                $dersSaatleri[$no] = $okul->slotSaatleri($no);
            }
        }

        return view('schools.index', [
            'okul' => $okul,
            'gunAdlari' => self::GUN_ADLARI,
            'aktifGunler' => $aktifGunler,
            'dersSaatleri' => $dersSaatleri,
            'haftalikKapasite' => $okul->haftalikKapasite(),
        ]);
    }

    public function edit()
    {
        $okul = School::with('days')->first();

        $gunVerileri = [];

        foreach (self::GUN_ADLARI as $no => $ad) {
            $gun = $okul->days->firstWhere('weekday', $no);

            $gunVerileri[$no] = [
                'ad' => $ad,
                'aktif' => $gun?->is_active ?? false,
                'saat' => $gun ? substr($gun->start_time, 0, 5) : '08:00',
            ];
        }

        return view('schools.edit', [
            'okul' => $okul,
            'gunVerileri' => $gunVerileri,
        ]);
    }

    public function update(SchoolRequest $request, OkulAyarServisi $servis)
    {
        $servis->guncelle(School::first(), $request->validated());

        return redirect()
            ->route('okul.index')
            ->with('success', 'Okul ayarları güncellendi.');
    }
}
