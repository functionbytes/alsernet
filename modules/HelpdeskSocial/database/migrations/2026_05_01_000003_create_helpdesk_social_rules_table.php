<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_social_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('platform', 32)->nullable(); // null = all platforms
            $table->json('conditions'); // [{field, operator, value}]
            $table->json('actions'); // [{type, params}]
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('stop_processing')->default(false); // stop further rules
            $table->unsignedInteger('trigger_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'priority']);
            $table->index('platform');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_social_rules');
    }
};
