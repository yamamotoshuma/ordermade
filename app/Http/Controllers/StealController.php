<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyStealRequest;
use App\Http\Requests\StoreStealRequest;
use App\Models\Game;
use App\Services\StealService;
use Exception;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class StealController extends Controller
{
    public function __construct(
        private readonly StealService $stealService
    ) {
    }

    public function index(Game $game)
    {
        return view('steal.index', $this->stealService->getIndexData($game));
    }

    public function create()
    {
        //
    }

    /**
     * 盗塁追加の永続化処理は Service へ分離する。
     */
    public function store(StoreStealRequest $request): RedirectResponse
    {
        try {
            $this->stealService->create($request->validated());

            return redirect()->back()->with('message', '盗塁数を増やしました');
        } catch (Exception $e) {
            return redirect()->back()->with('error', '盗塁数の増加中にエラーが発生しました');
        }
    }

    public function show()
    {
        //
    }

    public function edit()
    {
        //
    }

    public function update()
    {
        //
    }

    /**
     * 直近の盗塁だけを取り消す仕様を Service に閉じ込める。
     */
    public function destroy(DestroyStealRequest $request): RedirectResponse
    {
        try {
            $this->stealService->deleteLatest($request->validated());

            return redirect()->back()->with('message', 'スチール数を減らしました');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'スチール数の減少中にエラーが発生しました');
        }
    }
}
