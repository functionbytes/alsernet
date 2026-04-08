<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('helpdesk_group_user')) {
            return;
        }

        Schema::create('helpdesk_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('helpdesk_group_id')->constrained('helpdesk_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member');  // member, supervisor, admin
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['helpdesk_group_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_group_user');
    }
};
