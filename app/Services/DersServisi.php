<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseLevel;
use App\Models\School;
use Illuminate\Support\Facades\DB;

/**
 * Ders olusturma ve guncelleme.
 *
 * Neden servis: tek bir kullanici islemi IKI tabloya birden yaziyor
 * (courses + course_level). Islem olmadan ders kaydedilip seviye
 * saatleri yazilamazsa mufredat yarim kalir; sihirbaz o dersi hic
 * yerlestiremez ve kimse sebebini anlamaz.
 */
class DersServisi
{
    public function olustur(School $okul, array $veri): Course
    {
        return DB::transaction(function () use ($okul, $veri) {
            $ders = $okul->courses()->create([
                'name' => $veri['name'],
                'hourly_rate' => $veri['hourly_rate'],
            ]);

            $this->seviyeleriYaz($ders, $veri['seviyeler'] ?? []);

            return $ders;
        });
    }

    public function guncelle(Course $ders, array $veri): void
    {
        DB::transaction(function () use ($ders, $veri) {
            $ders->update([
                'name' => $veri['name'],
                'hourly_rate' => $veri['hourly_rate'],
            ]);

            $this->seviyeleriYaz($ders, $veri['seviyeler'] ?? []);
        });
    }

    /**
     * Isaretli seviyelere haftalik saati yazar, isareti kaldirilanlari siler.
     *
     * "Secili ama saat bos" durumu buraya hic gelmiyor: CourseRequest
     * onu dogrulamada reddediyor. Aksi halde kullanici farkinda olmadan
     * mufredat satirini silerdi.
     */
    private function seviyeleriYaz(Course $ders, array $seviyeVerileri): void
    {
        foreach ($seviyeVerileri as $levelId => $veri) {
            // isset degil ! empty: gizli alan "0" gonderirse
            // isset her zaman true doner, seviye hep secili sayilirdi.
            $secili = ! empty($veri['secili']);
            $saat = $veri['weekly_hours'] ?? null;

            if ($secili && $saat) {
                CourseLevel::updateOrCreate(
                    ['course_id' => $ders->id, 'level_id' => $levelId],
                    ['weekly_hours' => $saat]
                );

                continue;
            }

            CourseLevel::where('course_id', $ders->id)
                ->where('level_id', $levelId)
                ->delete();
        }
    }
}
