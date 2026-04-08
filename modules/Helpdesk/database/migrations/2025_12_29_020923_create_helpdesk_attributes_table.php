<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('type');
            $table->string('format');
            $table->boolean('required')->default(false);
            $table->string('permission')->nullable();
            $table->text('description')->nullable();
            $table->string('customer_name')->nullable();
            $table->text('customer_description')->nullable();
            $table->json('config')->nullable();
            $table->boolean('internal')->default(false);
            $table->boolean('materialized')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('key');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_attributes');
    }
};
