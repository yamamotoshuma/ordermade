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
        Schema::create('disburs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('Mcode');
            $table->foreignId('Scode');
            $table->smallInteger('disbur_year');
            $table->smallInteger('disbur_month');
            $table->smallInteger('disbur_amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disburs');
    }
};
