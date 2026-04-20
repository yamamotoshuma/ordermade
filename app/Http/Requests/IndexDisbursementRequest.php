<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexDisbursementRequest extends FormRequest
{
    /**
     * 出金一覧の検索を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 年フィルタは4桁の数値だけ受け付ける。
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', 'digits:4'],
        ];
    }
}
