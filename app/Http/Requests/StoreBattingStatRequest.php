<?php

namespace App\Http\Requests;

use App\Models\BattingOrder;
use App\Support\BattingConfirmationState;
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
     * 打順プルダウンの選択値から保存対象の userId / userName を補完する。
     */
    protected function prepareForValidation(): void
    {
        app(BattingConfirmationState::class)->restoreCreatePayload($this);

        $selectedOrderId = $this->input('selectedOrderId');

        if (! filled($selectedOrderId) || ! is_numeric($selectedOrderId)) {
            return;
        }

        $orderQuery = BattingOrder::where('orderId', (int) $selectedOrderId);
        $game = $this->route('game');

        if ($game) {
            $orderQuery->where('gameId', is_object($game) ? $game->gameId : $game);
        }

        $order = $orderQuery->first();

        if (! $order) {
            return;
        }

        $this->merge([
            'userId' => $order->userId ?: null,
            'userName' => $order->userId ? null : trim((string) $order->userName),
        ]);
    }

    /**
     * 通常登録に必要な入力と、確認後の再送フラグを検証する。
     */
    public function rules(): array
    {
        return [
            'selectedOrderId' => ['nullable'],
            'userId' => ['required_without:userName', 'nullable'],
            'userName' => ['required_without:userId', 'nullable', 'string'],
            'inning' => ['required', 'integer', 'min:1'],
            'resultId1' => ['required', 'integer'],
            'resultId2' => ['required', 'integer'],
            'resultId3' => ['required', 'integer'],
            'fromEdit' => ['nullable'],
            'confirmationResolution' => ['nullable', 'in:duplicate,rbi'],
            'offenseStateVersion' => ['nullable', 'integer', 'min:1'],
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
