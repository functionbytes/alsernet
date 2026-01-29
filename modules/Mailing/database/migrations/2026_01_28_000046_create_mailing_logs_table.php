<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_logs', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('customer_id');
            $table->string('type');
            $table->string('name');
            $table->text('data');
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
        Schema::connection('acelle')->dropIfExists('mailing_logs');
    }
};
