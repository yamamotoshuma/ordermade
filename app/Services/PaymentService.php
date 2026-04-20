<?php

namespace App\Services;

use App\Models\balance;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class PaymentService
{
    /**
     * 入金一覧画面で使う一覧・合計・残高をまとめて返す。
     */
    public function getIndexData(int $year): array
    {
        $payments = Payment::query()
            ->selectRaw('
                users.id,
                users.name,
                SUM(CASE WHEN payments.payment_month = 1 THEN payments.payment_amount ELSE 0 END) AS month1,
                MAX(CASE WHEN payments.payment_month = 1 THEN payments.id ELSE null END) AS id1,
                SUM(CASE WHEN payments.payment_month = 2 THEN payments.payment_amount ELSE 0 END) AS month2,
                MAX(CASE WHEN payments.payment_month = 2 THEN payments.id ELSE null END) AS id2,
                SUM(CASE WHEN payments.payment_month = 3 THEN payments.payment_amount ELSE 0 END) AS month3,
                MAX(CASE WHEN payments.payment_month = 3 THEN payments.id ELSE null END) AS id3,
                SUM(CASE WHEN payments.payment_month = 4 THEN payments.payment_amount ELSE 0 END) AS month4,
                MAX(CASE WHEN payments.payment_month = 4 THEN payments.id ELSE null END) AS id4,
                SUM(CASE WHEN payments.payment_month = 5 THEN payments.payment_amount ELSE 0 END) AS month5,
                MAX(CASE WHEN payments.payment_month = 5 THEN payments.id ELSE null END) AS id5,
                SUM(CASE WHEN payments.payment_month = 6 THEN payments.payment_amount ELSE 0 END) AS month6,
                MAX(CASE WHEN payments.payment_month = 6 THEN payments.id ELSE null END) AS id6,
                SUM(CASE WHEN payments.payment_month = 7 THEN payments.payment_amount ELSE 0 END) AS month7,
                MAX(CASE WHEN payments.payment_month = 7 THEN payments.id ELSE null END) AS id7,
                SUM(CASE WHEN payments.payment_month = 8 THEN payments.payment_amount ELSE 0 END) AS month8,
                MAX(CASE WHEN payments.payment_month = 8 THEN payments.id ELSE null END) AS id8,
                SUM(CASE WHEN payments.payment_month = 9 THEN payments.payment_amount ELSE 0 END) AS month9,
                MAX(CASE WHEN payments.payment_month = 9 THEN payments.id ELSE null END) AS id9,
                SUM(CASE WHEN payments.payment_month = 10 THEN payments.payment_amount ELSE 0 END) AS month10,
                MAX(CASE WHEN payments.payment_month = 10 THEN payments.id ELSE null END) AS id10,
                SUM(CASE WHEN payments.payment_month = 11 THEN payments.payment_amount ELSE 0 END) AS month11,
                MAX(CASE WHEN payments.payment_month = 11 THEN payments.id ELSE null END) AS id11,
                SUM(CASE WHEN payments.payment_month = 12 THEN payments.payment_amount ELSE 0 END) AS month12,
                MAX(CASE WHEN payments.payment_month = 12 THEN payments.id ELSE null END) AS id12,
                SUM(payments.payment_amount) AS total_amount
            ')
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->where('payments.payment_year', $year)
            ->groupBy('users.id', 'users.name', 'payments.user_id')
            ->orderBy('payments.user_id')
            ->get();

        return [
            'payments' => $payments,
            'totalPayments' => Payment::where('payment_year', $year)->sum('payment_amount'),
            'total_balance' => balance::query()->findOrFail(1),
            'total_month' => Payment::selectRaw('SUM(payment_amount) as total')
                ->where('payment_year', $year)
                ->groupBy('payment_month')
                ->get(),
            'users' => User::all(),
            'year' => $year,
        ];
    }

    /**
     * 入金登録画面に必要なユーザー一覧を返す。
     */
    public function getCreateData(): array
    {
        return [
            'users' => User::all(),
        ];
    }

    /**
     * 入金編集画面用にユーザー情報込みで再読込する。
     */
    public function getEditData(Payment $payment): array
    {
        return [
            'payment' => Payment::where('id', $payment->id)->with('user')->firstOrFail(),
        ];
    }

    /**
     * 削除前確認画面用に対象の入金レコードを引き当てる。
     */
    public function findPaymentForDeletion(array $payload): Payment
    {
        $payment = Payment::where([
            'user_id' => (int) $payload['user_name'],
            'payment_year' => (int) $payload['payment_year'],
            'payment_month' => (int) $payload['payment_month'],
        ])->first();

        if (! $payment) {
            throw new RuntimeException('指定された年月の入金は登録されていません。');
        }

        return $payment;
    }

    /**
     * 単票の入金を登録し、残高も同時に更新する。
     */
    public function create(array $payload): Payment
    {
        return DB::transaction(function () use ($payload): Payment {
            $this->ensurePaymentDoesNotExist(
                (int) $payload['user_name'],
                (int) $payload['payment_year'],
                (int) $payload['payment_month']
            );

            $payment = Payment::create([
                'user_id' => (int) $payload['user_name'],
                'payment_year' => (int) $payload['payment_year'],
                'payment_month' => (int) $payload['payment_month'],
                'payment_amount' => (int) $payload['payment_amount'],
            ]);

            $this->adjustBalance((int) $payload['payment_amount']);

            return $payment;
        });
    }

    /**
     * 既存入金の年月・金額を更新し、差分だけ残高へ反映する。
     */
    public function update(Payment $payment, array $payload): Payment
    {
        return DB::transaction(function () use ($payment, $payload): Payment {
            $this->ensurePaymentDoesNotExist(
                (int) $payment->user_id,
                (int) $payload['payment_year'],
                (int) $payload['payment_month'],
                $payment->id
            );

            $newAmount = (int) $payload['payment_amount'];
            $delta = $newAmount - (int) $payment->payment_amount;

            $payment->payment_year = (int) $payload['payment_year'];
            $payment->payment_month = (int) $payload['payment_month'];
            $payment->payment_amount = $newAmount;
            $payment->save();

            if ($delta !== 0) {
                $this->adjustBalance($delta);
            }

            return $payment->fresh('user');
        });
    }

    /**
     * 入金を削除し、残高から同額を差し引く。
     */
    public function delete(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $amount = (int) $payment->payment_amount;
            $payment->delete();
            $this->adjustBalance(-1 * $amount);
        });
    }

    /**
     * 一括入金登録を行い、対象ユーザー分だけ順次残高へ加算する。
     */
    public function bulkCreate(array $payload): void
    {
        DB::transaction(function () use ($payload): void {
            $paymentYear = (int) $payload['payment_Year'];
            $paymentMonth = (int) $payload['payment_month'];
            $users = $payload['users'] ?? [];
            $paymentAmounts = $payload['payment_amounts'] ?? [];

            foreach ($users as $userId) {
                $amount = $paymentAmounts[$userId] ?? null;

                if ($amount === null || $amount === '') {
                    throw new RuntimeException('チェックしたユーザーの入金額を入力してください。');
                }

                $this->ensurePaymentDoesNotExist((int) $userId, $paymentYear, $paymentMonth);

                Payment::create([
                    'user_id' => (int) $userId,
                    'payment_year' => $paymentYear,
                    'payment_month' => $paymentMonth,
                    'payment_amount' => (int) $amount,
                ]);

                $this->adjustBalance((int) $amount);
            }
        });
    }

    /**
     * 既に同一ユーザー・同一年月の入金が存在しないか確認する。
     */
    private function ensurePaymentDoesNotExist(int $userId, int $year, int $month, ?int $ignoreId = null): void
    {
        $query = Payment::where([
            'user_id' => $userId,
            'payment_year' => $year,
            'payment_month' => $month,
        ]);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new RuntimeException('指定された年月の入金は既に登録されています。');
        }
    }

    /**
     * 部費残高を差分値で加減算する。
     */
    private function adjustBalance(int $delta): void
    {
        $balance = balance::query()->findOrFail(1);

        if ($delta >= 0) {
            $balance->increment('balance', $delta);
            return;
        }

        $balance->decrement('balance', abs($delta));
    }

    /**
     * DB 例外を既存画面向けの日本語メッセージへ寄せる。
     */
    public function toUserMessage(Throwable $e, string $defaultMessage): string
    {
        if ($e instanceof RuntimeException) {
            return $e->getMessage();
        }

        if ($e instanceof QueryException) {
            return '他の人が登録している可能性があります。';
        }

        return $defaultMessage;
    }
}
