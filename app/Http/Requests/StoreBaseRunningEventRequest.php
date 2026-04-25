<?php

namespace App\Http\Requests;

use App\Services\OffenseStateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBaseRunningEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offenseStateVersion' => ['required', 'integer', 'min:1'],
            'action' => [
                'required',
                Rule::in([
                    OffenseStateService::EVENT_STOLEN_BASE,
                    OffenseStateService::EVENT_DOUBLE_STEAL,
                    OffenseStateService::EVENT_ADVANCE,
                    OffenseStateService::EVENT_CAUGHT_STEALING,
                    OffenseStateService::EVENT_PICKOFF_OUT,
                    OffenseStateService::EVENT_RUNNER_OUT,
                    OffenseStateService::EVENT_MANUAL_PLACE,
                    OffenseStateService::EVENT_CLEAR_BASE,
                ]),
            ],
            'base' => ['nullable', 'integer', 'between:1,3'],
            'targetBase' => ['nullable', 'integer', 'between:1,3'],
            'orderId' => ['nullable', 'integer'],
            'userId' => ['nullable', 'integer'],
            'userName' => ['nullable', 'string'],
            'displayName' => ['nullable', 'string'],
        ];
    }
}
