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
        Schema::create('batting_orders', function (Blueprint $table) {
            $table->id('orderId'); // 打順ID (PK)
            $table->unsignedBigInteger('gameId'); // 試合ID (FK)
            $table->integer('battingOrder'); // 打順 (整数)
            $table->unsignedBigInteger('positionId');//守備位置
            $table->unsignedBigInteger('userId')->nullable(); // ユーザーID (FK, NULL許容)
            $table->string('userName')->nullable(); // ユーザー名 (NULL許容)
            $table->integer('ranking')->default(1); // 順位付け（デフォルトは1）

            // 外部キー制約
            $table->foreign('gameId')->references('gameId')->on('games')->onDelete('cascade');; // 'games' テーブル名に合わせて変更
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');; // 'users' テーブル名に合わせて変更
            $table->foreign('positionId')->references('positionId')->on('positions')->onDelete('cascade');; // 'positions' テーブル名に合わせて変更

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batting_orders');
    }
};
