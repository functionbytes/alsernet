<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_attributes', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->char('uid', 36)->nullable()->unique();
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->string('status')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('category_id')->references('id')->on('mailing_categories')->onDelete('cascade');

            $table->index('category_id');

            // Foreign Keys
            $table->foreign('category_id')
                ->references('id')
                ->on('mailing_categories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_attributes');
    }
};
