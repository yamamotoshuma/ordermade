<?php

namespace App\Services;

use App\Models\balance;
use App\Models\disbur;
use App\Models\disburCategories;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DisbursementService
{
    /**
     * 出金一覧で使う月別集計と残高を返す。
     */
    public function getIndexData(int $year): array
    {
        $disbur = DB::select(
            '
                SELECT
                    subquery.Mname,
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
                        d.disbur_amount
                    FROM disburs AS d
                    INNER JOIN disbur_categories AS dc ON d.Mcode = dc.Mcode AND d.Scode = dc.Scode
                    WHERE d.disbur_year = ?
                    GROUP BY dc.Mname, dc.Sname, d.Mcode, d.Scode, d.disbur_month, d.disbur_amount
                ) AS subquery
                GROUP BY subquery.Mcode, subquery.Mname, subquery.Scode, subquery.Sname
                ORDER BY subquery.Mcode, subquery.Mname, subquery.Scode, subquery.Sname
            ',
            [$year]
        );

        return [
            'disbur' => $disbur,
            'totaldisbur' => disbur::where('disbur_year', $year)->sum('disbur_amount'),
            'total_balance' => balance::query()->findOrFail(1),
        ];
    }

    /**
     * 出金登録画面に必要なカテゴリ候補を返す。
     */
    public function getCreateData(): array
    {
        $disburCategories = disburCategories::select('Mcode', 'Mname')
            ->distinct()
            ->get();

        $initialCategory = $disburCategories->first();
        $scodes = $initialCategory
            ? disburCategories::select('Scode', 'Sname')->where('Mcode', $initialCategory->Mcode)->get()
            : collect();

        return [
            'disburCategories' => $disburCategories,
            'Scodes' => $scodes,
        ];
    }

    /**
     * 出金を登録し、残高から同額を差し引く。
     */
    public function create(array $payload): disbur
    {
        return DB::transaction(function () use ($payload): disbur {
            $this->ensureDisbursementDoesNotExist(
                (int) $payload['Mcode'],
                (int) $payload['Scode'],
                (int) $payload['disbur_year'],
                (int) $payload['disbur_month']
            );

            $disbur = new disbur();
            $disbur->Mcode = (int) $payload['Mcode'];
            $disbur->Scode = (int) $payload['Scode'];
            $disbur->disbur_year = (int) $payload['disbur_year'];
            $disbur->disbur_month = (int) $payload['disbur_month'];
            $disbur->disbur_amount = (int) $payload['disbur_amount'];
            $disbur->save();

            $this->adjustBalance(-1 * (int) $payload['disbur_amount']);

            return $disbur;
        });
    }

    /**
     * 削除候補の出金一覧を検索し、確認画面へ渡せるようにする。
     */
    public function findForDeletion(array $payload): Collection
    {
        $rows = disbur::where([
            'Mcode' => (int) $payload['Mcode'],
            'Scode' => (int) $payload['Scode'],
            'disbur_year' => (int) $payload['disbur_year'],
            'disbur_month' => (int) $payload['disbur_month'],
        ])->get();

        if ($rows->isEmpty()) {
            throw new RuntimeException('指定された年月の出金は登録されていません。');
        }

        return $rows;
    }

    /**
     * 出金を1件削除し、残高へ戻し入れる。
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $disbur = disbur::findOrFail($id);
            $amount = (int) $disbur->disbur_amount;
            $disbur->delete();
            $this->adjustBalance($amount);
        });
    }

    /**
     * 中分類にぶら下がる小分類だけを返す。
     */
    public function getSmallCategories(string $category): Collection
    {
        return disburCategories::select('Scode', 'Sname')
            ->where('Mcode', $category)
            ->get();
    }

    /**
     * 同一カテゴリ・同一年月の出金重複を防ぐ。
     */
    private function ensureDisbursementDoesNotExist(int $mcode, int $scode, int $year, int $month): void
    {
        $exists = disbur::where([
            'Mcode' => $mcode,
            'Scode' => $scode,
            'disbur_year' => $year,
            'disbur_month' => $month,
        ])->exists();

        if ($exists) {
            throw new RuntimeException('指定された年月の出金は既に登録されています。');
        }
    }

    /**
     * 部費残高を増減させる共通処理。
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
     * 例外内容を既存画面に合わせたメッセージへ変換する。
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
