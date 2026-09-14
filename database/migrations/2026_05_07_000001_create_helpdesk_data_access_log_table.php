<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_data_access_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email_accessed', 255);
            $table->string('source', 16); // 'erp' | 'prestashop'
            $table->string('action', 64); // 'context_view' | 'order_detail' | etc.
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('request_id', 64)->nullable()->index();
            $table->timestamp('accessed_at')->useCurrent();
            $table->index(['email_accessed', 'accessed_at']);
            $table->index(['user_id', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_data_access_log');
    }
};
