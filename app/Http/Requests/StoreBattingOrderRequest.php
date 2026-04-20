<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBattingOrderRequest extends FormRequest
{
    /**
     * 打順登録はログイン済みユーザーに許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 配列形式の入力だけ先に担保し、行単位の厳密検証は Service で行う。
     */
    public function rules(): array
    {
        return [
            'gameId' => ['required', 'exists:games,gameId'],
            'battingOrder' => ['nullable', 'array'],
            'positionId' => ['nullable', 'array'],
            'userId' => ['nullable', 'array'],
            'userName' => ['nullable', 'array'],
            'ranking' => ['nullable', 'array'],
        ];
    }
}
