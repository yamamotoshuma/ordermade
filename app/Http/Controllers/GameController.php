<?php

namespace App\Http\Controllers;

use App\Models\BattingOrder;
use App\Models\Game;
use App\Models\Point;
use App\Models\BattingStats;
use App\Models\pitchingStats;
use App\Models\Steal;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    // リクエストから年度を取得
    $year = $request->input('year');

    // 年度が指定された場合、その年度で絞り込む
    if ($year) {
        $games = Game::where('year', $year)->orderBy('gameDates','desc')->get();
        $points = Point::all();
    } else {
        // 年度が指定されない場合、現在の年度を取得してデータを表示
        $currentYear = date('Y'); // 現在の年度を取得
        $games = Game::where('year', $currentYear)->orderBy('gameDates','desc')->get();
        $points = Point::all();
    }

    return view('game.index', compact('games', 'points'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('game.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    // バリデーションルールを定義
    $rules = [
        'gameName' => 'required|string|max:255',
        'year' => 'required|integer',
        'gameDates' => 'required|date',
        'enemyName' => 'required|string|max:255',
        'gameFirstFlg' => 'required|in:0,1',
    ];

    // バリデーションを実行
    $request->validate($rules);

    try {
        // トランザクションを開始
        DB::beginTransaction();
        // ゲームを作成
        $game = new Game();
        $game->gameName = $request->input('gameName');
        $game->year = $request->input('year');
        $game->gameDates = $request->input('gameDates');
        $game->enemyName = $request->input('enemyName');
        $game->gameFirstFlg = $request->input('gameFirstFlg');

        $game->save();

        // トランザクションをコミット
        DB::commit();

        Log::info('新規試合を登録しました。', ['user' => Auth::user()->id, 'game' => $game->id]);

        // 成功メッセージを設定してリダイレクト
        return redirect()->route('game.index')->with('message', 'ゲームが登録されました');
    } catch (Exception $e) {
        // トランザクションをロールバック
        DB::rollback();

        // try内で発生したエラー内容を表示してくれる
        Log::debug($e->getMessage());

        // エラーメッセージを設定してリダイレクト
        return redirect()->route('game.create')->with('error', 'ゲームの登録中にエラーが発生しました');
    }
}

    /**
     * Display the specified resource.
     */
    public function show(game $game)
    {
        //
        $points = Point::where('gameId', $game->gameId)->get();
        $orders = BattingOrder::where('gameId', $game->gameId)
            ->with('position', 'user')
            ->orderBy('battingOrder', 'asc') // 打順の昇順に並び替え
            ->get();
        $battingStats = BattingStats::where('gameId', $game->gameId)
            ->with('user','result1','result2','result3','result4','result5')
            ->get();

        $stealCounts = Steal::select('userId', DB::raw('count(*) as count'))
            ->where('gameId', $game->gameId)
            ->whereNotNull('userId')
            ->groupBy('userId')
            ->get();

        $pitchingStats = pitchingStats::where('gameId', $game->gameId)->with('user','game')->orderBy('pitchingOrder','asc')->get();

        return view('game.show', compact('game','points','orders','battingStats','stealCounts','pitchingStats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(game $game)
    {
        //
        $points = Point::where('gameId', $game->gameId)->get();
        return view('game.edit', compact('game','points'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, game $game)
    {
        //
        $request->validate([
            'gameName' => 'required|string',
            'year' => 'required|integer',
            'gameDates' => 'required|date',
            'enemyName' => 'required|string',
            'gameFirstFlg' => 'required|in:0,1',
        ]);

        try{
            // トランザクションを開始
            DB::beginTransaction();

            $game->gameName = $request->input('gameName');
            $game->year = $request->input('year');
            $game->gameDates = $request->input('gameDates');
            $game->enemyName = $request->input('enemyName');
            $game->gameFirstFlg = $request->input('gameFirstFlg');
            $game->winFlg = $request->input('winFlg');

            $game->save();

             // トランザクションをコミット
            DB::commit();
            Log::info('試合基本情報を編集しました。', ['user' => Auth::user()->id, 'game' => $game->id]);

            return redirect()->route('game.edit', ['game' => $game])->with('success', 'ゲーム情報が更新されました');

        }catch (Exception $e) {
            DB::rollback();
            Log::debug($e->getMessage());

            // エラーメッセージを設定してリダイレクト
            return redirect()->back()->with('error', 'ゲームの基本情報更新中にエラーが発生しました。');
        }
    }

    public function bulkUpdateOrInsert(Request $request)
    {
        $request->validate([
            'inning' => 'required|array',
            'gameId' => 'required|exists:games,gameId',
        ]);

        try{
            DB::beginTransaction();

            $gameId = $request->input('gameId');
            $inningData = $request->input('inning');

            foreach ($inningData as $inningNumber => $inningScores) {
                foreach ($inningScores as $inningSide => $score) {
                    //nullの時は無視する
                    if($score !== null){
                        // Pointレコードの存在をチェック
                        $existingPoint = Point::where('gameId', $gameId)
                            ->where('inning', $inningNumber)
                            ->where('inning_side', $inningSide)
                            ->first();

                        if ($existingPoint) {
                            // 既存のレコードが存在する場合は更新
                            $existingPoint->score = $score;
                            $existingPoint->save();
                        } else {
                            // 既存のレコードが存在しない場合は新規作成
                            Point::create([
                                'gameId' => $gameId,
                                'inning' => $inningNumber,
                                'inning_side' => $inningSide,
                                'score' => $score,
                            ]);
                        }
                    }
                }
            }
            // トランザクションをコミット
            DB::commit();
            Log::info('試合のスコアを編集しました。', ['user' => Auth::user()->id, 'game' => $gameId]);

            return redirect()->back()->with('success', '点数が一括更新/登録されました');
        }catch(Exception $e) {
            DB::rollback();
            Log::debug($e->getMessage());

            // エラーメッセージを設定してリダイレクト
            return redirect()->back()->with('error', 'ゲームのスコア登録中にエラーが発生しました。');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(game $game)
    {
        //
        $game->delete();

        return redirect()->route('game.index')
        ->with('success', 'ゲーム情報が削除されました');
    }
}
