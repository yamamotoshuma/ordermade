<?php

namespace App\Http\Controllers;

use App\Models\disbur;
use App\Models\disburCategories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class dCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $dc = disburCategories::all();
        return view('disburCategories.index', compact('dc'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('disburCategories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $inputs = $request->validate([
                'Mcode' => 'required',
                'Mname' => 'required',
                'Scode' => 'required',
                'Sname' => 'required'
            ]);

            $existingPayment = disburCategories::where([
                'Mcode' => $request->Mcode,
                'Scode' => $request->Scode
            ])->first();

            if ($existingPayment) {
                return redirect()->back()->withErrors(['duplicate' => '指定されたカテゴリコードは既に登録されています。']);
            }

            $dc = new disburCategories();
            $dc->Mcode = $inputs['Mcode'];
            $dc->Mname = $inputs['Mname'];
            $dc->Scode = $inputs['Scode'];
            $dc->Sname = $inputs['Sname'];
            $dc->save();
            DB::commit();
            return redirect()->route('dcategory.create')->with('message', 'カテゴリを作成しました');
        } catch (Throwable $e) {
            DB::rollback();

            // DB例外の場合
            if ($e instanceof \Illuminate\Database\QueryException) {
                redirect()->back()->withErrors(['duplicate' => '他の人が登録している可能性があります。']);
            } else {
                $errorMessage = $e->getMessage();
                return view('error', compact('errorMessage'));
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(disburCategories $disburCategories)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(disburCategories $disburCategories)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, disburCategories $disburCategories)
    {
        //

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        try {
            DB::beginTransaction();
            $disburCategories = disburCategories::find($id);
            $disburCategories->delete();
            DB::commit();
            return redirect()->route('dcategory.index')->with('message', 'カテゴリを削除しました');
        } catch (Throwable $e) {
            DB::rollback();

            // DB例外の場合
            if ($e instanceof \Illuminate\Database\QueryException) {
                redirect()->back()->withErrors(['duplicate' => '他の人が登録している可能性があります。']);
            } else {
                $errorMessage = $e->getMessage();
                return view('error', compact('errorMessage'));
            }
        }
    }
}
