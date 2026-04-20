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
        Schema::create('points', function (Blueprint $table) {
            $table->id('pointId'); // 点数ID (PK)
            $table->unsignedBigInteger('gameId'); // 試合ID (FK)
            $table->unsignedInteger('inning'); // イニング
            $table->unsignedInteger('inning_side'); // 表裏（整数型）0=自チーム　1＝相手チーム
            $table->unsignedInteger('score'); // 点数
            $table->timestamps();

            // 外部キー制約の設定
            $table->foreign('gameId')->references('gameId')->on('games')->onDelete('cascade');;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points');
    }
};
