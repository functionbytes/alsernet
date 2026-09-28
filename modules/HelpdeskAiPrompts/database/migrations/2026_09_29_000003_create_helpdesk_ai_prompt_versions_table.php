<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ai_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 16); // 'block' | 'case'
            $table->unsignedBigInteger('subject_id');
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ai_prompt_versions');
    }
};
