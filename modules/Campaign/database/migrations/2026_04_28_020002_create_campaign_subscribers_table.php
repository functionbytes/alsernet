<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('email')->index();
            $table->json('attributes')->nullable(); // first_name, last_name, custom fields
            $table->string('ip')->nullable();
            $table->string('source', 32)->default('web');
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_subscribers');
    }
};
