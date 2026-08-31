<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subeId = $this->route('sube')?->id;
        $seviyeId = $this->input('level_id');

        return [
            'level_id' => 'required|exists:levels,id',

            // Ayni seviyede iki "9A" olmasin. Semada bu kisit yok,
            // dogrulama katmaninda tutuyorum; migration eklemek
            // mevcut veriyle catisma riski tasiyordu.
            'name' => [
                'required',
                'string',
                'max:10',
                Rule::unique('sections', 'name')
                    ->where('level_id', $seviyeId)
                    ->ignore($subeId),
            ],

            // Bir derslik yalnizca bir subeye ait olabilir.
            // Asil garanti migration'daki unique indekste; bu kural
            // kullaniciya anlasilir mesaj gostermek icin.
            'room_id' => [
                'nullable',
                'exists:rooms,id',
                Rule::unique('sections', 'room_id')->ignore($subeId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'level_id.required' => 'Seviye seçilmelidir.',
            'level_id.exists' => 'Seçilen seviye bulunamadı.',
            'name.required' => 'Şube adı zorunludur.',
            'name.max' => 'Şube adı en fazla 10 karakter olabilir.',
            'name.unique' => 'Bu seviyede :input adında bir şube zaten var.',
            'room_id.exists' => 'Seçilen derslik bulunamadı.',
            'room_id.unique' => 'Bu derslik başka bir şubeye atanmış.',
        ];
    }
}
