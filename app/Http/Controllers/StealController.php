<?php

namespace App\Http\Controllers;

use App\Models\Steal;
use App\Models\Game;
use App\Models\BattingOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class StealController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Game $game)
    {
        //

        $battingOrders = BattingOrder::where("gameId", $game->gameId)
        ->with('position', 'user')
        ->orderBy('battingOrder', 'asc')
        ->get();

        $stealCounts = Steal::select('userId', DB::raw('count(*) as count'))
            ->where('gameId', $game->gameId)
            ->whereNotNull('userId')
            ->groupBy('userId')
            ->get();

        return view("steal.index", compact("stealCounts","game","battingOrders"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $userId = $request->input('userId');
        $gameId = $request->input('gameId'); // ゲームIDを設定してください

        try {
            // トランザクションの開始
            DB::beginTransaction();

            // スチール数を増やす処理
            $steal = new Steal();
            $steal->gameId = $gameId;
            $steal->userId = $userId;

            $steal->save();

            // トランザクションをコミット
            DB::commit();

            return redirect()->back()->with('message', '盗塁数を増やしました');
        } catch (Exception $e) {
            // トランザクションのロールバック
            DB::rollBack();

            return redirect()->back()->with('error', '盗塁数の増加中にエラーが発生しました');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Steal $steal)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Steal $steal)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Steal $steal)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        //
        $userId = $request->input('userId');
        $gameId = $request->input('gameId'); // ゲームIDを設定してください

        // トランザクションの開始
        DB::beginTransaction();

        try {
            // スチール数を減らす処理
            Steal::where('userId', $userId)
                ->where('gameId', $gameId)
                ->orderBy('created_at', 'desc') // 最新のスチールを削除
                ->first()
                ->delete();

            // トランザクションをコミット
            DB::commit();

            return redirect()->back()->with('message', 'スチール数を減らしました');
        } catch (Exception $e) {
            // トランザクションのロールバック
            DB::rollBack();

            return redirect()->back()->with('error', 'スチール数の減少中にエラーが発生しました');
        }
    }
}
