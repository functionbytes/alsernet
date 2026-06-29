<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->string('name', 150);
            $table->unsignedInteger('send_after_days')->default(1);
            $table->unsignedBigInteger('email_template_id')->nullable();
            $table->enum('recipient_type', ['admin', 'submitter', 'both'])->default('admin');
            $table->string('condition_field_key', 100)->nullable();
            $table->string('condition_value', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('form_id');
            $table->index('is_active');

            $table->foreign('form_id')
                ->references('id')
                ->on('forms')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_follow_ups');
    }
};
