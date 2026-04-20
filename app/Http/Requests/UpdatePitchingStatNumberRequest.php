<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePitchingStatNumberRequest extends FormRequest
{
    /**
     * 数値項目の更新を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 更新対象の項目名だけは先に制限しておく。
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:inning,hitsAllowed,homeRunsAllowed,strikeouts,walks,wildPitches,balks,runsAllowed,earnedRuns'],
            'inning' => ['nullable', 'numeric'],
            'hitsAllowed' => ['nullable', 'integer'],
            'homeRunsAllowed' => ['nullable', 'integer'],
            'strikeouts' => ['nullable', 'integer'],
            'walks' => ['nullable', 'integer'],
            'wildPitches' => ['nullable', 'integer'],
            'balks' => ['nullable', 'integer'],
            'runsAllowed' => ['nullable', 'integer'],
            'earnedRuns' => ['nullable', 'integer'],
        ];
    }
}
