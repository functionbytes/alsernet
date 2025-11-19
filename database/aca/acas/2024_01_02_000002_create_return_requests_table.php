<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('return_address')->nullable();
            $table->unsignedInteger('pickup_selection')->default(0);
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('address_id')->default(0);
            $table->unsignedBigInteger('detail_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('type_id');
            $table->text('description');
            $table->unsignedBigInteger('reason_id');
            $table->unsignedInteger('product_quantity')->default(0);
            $table->unsignedInteger('product_quantity_reinjected')->default(0);
            $table->datetime('received_date')->nullable();
            $table->datetime('pickup_date')->nullable();
            $table->boolean('is_refunded')->default(false);
            $table->unsignedInteger('is_wallet_used')->default(0);
            $table->unsignedBigInteger('shop_id');

            // Campos adicionales para nuestro sistema
            $table->string('customer_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('iban')->nullable();
            $table->string('pdf_path')->nullable();
            $table->enum('logistics_mode', ['customer_transport', 'home_pickup', 'store_delivery', 'inpost'])->nullable();
            $table->string('created_by')->nullable(); // admin/callcenter/web/guest

            $table->timestamps();

            $table->index(['customer_id']);
            $table->index(['order_id']);
            $table->index(['email']);
            $table->index(['status_id']);
            $table->index(['created_at']);
            $table->index(['logistics_mode']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_requests');
    }
}
