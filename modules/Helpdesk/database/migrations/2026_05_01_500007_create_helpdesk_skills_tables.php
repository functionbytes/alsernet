<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('helpdesk_user_skills', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->foreignId('skill_id')->constrained('helpdesk_skills')->cascadeOnDelete();
            $table->tinyInteger('proficiency')->default(3); // 1-5

            $table->primary(['user_id', 'skill_id']);
            $table->index('skill_id');
        });

        Schema::connection($this->connection)->create('helpdesk_conversation_skills', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id');
            $table->foreignId('skill_id')->constrained('helpdesk_skills')->cascadeOnDelete();

            $table->primary(['conversation_id', 'skill_id']);
            $table->index('skill_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_conversation_skills');
        Schema::connection($this->connection)->dropIfExists('helpdesk_user_skills');
        Schema::connection($this->connection)->dropIfExists('helpdesk_skills');
    }
};
