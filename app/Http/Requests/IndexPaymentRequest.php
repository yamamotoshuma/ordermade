<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexPaymentRequest extends FormRequest
{
    /**
     * 一覧の年フィルタは誰でも使える。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 年は4桁の数値だけ受け付ける。
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', 'digits:4'],
        ];
    }
}
