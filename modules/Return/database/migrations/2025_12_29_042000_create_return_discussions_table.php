<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_discussions', function (Blueprint $table) {
            $table->id('id_return_discussion');
            $table->foreignId('id_return_request')->constrained('return_requests')->cascadeOnDelete();
            $table->foreignId('id_employee')->nullable();
            $table->longText('message')->nullable();
            $table->string('file_name')->nullable();
            $table->boolean('private')->default(false);
            $table->timestamps();
            $table->index('id_return_request');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_discussions');
    }
};
