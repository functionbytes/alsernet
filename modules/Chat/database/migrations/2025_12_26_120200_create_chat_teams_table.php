<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('allow_auto_assign')->default(true);
            $table->string('auto_assignment_strategy')->default('round_robin'); // round_robin, least_busy, random
            $table->timestamps();

            $table->index('account_id');
        });

        Schema::create('chat_team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('chat_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_lead')->default(false);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_team_user');
        Schema::dropIfExists('chat_teams');
    }
};
