<?php

namespace App\Http\Requests;

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
                Rule::unique('rooms', 'name')->ignore($derslikId),
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
