<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 打者一巡に備えて、同一イニング内の何打席目かを保持する。
     */
    public function up(): void
    {
        Schema::table('batting_stats', function (Blueprint $table) {
            $table->unsignedInteger('inningTurn')->default(1)->after('inning');
            $table->index(['gameId', 'inning', 'inningTurn']);
        });
    }

    public function down(): void
    {
        Schema::table('batting_stats', function (Blueprint $table) {
            $table->dropIndex(['gameId', 'inning', 'inningTurn']);
            $table->dropColumn('inningTurn');
        });
    }
};
