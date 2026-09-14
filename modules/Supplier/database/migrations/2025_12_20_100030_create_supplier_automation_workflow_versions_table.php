<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_automation_workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('supplier_automation_workflows')->onDelete('cascade');

            $table->integer('version');
            $table->json('workflow_json');
            // Complete workflow definition

            $table->text('changelog')->nullable();

            $table->boolean('is_active')->default(false);
            $table->timestampTz('activated_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestampsTz();

            // Indexes
            $table->index(['workflow_id', 'version'], 'wf_versions_workflow_ver_idx');
            $table->index(['workflow_id', 'is_active'], 'wf_versions_workflow_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_workflow_versions');
    }
};
