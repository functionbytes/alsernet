<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarrierPickupRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('carrier_pickup_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('carrier_id');
            $table->string('pickup_code')->nullable();
            $table->string('tracking_number')->nullable();
            $table->date('pickup_date');
            $table->string('pickup_time_slot')->nullable();
            $table->json('pickup_address');
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('contact_email')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'in_transit', 'collected', 'delivered', 'cancelled', 'failed'])->default('pending');
            $table->json('carrier_request')->nullable();
            $table->json('carrier_response')->nullable();
            $table->text('status_message')->nullable();
            $table->integer('packages_count')->default(1);
            $table->decimal('total_weight', 8, 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('return_requests')->onDelete('cascade');
            $table->foreign('carrier_id')->references('id')->on('carriers');

            $table->index(['carrier_id', 'status']);
            $table->index(['pickup_date', 'status']);
            $table->index('tracking_number');
            $table->unique(['return_request_id', 'carrier_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('carrier_pickup_requests');
    }
}
