<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_agent_inbox_capacity', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('inbox_id');
            $table->integer('max_concurrent')->default(0); // 0 = unlimited
            $table->boolean('accepts_new')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'inbox_id']);
            $table->index('inbox_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_agent_inbox_capacity');
    }
};
