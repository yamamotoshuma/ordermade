<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyLatestBaseRunningEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offenseStateVersion' => ['required', 'integer', 'min:1'],
        ];
    }
}
