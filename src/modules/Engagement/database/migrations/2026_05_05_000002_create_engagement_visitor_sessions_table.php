<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_visitor_sessions')) {
            return;
        }

        Schema::connection('helpdesk')->create('engagement_visitor_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('inbox_id')->nullable();
            $table->string('session_token', 64)->unique();
            $table->string('visitor_id', 64)->nullable()->index();
            $table->string('current_url', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->json('device')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'last_activity_at']);
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('engagement_visitor_sessions');
    }
};
