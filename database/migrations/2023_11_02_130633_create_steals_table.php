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
        Schema::create('steals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gameId'); // 試合ID (FK)
            $table->unsignedBigInteger('userId');//ユーザーID]

            //外部キー制約
            $table->foreign('gameId')->references('gameId')->on('games')->onDelete('cascade');; // 'games' テーブル名に合わせて変更
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');; // 'games' テーブル名に合わせて変更

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('steals');
    }
};
