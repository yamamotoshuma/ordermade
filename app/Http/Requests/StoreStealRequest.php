<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStealRequest extends FormRequest
{
    /**
     * 盗塁追加を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 対象試合と選手を検証する。
     */
    public function rules(): array
    {
        return [
            'gameId' => ['required', 'exists:games,gameId'],
            'userId' => ['required', 'exists:users,id'],
        ];
    }
}
