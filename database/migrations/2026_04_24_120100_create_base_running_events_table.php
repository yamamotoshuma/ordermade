<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 走塁イベントを履歴として持ち、盗塁数の集計元もこちらへ寄せる。
     */
    public function up(): void
    {
        Schema::create('base_running_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gameId');
            $table->unsignedInteger('inning')->nullable();
            $table->unsignedBigInteger('actorOrderId')->nullable();
            $table->unsignedBigInteger('actorUserId')->nullable();
            $table->string('actorUserName')->nullable();
            $table->unsignedTinyInteger('startBase')->nullable();
            $table->unsignedTinyInteger('endBase')->nullable();
            $table->string('eventType', 32);
            $table->unsignedTinyInteger('outsRecorded')->default(0);
            $table->boolean('affectsState')->default(true);
            $table->unsignedBigInteger('stateVersion')->nullable();
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('gameId')->references('gameId')->on('games')->onDelete('cascade');
            $table->foreign('actorOrderId')->references('orderId')->on('batting_orders')->nullOnDelete();
            $table->foreign('actorUserId')->references('id')->on('users')->nullOnDelete();
            $table->foreign('createdBy')->references('id')->on('users')->nullOnDelete();
            $table->index(['gameId', 'eventType']);
            $table->index(['gameId', 'actorUserId']);
        });

        // 既存の steals を集計互換の履歴としてバックフィルする。
        if (Schema::hasTable('steals')) {
            DB::table('steals')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    $payload = [];

                    foreach ($rows as $row) {
                        $payload[] = [
                            'gameId' => $row->gameId,
                            'inning' => null,
                            'actorOrderId' => null,
                            'actorUserId' => $row->userId,
                            'actorUserName' => null,
                            'startBase' => null,
                            'endBase' => null,
                            'eventType' => 'stolen_base',
                            'outsRecorded' => 0,
                            'affectsState' => false,
                            'stateVersion' => null,
                            'createdBy' => null,
                            'meta' => json_encode(['source' => 'legacy_steal_migration'], JSON_UNESCAPED_UNICODE),
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ];
                    }

                    if ($payload !== []) {
                        DB::table('base_running_events')->insert($payload);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('base_running_events');
    }
};
