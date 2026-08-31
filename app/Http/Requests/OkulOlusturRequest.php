<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Yeni okul olusturma. Sartname 2.1.7 - birden fazla okul eklenebilmeli.
 *
 * Egitim gunleri burada istenmiyor: yeni okul once bos olusuyor,
 * yonetici olusturduktan sonra /okul/duzenle uzerinden gunleri
 * tanimliyor. Ayni formda ikisini birden istemek formu sisirirdi.
 */
class OkulOlusturRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Okul adı zorunludur.',
            'city.required' => 'İl zorunludur.',
            'district.required' => 'İlçe zorunludur.',
        ];
    }
}
