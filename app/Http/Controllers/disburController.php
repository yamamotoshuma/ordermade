<?php

namespace App\Http\Controllers;

use App\Models\disbur;
use App\Models\disburCategories;
use App\Models\balance;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Throwable;


class disburController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $inputs = $request->validate([
            'year' => 'max:4',
        ]);

        $year = $request->input('year', date('Y'));
        $searchYear = $year;

        $disbur = DB::select("
        SELECT
	        CASE WHEN rn = 1 THEN Max(subquery.Mname) ELSE NULL END AS Mname,
                subquery.Sname AS Sname,
                SUM(CASE WHEN subquery.disbur_month = 1 THEN subquery.disbur_amount ELSE 0 END) AS month1,
                SUM(CASE WHEN subquery.disbur_month = 2 THEN subquery.disbur_amount ELSE 0 END) AS month2,
                SUM(CASE WHEN subquery.disbur_month = 3 THEN subquery.disbur_amount ELSE 0 END) AS month3,
                SUM(CASE WHEN subquery.disbur_month = 4 THEN subquery.disbur_amount ELSE 0 END) AS month4,
                SUM(CASE WHEN subquery.disbur_month = 5 THEN subquery.disbur_amount ELSE 0 END) AS month5,
                SUM(CASE WHEN subquery.disbur_month = 6 THEN subquery.disbur_amount ELSE 0 END) AS month6,
                SUM(CASE WHEN subquery.disbur_month = 7 THEN subquery.disbur_amount ELSE 0 END) AS month7,
                SUM(CASE WHEN subquery.disbur_month = 8 THEN subquery.disbur_amount ELSE 0 END) AS month8,
                SUM(CASE WHEN subquery.disbur_month = 9 THEN subquery.disbur_amount ELSE 0 END) AS month9,
                SUM(CASE WHEN subquery.disbur_month = 10 THEN subquery.disbur_amount ELSE 0 END) AS month10,
                SUM(CASE WHEN subquery.disbur_month = 11 THEN subquery.disbur_amount ELSE 0 END) AS month11,
                SUM(CASE WHEN subquery.disbur_month = 12 THEN subquery.disbur_amount ELSE 0 END) AS month12,
                SUM(subquery.disbur_amount) AS total
            FROM (
                SELECT
                    dc.Mname,
                    dc.Sname,
                    d.Mcode,
                    d.Scode,
                    d.disbur_month,
                    d.disbur_amount,
                    ROW_NUMBER() OVER (PARTITION BY d.Mcode ORDER BY d.Scode) AS rn
                FROM
                    disburs AS d
                INNER JOIN
                    disbur_categories AS dc ON d.Mcode = dc.Mcode AND d.Scode = dc.Scode
                WHERE
                    d.disbur_year = ?
            ) AS subquery
            GROUP BY
                
                subquery.Scode,
                subquery.Sname
            ORDER BY
                Max(subquery.Mcode),
                subquery.Scode,
                subquery.Sname;
    ", [$year]);

        $totaldisbur = disbur::where('disbur_year', $year)->sum('disbur_amount');
        $total_balance = balance::where('id', 1)->first();

        return view('disbur.index', compact('disbur', 'totaldisbur', 'total_balance'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $disburCategories = disburCategories::select('Mcode', 'Mname')
            ->distinct()
            ->get();

        $initialCategory = $disburCategories->first();
        $Scodes = disburCategories::select('Scode', 'Sname')->where('Mcode', $initialCategory->Mcode)->get();
        return view('disbur.create', compact('disburCategories', 'Scodes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        try {
            DB::beginTransaction();
            if ($request->has('create')) {
                $inputs = $request->validate([
                    'Mcode' => 'required',
                    'Scode' => 'required',
                    'disbur_year' => 'required|max:4',
                    'disbur_month' => 'required|max:2',
                    'disbur_amount' => 'required'
                ]);

                $disbur = new disbur();
                $disbur->Mcode = $request->Mcode;
                $disbur->Scode = $request->Scode;
                $disbur->disbur_year = $request->disbur_year;
                $disbur->disbur_month = $request->disbur_month;
                $disbur->disbur_amount = $request->disbur_amount;
                $disbur->save();

                balance::where('id', '=', '1')->update([
                    'balance' => DB::raw('balance - ' . $request->disbur_amount)
                ]);

                DB::commit();
                return redirect()->route('disbur.create')->with('message', '出金を登録しました');
            } else if ($request->has('delete')) {
                $inputs = $request->validate([
                    'Mcode' => 'required',
                    'Scode' => 'required',
                    'disbur_year' => 'required|max:4',
                    'disbur_month' => 'required|max:2'
                ]);

                $existingdisbur = disbur::where([
                    'Mcode' => $request->Mcode,
                    'Scode' => $request->Scode,
                    'disbur_year' => $request->disbur_year,
                    'disbur_month' => $request->disbur_month
                ])->first();

                if (!$existingdisbur) {
                    return redirect()->back()->withErrors(['duplicate' => '指定された年月の出金は登録されていません。']);
                }

                $disburDelete = disbur::where([
                    'Mcode' => $request->Mcode,
                    'Scode' => $request->Scode,
                    'disbur_year' => $request->disbur_year,
                    'disbur_month' => $request->disbur_month
                ])->get();
                
                DB::commit();
                return view('disbur.delete', compact('disburDelete'));
            }
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
    public function show(disbur $disbur)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(disbur $disbur)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, disbur $disbur)
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

            $disbur = disbur::find($id);
            $amount = $disbur->disbur_amount;
            $disbur->delete();

            balance::where('id', '=', '1')->update([
                'balance' => DB::raw('balance + ' . $amount)
            ]);
            DB::commit();
            return redirect()->route('disbur.create')->with('message', '出金を削除しました');
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

    public function getScode(Request $request)
    {
        $category = $request->get('category');
        $smallCategories = disburCategories::select('Scode', 'Sname')->where('Mcode', $category)->get();

        return response()->json($smallCategories);
    }

    public function score()
    {
        return view('score');
    }
}
