<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_customer_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('session_id')->unique();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('helpdesk_customers')->onDelete('cascade');

            $table->index('customer_id');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_customer_sessions');
    }
};
