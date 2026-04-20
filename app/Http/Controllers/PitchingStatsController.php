<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use App\Models\pitchingStats;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PitchingStatsController extends Controller
{
    //
    public function index(Game $game){
        $pitchingStats = pitchingStats::where('gameId', $game->gameId)->with('user','game')->orderBy('pitchingOrder','asc')->get();

        return view('pitching.index', compact('pitchingStats','game'));

    }

    public function create($gameId){
        $users = User::where('active_flg', 1)->get();
        return view('pitching.create', compact('gameId','users'));
    }

    public function store(Request $request, $gameId){
        $request->validate([
            'pitchingOrder' => 'required|integer',
            'userId' => 'required',
            'result' => '',
            'save' => '',
        ]);

        try {
            // トランザクションを開始
            DB::beginTransaction();

            // 対応するモデルをインスタンス化
            $pitching = new PitchingStats();

            // モデルに値をセット
            $pitching->gameId = $gameId;
            $pitching->pitchingOrder = $request->input('pitchingOrder');
            $pitching->userId = $request->input('userId');
            $pitching->result = $request->input('result');
            $pitching->save = $request->input('save');

            // モデルを保存
            $pitching->save();

            // トランザクションをコミット
            DB::commit();
            Log::info('投手成績を登録しました。', ['user' => Auth::user()->id, 'game' => $pitching->id]);

            return redirect()->route('pitching.create', ['gameId' => $gameId])->with('message', '投手成績が登録されました');

        } catch (Exception $e) {
            DB::rollback();
            Log::debug($e->getMessage());

            // エラーメッセージを設定してリダイレクト
            return redirect()->back()->with('error', '投手成績の登録中にエラーが発生しました。');
        }
    }

    public function destroy(pitchingStats $pitching){
        try {
            // トランザクション開始
            DB::beginTransaction();
            $game = Game::find($pitching->gameId);
            $pitching->delete();
            // トランザクションのコミット
            DB::commit();
            Log::info("打撃削除完了");
            return redirect()->route('pitching', ['game' => $game])->with('message', '投手成績を削除しました');
        } catch (Exception $e) {
            // エラーハンドリング
            // エラーメッセージを設定してリダイレクト
            DB::rollback();
            Log::debug($e->getMessage());
            return redirect()->back()->with('error', '投手成績の削除中にエラーが発生しました。');
        }
    }

    public function edit(pitchingStats $pitching){
        $users = User::where('active_flg', 1)->get();
        return view('pitching.edit', compact('pitching','users'));
    }

    public function update(Request $request, pitchingStats $pitching) // $pitchingStat を引数として受け取る
    {
        $request->validate([
            'pitchingOrder' => 'required|integer', // 'gameName' を 'pitchingOrder' に修正
            'userId' => 'required', // 'userId' を 'userId' に修正
            'result' => '', // 'result' を 'result' に修正
            'save' => '', // 'gameFirstFlg' を 'save' に修正
        ]);

        try {
            // トランザクションを開始
            DB::beginTransaction();

            // 以下の行を修正
            $pitching->pitchingOrder = $request->input('pitchingOrder');
            $pitching->userId = $request->input('userId');
            $pitching->result = $request->input('result');
            $pitching->save = $request->input('save');

            $pitching->save();

            // トランザクションをコミット
            DB::commit();
            Log::info('投手成績を更新しました。', ['user' => Auth::user()->id, 'game' => $pitching->id]);

            return redirect()->route('pitching.edit', ['pitching' => $pitching])->with('message', '投手成績が更新されました');

        } catch (Exception $e) {
            DB::rollback();
            Log::debug($e->getMessage());

            // エラーメッセージを設定してリダイレクト
            return redirect()->back()->with('error', '投手成績の更新中にエラーが発生しました。');
        }
    }

    public function updateNumber(Request $request, pitchingStats $pitching){
        try {
            // トランザクションを開始
            DB::beginTransaction();

            // 選択した項目に応じて増減を行う
            switch($request->input('type')) {
                case 'inning':
                    $pitching->inning = $request->input('inning');
                    break;
                case 'hitsAllowed':
                    $pitching->hitsAllowed = $request->input('hitsAllowed');
                    break;
                case 'homeRunsAllowed':
                    $pitching->homeRunsAllowed = $request->input('homeRunsAllowed');
                    break;
                case 'strikeouts':
                    $pitching->strikeouts = $request->input('strikeouts');
                    break;
                case 'walks':
                    $pitching->walks = $request->input('walks');
                    break;
                case 'wildPitches':
                    $pitching->wildPitches = $request->input('wildPitches');
                    break;
                case 'balks':
                    $pitching->balks = $request->input('balks');
                    break;
                case 'runsAllowed':
                    $pitching->runsAllowed = $request->input('runsAllowed');
                    break;
                case 'earnedRuns':
                    $pitching->earnedRuns = $request->input('earnedRuns');
                    break;
                // 他の項目に対する処理も同様に追加
            }

            $pitching->save();

            // トランザクションをコミット
            DB::commit();
            Log::info('投手成績を更新しました。', ['user' => Auth::user()->id, 'game' => $pitching->id]);

            return redirect()->route('pitching.edit', ['pitching' => $pitching])->with('message', '投手成績が更新されました');

        } catch (Exception $e) {
            DB::rollback();
            Log::debug($e->getMessage());

            // エラーメッセージを設定してリダイレクト
            return redirect()->back()->with('error', '投手成績の更新中にエラーが発生しました。');
        }
    }
}
