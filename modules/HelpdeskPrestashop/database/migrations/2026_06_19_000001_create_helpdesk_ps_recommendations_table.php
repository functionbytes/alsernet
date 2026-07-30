<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ps_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('id_product_attribute')->nullable();
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->decimal('price_with_tax', 10, 2)->default(0);
            $table->string('product_url')->nullable();
            $table->string('product_image')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ps_recommendations');
    }
};
