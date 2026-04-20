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
        Schema::create('games', function (Blueprint $table) {
            $table->id('gameId');
            $table->text('gameName');
            $table->integer('year');
            $table->dateTime('gameDates');
            $table->text('enemyName');
            $table->integer('gameFirstFlg')->nullable();//0が先行　1が後攻
            $table->integer('winFlg')->nullable();//0が勝ち　1が負け
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
