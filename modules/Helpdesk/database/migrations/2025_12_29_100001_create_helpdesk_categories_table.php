<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_categories', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable(); // #RRGGBB
            $table->string('icon', 50)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->foreign('parent_id')->references('id')->on('helpdesk_categories')->onDelete('cascade');
            $table->index(['parent_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_categories');
    }
};
