<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_assisted_cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assisted_cart_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('assisted_cart_id')->references('id')->on('helpdesk_assisted_carts')->onDelete('cascade');

            $table->index('assisted_cart_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_assisted_cart_items');
    }
};
