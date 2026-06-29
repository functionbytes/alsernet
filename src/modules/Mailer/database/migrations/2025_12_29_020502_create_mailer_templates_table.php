<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailer_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('key')->unique();
            $table->string('name');
            $table->foreignId('layout_id')->nullable()->constrained('mailer_layouts')->onDelete('set null');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_protected')->default(false);
            $table->json('variables')->nullable();
            $table->string('module')->default('core');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('module');
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailer_templates');
    }
};
