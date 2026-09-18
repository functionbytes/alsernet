<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform', 32); // facebook, instagram, whatsapp, tiktok, x, linkedin
            $table->string('account_type', 32)->default('page'); // page, profile, business
            $table->string('external_id'); // page_id, instagram_id, phone_number_id
            $table->string('username')->nullable();
            $table->string('profile_url')->nullable();
            $table->text('page_access_token')->nullable();
            $table->text('user_access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('permissions')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('comments_enabled')->default(true);
            $table->boolean('messages_enabled')->default(true);
            $table->boolean('auto_reply_enabled')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->unsignedBigInteger('connected_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['platform', 'external_id']);
            $table->index('is_active');
            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_social_accounts');
    }
};
