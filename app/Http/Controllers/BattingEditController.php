<?php

namespace App\Http\Controllers;

use App\Exceptions\BattingStatConflictException;
use App\Http\Requests\StoreBattingStatRequest;
use App\Http\Requests\UpdateBattingStatRequest;
use App\Models\BattingStats;
use App\Models\Game;
use App\Services\BattingStatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BattingEditController extends Controller
{
    public function __construct(
        private readonly BattingStatService $battingStatService
    ) {
    }

    public function index(Game $game, Request $request)
    {
        return view('batting.index', $this->battingStatService->getIndexData(
            $game,
            (string) $request->input('statsId', '')
        ));
    }

    public function create(Game $game)
    {
        return view('batting.create', $this->battingStatService->getCreateData($game));
    }

    public function store(Game $game, StoreBattingStatRequest $request): RedirectResponse
    {
        try {
            $battingStat = $this->battingStatService->create($game, $request->validated());
            $message = $battingStat->wasRecentlyCreated ? '打撃成績を登録しました' : '打撃成績を更新しました';
            Log::info($battingStat->wasRecentlyCreated ? '打撃登録完了' : '打撃衝突更新完了');

            if ($request->boolean('fromEdit')) {
                return redirect()
                    ->route('batting.index', ['game' => $game, 'statsId' => $battingStat->id])
                    ->with('message', $message);
            }

            return redirect()
                ->route('batting.create', ['game' => $game])
                ->with('message', $message);
        } catch (BattingStatConflictException $e) {
            return redirect()
                ->route('batting.create', ['game' => $game])
                ->withInput()
                ->with('batting_conflict', [
                    'statsId' => $e->battingStat->id,
                    'message' => $e->getMessage(),
                ]);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('batting.create', ['game' => $game])
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::debug($e->getMessage());

            return redirect()
                ->route('batting.create', ['game' => $game])
                ->withInput()
                ->with('error', 'データの保存中にエラーが発生しました');
        }
    }

    public function edit(BattingStats $batting)
    {
        return view('batting.edit', $this->battingStatService->getEditData($batting));
    }

    public function update(UpdateBattingStatRequest $request, BattingStats $batting): RedirectResponse
    {
        try {
            $updatedBatting = $this->battingStatService->update($batting, $request->validated());
            Log::info('打撃更新完了');

            return redirect()
                ->route('batting.edit', ['batting' => $updatedBatting])
                ->with('message', '打撃成績を更新しました');
        } catch (\Throwable $e) {
            Log::debug($e->getMessage());

            return redirect()->back()->withInput()->with('error', '打撃成績の更新中にエラーが発生しました。');
        }
    }

    public function destroy(BattingStats $batting): RedirectResponse
    {
        try {
            $game = $this->battingStatService->delete($batting);
            Log::info('打撃削除完了');

            return redirect()
                ->route('batting.index', ['game' => $game])
                ->with('message', '打撃成績を削除しました');
        } catch (\Throwable $e) {
            Log::debug($e->getMessage());

            return redirect()->back()->with('error', '打撃成績の削除中にエラーが発生しました。');
        }
    }
}
