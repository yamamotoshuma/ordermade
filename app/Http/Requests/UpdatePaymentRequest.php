<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * 入金更新を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 編集画面の更新項目を検証する。
     */
    public function rules(): array
    {
        return [
            'payment_year' => ['required', 'digits:4'],
            'payment_month' => ['required', 'integer', 'between:1,12'],
            'payment_amount' => ['required', 'integer', 'min:1', 'max:99999'],
        ];
    }
}
