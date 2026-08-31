<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Guncellemede route model binding'den Level gelir, eklemede null.
        $seviyeId = $this->route('seviye')?->id;

        return [
            // 9-12 araligi Vue tarafindaki kontrolle ayni. Ikisi
            // farkli olsaydi kullanici Vue'yu gecip sunucuda takilirdi.
            'grade_number' => [
                'required',
                'integer',
                'min:9',
                'max:12',
                // Rule::unique()->ignore() null'i dogru isliyor.
                // where('school_id', ...) coklu okulda gerekli: iki
                // okul da kendi "9. Sinif"ina sahip olabilmeli, benzersizlik
                // sadece AYNI okul icinde aranmali.
                Rule::unique('levels', 'grade_number')
                    ->where('school_id', School::aktif()?->id)
                    ->ignore($seviyeId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'grade_number.required' => 'Sınıf numarası zorunludur.',
            'grade_number.integer' => 'Sınıf numarası tam sayı olmalıdır.',
            'grade_number.min' => 'Sınıf numarası 9 ile 12 arasında olmalıdır.',
            'grade_number.max' => 'Sınıf numarası 9 ile 12 arasında olmalıdır.',
            'grade_number.unique' => ':input. Sınıf zaten tanımlı.',
        ];
    }
}
