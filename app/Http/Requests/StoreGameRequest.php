<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGameRequest extends FormRequest
{
    /**
     * 試合登録を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 新規試合に必要な基本項目を検証する。
     */
    public function rules(): array
    {
        return [
            'gameName' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer'],
            'gameDates' => ['required', 'date'],
            'enemyName' => ['required', 'string', 'max:255'],
            'gameFirstFlg' => ['required', 'in:0,1'],
        ];
    }
}
