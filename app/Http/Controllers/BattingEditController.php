<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BattingStats;
use App\Models\Game;
use App\Models\Point;
use App\Models\BattingOrder;
use App\Models\User;
use App\Models\BattingResultMaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class BattingEditController extends Controller
{
    public function index(game $game, Request $request){
        //
        $points = Point::where('gameId', $game->gameId)->get();
        $orders = BattingOrder::where('gameId', $game->gameId)
            ->with('position', 'user')
            ->orderBy('battingOrder', 'asc')
            ->orderBy('ranking', 'asc')
            ->get();
        $battingStats = BattingStats::where('gameId', $game->gameId)
            ->with('user','result1','result2','result3','result4','result5')
            ->get();

        $statsId = $request->input('statsId', ''); // クエリパラメータからstatsIdを取得

        return view('batting.index', compact('game','points','orders','battingStats', 'statsId')); // statsIdをviewに渡す
    }

    public function create(game $game, Request $request){
        $orders = BattingOrder::where('gameId', $game->gameId)
            ->with('user')
            ->orderBy('battingOrder')
            ->orderBy('ranking')
            ->get();

        $userIdsInOrder = $orders->pluck('userId')->filter()->unique()->values();

        $users = User::where('active_flg', 1)
                    ->whereIn('id', $userIdsInOrder)
                    ->get();

        $results = BattingResultMaster::all();

        $battingStats = BattingStats::where('gameId', $game->gameId)
            ->with('result1')
            ->orderBy('inning')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $createDefaults = $this->buildCreateDefaults($orders, $users, $battingStats);

        return view("batting.create", compact("game", "users", "results", "orders", "createDefaults"));
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

        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
            // エラーハンドリング

            // エラーメッセージを設定してリダイレクト
            DB::rollback();
            Log::debug($e->getMessage());
            return redirect()->back()->with('error', '打撃成績の削除中にエラーが発生しました。');
        }

    }

    private function buildCreateDefaults(Collection $orders, Collection $users, Collection $battingStats): array
    {
        $inningOutCounts = $this->buildInningOutCounts($battingStats);
        $defaultInning = 1;

        if ($battingStats->isNotEmpty()) {
            $latestStat = $battingStats->last();
            $latestInning = (int) $latestStat->inning;
            $defaultInning = $latestInning + (($inningOutCounts[$latestInning] ?? 0) >= 3 ? 1 : 0);
        }

        $selectableOrders = $orders->filter(function ($order) use ($users) {
            if ($order->userId) {
                return $users->contains('id', $order->userId);
            }

            return filled($order->userName);
        })->values();

        $nextOrder = $this->resolveNextOrder($selectableOrders, $battingStats);

        return [
            'defaultUserId' => $nextOrder?->userId ? (string) $nextOrder->userId : '',
            'defaultUserName' => $nextOrder && ! $nextOrder->userId ? (string) $nextOrder->userName : '',
            'defaultInning' => $defaultInning,
            'inningOutCounts' => $inningOutCounts,
        ];
    }

    private function resolveNextOrder(Collection $orders, Collection $battingStats): ?BattingOrder
    {
        if ($orders->isEmpty()) {
            return null;
        }

        if ($battingStats->isEmpty()) {
            return $orders->first();
        }

        $orderKeys = $orders->map(function ($order) {
            return $this->makeOrderKey($order->userId, $order->userName);
        })->all();

        foreach ($battingStats->reverse() as $stat) {
            $matchedIndex = array_search($this->makeOrderKey($stat->userId, $stat->userName), $orderKeys, true);

            if ($matchedIndex === false) {
                continue;
            }

            $nextIndex = ($matchedIndex + 1) % $orders->count();

            return $orders->get($nextIndex);
        }

        return $orders->first();
    }

    private function buildInningOutCounts(Collection $battingStats): array
    {
        $outCounts = [];

        foreach ($battingStats as $stat) {
            $inning = (int) $stat->inning;
            $outCounts[$inning] = ($outCounts[$inning] ?? 0) + $this->resultOutCount((string) optional($stat->result1)->name);
        }

        ksort($outCounts);

        return $outCounts;
    }

    private function resultOutCount(string $resultName): int
    {
        return match (trim($resultName)) {
            'ゴロ', 'フライ', '三振', 'ライナー', '犠打', '犠飛' => 1,
            '併殺' => 2,
            '三重殺' => 3,
            default => 0,
        };
    }

    private function makeOrderKey(?int $userId, ?string $userName): string
    {
        if ($userId) {
            return 'id:' . $userId;
        }

        return 'name:' . trim((string) $userName);
    }
}
