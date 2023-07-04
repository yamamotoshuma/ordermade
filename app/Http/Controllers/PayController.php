<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Models\balance;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Throwable;

class PayController extends Controller
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

        $payments = Payment::with('user')
            ->selectRaw('
            users.name, 
            SUM(CASE WHEN payments.payment_month = 1 THEN payments.payment_amount ELSE 0 END) AS month1,
            SUM(CASE WHEN payments.payment_month = 2 THEN payments.payment_amount ELSE 0 END) AS month2,
            SUM(CASE WHEN payments.payment_month = 3 THEN payments.payment_amount ELSE 0 END) AS month3,
            SUM(CASE WHEN payments.payment_month = 4 THEN payments.payment_amount ELSE 0 END) AS month4,
            SUM(CASE WHEN payments.payment_month = 5 THEN payments.payment_amount ELSE 0 END) AS month5,
            SUM(CASE WHEN payments.payment_month = 6 THEN payments.payment_amount ELSE 0 END) AS month6,
            SUM(CASE WHEN payments.payment_month = 7 THEN payments.payment_amount ELSE 0 END) AS month7,
            SUM(CASE WHEN payments.payment_month = 8 THEN payments.payment_amount ELSE 0 END) AS month8,
            SUM(CASE WHEN payments.payment_month = 9 THEN payments.payment_amount ELSE 0 END) AS month9,
            SUM(CASE WHEN payments.payment_month = 10 THEN payments.payment_amount ELSE 0 END) AS month10,
            SUM(CASE WHEN payments.payment_month = 11 THEN payments.payment_amount ELSE 0 END) AS month11,
            SUM(CASE WHEN payments.payment_month = 12 THEN payments.payment_amount ELSE 0 END) AS month12,
            SUM(payments.payment_amount) AS total_amount
        ')
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->where('payments.payment_year', $year)
            ->groupBy('users.name', 'payments.user_id')
            ->get();

        $totalPayments = Payment::where('payment_year', $year)->sum('payment_amount');
        $total_balance = balance::where('id', 1)->first();

        return view('payments.index', compact('payments', 'totalPayments', 'total_balance'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $users = User::all(); // ユーザーモデルからユーザーリストを取得する例
        return view('payments.insert', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            if ($request->has('insert')) {
                //登録の場合登録
                $inputs = $request->validate([
                    'user_name' => 'required',
                    'payment_year' => 'required|max:4',
                    'payment_month' => 'required|max:2',
                    'payment_amount' => 'required|max:5'
                ]);

                $user = User::findOrFail($request->user_name);

                if (!$user) {
                    //万が一
                    return redirect()->back()->withErrors(['user_name' => '指定されたユーザーは存在しません。']);
                }

                $existingPayment = Payment::where([
                    'user_id' => $user->id,
                    'payment_year' => $request->payment_year,
                    'payment_month' => $request->payment_month
                ])->first();

                if ($existingPayment) {
                    return redirect()->back()->withErrors(['duplicate' => '指定された年月の入金は既に登録されています。']);
                }

                $payment = new Payment();
                $payment->user_id = $user->id;
                $payment->payment_year = $request->payment_year;
                $payment->payment_month = $request->payment_month;
                $payment->payment_amount = $request->payment_amount;
                $payment->save();

                balance::where('id', '=', '1')->update([
                    'balance' => DB::raw('balance + ' . $request->payment_amount)
                ]);
                DB::commit();
                return redirect()->route('payment.create')->with('message', '入金を登録しました');
            } else if ($request->has('delete')) {
                //更新の場合画面遷移
                $inputs = $request->validate([
                    'user_name' => 'required',
                    'payment_year' => 'required|max:4',
                    'payment_month' => 'required|max:2',
                ]);

                $user = User::findOrFail($request->user_name);

                if (!$user) {
                    //万が一
                    return redirect()->back()->withErrors(['user_name' => '指定されたユーザーは存在しません。']);
                }

                $existingPayment = Payment::where([
                    'user_id' => $user->id,
                    'payment_year' => $request->payment_year,
                    'payment_month' => $request->payment_month
                ])->first();

                if (!$existingPayment) {
                    return redirect()->back()->withErrors(['duplicate' => '指定された年月の入金は登録されていません。']);
                }

                $payment = Payment::where('user_id', $user->id)
                    ->where('payment_year', $request->payment_year)
                    ->where('payment_month', $request->payment_month)
                    ->first();
                DB::commit();    
                return redirect()->route('payment.show', $payment);
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
    public function show(Payment $payment)
    {
        //
        $users = User::all(); // ユーザーモデルからユーザーリストを取得する例
        return view('payments.update', compact('users', 'payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        //
        $amount = $payment->payment_amount;
        $payment->delete();

        balance::where('id', '=', '1')->update([
            'balance' => DB::raw('balance - ' . $amount)
        ]);

        $users = User::all();
        return redirect()->route('payment.create', compact('users'))->with('message', '入金を削除しました');
    }
}
