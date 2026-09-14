<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_webhooks')) {
            $schema->create('helpdesk_webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('url', 500);
                $table->json('events');
                $table->string('secret', 64)->nullable();
                $table->json('headers')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->integer('success_count')->default(0);
                $table->integer('failure_count')->default(0);
                $table->timestamp('last_triggered_at')->nullable();
                $table->text('last_error')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! $schema->hasTable('helpdesk_webhook_deliveries')) {
            $schema->create('helpdesk_webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('webhook_id')->constrained('helpdesk_webhooks')->cascadeOnDelete();
                $table->string('event', 64)->index();
                $table->json('payload');
                $table->integer('response_status')->nullable();
                $table->text('response_body')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();
                $table->index(['webhook_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_webhook_deliveries');
        Schema::connection($this->connection)->dropIfExists('helpdesk_webhooks');
    }
};
