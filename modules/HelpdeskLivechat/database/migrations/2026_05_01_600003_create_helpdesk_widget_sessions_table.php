<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_widget_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('session_token', 64)->unique();
            $table->text('current_url')->nullable();
            $table->text('referrer')->nullable();
            $table->json('device')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')
                ->on('helpdesk_customers')
                ->nullOnDelete();

            $table->index('session_token');
            $table->index('last_activity_at');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_widget_sessions');
    }
};
