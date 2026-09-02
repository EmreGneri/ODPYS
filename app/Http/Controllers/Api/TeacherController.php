<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

/**
 * Web tarafindaki TeacherController'in JSON karsiligi. Dogrulama
 * kurallari (kurallar()/mesajlar()) web'dekiyle birebir ayni - ayri
 * bir FormRequest'e cikarilmadi (web tarafi da inline validate()
 * kullaniyor), kucuk bir tekrar ama iki dosyada da okunmasi kolay.
 */
class TeacherController extends Controller
{
    public function index(Request $request)
    {
        return Teacher::query()
            ->with('courseLevels')
            ->when($request->filled('ara'), function ($sorgu) use ($request) {
                $terim = '%' . $request->ara . '%';

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
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->kurallar(), $this->mesajlar());

        unset($validated['photo'], $validated['cv']);
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('teachers/photos', 'public');
        }

        if ($request->hasFile('cv')) {
            $validated['cv_path'] = $request->file('cv')->store('teachers/cv', 'public');
        }

        $ogretmen = School::aktif()->teachers()->create($validated);

        return response()->json($ogretmen, 201);
    }

    public function update(Request $request, Teacher $ogretmen)
    {
        $validated = $request->validate($this->kurallar($ogretmen->id), $this->mesajlar());

        unset($validated['photo'], $validated['cv']);
        $validated['is_active'] = $request->boolean('is_active');

        // Dosya gonderilmediyse anahtar diziye hic girmiyor,
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

        return $ogretmen->fresh();
    }

    public function destroy(Teacher $ogretmen)
    {
        $atamaVar = SectionCourseTeacher::where('teacher_id', $ogretmen->id)->exists();

        if ($atamaVar) {
            return response()->json([
                'message' => 'Bu öğretmenin ders atamaları var. Önce atamaları kaldırın veya öğretmeni pasife alın.',
            ], 422);
        }

        $this->dosyaSil($ogretmen->photo_path);
        $this->dosyaSil($ogretmen->cv_path);

        $ogretmen->delete();

        return response()->json(null, 204);
    }

    /**
     * Sartname 2.8 - ogretmene giris hesabi acar.
     */
    public function hesapAc(Teacher $ogretmen, OgretmenServisi $servis)
    {
        $sonuc = $servis->hesapAc($ogretmen);

        if (! $sonuc['basarili']) {
            return response()->json(['message' => $sonuc['mesaj']], 422);
        }

        return [
            'ad' => $ogretmen->full_name,
            'eposta' => $ogretmen->email,
            'parola' => $sonuc['parola'],
        ];
    }

    /**
     * Sartname 2.4 - ogretmene ders/branş atama matrisi icin veri.
     */
    public function dersler(Teacher $ogretmen)
    {
        $mevcut = TeacherCourseLevel::where('teacher_id', $ogretmen->id)
            ->get()
            ->mapWithKeys(fn ($y) => [$y->course_id . '-' . $y->level_id => true])
            ->all();

        return [
            // Ekran artik ogretmenler arasinda gezinebiliyor; kimin
            // yetkilerini duzenledigi basligta yazsin diye.
            'ogretmen' => ['id' => $ogretmen->id, 'ad' => $ogretmen->full_name],
            'dersler' => Course::orderBy('name')->get(),
            'seviyeler' => Level::orderBy('grade_number')->get(),
            'mevcut' => $mevcut,
        ];
    }

    public function derslerKaydet(Request $request, Teacher $ogretmen, OgretmenServisi $servis)
    {
        $sayi = $servis->yetkileriKaydet($ogretmen, $request->input('yetkiler', []));

        return ['mesaj' => $ogretmen->full_name . ' için ' . $sayi . ' ders-seviye yetkisi kaydedildi.'];
    }

    private function haftalikKapasite(): int
    {
        $okul = School::aktif();

        return $okul ? $okul->haftalikKapasite() : 0;
    }

    private function dosyaSil(?string $yol): void
    {
        if ($yol && Storage::disk('public')->exists($yol)) {
            Storage::disk('public')->delete($yol);
        }
    }

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
