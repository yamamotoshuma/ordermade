<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePitchingStatRequest extends FormRequest
{
    /**
     * 投手成績の更新を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 編集画面の基本項目を検証する。
     */
    public function rules(): array
    {
        return [
            'pitchingOrder' => ['required', 'integer'],
            'userId' => ['required', 'integer', 'exists:users,id'],
            'result' => ['nullable', 'in:勝,負'],
            'save' => ['nullable', 'in:0,1'],
        ];
    }
}
