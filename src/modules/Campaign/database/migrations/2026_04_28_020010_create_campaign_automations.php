<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_automations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->string('time_zone', 64)->default('UTC');
            $table->string('status', 32)->default('inactive')->index(); // active|inactive|paused
            $table->longText('data')->nullable(); // árbol del workflow (JSON serializado)
            $table->foreignId('mail_list_id')
                ->nullable()
                ->constrained('campaign_maillists')
                ->nullOnDelete();
            $table->string('segment_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_automation_elements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('automation_id')
                ->constrained('campaign_automations')
                ->cascadeOnDelete();
            $table->string('element_id')->index(); // id en el árbol JSON
            $table->string('type', 64); // trigger|wait|evaluate|operate|send|webhook
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_auto_triggers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('automation_id')
                ->constrained('campaign_automations')
                ->cascadeOnDelete();
            $table->foreignId('subscriber_id')
                ->nullable()
                ->constrained('campaign_subscribers')
                ->nullOnDelete();
            $table->json('data')->nullable(); // árbol con estado por trigger session
            $table->timestamp('last_executed_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('campaign_trigger_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('auto_trigger_id')
                ->constrained('campaign_auto_triggers')
                ->cascadeOnDelete();
            $table->string('status', 32)->default('queued')->index();
            $table->text('last_error')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_trigger_sessions');
        Schema::dropIfExists('campaign_auto_triggers');
        Schema::dropIfExists('campaign_automation_elements');
        Schema::dropIfExists('campaign_automations');
    }
};
