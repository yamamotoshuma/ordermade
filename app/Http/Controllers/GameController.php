<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkUpdateGameScoreRequest;
use App\Http\Requests\StoreGameRequest;
use App\Http\Requests\UpdateGameRequest;
use App\Models\Game;
use App\Services\GameService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    public function __construct(
        private readonly GameService $gameService
    ) {
    }

    /**
     * 年フィルタ付きの試合一覧取得を Service に委譲する。
     */
    public function index(Request $request)
    {
        return view('game.index', $this->gameService->getIndexData(
            $request->filled('year') ? (int) $request->input('year') : null
        ));
    }

    public function create()
    {
        return view('game.create');
    }

    /**
     * 試合登録処理は Service 側へまとめる。
     */
    public function store(StoreGameRequest $request): RedirectResponse
    {
        try {
            $game = $this->gameService->create($request->validated());
            Log::info('新規試合を登録しました。', ['user' => Auth::id(), 'game' => $game->gameId]);

            return redirect()->route('game.index')->with('message', 'ゲームが登録されました');
        } catch (Exception $e) {
            Log::debug($e->getMessage());

            return redirect()->route('game.create')->with('error', 'ゲームの登録中にエラーが発生しました');
        }
    }

    public function show(Game $game)
    {
        return view('game.show', $this->gameService->getShowData($game));
    }

    public function edit(Game $game)
    {
        return view('game.edit', $this->gameService->getEditData($game));
    }

    /**
     * 基本情報更新のトランザクションも Service へ移す。
     */
    public function update(UpdateGameRequest $request, Game $game): RedirectResponse
    {
        try {
            $updatedGame = $this->gameService->update($game, $request->validated());
            Log::info('試合基本情報を編集しました。', ['user' => Auth::id(), 'game' => $updatedGame->gameId]);

            return redirect()->route('game.edit', ['game' => $updatedGame])->with('success', 'ゲーム情報が更新されました');
        } catch (Exception $e) {
            Log::debug($e->getMessage());

            return redirect()->back()->with('error', 'ゲームの基本情報更新中にエラーが発生しました。');
        }
    }

    /**
     * 点数表の一括更新も Service 側へ切り出す。
     */
    public function bulkUpdateOrInsert(BulkUpdateGameScoreRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $gameId = (int) $validated['gameId'];

            $this->gameService->upsertScores($gameId, $validated['inning']);
            Log::info('試合のスコアを編集しました。', ['user' => Auth::id(), 'game' => $gameId]);

            return redirect()->back()->with('success', '点数が一括更新/登録されました');
        } catch (Exception $e) {
            Log::debug($e->getMessage());

            return redirect()->back()->with('error', 'ゲームのスコア登録中にエラーが発生しました。');
        }
    }

    public function destroy(Game $game): RedirectResponse
    {
        $this->gameService->delete($game);

        return redirect()->route('game.index')->with('success', 'ゲーム情報が削除されました');
    }
}
