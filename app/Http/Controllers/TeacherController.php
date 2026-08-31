<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Level;
use App\Models\School;
use App\Models\SectionCourseTeacher;
use App\Models\Teacher;
use App\Models\TeacherCourseLevel;
use App\Services\OgretmenServisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $ogretmenler = Teacher::query()
            // Branş sütunu için yetkiler lazım. with() olmadan
            // 20 öğretmen = 21 sorgu olurdu (N+1).
            ->with('courseLevels')
            ->when($request->filled('ara'), function ($sorgu) use ($request) {
                $terim = '%' . $request->ara . '%';

                // İç sarmalayıcı şart: durum filtresiyle AND'lensin,
                // OR grubu kendi parantezinde kalsın.
                $sorgu->where(function ($q) use ($terim) {
                    $q->where('first_name', 'ilike', $terim)
                      ->orWhere('last_name', 'ilike', $terim)
                      ->orWhere('email', 'ilike', $terim)
                      ->orWhere('national_id', 'like', $terim);
                });
            })
            ->when($request->filled('durum'), fn ($q) =>
                $q->where('is_active', $request->durum === 'aktif'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('teachers.index', [
            'ogretmenler' => $ogretmenler,
            // id => ad haritası. Tek sorgu, her satırda ders adı aramak
            // için ayrıca veritabanına gidilmiyor.
            'dersAdlari' => Course::pluck('name', 'id'),
        ]);
    }

    public function create()
    {
        return view('teachers.create', [
            'azamiSaat' => $this->haftalikKapasite(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->kurallar(), $this->mesajlar());

        // 'photo' ve 'cv' form alanı adları — sütunlar 'photo_path' / 'cv_path'.
        // Fillable'da olmadıkları için diziden çıkarılmalı.
        unset($validated['photo'], $validated['cv']);

        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('teachers/photos', 'public');
        }

        if ($request->hasFile('cv')) {
            $validated['cv_path'] = $request->file('cv')->store('teachers/cv', 'public');
        }

        School::first()->teachers()->create($validated);

        return redirect()
            ->route('ogretmenler.index')
            ->with('success', 'Öğretmen eklendi.');
    }

    public function edit(Teacher $ogretmen)
    {
        return view('teachers.edit', [
            'ogretmen' => $ogretmen,
            'azamiSaat' => $this->haftalikKapasite(),
        ]);
    }

    public function update(Request $request, Teacher $ogretmen)
    {
        $validated = $request->validate($this->kurallar($ogretmen->id), $this->mesajlar());

        unset($validated['photo'], $validated['cv']);

        $validated['is_active'] = $request->boolean('is_active');

        // Dosya gönderilmediyse anahtar diziye hiç girmiyor,
        // yani mevcut dosya korunuyor.
        if ($request->hasFile('photo')) {
            $this->dosyaSil($ogretmen->photo_path);
            $validated['photo_path'] = $request->file('photo')->store('teachers/photos', 'public');
        }

        if ($request->hasFile('cv')) {
            $this->dosyaSil($ogretmen->cv_path);
            $validated['cv_path'] = $request->file('cv')->store('teachers/cv', 'public');
        }

        $ogretmen->update($validated);

        return redirect()
            ->route('ogretmenler.index')
            ->with('success', 'Öğretmen güncellendi.');
    }

    public function destroy(Teacher $ogretmen)
    {
        $atamaVar = SectionCourseTeacher::where('teacher_id', $ogretmen->id)->exists();

        if ($atamaVar) {
            return redirect()
                ->route('ogretmenler.index')
                ->with('error', 'Bu öğretmenin ders atamaları var. Önce atamaları kaldırın veya öğretmeni pasife alın.');
        }

        $this->dosyaSil($ogretmen->photo_path);
        $this->dosyaSil($ogretmen->cv_path);

        $ogretmen->delete();

        return redirect()
            ->route('ogretmenler.index')
            ->with('success', 'Öğretmen silindi.');
    }

    /**
     * Şartname 2.8 — öğretmene giriş hesabı açar.
     *
     * İş mantığı OgretmenServisi'nde; burada sadece sonucu
     * HTTP cevabına çeviriyorum.
     */
    public function hesapAc(Teacher $ogretmen, OgretmenServisi $servis)
    {
        $sonuc = $servis->hesapAc($ogretmen);

        if (! $sonuc['basarili']) {
            return redirect()
                ->route('ogretmenler.index')
                ->with('error', $sonuc['mesaj']);
        }

        return redirect()
            ->route('ogretmenler.index')
            ->with('yeniHesap', [
                'ad' => $ogretmen->full_name,
                'eposta' => $ogretmen->email,
                'parola' => $sonuc['parola'],
            ]);
    }

    /**
     * Şartname 2.4 — Öğretmene ders/branş atama ekranı.
     * Atama SEVİYE BAZLI: "Ahmet Hoca → Türkçe → 9. sınıf"
     */
    public function dersler(Teacher $ogretmen)
    {
        // Mevcut yetkiler: "dersId-seviyeId" => true
        // Blade'de her kutu için tek tek sorgu atmamak amacıyla
        // önceden düz bir haritaya çeviriyorum.
        $mevcut = TeacherCourseLevel::where('teacher_id', $ogretmen->id)
            ->get()
            ->mapWithKeys(fn ($y) => [$y->course_id . '-' . $y->level_id => true])
            ->all();

        return view('teachers.courses', [
            'ogretmen' => $ogretmen,
            'dersler' => Course::orderBy('name')->get(),
            'seviyeler' => Level::orderBy('grade_number')->get(),
            'mevcut' => $mevcut,
        ]);
    }

    public function derslerKaydet(Request $request, Teacher $ogretmen, OgretmenServisi $servis)
    {
        $sayi = $servis->yetkileriKaydet($ogretmen, $request->input('yetkiler', []));

        return redirect()
            ->route('ogretmenler.index')
            ->with('success', $ogretmen->full_name . ' için ' . $sayi . ' ders-seviye yetkisi kaydedildi.');
    }

    /**
     * Bir öğretmenin haftada girebileceği azami ders saati:
     * günlük ders sayısı × aktif gün sayısı.
     */
    private function haftalikKapasite(): int
    {
        $okul = School::first();

        // Hesap School modelinde; ders saati doğrulaması ve program
        // sihirbazı da aynı metodu kullanıyor, formül tek yerde duruyor.
        return $okul ? $okul->haftalikKapasite() : 0;
    }

    /**
     * Depodaki dosyayı siler. Yol boşsa hiçbir şey yapmaz.
     * Yetim dosya birikmesin diye kayıt değiştiğinde çağrılıyor.
     */
    private function dosyaSil(?string $yol): void
    {
        if ($yol && Storage::disk('public')->exists($yol)) {
            Storage::disk('public')->delete($yol);
        }
    }

    /**
     * Kurallar tek yerde: store() ve update() aynı listeyi kullanıyor.
     * $ogretmenId sadece güncellemede dolu — unique kuralı kendi kaydını
     * çakışma saymasın diye hariç tutuluyor.
     */
    private function kurallar(?int $ogretmenId = null): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'national_id' => [
                'required',
                'digits:11',
                Rule::unique('teachers', 'national_id')->ignore($ogretmenId),
            ],
            'birth_date' => 'required|date|before:-18 years',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('teachers', 'email')->ignore($ogretmenId),
            ],
            'max_weekly_hours' => 'required|integer|min:1|max:' . $this->haftalikKapasite(),
            'is_active' => 'nullable|boolean',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'cv' => 'nullable|file|mimes:pdf|max:5120',
        ];
    }

    private function mesajlar(): array
    {
        return [
            'national_id.digits' => 'TC kimlik numarası 11 haneli olmalıdır.',
            'national_id.unique' => 'Bu TC kimlik numarası zaten kayıtlı.',
            'birth_date.before' => 'Öğretmen 18 yaşından büyük olmalıdır.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'max_weekly_hours.max' => 'Okulda haftada :max ders saati var, daha fazlası atanamaz.',
            'photo.image' => 'Fotoğraf bir görsel dosyası olmalıdır.',
            'photo.max' => 'Fotoğraf en fazla 2 MB olabilir.',
            'cv.mimes' => 'Özgeçmiş PDF formatında olmalıdır.',
            'cv.max' => 'Özgeçmiş en fazla 5 MB olabilir.',
        ];
    }
}
