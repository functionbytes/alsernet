<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submission_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('form_field_id')->nullable();
            $table->string('field_key', 100);
            $table->string('field_label', 255);
            $table->string('field_type', 50);
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->index('submission_id');
            $table->index('form_field_id');

            $table->foreign('submission_id')
                ->references('id')
                ->on('form_submissions')
                ->cascadeOnDelete();

            $table->foreign('form_field_id')
                ->references('id')
                ->on('form_fields')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submission_values');
    }
};
