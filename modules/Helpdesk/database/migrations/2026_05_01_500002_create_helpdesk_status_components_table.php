<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_status_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['operational', 'degraded', 'partial_outage', 'major_outage', 'maintenance'])
                ->default('operational')
                ->index();
            $table->unsignedSmallInteger('order')->default(0)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('helpdesk_status_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->enum('severity', ['minor', 'major', 'critical'])->default('minor')->index();
            $table->enum('status', ['investigating', 'identified', 'monitoring', 'resolved'])
                ->default('investigating')
                ->index();
            $table->json('affected_components')->nullable();
            $table->timestamp('started_at')->useCurrent()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_status_incidents');
        Schema::connection($this->connection)->dropIfExists('helpdesk_status_components');
    }
};
