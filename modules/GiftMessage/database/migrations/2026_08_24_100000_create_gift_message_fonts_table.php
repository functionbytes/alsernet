<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_message_fonts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('family', 100);
            $table->string('weight', 10)->default('normal');
            $table->string('style', 10)->default('normal');
            $table->string('file_path');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['family', 'weight', 'style']);
            $table->index('family');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_message_fonts');
    }
};
