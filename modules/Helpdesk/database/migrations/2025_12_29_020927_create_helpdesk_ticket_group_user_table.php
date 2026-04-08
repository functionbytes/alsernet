<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_group_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('user_id');
            $table->string('conversation_priority')->default('primary');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('group_id')->references('id')->on('helpdesk_groups')->onDelete('cascade');

            $table->unique(['group_id', 'user_id'], 'idx_87211');
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_group_user');
    }
};
