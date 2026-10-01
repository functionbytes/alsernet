<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_ai_regression_reports')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_ai_regression_reports', function (Blueprint $table) {
            $table->id();
            $table->string('case_key', 64);
            $table->unsignedInteger('case_version')->nullable();
            $table->boolean('is_draft')->default(false);
            $table->string('status', 16)->default('queued'); // queued|running|completed|failed
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->unsignedSmallInteger('questions_total')->default(0);
            $table->unsignedSmallInteger('regressions')->default(0);
            $table->decimal('avg_score', 3, 2)->nullable();
            $table->decimal('cost_eur', 10, 6)->default(0);
            $table->json('summary')->nullable();
            $table->json('results')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['case_key', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ai_regression_reports');
    }
};
