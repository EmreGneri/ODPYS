<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SectionCourseTeacher;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $ogretmenler = Teacher::query()
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

        return view('teachers.index', ['ogretmenler' => $ogretmenler]);
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
     * Bir öğretmenin haftada girebileceği azami ders saati:
     * günlük ders sayısı × aktif gün sayısı.
     */
    private function haftalikKapasite(): int
    {
        $okul = School::first();

        return $okul->lessons_per_day
            * $okul->days()->where('is_active', true)->count();
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
