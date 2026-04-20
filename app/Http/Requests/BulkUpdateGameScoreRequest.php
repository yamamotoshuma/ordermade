<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateGameScoreRequest extends FormRequest
{
    /**
     * スコア一括更新を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * イニング配列と対象試合の存在を検証する。
     */
    public function rules(): array
    {
        return [
            'inning' => ['required', 'array'],
            'gameId' => ['required', 'exists:games,gameId'],
        ];
    }
}
