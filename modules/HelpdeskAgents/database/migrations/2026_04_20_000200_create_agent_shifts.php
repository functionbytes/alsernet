<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $s = Schema::connection($this->connection);

        if (! $s->hasTable('helpdesk_agent_shifts')) {
            $s->create('helpdesk_agent_shifts', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id');
                $t->tinyInteger('day_of_week'); // 0=Sunday, 6=Saturday
                $t->time('start_time');
                $t->time('end_time');
                $t->string('timezone', 64)->default('UTC');
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index(['user_id', 'day_of_week']);
            });
        }

        if (! $s->hasTable('helpdesk_agent_vacations')) {
            $s->create('helpdesk_agent_vacations', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id');
                $t->date('starts_at');
                $t->date('ends_at');
                $t->string('reason')->nullable();
                $t->string('status', 16)->default('approved'); // pending, approved, rejected
                $t->timestamps();
                $t->index(['user_id', 'starts_at']);
            });
        }

        if (! $s->hasTable('helpdesk_oncall_rotations')) {
            $s->create('helpdesk_oncall_rotations', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->json('user_ids'); // array of user IDs in rotation order
                $t->integer('shift_duration_hours')->default(24);
                $t->timestamp('started_at');
                $t->foreignId('current_user_id')->nullable();
                $t->timestamp('next_handoff_at')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
    }

    public function down(): void {}
};
