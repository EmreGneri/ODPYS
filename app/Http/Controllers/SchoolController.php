<?php

namespace App\Http\Controllers;

use App\Http\Requests\OkulOlusturRequest;
use App\Http\Requests\SchoolRequest;
use App\Models\Level;
use App\Models\School;
use App\Models\Teacher;
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
        $okul = School::aktif()?->load('days');

        if (! $okul) {
            return redirect()
                ->route('okullar.create')
                ->with('error', 'Henüz hiç okul tanımlanmamış. Önce bir okul ekleyin.');
        }

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
        $okul = School::aktif()?->load('days');

        if (! $okul) {
            return redirect()
                ->route('okullar.create')
                ->with('error', 'Henüz hiç okul tanımlanmamış. Önce bir okul ekleyin.');
        }

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
        $servis->guncelle(School::aktif(), $request->validated());

        return redirect()
            ->route('okul.index')
            ->with('success', 'Okul ayarları güncellendi.');
    }

    // ───── ÇOKLU OKUL — Sartname 2.1.7 ──────────────────────────

    /**
     * Tum okullarin listesi + hangisinin aktif oldugu + okul degistirme.
     *
     * withCount('levels') KULLANMIYORUM: Level modelinde global scope
     * var, withCount'un urettigi correlated subquery'ye scope'un
     * "AND school_id = <aktif>" kosulu da eklenir. Sonuc: sadece aktif
     * okulun sayisi dogru cikar, digerleri hep 0 gorunur. Bunun yerine
     * withoutGlobalScopes() ile scope'u BILEREK atlayip her okul icin
     * kendi id'sine gore ayri sayiyorum.
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

        return view('schools.okullar', [
            'okullar' => $okullar,
            'aktifId' => School::aktif()?->id,
        ]);
    }

    public function okulEkleForm()
    {
        return view('schools.ekle');
    }

    /**
     * Yeni okul olusturur ve OTOMATIK aktif okul yapar - yonetici
     * ekledigi okulu hemen yapilandirmaya devam edebilsin diye.
     */
    public function okulEkle(OkulOlusturRequest $request)
    {
        $okul = School::create($request->validated());

        session(['aktif_okul_id' => $okul->id]);

        return redirect()
            ->route('okul.edit')
            ->with('success', "\"{$okul->name}\" eklendi ve aktif okul olarak seçildi. Şimdi eğitim günlerini tanımlayın.");
    }

    /**
     * Aktif okulu degistirir. Sonraki istekten itibaren OkulScope/
     * SubeOkulScope butun sorgulari bu okula filtreler.
     */
    public function aktifYap(School $okul)
    {
        session(['aktif_okul_id' => $okul->id]);

        return redirect()
            ->route('okullar.index')
            ->with('success', "\"{$okul->name}\" aktif okul olarak seçildi.");
    }
}
