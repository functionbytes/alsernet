<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('assignment_mode')->default('round_robin');
            $table->boolean('default')->default(false);
            $table->timestamps();

            $table->index('default');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_groups');
    }
};
