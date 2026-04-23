<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBattingStatRequest extends FormRequest
{
    /**
     * 打撃成績登録はログイン済みユーザーに許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 通常登録に必要な入力と、衝突時の更新確認フラグを検証する。
     */
    public function rules(): array
    {
        return [
            'userId' => ['required_without:userName', 'nullable'],
            'userName' => ['required_without:userId', 'nullable', 'string'],
            'inning' => ['required', 'integer', 'min:1'],
            'resultId1' => ['required', 'integer'],
            'resultId2' => ['required', 'integer'],
            'resultId3' => ['required', 'integer'],
            'fromEdit' => ['nullable'],
            'conflictResolution' => ['nullable', 'in:update'],
        ];
    }

    /**
     * 既存画面の日本語エラー表示に合わせる。
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute フィールドは必須です。',
            'required_without' => ':attribute フィールドは、:values のいずれかが存在する場合、必須です。',
        ];
    }
}
