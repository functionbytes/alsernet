<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_conditional_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->string('condition_field_key', 100);
            $table->enum('condition_operator', ['equals', 'contains', 'not_equals', 'starts_with', 'ends_with'])
                ->default('equals');
            $table->string('condition_value', 255);
            $table->unsignedBigInteger('admin_template_id')->nullable();
            $table->unsignedBigInteger('client_template_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('form_id');

            $table->foreign('form_id')
                ->references('id')
                ->on('forms')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_conditional_emails');
    }
};
