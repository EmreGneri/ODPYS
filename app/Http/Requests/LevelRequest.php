<?php

namespace App\Http\Requests;

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
                // Metin birlestirme kullansaydim eklemede bos string
                // uretir, PostgreSQL "id <> ''" karsilastirmasini reddederdi.
                Rule::unique('levels', 'grade_number')->ignore($seviyeId),
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
