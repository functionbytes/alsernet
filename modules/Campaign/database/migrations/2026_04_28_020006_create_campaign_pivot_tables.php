<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot: campaña ↔ lista ↔ segmento (qué subset de listas/segmentos enviar)
        Schema::create('campaign_lists_segments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->cascadeOnDelete();
            $table->foreignId('mail_list_id')
                ->constrained('campaign_maillists')
                ->cascadeOnDelete();
            $table->foreignId('segment_id')
                ->nullable()
                ->constrained('campaign_segments')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['campaign_id', 'mail_list_id', 'segment_id'], 'cls_idx');
        });

        // Pivot: lista ↔ sending server (cross-module FK)
        Schema::create('campaign_maillists_sending_servers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mail_list_id')
                ->constrained('campaign_maillists')
                ->cascadeOnDelete();
            $table->foreignId('sending_server_id')
                ->constrained('campaign_sending_servers')
                ->cascadeOnDelete();
            $table->integer('priority')->default(1);
            $table->timestamps();

            $table->unique(['mail_list_id', 'sending_server_id'], 'mls_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_maillists_sending_servers');
        Schema::dropIfExists('campaign_lists_segments');
    }
};
