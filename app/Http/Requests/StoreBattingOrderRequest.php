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
            'battingOrder.*' => ['nullable', 'integer', 'min:1'],
            'positionId' => ['nullable', 'array'],
            'positionId.*' => ['nullable', 'integer', 'exists:positions,positionId'],
            'userId' => ['nullable', 'array'],
            'userId.*' => ['nullable', 'integer', 'exists:users,id'],
            'userName' => ['nullable', 'array'],
            'userName.*' => ['nullable', 'string', 'max:255'],
            'ranking' => ['nullable', 'array'],
            'ranking.*' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
