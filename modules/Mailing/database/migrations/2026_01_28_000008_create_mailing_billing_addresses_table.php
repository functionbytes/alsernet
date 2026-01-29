<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_billing_addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedInteger('customer_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->unsignedInteger('country_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('country_id')->references('id')->on('mailing_countries')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('cascade');

            $table->index('country_id');
            $table->index('customer_id');

            // Foreign Keys
            $table->foreign('country_id')
                ->references('id')
                ->on('mailing_countries')
                ->onDelete('cascade');
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_billing_addresses');
    }
};
