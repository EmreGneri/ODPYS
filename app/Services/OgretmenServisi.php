<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Level;
use App\Models\Teacher;
use App\Models\TeacherCourseLevel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ogretmenle ilgili, birden fazla tabloya dokunan islemler.
 *
 * Neden servis: iki islem de iki tabloya birden yaziyor ve HTTP ile
 * ilgisi yok. Controller'da kalsalardi metotlar 30 satiri asardi ve
 * islemi HTTP olmadan calistirmak (test, artisan komutu) imkansiz olurdu.
 */
class OgretmenServisi
{
    /**
     * Ogretmene giris hesabi acar. Sartname 2.8
     *
     * users + teachers tablolarina birden yaziyor: kullanici olusuyor
     * ve teachers.user_id ona baglaniyor. Yarim kalirsa ortada sahipsiz
     * bir hesap kalir ve ikinci denemede "e-posta zaten kayitli" hatasi
     * alinir — o yuzden islem icinde.
     *
     * @return array{basarili: bool, mesaj: string, parola: ?string}
     */
    public function hesapAc(Teacher $ogretmen): array
    {
        if ($ogretmen->user_id) {
            return [
                'basarili' => false,
                'mesaj' => $ogretmen->full_name . ' için zaten bir giriş hesabı var.',
                'parola' => null,
            ];
        }

        // users.email benzersiz. Veritabani hatasi yerine anlasilir mesaj.
        if (User::where('email', $ogretmen->email)->exists()) {
            return [
                'basarili' => false,
                'mesaj' => $ogretmen->email . ' adresi başka bir kullanıcıya ait. Önce öğretmenin e-postasını değiştirin.',
                'parola' => null,
            ];
        }

        // Yonetici parola belirlemiyor: 31 kisiye parola yazan yonetici
        // hepsine ayni zayif parolayi verir ve hepsini bilir.
        $gecici = Str::random(10);

        DB::transaction(function () use ($ogretmen, $gecici) {
            $kullanici = User::create([
                'name' => $ogretmen->full_name,
                'email' => $ogretmen->email,
                // User modelindeki 'password' => 'hashed' cast'i bunu
                // otomatik bcrypt'liyor; Hash::make() gereksiz olurdu.
                'password' => $gecici,
                'role' => 'teacher',
            ]);

            $ogretmen->update(['user_id' => $kullanici->id]);
        });

        return [
            'basarili' => true,
            'mesaj' => $ogretmen->full_name . ' için giriş hesabı oluşturuldu.',
            'parola' => $gecici,
        ];
    }

    /**
     * Ogretmenin ders-seviye yetkilerini kaydeder. Sartname 2.4
     *
     * Sil-ve-yeniden-yaz: hangi kutunun acildigini kapandigini tek tek
     * karsilastirmaktan cok daha basit ve hatasiz. Islem icinde oldugu
     * icin silme basarili olup ekleme patlarsa yetkiler kaybolmuyor.
     *
     * @param  array  $yetkiler  yetkiler[dersId][seviyeId] = "1"
     * @return int    Kaydedilen yetki sayisi
     */
    public function yetkileriKaydet(Teacher $ogretmen, array $yetkiler): int
    {
        // Gecerli id kumeleri — kullanici formu kurcalarsa uydurma
        // id'ler yazilmasin diye. flip() ile isset() kontrolu O(1);
        // in_array olsaydi her kontrolde dizi bastan taranirdi.
        $gecerliDersler = Course::pluck('id')->flip();
        $gecerliSeviyeler = Level::pluck('id')->flip();

        $satirlar = [];

        foreach ($yetkiler as $dersId => $seviyeler) {
            if (! isset($gecerliDersler[$dersId]) || ! is_array($seviyeler)) {
                continue;
            }

            foreach (array_keys($seviyeler) as $seviyeId) {
                if (! isset($gecerliSeviyeler[$seviyeId])) {
                    continue;
                }

                $satirlar[] = [
                    'teacher_id' => $ogretmen->id,
                    'course_id' => (int) $dersId,
                    'level_id' => (int) $seviyeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::transaction(function () use ($ogretmen, $satirlar) {
            TeacherCourseLevel::where('teacher_id', $ogretmen->id)->delete();

            foreach (array_chunk($satirlar, 500) as $parca) {
                TeacherCourseLevel::insert($parca);
            }
        });

        return count($satirlar);
    }
}
