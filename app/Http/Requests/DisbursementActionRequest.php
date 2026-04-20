<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisbursementActionRequest extends FormRequest
{
    /**
     * 出金登録画面の操作を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 登録時と削除検索時で必要な項目を分ける。
     */
    public function rules(): array
    {
        $rules = [
            'Mcode' => ['required', 'integer'],
            'Scode' => ['required', 'integer'],
            'disbur_year' => ['required', 'digits:4'],
            'disbur_month' => ['required', 'integer', 'between:1,12'],
        ];

        if ($this->has('create')) {
            $rules['disbur_amount'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }
}
