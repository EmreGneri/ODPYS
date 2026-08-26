<?php

namespace Database\Seeders;

use App\Models\CourseLevel;
use App\Models\School;
use App\Models\TeacherCourseLevel;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Sistem Yöneticisi',
            'email' => 'admin@okul.edu.tr',
            'password' => 'password',
            'role' => 'admin',
        ]);
        // --- Okul ---

        $okul = School::create([
            'name' => 'Ankara Fen Lisesi',
            'city' => 'Ankara',
            'district' => 'Çankaya',
            'lessons_per_day' => 7,
            'lesson_duration' => 40,
            'break_duration' => 10,
        ]);

        // --- Eğitim günleri ve başlangıç saatleri ---

        $gunler = [
            1 => '08:00',
            2 => '09:00',
            3 => '08:00',
            4 => '08:00',
            5 => '08:00',
        ];

        foreach ($gunler as $gun => $saat) {
            $okul->days()->create([
                'weekday' => $gun,
                'start_time' => $saat,
                'is_active' => true,
            ]);
        }

        // --- Seviyeler, şubeler ve derslikler ---

        $subeSayisi = 3;
        $harfler = ['A', 'B', 'C', 'D', 'E'];

        $seviyeler = [];

        foreach ([9, 10, 11, 12] as $no) {
            $seviye = $okul->levels()->create([
                'name' => $no . '. Sınıf',
                'grade_number' => $no,
            ]);

            for ($i = 0; $i < $subeSayisi; $i++) {
                $subeAdi = $no . $harfler[$i];

                $derslik = $okul->rooms()->create([
                    'name' => 'D-' . $subeAdi,
                ]);

                $seviye->sections()->create([
                    'name' => $subeAdi,
                    'room_id' => $derslik->id,
                ]);
            }

            $seviyeler[] = $seviye;
        }

        // --- Dersler, haftalık saatler ve öğretmenler ---

        $dersler = [
            ['ad' => 'Matematik',              'ucret' => 600, 'saat' => 6, 'ogretmen' => 5],
            ['ad' => 'Fizik',                  'ucret' => 560, 'saat' => 4, 'ogretmen' => 3],
            ['ad' => 'Kimya',                  'ucret' => 540, 'saat' => 3, 'ogretmen' => 3],
            ['ad' => 'Biyoloji',               'ucret' => 520, 'saat' => 3, 'ogretmen' => 3],
            ['ad' => 'İngilizce',              'ucret' => 500, 'saat' => 4, 'ogretmen' => 3],
            ['ad' => 'Türk Dili ve Edebiyatı', 'ucret' => 480, 'saat' => 5, 'ogretmen' => 4],
            ['ad' => 'Tarih',                  'ucret' => 420, 'saat' => 2, 'ogretmen' => 2],
            ['ad' => 'Coğrafya',               'ucret' => 400, 'saat' => 2, 'ogretmen' => 2],
            ['ad' => 'Beden Eğitimi',          'ucret' => 320, 'saat' => 2, 'ogretmen' => 3],
            ['ad' => 'Müzik',                  'ucret' => 300, 'saat' => 1, 'ogretmen' => 2],
        ];

        $ogretmenSirasi = 1;

        foreach ($dersler as $d) {
            $ders = $okul->courses()->create([
                'name' => $d['ad'],
                'hourly_rate' => $d['ucret'],
            ]);

            // Bu dersin her seviyedeki haftalık saati
            foreach ($seviyeler as $seviye) {
                CourseLevel::create([
                    'course_id' => $ders->id,
                    'level_id' => $seviye->id,
                    'weekly_hours' => $d['saat'],
                ]);
            }

            // Bu dersi verebilecek öğretmenler
            for ($i = 0; $i < $d['ogretmen']; $i++) {
                $ad = fake('tr_TR')->firstName();
                $soyad = fake('tr_TR')->lastName();

                $ogretmen = $okul->teachers()->create([
                    'first_name' => $ad,
                    'last_name' => $soyad,
                    'national_id' => str_pad($ogretmenSirasi, 11, '0', STR_PAD_LEFT),
                    'birth_date' => fake()->dateTimeBetween('-55 years', '-25 years')->format('Y-m-d'),
                    'email' => Str::slug($ad . ' ' . $soyad, '.') . '@okul.edu.tr',
                    'is_active' => true,
                    'max_weekly_hours' => 30,
                ]);

                // Öğretmen bu dersi tüm seviyelerde verebilir
                foreach ($seviyeler as $seviye) {
                    TeacherCourseLevel::create([
                        'teacher_id' => $ogretmen->id,
                        'course_id' => $ders->id,
                        'level_id' => $seviye->id,
                    ]);
                }

                $ogretmenSirasi++;
            }
        }
    }
}
