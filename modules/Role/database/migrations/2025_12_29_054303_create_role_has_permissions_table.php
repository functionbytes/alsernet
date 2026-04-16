<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('role_has_permissions')) {
            return;
        }

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_id')->index();
            $table->unsignedBigInteger('role_id')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['permission_id', 'role_id'], 'idx_permission_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
    }
};
