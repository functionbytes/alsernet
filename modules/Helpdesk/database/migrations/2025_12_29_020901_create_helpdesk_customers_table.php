<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('language')->default('es');
            $table->string('timezone')->nullable();
            $table->json('custom_attributes')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->text('ban_reason')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->integer('total_conversations')->default(0);
            $table->integer('total_page_visits')->default(0);
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('banned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_customers');
    }
};
