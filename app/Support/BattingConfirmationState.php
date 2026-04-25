<?php

namespace App\Support;

use App\Models\BattingStats;
use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Arr;

class BattingConfirmationState
{
    private const FLASH_KEY = 'batting_confirmation';

    private const CREATE_PREFIX = 'batting_confirmation_payload.create.';

    private const EDIT_PREFIX = 'batting_confirmation_payload.edit.';

    private const PAYLOAD_FIELDS = [
        'selectedOrderId',
        'userId',
        'userName',
        'inning',
        'resultId1',
        'resultId2',
        'resultId3',
        'fromEdit',
        'returnTo',
        'offenseStateVersion',
    ];

    private const CREATE_RESTORE_FIELDS = [
        'selectedOrderId',
        'userId',
        'userName',
        'inning',
        'resultId1',
        'resultId2',
        'resultId3',
        'fromEdit',
        'offenseStateVersion',
    ];

    private const EDIT_RESTORE_FIELDS = [
        'userId',
        'userName',
        'inning',
        'resultId1',
        'resultId2',
        'resultId3',
        'returnTo',
    ];

    /**
     * 登録画面に確認メッセージが残っていない時だけ退避入力を破棄する。
     */
    public function clearCreatePayloadIfNoConfirmation(SessionStore $session, Game|int|string $game): void
    {
        if ($session->has(self::FLASH_KEY)) {
            return;
        }

        $session->forget($this->createPayloadKey($game));
    }

    /**
     * 編集画面に確認メッセージが残っていない時だけ退避入力を破棄する。
     */
    public function clearEditPayloadIfNoConfirmation(SessionStore $session, BattingStats|int|string $batting): void
    {
        if ($session->has(self::FLASH_KEY)) {
            return;
        }

        $session->forget($this->editPayloadKey($batting));
    }

    /**
     * 登録確認用に必要な入力だけをセッションへ退避する。
     */
    public function storeCreatePayload(SessionStore $session, Game|int|string $game, array $input): void
    {
        $session->put($this->createPayloadKey($game), $this->extractPayload($input));
    }

    /**
     * 編集確認用に必要な入力だけをセッションへ退避する。
     */
    public function storeEditPayload(SessionStore $session, BattingStats|int|string $batting, array $input): void
    {
        $session->put($this->editPayloadKey($batting), $this->extractPayload($input));
    }

    /**
     * 登録確認が完了したら退避入力を破棄する。
     */
    public function clearCreatePayload(SessionStore $session, Game|int|string $game): void
    {
        $session->forget($this->createPayloadKey($game));
    }

    /**
     * 編集確認が完了したら退避入力を破棄する。
     */
    public function clearEditPayload(SessionStore $session, BattingStats|int|string $batting): void
    {
        $session->forget($this->editPayloadKey($batting));
    }

    /**
     * 登録確認後の再送時に不足した入力をサーバー側で復元する。
     */
    public function restoreCreatePayload(FormRequest $request): void
    {
        if (! filled($request->input('confirmationResolution'))) {
            return;
        }

        $pending = $request->session()->get($this->createPayloadKey($request->route('game')));

        if (! is_array($pending)) {
            return;
        }

        $request->merge($this->collectRestoredFields($request, $pending, self::CREATE_RESTORE_FIELDS));
    }

    /**
     * 編集確認後の再送時に不足した入力をサーバー側で復元する。
     */
    public function restoreEditPayload(FormRequest $request): void
    {
        if (! filled($request->input('confirmationResolution'))) {
            return;
        }

        $pending = $request->session()->get($this->editPayloadKey($request->route('batting')));

        if (! is_array($pending)) {
            return;
        }

        $request->merge($this->collectRestoredFields($request, $pending, self::EDIT_RESTORE_FIELDS));
    }

    /**
     * 退避対象の入力だけを抽出する。
     */
    public function extractPayload(array $input): array
    {
        return Arr::only($input, self::PAYLOAD_FIELDS);
    }

    /**
     * 再送時に未入力だった項目だけを取り出す。
     */
    private function collectRestoredFields(FormRequest $request, array $pending, array $fields): array
    {
        $restored = [];

        foreach ($fields as $field) {
            if (filled($request->input($field))) {
                continue;
            }

            if (! array_key_exists($field, $pending) || ! filled($pending[$field])) {
                continue;
            }

            $restored[$field] = $pending[$field];
        }

        return $restored;
    }

    /**
     * 打撃登録画面用の退避キーを返す。
     */
    private function createPayloadKey(Game|int|string|null $game): string
    {
        return self::CREATE_PREFIX . $this->normalizeRouteKey($game, 'gameId');
    }

    /**
     * 打撃編集画面用の退避キーを返す。
     */
    private function editPayloadKey(BattingStats|int|string|null $batting): string
    {
        return self::EDIT_PREFIX . $this->normalizeRouteKey($batting, 'id');
    }

    /**
     * Eloquentモデルまたはルート値から一意キーを取り出す。
     */
    private function normalizeRouteKey(object|int|string|null $value, string $property): int|string
    {
        if (is_object($value)) {
            return $value->{$property};
        }

        return (string) $value;
    }
}
