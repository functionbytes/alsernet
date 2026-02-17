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
        Schema::create('mailrelay_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('endpoint_key', 255)->unique()->comment('Unique key for this endpoint');
            $table->text('description')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('mails_email_templates')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->text('api_key')->nullable()->comment('Encrypted API key for authentication');
            $table->integer('rate_limit')->default(60)->comment('Requests per minute');
            $table->json('allowed_ips')->nullable()->comment('Array of allowed IP addresses');
            $table->string('webhook_url', 500)->nullable()->comment('Callback URL for notifications');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mailrelay_endpoint_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailrelay_endpoint_id')->constrained('mailrelay_endpoints')->cascadeOnDelete();
            $table->string('request_ip', 45)->nullable();
            $table->string('request_method', 10)->nullable();
            $table->json('request_payload')->nullable();
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->useCurrent();

            $table->index(['mailrelay_endpoint_id', 'executed_at'], 'idx_endpoint_executed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailrelay_endpoint_logs');
        Schema::dropIfExists('mailrelay_endpoints');
    }
};
