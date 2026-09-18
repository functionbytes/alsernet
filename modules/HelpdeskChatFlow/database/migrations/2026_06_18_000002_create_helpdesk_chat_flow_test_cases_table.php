<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_chat_flow_test_cases')) {
            $schema->create('helpdesk_chat_flow_test_cases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chat_flow_id')->index();
                $table->string('name');
                // steps: [{ input: string, expect_contains: string }]
                $table->json('steps');
                $table->string('last_result')->nullable(); // passed | failed | null
                $table->timestamp('last_run_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_chat_flow_test_cases');
    }
};
