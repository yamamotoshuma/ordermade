<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BattingOrder;
use App\Models\game;
use App\Models\user;
use App\Models\Positions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Rfc4122\Validator;

class BattingOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(game $game)
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // トランザクション開始
            DB::beginTransaction();
            $gameId = $request->input('gameId');

            // バリデーションルールの設定
            $rules = [
                'battingOrder' => 'required',
                'positionId' => 'required',
                'userId' => 'required_without:userName', // userName がない場合 userId は必須
                'userName' => 'required_without:userId', // userId がない場合 userName は必須
                'ranking' => 'required',
            ];

            $messages = [
                'required' => ':attribute フィールドは必須です。',
                'required_without' => ':attribute フィールドは、:values のいずれかが存在する場合、必須です。',
            ];

            $request->validate($rules, $messages);

            $data = [];
            foreach ($request->input('battingOrder') as $key => $battingOrder) {
                $positionId = $request->input('positionId')[$key];
                $userId = $request->input('userId')[$key];
                $userName = $request->input('userName')[$key];
                $ranking = $request->input('ranking')[$key];

                if($userId && $userName) {
                    DB::rollBack();
                    return redirect()->route('order.edit', ['order' => $gameId])->with('error', 'ユーザーIDとユーザー名は同時に入力しないでください。');
                }

                // すべての入力がない行は無視
                if ($positionId || ($userId && $userName) || $ranking) {
                    $data[] = [
                        'gameId' => $gameId,
                        'battingOrder' => $battingOrder,
                        'positionId' => $positionId,
                        'userId' => $userId,
                        'userName' => $userName,
                        'ranking' => $ranking,
                    ];
                }
            }

            // データの一括登録または更新
            BattingOrder::where('gameId', $gameId)->delete();
            BattingOrder::insert($data);

            // トランザクションのコミット
            DB::commit();
            return redirect()->route('order.edit', ['order' => $gameId])->with('message', 'データが保存されました');
        } catch (Exception $e) {
            // その他の例外が発生した場合の処理
            DB::rollBack();
            // エラーメッセージやログ出力などの適切な処理を追加
            return redirect()->back()->with('error', 'データの保存中にエラーが発生しました');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
        //
        $positions = Positions::All();
        $orders = BattingOrder::where("gameId", $id)->with('position','user')->get();
        $users = User::where('active_flg', 1)->get();
        return view("order.edit", compact("orders","positions","users","id"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
