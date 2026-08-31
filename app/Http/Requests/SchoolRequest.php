<?php

namespace App\Http\Requests;

use App\Models\CourseLevel;
use App\Models\Level;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // route zaten auth + admin middleware'inde
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'lessons_per_day' => 'required|integer|min:1|max:20',
            'lesson_duration' => 'required|integer|min:5|max:120',
            'break_duration' => 'required|integer|min:0|max:60',
            'days' => 'required|array',
            'days.*.is_active' => 'nullable|boolean',
            'days.*.start_time' => 'required|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Okul adı zorunludur.',
            'city.required' => 'İl zorunludur.',
            'district.required' => 'İlçe zorunludur.',
            'lessons_per_day.required' => 'Günlük ders sayısı zorunludur.',
            'lessons_per_day.min' => 'Günde en az 1 ders olmalıdır.',
            'lesson_duration.min' => 'Ders süresi en az 5 dakika olmalıdır.',
            'break_duration.min' => 'Mola süresi negatif olamaz.',
            'days.*.start_time.required' => 'Her gün için başlangıç saati girilmelidir.',
            'days.*.start_time.date_format' => 'Başlangıç saati SS:DD biçiminde olmalıdır.',
        ];
    }

    /**
     * Alan bazlı kurallarla ifade edilemeyen iki iş kuralı:
     * en az bir eğitim günü, ve mevcut müfredatı imkânsız kılmama.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $gunler = $this->input('days', []);

            $aktifSayisi = collect($gunler)
                ->filter(fn ($g) => ! empty($g['is_active']))
                ->count();

            if ($aktifSayisi < 1) {
                $validator->errors()->add(
                    'days',
                    'En az bir eğitim günü seçilmelidir. Aksi halde haftalık kapasite sıfır olur ve program üretilemez.'
                );

                return;
            }

            $yeniKapasite = (int) $this->input('lessons_per_day') * $aktifSayisi;

            // Kapasiteyi düşürmek mevcut müfredatı imkânsız kılıyor mu?
            // En yüklü seviyenin toplamı yeni kapasiteyi aşıyorsa
            // hiçbir program üretilemez hale gelir.
            $enYuklu = CourseLevel::query()
                ->selectRaw('level_id, SUM(weekly_hours) AS toplam')
                ->groupBy('level_id')
                ->orderByDesc('toplam')
                ->first();

            if ($enYuklu && (int) $enYuklu->toplam > $yeniKapasite) {
                $seviyeAdi = Level::find($enYuklu->level_id)?->name ?? 'Bir seviye';

                $validator->errors()->add(
                    'lessons_per_day',
                    "Bu ayarla haftalık kapasite {$yeniKapasite} saat olurdu, ancak {$seviyeAdi} müfredatı {$enYuklu->toplam} saat. "
                    . 'Önce ders saatlerini azaltın ya da daha fazla gün/ders tanımlayın.'
                );
            }
        });
    }
}
