<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * 問い合わせ送信を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 最低限の問い合わせ本文を検証する。
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ];
    }
}
