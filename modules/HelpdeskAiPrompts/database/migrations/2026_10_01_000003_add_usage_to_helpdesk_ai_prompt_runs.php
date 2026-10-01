<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('helpdesk_ai_prompt_runs', 'prompt_tokens')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_ai_prompt_runs', function (Blueprint $table) {
            $table->unsignedInteger('prompt_tokens')->default(0)->after('latency_ms');
            $table->unsignedInteger('completion_tokens')->default(0)->after('prompt_tokens');
            $table->string('model', 64)->nullable()->after('completion_tokens');
            $table->decimal('cost_eur', 10, 6)->default(0)->after('model');
            $table->unsignedSmallInteger('calls')->default(0)->after('cost_eur');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ai_prompt_runs', function (Blueprint $table) {
            $table->dropColumn(['prompt_tokens', 'completion_tokens', 'model', 'cost_eur', 'calls']);
        });
    }
};
