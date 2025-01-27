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
        Schema::create('live_chat_customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('cust_unique_id');
            $table->longText('username');
            $table->string('email')->unique();
            $table->longText('chat_flow_messages')->nullable();
            $table->longText('engage_conversation')->nullable();
            $table->boolean('file_upload_permission')->nullable();
            $table->string('mobile_number');
            $table->string('browser_info');
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->longText('mark_as_unread')->nullable();
            $table->string('city')->nullable();
            $table->string('full_address')->nullable();
            $table->string('timezone')->nullable();
            $table->string('userType')->nullable();
            $table->string('status')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamp('login_at');
            $table->string('login_ip');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_chat_customers');
    }
};
