<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_segment_conditions', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('segment_id');
            $table->unsignedInteger('field_id')->nullable();
            $table->string('operator');
            $table->string('value')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('field_id')->references('id')->on('mailing_fields')->onDelete('set null');
            $table->foreign('segment_id')->references('id')->on('mailing_segments')->onDelete('cascade');

            $table->index('field_id');
            $table->index('segment_id');

            // Foreign Keys
            $table->foreign('field_id')
                ->references('id')
                ->on('mailing_fields')
                ->onDelete('set null');
            $table->foreign('segment_id')
                ->references('id')
                ->on('mailing_segments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_segment_conditions');
    }
};
