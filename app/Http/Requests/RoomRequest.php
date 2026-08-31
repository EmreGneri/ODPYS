<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $derslikId = $this->route('derslik')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // where('school_id', ...): iki okul da kendi "D-101"ine
                // sahip olabilmeli, benzersizlik okul icinde aranmali.
                Rule::unique('rooms', 'name')
                    ->where('school_id', School::aktif()?->id)
                    ->ignore($derslikId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Derslik adı zorunludur.',
            'name.unique' => 'Bu adda bir derslik zaten var.',
        ];
    }
}
