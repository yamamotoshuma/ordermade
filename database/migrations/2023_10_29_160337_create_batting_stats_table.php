<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batting_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gameId');//試合ID
            $table->unsignedBigInteger('userId')->nullable();//ユーザーID
            $table->string('userName')->nullable();//選手名 (ユーザーID　nullの時)
            $table->integer('inning');
            $table->unsignedBigInteger('resultId1');//安打、三振
            $table->unsignedBigInteger('resultId2')->nullable();//打球方向
            $table->unsignedBigInteger('resultId3')->nullable();//打点
            $table->unsignedBigInteger('resultId4')->nullable();//バッファ
            $table->unsignedBigInteger('resultId5')->nullable();//バッファ

            // 外部キー制約
            $table->foreign('gameId')->references('gameId')->on('games')->onDelete('cascade');; // 'games' テーブル名に合わせて変更
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');; // 'games' テーブル名に合わせて変更
            $table->foreign('resultId1')->references('id')->on('batting_result_masters')->onDelete('cascade');; // テーブル名に合わせて変更
            $table->foreign('resultId2')->references('id')->on('batting_result_masters')->onDelete('cascade');; // テーブル名に合わせて変更
            $table->foreign('resultId3')->references('id')->on('batting_result_masters')->onDelete('cascade');; // テーブル名に合わせて変更
            $table->foreign('resultId4')->references('id')->on('batting_result_masters')->onDelete('cascade');; // テーブル名に合わせて変更
            $table->foreign('resultId5')->references('id')->on('batting_result_masters')->onDelete('cascade');; // テーブル名に合わせて変更

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batting_stats');
    }
};
