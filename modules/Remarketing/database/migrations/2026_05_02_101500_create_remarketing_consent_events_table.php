<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_consent_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('remarketing_customers')->nullOnDelete();
            $table->string('email');
            $table->enum('event_type', ['granted', 'confirmed', 'withdrawn', 'bounced', 'complained', 'imported']);
            $table->enum('source', ['popup', 'checkout', 'api', 'import', 'admin', 'double_optin_confirm']);
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('form_url')->nullable();
            $table->string('policy_version', 20)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['store_id', 'email', 'occurred_at']);
            $table->index(['customer_id', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_consent_events');
    }
};
