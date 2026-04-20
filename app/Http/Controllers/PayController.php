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
            users.id,
            users.name,
            SUM(CASE WHEN payments.payment_month = 1 THEN payments.payment_amount ELSE 0 END) AS month1,
            MAX(CASE WHEN payments.payment_month = 1 THEN payments.id ELSE null END) AS id1,
            SUM(CASE WHEN payments.payment_month = 2 THEN payments.payment_amount ELSE 0 END) AS month2,
            MAX(CASE WHEN payments.payment_month = 2 THEN payments.id ELSE null END) AS id2,
            SUM(CASE WHEN payments.payment_month = 3 THEN payments.payment_amount ELSE 0 END) AS month3,
            SUM(CASE WHEN payments.payment_month = 3 THEN payments.id ELSE null END) AS id3,
            SUM(CASE WHEN payments.payment_month = 4 THEN payments.payment_amount ELSE 0 END) AS month4,
            SUM(CASE WHEN payments.payment_month = 4 THEN payments.id ELSE null END) AS id4,
            SUM(CASE WHEN payments.payment_month = 5 THEN payments.payment_amount ELSE 0 END) AS month5,
            SUM(CASE WHEN payments.payment_month = 5 THEN payments.id ELSE null END) AS id5,
            SUM(CASE WHEN payments.payment_month = 6 THEN payments.payment_amount ELSE 0 END) AS month6,
            SUM(CASE WHEN payments.payment_month = 6 THEN payments.id ELSE null END) AS id6,
            SUM(CASE WHEN payments.payment_month = 7 THEN payments.payment_amount ELSE 0 END) AS month7,
            SUM(CASE WHEN payments.payment_month = 7 THEN payments.id ELSE null END) AS id7,
            SUM(CASE WHEN payments.payment_month = 8 THEN payments.payment_amount ELSE 0 END) AS month8,
            SUM(CASE WHEN payments.payment_month = 8 THEN payments.id ELSE null END) AS id8,
            SUM(CASE WHEN payments.payment_month = 9 THEN payments.payment_amount ELSE 0 END) AS month9,
            SUM(CASE WHEN payments.payment_month = 9 THEN payments.id ELSE null END) AS id9,
            SUM(CASE WHEN payments.payment_month = 10 THEN payments.payment_amount ELSE 0 END) AS month10,
            SUM(CASE WHEN payments.payment_month = 10 THEN payments.id ELSE null END) AS id10,
            SUM(CASE WHEN payments.payment_month = 11 THEN payments.payment_amount ELSE 0 END) AS month11,
            SUM(CASE WHEN payments.payment_month = 11 THEN payments.id ELSE null END) AS id11,
            SUM(CASE WHEN payments.payment_month = 12 THEN payments.payment_amount ELSE 0 END) AS month12,
            SUM(CASE WHEN payments.payment_month = 12 THEN payments.id ELSE null END) AS id12,
            SUM(payments.payment_amount) AS total_amount
        ')
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->where('payments.payment_year', $year)
            ->groupBy('users.name', 'payments.user_id')
            ->orderBy('payments.user_id')
            ->get();

        $total_month = Payment::selectRaw('sum(payment_amount)')->Where('payment_year', $year)->groupBy('payment_month')->get();
        $totalPayments = Payment::where('payment_year', $year)->sum('payment_amount');
        $total_balance = balance::where('id', 1)->first();

        $users = User::all();

        return view('payments.index', compact('payments', 'totalPayments', 'total_balance', 'total_month','users','year'));
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

        if ($request->has('insert')) {
            //登録の場合登録
            $inputs = $request->validate([
                'user_name' => 'required',
                'payment_year' => 'required|max:4',
                'payment_month' => 'required|max:2',
                'payment_amount' => 'required|max:5'
            ]);
            try {
                DB::beginTransaction();
                $user = User::findOrFail($request->user_name);

                if (!$user) {
                    //万が一
                    DB::rollback();
                    return redirect()->back()->withErrors(['user_name' => '指定されたユーザーは存在しません。']);
                }

                $existingPayment = Payment::where([
                    'user_id' => $user->id,
                    'payment_year' => $request->payment_year,
                    'payment_month' => $request->payment_month
                ])->first();

                if ($existingPayment) {
                    DB::rollback();
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
        } else if ($request->has('delete')) {
            //更新の場合画面遷移
            $inputs = $request->validate([
                'user_name' => 'required',
                'payment_year' => 'required|max:4',
                'payment_month' => 'required|max:2',
            ]);
            try {
                DB::beginTransaction();
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
        $payment = Payment::where('id',$payment->id)->with('user')->first();

        //dd($payment);
        return view('payments.edit',compact('payment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
{
    $inputs = $request->validate([
        'payment_year' => 'required|max:4',
        'payment_month' => 'required|max:2',
        'payment_amount' => 'required|max:5'
    ]);

    try {
        DB::beginTransaction();

        $existingPayment = Payment::where([
            'user_id' => $payment->user_id,
            'payment_year' => $inputs['payment_year'],
            'payment_month' => $inputs['payment_month']
        ])->where('id', '!=', $payment->id)
          ->first();

        if ($existingPayment) {
            DB::rollback();
            return redirect()->back()->withErrors(['duplicate' => '指定された年月の入金は既に登録されています。']);
        }

        // バランス情報を更新
        $balance = Balance::findOrFail(1); // 1はダミーのID、実際のシステムに合わせて修正してください
        $balance->balance = $balance->balance - ($payment->payment_amount - $request->payment_amount);
        $balance->save();

        $payment->payment_year = $inputs['payment_year'];
        $payment->payment_month = $inputs['payment_month'];
        $payment->payment_amount = $inputs['payment_amount'];
        $payment->save();

        DB::commit();
        return redirect()->route('payment.index')->with('message', '入金情報を更新しました');
    } catch (Throwable $e) {
        DB::rollback();
        $errorMessage = $e->getMessage();
        return view('error', compact('errorMessage'));
    }
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
        return redirect()->route('payment.index')->with('message', '入金を削除しました');
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'payment_Year' => 'required|digits:4',
            'payment_month' => 'required|numeric|min:1|max:12',
            'users' => 'required|array', // ユーザーIDの配列
            'users.*' => 'exists:users,id', // ユーザーIDが存在することを確認
            'payment_amounts' => 'required|array', // 入金額の配列
            'payment_amounts.*' => 'nullable|numeric|min:0', // 入金額が0以上の数字か確認（nullを許可する場合はnullableを使用）
        ]);

        try {
            DB::beginTransaction();

            $paymentYear = $request->input('payment_Year');
            $paymentMonth = $request->input('payment_month');
            $users = $request->input('users');
            $paymentAmounts = $request->input('payment_amounts');

            foreach ($users as $key => $userId) {
                // チェックされたユーザーのみを処理対象とする
                if ($request->has('users.' . $key)) {
                    // 入金額が空の場合はエラーを出す
                    if (empty($paymentAmounts[$userId])) {
                        DB::rollback();
                        return redirect()->back()->withErrors(['payment_amounts.' . $key => '入金額を入力してください。']);
                    }

                    $existingPayment = Payment::where([
                        'user_id' => $userId,
                        'payment_year' => $paymentYear,
                        'payment_month' => $paymentMonth
                    ])->first();

                    if ($existingPayment) {
                        DB::rollback();
                        return redirect()->back()->withErrors(['duplicate' => '指定された年月の入金は既に登録されています。']);
                    }

                    $payment = new Payment();
                    $payment->user_id = $userId;
                    $payment->payment_year = $paymentYear;
                    $payment->payment_month = $paymentMonth;
                    $payment->payment_amount = $paymentAmounts[$userId]; // チェックされているので、空でないことが確認済み
                    $payment->save();

                    balance::where('id', '=', '1')->update([
                        'balance' => DB::raw('balance + ' . $paymentAmounts[$userId])
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('payment.index')->with('message', '入金を一括登録しました');
        } catch (Throwable $e) {
            DB::rollback();
            $errorMessage = $e->getMessage();
            return redirect()->back()->withErrors(['duplicate' => '不明なエラーが発生しました。']);
        }

    }
}
