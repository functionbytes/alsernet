<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->enum('match_type', ['contains', 'exact', 'regex'])->default('contains');
            $table->unsignedBigInteger('assign_to_user_id')->nullable();
            $table->unsignedBigInteger('assign_to_team_id')->nullable();
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_routing_rules');
    }
};
