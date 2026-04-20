<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePitchingStatRequest;
use App\Http\Requests\UpdatePitchingStatNumberRequest;
use App\Http\Requests\UpdatePitchingStatRequest;
use App\Models\Game;
use App\Models\pitchingStats;
use App\Services\PitchingStatService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PitchingStatsController extends Controller
{
    public function __construct(
        private readonly PitchingStatService $pitchingStatService
    ) {
    }

    public function index(Game $game)
    {
        return view('pitching.index', $this->pitchingStatService->getIndexData($game));
    }

    public function create($gameId)
    {
        return view('pitching.create', $this->pitchingStatService->getCreateData((int) $gameId));
    }

    /**
     * 新規登録の実体は Service 側で持つ。
     */
    public function store(StorePitchingStatRequest $request, $gameId): RedirectResponse
    {
        try {
            $pitching = $this->pitchingStatService->create((int) $gameId, $request->validated());
            Log::info('投手成績を登録しました。', ['user' => Auth::id(), 'game' => $pitching->id]);

            return redirect()->route('pitching.create', ['gameId' => $gameId])->with('message', '投手成績が登録されました');
        } catch (Exception $e) {
            Log::debug($e->getMessage());

            return redirect()->back()->withInput()->with('error', '投手成績の登録中にエラーが発生しました。');
        }
    }

    public function destroy(pitchingStats $pitching): RedirectResponse
    {
        try {
            $game = $this->pitchingStatService->delete($pitching);
            Log::info('投手成績を削除しました。', ['user' => Auth::id(), 'game' => $game->gameId]);

            return redirect()->route('pitching', ['game' => $game])->with('message', '投手成績を削除しました');
        } catch (Exception $e) {
            Log::debug($e->getMessage());

            return redirect()->back()->with('error', '投手成績の削除中にエラーが発生しました。');
        }
    }

    public function edit(pitchingStats $pitching)
    {
        return view('pitching.edit', $this->pitchingStatService->getEditData($pitching));
    }

    /**
     * 基本項目更新を Service に寄せる。
     */
    public function update(UpdatePitchingStatRequest $request, pitchingStats $pitching): RedirectResponse
    {
        try {
            $updatedPitching = $this->pitchingStatService->update($pitching, $request->validated());
            Log::info('投手成績を更新しました。', ['user' => Auth::id(), 'game' => $updatedPitching->id]);

            return redirect()->route('pitching.edit', ['pitching' => $updatedPitching])->with('message', '投手成績が更新されました');
        } catch (Exception $e) {
            Log::debug($e->getMessage());

            return redirect()->back()->withInput()->with('error', '投手成績の更新中にエラーが発生しました。');
        }
    }

    /**
     * 個別数値の更新も Service に委譲する。
     */
    public function updateNumber(UpdatePitchingStatNumberRequest $request, pitchingStats $pitching): RedirectResponse
    {
        try {
            $updatedPitching = $this->pitchingStatService->updateNumber($pitching, $request->validated());
            Log::info('投手成績を更新しました。', ['user' => Auth::id(), 'game' => $updatedPitching->id]);

            return redirect()->route('pitching.edit', ['pitching' => $updatedPitching])->with('message', '投手成績が更新されました');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (Exception $e) {
            Log::debug($e->getMessage());

            return redirect()->back()->with('error', '投手成績の更新中にエラーが発生しました。');
        }
    }
}
