<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDisburCategoryRequest;
use App\Services\DisburCategoryService;
use Illuminate\Http\RedirectResponse;
use Throwable;

class dCategoryController extends Controller
{
    public function __construct(
        private readonly DisburCategoryService $disburCategoryService
    ) {
    }

    public function index()
    {
        return view('disburCategories.index', $this->disburCategoryService->getIndexData());
    }

    public function create()
    {
        return view('disburCategories.create');
    }

    /**
     * カテゴリ重複判定を Service 側へ切り出す。
     */
    public function store(StoreDisburCategoryRequest $request): RedirectResponse
    {
        try {
            $this->disburCategoryService->create($request->validated());

            return redirect()->route('dcategory.create')->with('message', 'カテゴリを作成しました');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->withErrors([
                'duplicate' => $this->disburCategoryService->toUserMessage($e, 'カテゴリ作成中にエラーが発生しました。'),
            ]);
        }
    }

    public function show($disburCategories)
    {
    }

    public function edit($disburCategories)
    {
        //
    }

    public function update($request, $disburCategories)
    {
        //
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $this->disburCategoryService->delete((int) $id);

            return redirect()->route('dcategory.index')->with('message', 'カテゴリを削除しました');
        } catch (Throwable $e) {
            return redirect()->back()->withErrors([
                'duplicate' => $this->disburCategoryService->toUserMessage($e, 'カテゴリ削除中にエラーが発生しました。'),
            ]);
        }
    }
}
