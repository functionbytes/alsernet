<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Automatizaciones (DAG) — portado de acellemail (tabla automation2s).
 * `data` guarda el grafo Flow (nodos+aristas) como JSON. En este destino
 * no-SaaS se omite customer_id; mail_list_id referencia campaign_maillists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaign_automations')) {
            return;
        }

        Schema::create('campaign_automations', function (Blueprint $table): void {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->string('name');
            $table->foreignId('mail_list_id')->nullable()
                ->constrained('campaign_maillists')->nullOnDelete();
            $table->string('time_zone')->nullable();
            $table->string('status')->default('inactive');
            $table->longText('data')->nullable();   // Flow JSON (nodos + aristas)
            $table->text('segment_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_automations');
    }
};
