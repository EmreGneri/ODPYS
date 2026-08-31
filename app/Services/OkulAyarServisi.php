<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\DB;

/**
 * Okul ayarlarını günceller.
 *
 * Neden servis: tek bir kullanıcı işlemi İKİ tabloya birden yazıyor
 * (schools + school_days) ve ikisi birlikte anlam ifade ediyor.
 * Haftalık kapasite = lessons_per_day x aktif gün sayısı; yarım kalan
 * bir güncelleme kapasiteyi tutarsız bırakır ve kapasite üç yerde
 * kullanılıyor (ders saati doğrulaması, öğretmen üst sınırı, sihirbaz).
 */
class OkulAyarServisi
{
    public function guncelle(School $okul, array $veri): void
    {
        DB::transaction(function () use ($okul, $veri) {
            $okul->update([
                'name' => $veri['name'],
                'city' => $veri['city'],
                'district' => $veri['district'],
                'lessons_per_day' => $veri['lessons_per_day'],
                'lesson_duration' => $veri['lesson_duration'],
                'break_duration' => $veri['break_duration'],
            ]);

            foreach ($veri['days'] as $gunNo => $gunVerisi) {
                $okul->days()->updateOrCreate(
                    ['weekday' => $gunNo],
                    [
                        'start_time' => $gunVerisi['start_time'],
                        // isset degil ! empty: gizli alan "0" gonderirse
                        // isset her zaman true doner, gun hep aktif kalirdi.
                        'is_active' => ! empty($gunVerisi['is_active']),
                    ]
                );
            }
        });

        // Gunler degistigi icin bellekteki iliski bayat kaldi;
        // cagiran taraf kapasiteyi hemen sorarsa eski sayiyi almasin.
        $okul->unsetRelation('days');
    }
}
