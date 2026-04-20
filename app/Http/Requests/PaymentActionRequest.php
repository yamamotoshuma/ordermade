<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentActionRequest extends FormRequest
{
    /**
     * 入金登録画面の操作を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 登録時と削除検索時で必要な項目だけ変える。
     */
    public function rules(): array
    {
        $rules = [
            'user_name' => ['required', 'integer', 'exists:users,id'],
            'payment_year' => ['required', 'digits:4'],
            'payment_month' => ['required', 'integer', 'between:1,12'],
        ];

        if ($this->has('insert')) {
            $rules['payment_amount'] = ['required', 'integer', 'min:1', 'max:99999'];
        }

        return $rules;
    }
}
