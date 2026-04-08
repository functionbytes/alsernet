<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->enum('type', ['open', 'pending', 'resolved', 'closed'])->index();
            $table->string('color', 7);
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_statuses');
    }
};
