<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_subfamilies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_id')->unique();
            $table->unsignedBigInteger('category_id')->nullable();    // → supplier_categories (familia)
            $table->unsignedBigInteger('erp_category_id')->nullable(); // idfamilia_cl
            $table->string('name', 255);
            $table->string('short_name', 100)->nullable();
            $table->boolean('available')->default(true)->index();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('supplier_categories')->nullOnDelete();
            $table->index('category_id');
            $table->index('erp_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_subfamilies');
    }
};
