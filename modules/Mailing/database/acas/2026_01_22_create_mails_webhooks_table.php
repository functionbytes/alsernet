<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mails_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('event_type', 100);
            $table->string('secret')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('verify_ssl')->default(true);
            $table->timestamp('last_called_at')->nullable();
            $table->integer('last_response_status')->nullable();
            $table->text('last_response_body')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('event_type');
            $table->index('active');
            $table->index('failed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mails_webhooks');
    }
};
