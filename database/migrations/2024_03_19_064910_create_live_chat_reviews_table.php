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
        Schema::create('livechat_reviews', function (Blueprint $table) {
            $table->id();
            $table->BigInteger('users_id')->nullable();
            $table->BigInteger('cust_id')->nullable();
            $table->BigInteger('starRating')->nullable();
            $table->string('problemrectified')->nullable();
            $table->longText('feedbackdata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livechat_reviews');
    }
};
