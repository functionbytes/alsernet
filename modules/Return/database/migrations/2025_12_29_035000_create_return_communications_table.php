<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('return_requests')->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->string('recipient')->nullable();
            $table->string('subject')->nullable();
            $table->longText('content')->nullable();
            $table->string('template_used')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->foreignId('sent_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_communications');
    }
};
