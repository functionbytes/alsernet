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
        if (Schema::hasTable('erp_endpoint_logs')) {
            return;
        }

        Schema::create('erp_endpoint_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('erp_endpoints')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Who triggered the request
            $table->string('method'); // GET, POST, etc.
            $table->text('url'); // Full URL with query params
            $table->json('request_headers')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('response_payload')->nullable();
            $table->integer('status_code')->nullable();
            $table->integer('execution_time')->nullable(); // milliseconds
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at');

            $table->index(['endpoint_id', 'created_at']);
            $table->index(['success', 'created_at']);
            $table->index('status_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_endpoint_logs');
    }
};
