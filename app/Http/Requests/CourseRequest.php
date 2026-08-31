<?php

namespace App\Http\Requests;

use App\Models\CourseLevel;
use App\Models\Level;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // route zaten auth + admin middleware'inde
    }

    public function rules(): array
    {
        // Güncellemede route model binding'den Course gelir, eklemede null.
        $dersId = $this->route('ders')?->id;

        return [
            // Rule::unique()->ignore() null'ı doğru işliyor.
            // Metin birleştirme kullansaydım eklemede boş string üretir,
            // PostgreSQL "id <> ''" karşılaştırmasını reddederdi.
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'name')->where('school_id', School::aktif()?->id)->ignore($dersId),
            ],
            'hourly_rate' => 'required|numeric|min:0|max:999999',
            'seviyeler' => 'nullable|array',
            'seviyeler.*.secili' => 'nullable|boolean',
            // max:40 kaldırıldı — üst sınır aşağıda kapasiteden hesaplanıyor
            'seviyeler.*.weekly_hours' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ders adı zorunludur.',
            'name.unique' => 'Bu ders zaten tanımlı.',
            'hourly_rate.required' => 'Saatlik ücret zorunludur.',
            'hourly_rate.numeric' => 'Saatlik ücret sayı olmalıdır.',
            'seviyeler.*.weekly_hours.min' => 'Haftalık saat en az 1 olmalıdır.',
            'seviyeler.*.weekly_hours.integer' => 'Haftalık saat tam sayı olmalıdır.',
        ];
    }

    /**
     * Alan bazlı kurallar yetmiyor: asıl kısıt bir seviyedeki
     * TÜM derslerin toplamının haftalık kapasiteyi aşmaması.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $okul = School::aktif();

            if (! $okul) {
                return;
            }

            $kapasite = $okul->haftalikKapasite();

            $dersId = $this->route('ders')?->id;

            foreach ($this->input('seviyeler', []) as $levelId => $veri) {
                // İşaretlenmemiş seviyeyi kontrol etme
                if (empty($veri['secili'])) {
                    continue;
                }

                $saat = (int) ($veri['weekly_hours'] ?? 0);
                $seviyeAdi = Level::find($levelId)?->name ?? "Seviye #{$levelId}";

                // Seçili ama saat girilmemiş — kayıt sessizce silinmesin
                if ($saat < 1) {
                    $validator->errors()->add(
                        "seviyeler.{$levelId}.weekly_hours",
                        "{$seviyeAdi} seçili — haftalık saat girmelisiniz. "
                        . "Bu seviyede ders verilmeyecekse kutunun işaretini kaldırın."
                    );

                    continue;
                }

                // Bu seviyedeki DİĞER derslerin toplamı.
                // when($dersId) düzenlenen dersin kendi saatini çıkarıyor;
                // olmasaydı hiçbir ders değiştirilmeden kaydedilemezdi.
                $digerleri = CourseLevel::where('level_id', $levelId)
                    ->when($dersId, fn ($q) => $q->where('course_id', '!=', $dersId))
                    ->sum('weekly_hours');

                if ($digerleri + $saat > $kapasite) {
                    $kalan = max(0, $kapasite - $digerleri);

                    $validator->errors()->add(
                        "seviyeler.{$levelId}.weekly_hours",
                        "{$seviyeAdi} için en fazla {$kalan} saat verilebilir. "
                        . "Haftalık kapasite {$kapasite} saat, diğer dersler {$digerleri} saat kullanıyor."
                    );
                }
            }
        });
    }
}
