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
        Schema::create('chat_product_views', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->integer('customer_id')->nullable();
            $table->foreignId('session_id')->nullable()->constrained('chat_customer_sessions')->nullOnDelete();
            $table->string('session_ip', 45)->nullable();
            $table->string('product_name');
            $table->integer('category_id')->nullable();
            $table->string('category_name')->nullable();
            $table->decimal('product_price', 20, 6)->nullable();
            $table->string('product_image_url', 512)->nullable();
            $table->string('product_reference', 64)->nullable();
            $table->integer('view_duration')->nullable();
            $table->integer('view_count')->default(1);
            $table->string('referrer_url', 512)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('view_date');
            $table->timestamp('last_view_date');
            $table->timestamps();

            $table->index('product_id', 'hd_pviews_product_idx');
            $table->index('customer_id', 'hd_pviews_customer_idx');
            $table->index('session_id', 'hd_pviews_session_idx');
            $table->index('session_id');
            $table->index('view_date');
            $table->index('category_id', 'hd_pviews_category_idx');
            $table->unique(['product_id', 'customer_id', 'session_id'], 'hd_pviews_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_product_views');
    }
};
