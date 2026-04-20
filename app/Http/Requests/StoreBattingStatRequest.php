<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBattingStatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'userId' => ['required_without:userName', 'nullable'],
            'userName' => ['required_without:userId', 'nullable', 'string'],
            'inning' => ['required', 'integer', 'min:1'],
            'resultId1' => ['required', 'integer'],
            'resultId2' => ['required', 'integer'],
            'resultId3' => ['required', 'integer'],
            'fromEdit' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute フィールドは必須です。',
            'required_without' => ':attribute フィールドは、:values のいずれかが存在する場合、必須です。',
        ];
    }
}
