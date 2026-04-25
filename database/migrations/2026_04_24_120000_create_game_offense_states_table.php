<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 試合ごとの現在の攻撃状況をキャッシュする。
     */
    public function up(): void
    {
        Schema::create('game_offense_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gameId')->unique();
            $table->unsignedInteger('inning')->default(1);
            $table->unsignedTinyInteger('outCount')->default(0);
            $table->unsignedBigInteger('batterOrderId')->nullable();
            $table->unsignedBigInteger('batterUserId')->nullable();
            $table->string('batterUserName')->nullable();
            $table->unsignedBigInteger('firstOrderId')->nullable();
            $table->unsignedBigInteger('firstUserId')->nullable();
            $table->string('firstUserName')->nullable();
            $table->unsignedBigInteger('secondOrderId')->nullable();
            $table->unsignedBigInteger('secondUserId')->nullable();
            $table->string('secondUserName')->nullable();
            $table->unsignedBigInteger('thirdOrderId')->nullable();
            $table->unsignedBigInteger('thirdUserId')->nullable();
            $table->string('thirdUserName')->nullable();
            $table->unsignedBigInteger('version')->default(1);
            $table->boolean('needsRunnerConfirmation')->default(false);
            $table->string('runnerConfirmationMessage')->nullable();
            $table->timestamps();

            $table->foreign('gameId')->references('gameId')->on('games')->onDelete('cascade');
            $table->foreign('batterOrderId')->references('orderId')->on('batting_orders')->nullOnDelete();
            $table->foreign('firstOrderId')->references('orderId')->on('batting_orders')->nullOnDelete();
            $table->foreign('secondOrderId')->references('orderId')->on('batting_orders')->nullOnDelete();
            $table->foreign('thirdOrderId')->references('orderId')->on('batting_orders')->nullOnDelete();
            $table->foreign('batterUserId')->references('id')->on('users')->nullOnDelete();
            $table->foreign('firstUserId')->references('id')->on('users')->nullOnDelete();
            $table->foreign('secondUserId')->references('id')->on('users')->nullOnDelete();
            $table->foreign('thirdUserId')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_offense_states');
    }
};
