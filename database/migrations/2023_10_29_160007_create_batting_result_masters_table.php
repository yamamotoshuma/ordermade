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
        Schema::create('batting_result_masters', function (Blueprint $table) {
            $table->id();
            $table->integer('type');//1＝安打、2＝打席に入らないもの、3＝凡退、4＝打球方向、5打点
            $table->string('name');//名称
            $table->integer('buffer')->nullable();//バッファ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batting_result_masters');
    }
};
