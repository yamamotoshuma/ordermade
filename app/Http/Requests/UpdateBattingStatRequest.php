<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBattingStatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'userId' => ['nullable'],
            'userName' => ['nullable', 'string'],
            'inning' => ['required', 'integer', 'min:1'],
            'resultId1' => ['required', 'integer'],
            'resultId2' => ['required', 'integer'],
            'resultId3' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute フィールドは必須です。',
        ];
    }
}
