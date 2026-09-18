<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_message_generations', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->unsignedInteger('rows_count')->default(0);
            $table->string('file_path');
            $table->string('file_name');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type']);
            $table->index(['generated_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_message_generations');
    }
};
