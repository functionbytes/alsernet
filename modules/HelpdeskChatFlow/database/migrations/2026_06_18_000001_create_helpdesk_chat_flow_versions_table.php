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

        if (! $schema->hasTable('helpdesk_chat_flow_versions')) {
            $schema->create('helpdesk_chat_flow_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chat_flow_id')->index();
                $table->string('name');
                $table->json('nodes');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['chat_flow_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_chat_flow_versions');
    }
};
