<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_ai_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('system_prompt');
            $table->json('knowledge_sources')->nullable();
            $table->json('enabled_channels')->nullable();
            $table->json('escalation_keywords')->nullable();
            $table->unsignedSmallInteger('max_messages_before_escalation')->default(5);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_ai_agents');
    }
};
