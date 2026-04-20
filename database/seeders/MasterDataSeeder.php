<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('disbur_categories')->upsert([
            ['id' => 1, 'Mcode' => 1, 'Mname' => '試合練習費', 'Scode' => 1, 'Sname' => 'リーグ参加費', 'created_at' => '2023-07-04 21:57:48', 'updated_at' => '2023-07-04 21:57:48'],
            ['id' => 2, 'Mcode' => 1, 'Mname' => '試合練習費', 'Scode' => 2, 'Sname' => 'トーナメント参加費', 'created_at' => '2023-07-05 12:31:50', 'updated_at' => '2023-07-05 12:31:50'],
            ['id' => 3, 'Mcode' => 1, 'Mname' => '試合練習費', 'Scode' => 3, 'Sname' => '新球購入費', 'created_at' => '2023-07-05 12:32:20', 'updated_at' => '2023-07-05 12:32:20'],
            ['id' => 4, 'Mcode' => 1, 'Mname' => '試合練習費', 'Scode' => 4, 'Sname' => '球場費', 'created_at' => '2023-07-05 12:32:45', 'updated_at' => '2023-07-05 12:32:45'],
            ['id' => 5, 'Mcode' => 1, 'Mname' => '試合練習費', 'Scode' => 5, 'Sname' => '審判費', 'created_at' => '2023-07-05 12:33:18', 'updated_at' => '2023-07-05 12:33:18'],
            ['id' => 6, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 1, 'Sname' => 'キャッチャー防具', 'created_at' => '2023-07-05 12:35:30', 'updated_at' => '2023-07-05 12:35:30'],
            ['id' => 7, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 2, 'Sname' => 'キャッチャー面', 'created_at' => '2023-07-05 12:35:59', 'updated_at' => '2023-07-05 12:35:59'],
            ['id' => 8, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 3, 'Sname' => 'キャッチャーメット', 'created_at' => '2023-07-06 10:51:06', 'updated_at' => '2023-07-06 10:51:06'],
            ['id' => 9, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 4, 'Sname' => 'キャッチャーミット', 'created_at' => '2023-07-06 10:51:29', 'updated_at' => '2023-07-06 10:51:29'],
            ['id' => 10, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 5, 'Sname' => 'ヘルメット', 'created_at' => '2023-07-06 10:51:44', 'updated_at' => '2023-07-06 10:51:44'],
            ['id' => 11, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 6, 'Sname' => 'バット', 'created_at' => '2023-07-06 10:51:58', 'updated_at' => '2023-07-06 10:51:58'],
            ['id' => 12, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 7, 'Sname' => 'バットケース', 'created_at' => '2023-07-06 10:52:14', 'updated_at' => '2023-07-06 10:52:14'],
            ['id' => 13, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 8, 'Sname' => 'グリップ取替', 'created_at' => '2023-07-06 10:52:32', 'updated_at' => '2023-07-06 10:52:32'],
            ['id' => 14, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 9, 'Sname' => 'すべり止め', 'created_at' => '2023-07-06 10:52:50', 'updated_at' => '2023-07-06 10:52:50'],
            ['id' => 15, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 10, 'Sname' => 'ロジンバッグ', 'created_at' => '2023-07-06 10:53:09', 'updated_at' => '2023-07-06 10:53:09'],
            ['id' => 16, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 11, 'Sname' => '練習球', 'created_at' => '2023-07-06 10:53:24', 'updated_at' => '2023-07-06 10:53:24'],
            ['id' => 17, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 12, 'Sname' => 'ボールケース', 'created_at' => '2023-07-06 10:53:37', 'updated_at' => '2023-07-06 10:53:37'],
            ['id' => 18, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 13, 'Sname' => 'ジャグ', 'created_at' => '2023-07-06 10:53:53', 'updated_at' => '2023-07-06 10:53:53'],
            ['id' => 19, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 14, 'Sname' => '飲料水', 'created_at' => '2023-07-06 10:54:13', 'updated_at' => '2023-07-06 10:54:13'],
            ['id' => 20, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 15, 'Sname' => 'その他備品', 'created_at' => '2023-07-06 10:54:28', 'updated_at' => '2023-07-06 10:54:28'],
            ['id' => 21, 'Mcode' => 5, 'Mname' => 'その他', 'Scode' => 1, 'Sname' => 'ドメイン12か月', 'created_at' => '2023-07-06 10:55:06', 'updated_at' => '2023-07-06 10:55:06'],
            ['id' => 22, 'Mcode' => 5, 'Mname' => 'その他', 'Scode' => 2, 'Sname' => 'サーバー12か月', 'created_at' => '2023-07-06 10:55:37', 'updated_at' => '2023-07-06 10:55:37'],
            ['id' => 23, 'Mcode' => 5, 'Mname' => 'その他', 'Scode' => 3, 'Sname' => 'スマホ用三脚', 'created_at' => '2023-07-06 10:56:06', 'updated_at' => '2023-07-06 10:56:06'],
            ['id' => 24, 'Mcode' => 1, 'Mname' => '試合練習費', 'Scode' => 6, 'Sname' => '交通費', 'created_at' => '2023-07-21 15:53:18', 'updated_at' => '2023-07-21 15:53:18'],
            ['id' => 25, 'Mcode' => 2, 'Mname' => 'チーム道具購入費', 'Scode' => 16, 'Sname' => '応急処置キット', 'created_at' => '2023-07-21 16:01:05', 'updated_at' => '2023-07-21 16:01:05'],
            ['id' => 26, 'Mcode' => 5, 'Mname' => 'その他', 'Scode' => 4, 'Sname' => 'スポーツ保険料', 'created_at' => '2023-08-25 18:24:15', 'updated_at' => '2023-08-25 18:24:15'],
            ['id' => 27, 'Mcode' => 1, 'Mname' => '試合練習費', 'Scode' => 7, 'Sname' => '駐車場代（道具運搬）', 'created_at' => '2023-12-29 14:36:48', 'updated_at' => '2023-12-29 14:36:48'],
        ], ['id'], ['Mcode', 'Mname', 'Scode', 'Sname', 'created_at', 'updated_at']);

        DB::table('positions')->upsert([
            ['positionId' => 1, 'positionName' => '投', 'created_at' => null, 'updated_at' => null],
            ['positionId' => 2, 'positionName' => '捕', 'created_at' => null, 'updated_at' => null],
            ['positionId' => 3, 'positionName' => '一', 'created_at' => null, 'updated_at' => null],
            ['positionId' => 4, 'positionName' => '二', 'created_at' => null, 'updated_at' => null],
            ['positionId' => 5, 'positionName' => '三', 'created_at' => null, 'updated_at' => null],
            ['positionId' => 6, 'positionName' => '遊', 'created_at' => null, 'updated_at' => null],
            ['positionId' => 7, 'positionName' => '左', 'created_at' => null, 'updated_at' => null],
            ['positionId' => 8, 'positionName' => '中', 'created_at' => null, 'updated_at' => null],
            ['positionId' => 9, 'positionName' => '右', 'created_at' => null, 'updated_at' => null],
            ['positionId' => 10, 'positionName' => '指', 'created_at' => null, 'updated_at' => null],
        ], ['positionId'], ['positionName', 'created_at', 'updated_at']);

        DB::table('batting_result_masters')->upsert([
            ['id' => 1, 'type' => 1, 'name' => '安打', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'type' => 1, 'name' => '二塁打', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'type' => 1, 'name' => '三塁打', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'type' => 1, 'name' => '本塁打', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 5, 'type' => 2, 'name' => '四球', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 6, 'type' => 2, 'name' => '死球', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 7, 'type' => 2, 'name' => '犠打', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 8, 'type' => 2, 'name' => '犠飛', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 9, 'type' => 2, 'name' => 'エラー', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 10, 'type' => 2, 'name' => 'FC', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 11, 'type' => 3, 'name' => 'ゴロ', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 12, 'type' => 3, 'name' => 'フライ', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 13, 'type' => 3, 'name' => '三振', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 14, 'type' => 3, 'name' => 'ライナー', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 15, 'type' => 3, 'name' => '併殺', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 16, 'type' => 3, 'name' => '三重殺', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 17, 'type' => 3, 'name' => '振逃', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 18, 'type' => 4, 'name' => '投', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 19, 'type' => 4, 'name' => '捕', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 20, 'type' => 4, 'name' => '一', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 21, 'type' => 4, 'name' => '二', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 22, 'type' => 4, 'name' => '三', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 23, 'type' => 4, 'name' => '遊', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 24, 'type' => 4, 'name' => '左', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 25, 'type' => 4, 'name' => '中', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 26, 'type' => 4, 'name' => '右', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 27, 'type' => 4, 'name' => '左中間', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 28, 'type' => 4, 'name' => '右中間', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 29, 'type' => 4, 'name' => '空振', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 30, 'type' => 4, 'name' => '見逃', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 31, 'type' => 5, 'name' => '0', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 32, 'type' => 5, 'name' => '1', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 33, 'type' => 5, 'name' => '2', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 34, 'type' => 5, 'name' => '3', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 35, 'type' => 5, 'name' => '4', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
            ['id' => 36, 'type' => 4, 'name' => '', 'buffer' => null, 'created_at' => null, 'updated_at' => null],
        ], ['id'], ['type', 'name', 'buffer', 'created_at', 'updated_at']);
    }
}
