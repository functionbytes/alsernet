<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monitores de job/batch del módulo Campaign. Reemplaza el dependency rota a
 * Modules\Horizon\Models\JobMonitor (módulo inexistente). Polimórfico vía
 * (subject_name, subject_id) para que cualquier modelo del módulo pueda
 * registrar y cancelar sus jobs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_job_monitors', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_name');
            $table->unsignedBigInteger('subject_id');
            $table->string('job_type');
            $table->string('job_id')->nullable()->index();
            $table->string('batch_id')->nullable()->index();
            $table->string('status', 32)->default('queued')->index();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['subject_name', 'subject_id'], 'campaign_jm_subject_idx');
            $table->index(['subject_name', 'subject_id', 'job_type'], 'campaign_jm_subject_jobtype_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_job_monitors');
    }
};
