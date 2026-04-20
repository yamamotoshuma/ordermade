<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('pitching_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gameId');
            $table->unsignedBigInteger('userId');
            $table->integer('pitchingOrder');
            $table->string('result')->nullable();
            $table->integer('save')->nullable();
            $table->decimal('inning', 3, 1)->nullable();
            $table->integer('hitsAllowed')->nullable();
            $table->integer('homeRunsAllowed')->nullable();
            $table->integer('strikeouts')->nullable();
            $table->integer('walks')->nullable();
            $table->integer('wildPitches')->nullable();
            $table->integer('balks')->nullable();
            $table->integer('runsAllowed')->nullable();
            $table->integer('earnedRuns')->nullable();
            $table->timestamps();

            $table->foreign('gameId')->references('gameId')->on('games')->onDelete('cascade');
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pitching_stats');
    }
};
