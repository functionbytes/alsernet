<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->morphs('model');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['role_id', 'model_id', 'model_type'], 'idx_role_model');
            $table->index('model_id');
            $table->index('model_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_roles');
    }
};
