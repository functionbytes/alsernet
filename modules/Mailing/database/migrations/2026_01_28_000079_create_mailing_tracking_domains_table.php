<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_tracking_domains', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('customer_id');
            $table->string('name');
            $table->string('status');
            $table->string('scheme');
            $table->string('verification_method');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('cascade');

            $table->index('customer_id');

            // Foreign Keys
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_tracking_domains');
    }
};
