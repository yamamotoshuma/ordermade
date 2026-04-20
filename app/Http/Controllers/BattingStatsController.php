<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BattingStats;
use App\Models\Game;
use App\Models\User;
use App\Models\BattingResultMaster;

class BattingStatsController extends Controller
{
    //
    public function index(Request $request)
    {
        $query = BattingStats::join('games as g', 'batting_stats.gameId', '=', 'g.gameId')
            ->join('users as u', 'u.id', '=', 'batting_stats.userId')
            ->join('batting_result_masters as b1', 'b1.id', '=', 'batting_stats.resultId1')
            ->join('batting_result_masters as b2', 'b2.id', '=', 'batting_stats.resultId2')
            ->join('batting_result_masters as b3', 'b3.id', '=', 'batting_stats.resultId3')
            ->select(
                'u.name as 選手名',
                DB::raw('COUNT(CASE WHEN b1.type = 1 THEN 1 END) / COUNT(CASE WHEN b1.type = 1 THEN 1 WHEN b1.type = 3 THEN 1 END) as 打率'),
                DB::raw('(COUNT(CASE WHEN b1.type = 1 THEN 1 END) + COUNT(CASE WHEN b1.name = "四球" THEN 1 END) + COUNT(CASE WHEN b1.name = "死球" THEN 1 END)) / COUNT(*) as 出塁率'),
                DB::raw('COUNT(DISTINCT g.gameId) as 試合'),
                DB::raw('COUNT(*) as 打席'),
                DB::raw('COUNT(CASE WHEN b1.type = 1 THEN 1 WHEN b1.type = 3 THEN 1 END) as 打数'),
                DB::raw('COUNT(CASE WHEN b1.type = 1 THEN 1 END) as 安打'),
                DB::raw('COUNT(CASE WHEN b1.name = "二塁打" THEN 1 END) as 二塁打'),
                DB::raw('COUNT(CASE WHEN b1.name = "三塁打" THEN 1 END) as 三塁打'),
                DB::raw('COUNT(CASE WHEN b1.name = "本塁打" THEN 1 END) as 本塁打'),
                DB::raw('SUM(CASE WHEN b3.type = 5 THEN CAST(b3.name AS signed) END) as 打点'),
                DB::raw('COUNT(CASE WHEN b1.name = "四球" THEN 1 END) as 四球'),
                DB::raw('COUNT(CASE WHEN b1.name = "死球" THEN 1 END) as 死球'),
                DB::raw('COUNT(CASE WHEN b1.name = "三振" THEN 1 END) as 三振'),
                DB::raw('COUNT(CASE WHEN b1.name = "併殺" THEN 1 END) as 併殺')
                // 他の列も必要に応じて追加
            )
            ->groupBy('u.id', 'u.name');

        if ($request->has('year')) {
            $query->where('g.year', '=', $request->input('year'));
        }else{
            //ない場合は今年
            $query->where('g.year', '=', date('Y'));
        }

            // 各項目での並び替え
        $sortColumns = [
            '打率','出塁率', '試合', '打席', '打数', '安打',
            '二塁打', '三塁打', '本塁打', '打点',
            '四球', '死球', '三振', '併殺'
        ];

        $sortColumn = in_array($request->input('sort'), $sortColumns) ? $request->input('sort') : '打率';
        $sortDirection = $request->input('direction', 'desc');

        $years = Game::distinct('year')->pluck('year');

        $battingStats = $query->orderBy($sortColumn, $sortDirection)->get();

        return view('battingStats.index', compact('battingStats', 'years','sortColumns', 'sortColumn', 'sortDirection'));
    }
}
