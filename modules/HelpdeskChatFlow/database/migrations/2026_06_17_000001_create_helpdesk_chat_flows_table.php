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

        if (! $schema->hasTable('helpdesk_chat_flows')) {
            $schema->create('helpdesk_chat_flows', function (Blueprint $table) {
                $table->id();
                $table->string('uid', 36)->unique();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('inbox_id')->nullable()->index();
                $table->enum('trigger_type', ['conversation_start', 'keyword', 'manual', 'no_agent'])->default('conversation_start');
                $table->json('trigger_conditions')->nullable();
                $table->json('nodes')->nullable();
                $table->json('edges')->nullable();
                $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
                $table->integer('priority')->default(0);
                $table->unsignedBigInteger('created_by');
                $table->timestamp('published_at')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['status', 'trigger_type']);
                $table->index(['inbox_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_chat_flows');
    }
};
