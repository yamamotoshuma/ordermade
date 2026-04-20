<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePitchingStatRequest extends FormRequest
{
    /**
     * 投手成績の新規登録を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 投手成績の基本行を検証する。
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
