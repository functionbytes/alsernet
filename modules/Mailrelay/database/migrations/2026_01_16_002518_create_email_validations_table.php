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
        Schema::create('email_validations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('email');
            $table->string('validation_type'); // syntax, dns, smtp, external, ml
            $table->string('status'); // valid, invalid, risky, disposable, suspicious, pending, failed
            $table->integer('score')->default(0); // 0-100
            $table->json('details')->nullable();
            $table->string('provider')->nullable();
            $table->timestamp('validated_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('email');
            $table->index('validation_type');
            $table->index('status');
            $table->index('provider');
            $table->index(['email', 'validation_type']);
            $table->index('validated_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_validations');
    }
};
