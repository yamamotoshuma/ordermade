<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOffenseStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offenseStateVersion' => ['required', 'integer', 'min:1'],
            'inning' => ['required', 'integer', 'min:1', 'max:99'],
            'outCount' => ['required', 'integer', 'between:0,2'],
            'batterOrderId' => ['nullable', 'integer'],
            'firstOrderId' => ['nullable', 'integer'],
            'secondOrderId' => ['nullable', 'integer'],
            'thirdOrderId' => ['nullable', 'integer'],
        ];
    }
}
