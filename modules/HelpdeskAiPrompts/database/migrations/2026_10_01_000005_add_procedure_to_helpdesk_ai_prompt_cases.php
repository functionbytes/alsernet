<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('helpdesk_ai_prompt_cases', 'procedure_flow_id')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_ai_prompt_cases', function (Blueprint $table) {
            $table->unsignedBigInteger('procedure_flow_id')->nullable();
            $table->json('procedure_input')->nullable();
            $table->json('procedure_outputs')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ai_prompt_cases', function (Blueprint $table) {
            $table->dropColumn(['procedure_flow_id', 'procedure_input', 'procedure_outputs']);
        });
    }
};
