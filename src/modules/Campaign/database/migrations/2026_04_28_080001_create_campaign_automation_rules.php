<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_automation_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->string('trigger_event', 64); // opened|clicked|not_opened|subscribed|tag_added
            $table->string('condition', 64)->nullable(); // segment|link|tag|time
            $table->json('condition_value')->nullable();
            $table->string('action', 64); // send_campaign|move_list|tag|wait|webhook
            $table->json('action_value')->nullable();
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_automation_rules');
    }
};
