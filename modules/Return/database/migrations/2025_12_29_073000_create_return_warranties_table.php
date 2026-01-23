<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_warranties', function (Blueprint $table) {
            $table->id();
            $table->string('warranty_number')->unique();
            $table->foreignId('order_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('warranty_type_id')->constrained('return_warranty_types');
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers');
            $table->foreignId('user_id')->constrained('users');
            $table->string('product_serial_number')->nullable();
            $table->string('product_model')->nullable();
            $table->decimal('product_price', 10, 2)->nullable();
            $table->integer('quantity')->default(1);
            $table->date('purchase_date')->nullable();
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->integer('warranty_duration_months')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_registered_with_manufacturer')->default(false);
            $table->string('manufacturer_warranty_id')->nullable();
            $table->dateTime('manufacturer_registration_date')->nullable();
            $table->date('activation_date')->nullable();
            $table->foreignId('activated_by')->nullable();
            $table->json('activation_details')->nullable();
            $table->foreignId('original_owner_id')->nullable();
            $table->dateTime('transferred_at')->nullable();
            $table->json('transfer_history')->nullable();
            $table->decimal('warranty_cost', 10, 2)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->dateTime('payment_date')->nullable();
            $table->json('documents')->nullable();
            $table->json('proof_of_purchase')->nullable();
            $table->json('terms_and_conditions')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->dateTime('terms_accepted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('warranty_number');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_warranties');
    }
};
