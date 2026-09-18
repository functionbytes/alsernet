<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the "Skills / Competencias" feature: it was not used and its
 * skill-based routing strategy has been dropped from the auto-assignment
 * services (Helpdesk conversations and HelpdeskTickets). Mirrors the down()
 * of 2026_05_01_500007_create_helpdesk_skills_tables.php, kept intact for
 * history.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_conversation_skills');
        Schema::connection($this->connection)->dropIfExists('helpdesk_user_skills');
        Schema::connection($this->connection)->dropIfExists('helpdesk_skills');
    }

    public function down(): void
    {
        Schema::connection($this->connection)->create('helpdesk_skills', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('helpdesk_user_skills', function ($table) {
            $table->unsignedBigInteger('user_id');
            $table->foreignId('skill_id')->constrained('helpdesk_skills')->cascadeOnDelete();
            $table->tinyInteger('proficiency')->default(3);

            $table->primary(['user_id', 'skill_id']);
            $table->index('skill_id');
        });

        Schema::connection($this->connection)->create('helpdesk_conversation_skills', function ($table) {
            $table->unsignedBigInteger('conversation_id');
            $table->foreignId('skill_id')->constrained('helpdesk_skills')->cascadeOnDelete();

            $table->primary(['conversation_id', 'skill_id']);
            $table->index('skill_id');
        });
    }
};
