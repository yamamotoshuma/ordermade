<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisbursementActionRequest;
use App\Http\Requests\IndexDisbursementRequest;
use App\Services\DisbursementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class disburController extends Controller
{
    public function __construct(
        private readonly DisbursementService $disbursementService
    ) {
    }

    /**
     * 出金一覧の集計取得を Service へ切り出す。
     */
    public function index(IndexDisbursementRequest $request)
    {
        $year = (int) ($request->validated('year') ?? date('Y'));

        return view('disbur.index', $this->disbursementService->getIndexData($year));
    }

    public function create()
    {
        return view('disbur.create', $this->disbursementService->getCreateData());
    }

    /**
     * 登録と削除検索の分岐だけを持つ薄いコントローラにする。
     */
    public function store(DisbursementActionRequest $request)
    {
        try {
            if ($request->has('create')) {
                $this->disbursementService->create($request->validated());

                return redirect()->route('disbur.create')->with('message', '出金を登録しました');
            }

            if ($request->has('delete')) {
                $disburDelete = $this->disbursementService->findForDeletion($request->validated());

                return view('disbur.delete', compact('disburDelete'));
            }

            return redirect()->back()->withErrors(['action' => '実行する操作を選択してください。']);
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->withErrors([
                'duplicate' => $this->disbursementService->toUserMessage($e, '出金処理中にエラーが発生しました。'),
            ]);
        }
    }

    public function show($disbur)
    {
        //
    }

    public function edit($disbur)
    {
        //
    }

    public function update(Request $request, $disbur)
    {
        //
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $this->disbursementService->delete((int) $id);

            return redirect()->route('disbur.create')->with('message', '出金を削除しました');
        } catch (Throwable $e) {
            return redirect()->back()->withErrors([
                'duplicate' => $this->disbursementService->toUserMessage($e, '出金削除中にエラーが発生しました。'),
            ]);
        }
    }

    /**
     * 小分類取得 API も Service に寄せる。
     */
    public function getScode(Request $request)
    {
        return response()->json(
            $this->disbursementService->getSmallCategories((string) $request->get('category'))
        );
    }
}
