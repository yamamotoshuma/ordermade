<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkStorePaymentRequest;
use App\Http\Requests\IndexPaymentRequest;
use App\Http\Requests\PaymentActionRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class PayController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {
    }

    /**
     * 入金一覧の集計取得は Service に寄せる。
     */
    public function index(IndexPaymentRequest $request)
    {
        $year = (int) ($request->validated('year') ?? date('Y'));

        return view('payments.index', $this->paymentService->getIndexData($year));
    }

    public function create()
    {
        return view('payments.insert', $this->paymentService->getCreateData());
    }

    /**
     * 登録と削除検索の分岐だけを持ち、実処理は Service に委譲する。
     */
    public function store(PaymentActionRequest $request)
    {
        try {
            if ($request->has('insert')) {
                $this->paymentService->create($request->validated());

                return redirect()->route('payment.create')->with('message', '入金を登録しました');
            }

            if ($request->has('delete')) {
                $payment = $this->paymentService->findPaymentForDeletion($request->validated());

                return redirect()->route('payment.show', $payment);
            }

            return redirect()->back()->withErrors(['action' => '実行する操作を選択してください。']);
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->withErrors([
                'duplicate' => $this->paymentService->toUserMessage($e, '入金処理中にエラーが発生しました。'),
            ]);
        }
    }

    public function show(Payment $payment)
    {
        return view('payments.update', array_merge(
            ['payment' => $payment],
            $this->paymentService->getCreateData()
        ));
    }

    public function edit(Payment $payment)
    {
        return view('payments.edit', $this->paymentService->getEditData($payment));
    }

    /**
     * 更新時の残高調整も Service 側で一括管理する。
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        try {
            $updatedPayment = $this->paymentService->update($payment, $request->validated());
            Log::info('入金情報を更新しました。', ['user' => Auth::id(), 'payment' => $updatedPayment->id]);

            return redirect()->route('payment.index')->with('message', '入金情報を更新しました');
        } catch (Throwable $e) {
            $errorMessage = $this->paymentService->toUserMessage($e, '入金情報の更新中にエラーが発生しました。');

            return redirect()->back()->withInput()->withErrors(['duplicate' => $errorMessage]);
        }
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        try {
            $this->paymentService->delete($payment);
            Log::info('入金を削除しました。', ['user' => Auth::id(), 'payment' => $payment->id]);

            return redirect()->route('payment.index')->with('message', '入金を削除しました');
        } catch (Throwable $e) {
            $errorMessage = $this->paymentService->toUserMessage($e, '入金削除中にエラーが発生しました。');

            return redirect()->back()->withErrors(['duplicate' => $errorMessage]);
        }
    }

    /**
     * 一括登録は Request で配列構造を検証してから Service に渡す。
     */
    public function bulkStore(BulkStorePaymentRequest $request): RedirectResponse
    {
        try {
            $this->paymentService->bulkCreate($request->validated());

            return redirect()->route('payment.index')->with('message', '入金を一括登録しました');
        } catch (Throwable $e) {
            $errorMessage = $this->paymentService->toUserMessage($e, '不明なエラーが発生しました。');

            return redirect()->back()->withInput()->withErrors(['duplicate' => $errorMessage]);
        }
    }
}
