<?php

namespace App\Http\Controllers;

use App\Exceptions\BattingStatConflictException;
use App\Http\Requests\StoreBattingStatRequest;
use App\Http\Requests\UpdateBattingStatRequest;
use App\Models\BattingStats;
use App\Models\Game;
use App\Services\BattingStatService;
use App\Support\BattingConfirmationState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BattingEditController extends Controller
{
    public function __construct(
        private readonly BattingStatService $battingStatService,
        private readonly BattingConfirmationState $battingConfirmationState,
    ) {
    }

    public function index(Game $game, Request $request)
    {
        return view('batting.index', $this->battingStatService->getIndexData(
            $game,
            (string) $request->input('statsId', '')
        ));
    }

    public function create(Request $request, Game $game)
    {
        $this->battingConfirmationState->clearCreatePayloadIfNoConfirmation($request->session(), $game);

        return view('batting.create', $this->battingStatService->getCreateData(
            $game,
            $request->session()->get('last_batting_stat_id')
        ));
    }

    public function store(Game $game, StoreBattingStatRequest $request): RedirectResponse
    {
        try {
            $battingStat = $this->battingStatService->create($game, $request->validated());
            $this->battingConfirmationState->clearCreatePayload($request->session(), $game);
            Log::info('打撃登録完了');

            if ($request->boolean('fromEdit')) {
                return redirect()
                    ->route('batting.index', ['game' => $game, 'statsId' => $battingStat->id])
                    ->with('message', '打撃成績を登録しました');
            }

            return redirect()
                ->route('batting.create', ['game' => $game])
                ->with('last_batting_stat_id', $battingStat->id);
        } catch (BattingStatConflictException $e) {
            $this->battingConfirmationState->storeCreatePayload($request->session(), $game, $request->all());

            return redirect()
                ->route('batting.create', ['game' => $game])
                ->withInput()
                ->with('batting_confirmation', [
                    'title' => $e->title,
                    'message' => $e->getMessage(),
                    'resolution' => $e->resolution,
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

    public function edit(Request $request, BattingStats $batting)
    {
        $this->battingConfirmationState->clearEditPayloadIfNoConfirmation($request->session(), $batting);

        return view('batting.edit', $this->battingStatService->getEditData($batting));
    }

    public function update(UpdateBattingStatRequest $request, BattingStats $batting): RedirectResponse
    {
        try {
            $updatedBatting = $this->battingStatService->update($batting, $request->validated());
            $this->battingConfirmationState->clearEditPayload($request->session(), $batting);
            Log::info('打撃更新完了');

            if ($request->input('returnTo') === 'create') {
                return redirect()
                    ->route('batting.create', ['game' => $updatedBatting->gameId])
                    ->with('last_batting_stat_id', $updatedBatting->id);
            }

            return redirect()
                ->route('batting.edit', ['batting' => $updatedBatting])
                ->with('message', '打撃成績を更新しました');
        } catch (BattingStatConflictException $e) {
            $this->battingConfirmationState->storeEditPayload($request->session(), $batting, $request->all());
            $routeParameters = ['batting' => $batting];

            if ($request->input('returnTo') === 'create') {
                $routeParameters['returnTo'] = 'create';
            }

            return redirect()
                ->route('batting.edit', $routeParameters)
                ->withInput()
                ->with('batting_confirmation', [
                    'title' => $e->title,
                    'message' => $e->getMessage(),
                    'resolution' => $e->resolution,
                ]);
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::debug($e->getMessage());

            return redirect()->back()->withInput()->with('error', '打撃成績の更新中にエラーが発生しました。');
        }
    }

    public function destroy(Request $request, BattingStats $batting): RedirectResponse
    {
        try {
            $game = $this->battingStatService->delete($batting);
            Log::info('打撃削除完了');

            if ($request->input('returnTo') === 'create') {
                return redirect()
                    ->route('batting.create', ['game' => $game])
                    ->with('message', '直前の打撃成績を取り消しました');
            }

            return redirect()
                ->route('batting.index', ['game' => $game])
                ->with('message', '打撃成績を削除しました');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::debug($e->getMessage());

            return redirect()->back()->with('error', '打撃成績の削除中にエラーが発生しました。');
        }
    }
}
