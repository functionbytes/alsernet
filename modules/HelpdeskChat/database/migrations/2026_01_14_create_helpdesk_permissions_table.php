<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('guard_name')->default('web');
            $table->timestamps();

            $table->index('category');
            $table->index('guard_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_permissions');
    }
};
