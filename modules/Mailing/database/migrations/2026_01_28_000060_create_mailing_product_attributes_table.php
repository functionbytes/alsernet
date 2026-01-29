<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_product_attributes', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->char('uid', 36)->nullable()->unique();
            $table->unsignedInteger('product_id');
            $table->unsignedBigInteger('attribute_id');
            $table->string('value');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('attribute_id')->references('id')->on('mailing_attributes')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('mailing_products')->onDelete('cascade');

            $table->index('attribute_id');
            $table->index('product_id');

            // Foreign Keys
            $table->foreign('attribute_id')
                ->references('id')
                ->on('mailing_attributes')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('mailing_products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_product_attributes');
    }
};
