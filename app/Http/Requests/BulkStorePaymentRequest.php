<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkStorePaymentRequest extends FormRequest
{
    /**
     * 一括入金登録を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 一括登録に必要な配列構造を担保する。
     */
    public function rules(): array
    {
        return [
            'payment_Year' => ['required', 'digits:4'],
            'payment_month' => ['required', 'integer', 'between:1,12'],
            'users' => ['required', 'array'],
            'users.*' => ['integer', 'exists:users,id'],
            'payment_amounts' => ['required', 'array'],
            'payment_amounts.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
