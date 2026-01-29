<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_products', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('source_id')->nullable();
            $table->string('title')->nullable();
            $table->mediumText('description')->nullable();
            $table->longText('content')->nullable();
            $table->double('price');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('unit')->nullable();
            $table->integer('stock')->nullable();
            $table->string('name')->nullable();
            $table->string('curency')->nullable();
            $table->text('file')->nullable();
            $table->string('pack')->nullable();
            $table->string('status')->nullable();
            $table->string('source_item_id')->nullable();
            $table->longText('meta')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('category_id')->references('id')->on('mailing_categories')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('cascade');
            $table->foreign('source_id')->references('id')->on('mailing_sources')->onDelete('set null');

            $table->index('category_id');
            $table->index('customer_id');
            $table->index('source_id');

            // Foreign Keys
            $table->foreign('category_id')
                ->references('id')
                ->on('mailing_categories')
                ->onDelete('set null');
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
            $table->foreign('source_id')
                ->references('id')
                ->on('mailing_sources')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_products');
    }
};
