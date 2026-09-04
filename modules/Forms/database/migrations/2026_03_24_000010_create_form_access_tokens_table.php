<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->string('token', 64)->unique();
            $table->string('email', 255)->nullable();
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('form_id');
            $table->index('expires_at');

            $table->foreign('form_id')
                ->references('id')
                ->on('forms')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_access_tokens');
    }
};
