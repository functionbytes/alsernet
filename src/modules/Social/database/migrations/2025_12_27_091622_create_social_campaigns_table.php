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
        Schema::create('social_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();

            $table->string('name');
            $table->string('color', 7)->default('#5D87FF'); // Hex color
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('status')->default(true); // true=active, false=inactive

            $table->timestamps();

            // Indexes
            $table->index(['account_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_campaigns');
    }
};
