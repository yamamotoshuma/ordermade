<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisburCategoryRequest extends FormRequest
{
    /**
     * カテゴリ追加を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * カテゴリコードと名称を検証する。
     */
    public function rules(): array
    {
        return [
            'Mcode' => ['required', 'integer'],
            'Mname' => ['required', 'string'],
            'Scode' => ['required', 'integer'],
            'Sname' => ['required', 'string'],
        ];
    }
}
