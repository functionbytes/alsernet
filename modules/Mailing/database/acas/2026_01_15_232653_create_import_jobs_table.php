<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('filename');
            $table->string('status')->default('pending');
            $table->integer('total_emails')->default(0);
            $table->integer('processed_emails')->default(0);
            $table->integer('valid_emails')->default(0);
            $table->integer('invalid_emails')->default(0);
            $table->json('report')->nullable();
            $table->json('options')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
