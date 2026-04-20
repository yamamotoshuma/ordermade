<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameRequest extends FormRequest
{
    /**
     * 試合更新を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 編集対象の基本項目を検証する。
     */
    public function rules(): array
    {
        return [
            'gameName' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer'],
            'gameDates' => ['required', 'date'],
            'enemyName' => ['required', 'string', 'max:255'],
            'gameFirstFlg' => ['required', 'in:0,1'],
            'winFlg' => ['nullable', 'in:0,1'],
        ];
    }
}
