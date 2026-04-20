<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BattingStats;
use App\Models\Game;
use App\Models\Point;
use App\Models\BattingOrder;
use App\Models\User;
use App\Models\BattingResultMaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BattingEditController extends Controller
{
    public function index(game $game, Request $request){
        //
        $points = Point::where('gameId', $game->gameId)->get();
        $orders = BattingOrder::where('gameId', $game->gameId)
            ->with('position', 'user')
            ->orderBy('battingOrder', 'asc') // 打順の昇順に並び替え
            ->get();
        $battingStats = BattingStats::where('gameId', $game->gameId)
            ->with('user','result1','result2','result3','result4','result5')
            ->get();

        $statsId = $request->input('statsId', ''); // クエリパラメータからstatsIdを取得

        return view('batting.index', compact('game','points','orders','battingStats', 'statsId')); // statsIdをviewに渡す
    }

    public function create(game $game){
        // ゲームに関連するオーダーを取得
        $orders = BattingOrder::where('gameId', $game->gameId)->orderBy('battingOrder')->get();

        // オーダーに存在するユーザーのIDを取得
        $userIdsInOrder = $orders->pluck('userId');

        // ユーザーの中からオーダーに存在するユーザーのみを取得
        $users = User::where('active_flg', 1)
                    ->whereIn('id', $userIdsInOrder)
                    ->get();

        $results = BattingResultMaster::all();

        $maxInning = BattingStats::where('gameId', $game->gameId)->max('inning');
        if ($maxInning === null || $maxInning === 0) {
            $maxInning = 1;
        }

        return view("batting.create", compact("game", "users", "results","orders","maxInning"));
    }

    public function store(game $game,Request $request){
        try {
            // トランザクション開始
            DB::beginTransaction();
            $gameId = $game->gameId;

            // バリデーションルールの設定
            $rules = [
                'userId' => 'required_without:userName', // userName がない場合 userId は必須
                'userName' => 'required_without:userId', // userId がない場合 userName は必須
                'inning' => 'required',
                'resultId1' => 'required',
                'resultId2' => 'required',
                'resultId3' => 'required',
            ];

            $messages = [
                'required' => ':attribute フィールドは必須です。',
                'required_without' => ':attribute フィールドは、:values のいずれかが存在する場合、必須です。',
            ];

            $request->validate($rules, $messages);

            if($request->userId && $request->userName){
                DB::rollBack();
                return redirect()->back()->with('error','ユーザーIDとユーザー名を同時に入力しないでください');
            }

            if($request->userId){
                $battingStat = BattingStats::where('userId',$request->userId)->where('inning', $request->inning)->where('gameId',$gameId)->first();
            }else{
                $battingStat = BattingStats::where('userName',$request->userName)->where('inning', $request->inning)->where('gameId',$gameId)->first();
            }

            if($battingStat){
                DB::rollBack();
                return redirect()->route('batting.create',['game' => $game])->with('error', 'すでに打撃データが存在します');
            }

            // バッティングスタッツを登録
            $battingStats = new BattingStats();
            $battingStats->gameId = $game->gameId;
            $battingStats->userId = $request->userId;
            $battingStats->userName = $request->userName;
            $battingStats->inning = $request->inning;
            $battingStats->resultId1 = $request->resultId1;
            $battingStats->resultId2 = $request->resultId2;
            $battingStats->resultId3 = $request->resultId3;

            // その他の必要なフィールドを設定

            $battingStats->save();
            // トランザクションのコミット
            DB::commit();
            Log::info("打撃登録完了");
            // fromEditのパラメータがtrueならbatting.indexにリダイレクト
            if ($request->fromEdit == true) {
                return redirect()->route('batting.index', ['game' => $game, 'statsId' => $battingStats->id])->with('message','打撃成績を登録しました');
            }
            return redirect()->route('batting.create',['game' => $game])->with('message','打撃成績を登録しました');

        } catch (Exception $e) {
            // その他の例外が発生した場合の処理
            DB::rollBack();
            // エラーメッセージやログ出力などの適切な処理を追加
            // try内で発生したエラー内容を表示してくれる
            Log::debug($e->getMessage());

            return redirect()->route('batting.create',['game' => $game])->with('error', 'データの保存中にエラーが発生しました');
        }

    }
    public function edit(string $id)
    {
        //
        $results = BattingResultMaster::all();
        $batting = BattingStats::where('id', $id)->with('game')->first();

        return view('batting.edit', compact('batting','results'));
    }

    public function update(Request $request, BattingStats $batting)
    {
        try {
            // トランザクション開始
            DB::beginTransaction();

            // バリデーションルールの設定
            $rules = [
                'resultId1' => 'required',
                'resultId2' => 'required',
                'resultId3' => 'required',
            ];

            $messages = [
                'required' => ':attribute フィールドは必須です。',
                'required_without' => ':attribute フィールドは、:values のいずれかが存在する場合、必須です。',
            ];

            $request->validate($rules, $messages);

            // 既存の $batting インスタンスを更新
            $batting->userId = $request->userId;
            $batting->userName = $request->userName;
            $batting->inning = $request->inning;
            $batting->resultId1 = $request->resultId1;
            $batting->resultId2 = $request->resultId2;
            $batting->resultId3 = $request->resultId3;

            // その他の必要なフィールドを設定

            $batting->save();

            // トランザクションのコミット
            DB::commit();
            Log::info("打撃更新完了");
            return redirect()->route('batting.edit', ['batting' => $batting])->with('message', '打撃成績を更新しました');
        } catch (Exception $e) {
            // エラーハンドリング

            // エラーメッセージを設定してリダイレクト
            DB::rollback();
            Log::debug($e->getMessage());
            return redirect()->back()->with('error', '打撃成績の更新中にエラーが発生しました。');
        }

    }

    public function destroy(BattingStats $batting)
    {
        try {
            // トランザクション開始
            DB::beginTransaction();

            $game = Game::find($batting->gameId);

            $batting->delete();

            // トランザクションのコミット
            DB::commit();
            Log::info("打撃削除完了");
            return redirect()->route('batting.index', ['game' => $game])->with('message', '打撃成績を削除しました');
        } catch (Exception $e) {
            // エラーハンドリング

            // エラーメッセージを設定してリダイレクト
            DB::rollback();
            Log::debug($e->getMessage());
            return redirect()->back()->with('error', '打撃成績の削除中にエラーが発生しました。');
        }

    }
}
