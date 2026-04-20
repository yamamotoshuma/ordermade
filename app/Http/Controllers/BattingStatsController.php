<?php

namespace App\Http\Controllers;

use App\Services\BattingStatsSummaryService;
use Illuminate\Http\Request;

class BattingStatsController extends Controller
{
    public function __construct(
        private readonly BattingStatsSummaryService $battingStatsSummaryService
    ) {
    }

    /**
     * 個人成績の集計クエリは専用 Service に閉じ込める。
     */
    public function index(Request $request)
    {
        return view('battingStats.index', $this->battingStatsSummaryService->getIndexData($request));
    }
}
