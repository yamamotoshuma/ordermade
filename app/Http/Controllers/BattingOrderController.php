<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBattingOrderRequest;
use App\Services\BattingOrderService;
use App\Services\GoogleSheetsOrderImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BattingOrderController extends Controller
{
    public function __construct(
        private readonly BattingOrderService $battingOrderService
    ) {
    }

    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    /**
     * 打順保存は Service に委譲し、コントローラは遷移制御だけを担う。
     */
    public function store(StoreBattingOrderRequest $request): RedirectResponse
    {
        $gameId = (string) $request->validated('gameId');

        try {
            $this->battingOrderService->store($gameId, $request->validated());

            return redirect()
                ->route('order.edit', ['order' => $gameId])
                ->with('message', 'データが保存されました');
        } catch (RuntimeException $e) {
            return redirect()
                ->route('order.edit', ['order' => $gameId])
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()
                ->route('order.edit', ['order' => $gameId])
                ->withInput()
                ->with('error', 'データの保存中にエラーが発生しました');
        }
    }

    /**
     * スプレッドシート反映の整形ロジックも Service 側へ寄せる。
     */
    public function importFromSpreadsheet(string $id, GoogleSheetsOrderImporter $importer): RedirectResponse
    {
        try {
            $this->battingOrderService->ensureGameExists($id);
            $message = $this->battingOrderService->importFromSpreadsheet($id, $importer->fetchOrderRows());

            return redirect()
                ->route('order.edit', ['order' => $id])
                ->with('message', $message);
        } catch (RuntimeException $e) {
            return redirect()->route('order.edit', ['order' => $id])->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->route('order.edit', ['order' => $id])->with('error', 'スプレッドシート反映中にエラーが発生しました');
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id, Request $request)
    {
        return view('order.edit', $this->battingOrderService->getEditData($id, $request->old()));
    }

    public function update(string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
