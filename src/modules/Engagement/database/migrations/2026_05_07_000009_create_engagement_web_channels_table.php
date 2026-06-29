<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->create('engagement_web_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbox_id')->constrained('helpdesk_inboxes')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('website_token', 64)->unique();
            $table->string('domain')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['website_token', 'is_active']);
            $table->index('inbox_id');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('engagement_web_channels');
    }
};
