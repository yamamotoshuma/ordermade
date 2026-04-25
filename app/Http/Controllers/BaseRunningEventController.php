<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyLatestBaseRunningEventRequest;
use App\Http\Requests\StoreBaseRunningEventRequest;
use App\Models\Game;
use App\Services\OffenseStateService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class BaseRunningEventController extends Controller
{
    public function __construct(
        private readonly OffenseStateService $offenseStateService
    ) {
    }

    /**
     * 打撃登録画面からの走者操作を保存する。
     */
    public function store(Game $game, StoreBaseRunningEventRequest $request): RedirectResponse
    {
        try {
            $this->offenseStateService->recordRunnerEvent($game, $request->validated());

            return redirect()
                ->route('batting.create', ['game' => $game])
                ->with('message', '走者状況を更新しました');
        } catch (RuntimeException $e) {
            return redirect()
                ->route('batting.create', ['game' => $game])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * 直前の走者操作だけを取り消す。
     */
    public function destroyLatest(Game $game, DestroyLatestBaseRunningEventRequest $request): RedirectResponse
    {
        try {
            $this->offenseStateService->deleteLatestRunnerEvent(
                $game,
                (int) $request->validated()['offenseStateVersion']
            );

            return redirect()
                ->route('batting.create', ['game' => $game])
                ->with('message', '直前の走者操作を取り消しました');
        } catch (RuntimeException $e) {
            return redirect()
                ->route('batting.create', ['game' => $game])
                ->with('error', $e->getMessage());
        }
    }
}
