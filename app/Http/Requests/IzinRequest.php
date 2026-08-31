<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ogretmenin kendi izin talebini olusturmasi. Sartname 2.7.1.
 *
 * teacher_id burada YOK - formdan gelmiyor. Kullanici baska bir
 * ogretmen adina talep olusturamamali; controller bunu
 * $request->user()->teacher uzerinden kendisi belirliyor.
 */
class IzinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.required' => 'Başlangıç tarih ve saati zorunludur.',
            'ends_at.required' => 'Bitiş tarih ve saati zorunludur.',
            'ends_at.after' => 'Bitiş, başlangıçtan sonra olmalıdır.',
        ];
    }
}
