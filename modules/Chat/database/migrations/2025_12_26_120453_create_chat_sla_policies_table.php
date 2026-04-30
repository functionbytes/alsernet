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
        Schema::create('chat_sla_policies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('sla_policies_account_id_index');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('first_response_time_minutes')->nullable()->comment('Target time for first response in minutes');
            $table->integer('resolution_time_minutes')->nullable()->comment('Target time for resolution in minutes');
            $table->boolean('is_active')->default(true)->index('sla_policies_is_active_index');
            $table->boolean('business_hours_only')->default(false)->comment('Only count business hours');
            $table->timestamps();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sla_policies');
    }
};
