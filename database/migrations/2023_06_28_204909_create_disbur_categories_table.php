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
        Schema::create('disbur_categories', function (Blueprint $table) {
            $table->id();
            $table->integer('Mcode');
            $table->string('Mname');
            $table->integer('Scode');
            $table->string('Sname');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbur_categories');
    }
};
