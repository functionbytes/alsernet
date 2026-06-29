<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_automations')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_automations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('trigger_event', 32)->index();
            $t->json('conditions');
            $t->json('actions');
            $t->integer('order')->default(0)->index();
            $t->boolean('is_active')->default(true)->index();
            $t->integer('run_count')->default(0);
            $t->timestamp('last_run_at')->nullable();
            $t->foreignId('user_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_automations');
    }
};
