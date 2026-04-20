<?php

namespace App\Services;

use App\Models\disburCategories;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DisburCategoryService
{
    /**
     * カテゴリ一覧表示用の全件を返す。
     */
    public function getIndexData(): array
    {
        return [
            'dc' => disburCategories::all(),
        ];
    }

    /**
     * カテゴリを1件登録する。
     */
    public function create(array $payload): disburCategories
    {
        return DB::transaction(function () use ($payload): disburCategories {
            $exists = disburCategories::where([
                'Mcode' => (int) $payload['Mcode'],
                'Scode' => (int) $payload['Scode'],
            ])->exists();

            if ($exists) {
                throw new RuntimeException('指定されたカテゴリコードは既に登録されています。');
            }

            return disburCategories::create([
                'Mcode' => (int) $payload['Mcode'],
                'Mname' => $payload['Mname'],
                'Scode' => (int) $payload['Scode'],
                'Sname' => $payload['Sname'],
            ]);
        });
    }

    /**
     * カテゴリを1件削除する。
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $category = disburCategories::findOrFail($id);
            $category->delete();
        });
    }

    /**
     * 例外を日本語メッセージへ変換する。
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
