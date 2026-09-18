<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_supervisor_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            // El usuario vive en la conexión por defecto (mysql/mariadb), no en
            // 'helpdesk' — igual que created_by/participant_user_id en
            // helpdesk_side_conversations. Sin FK real entre conexiones.
            $table->unsignedBigInteger('requested_by');
            $table->string('review_type');
            $table->text('comment')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('helpdesk_conversations')
                ->cascadeOnDelete();

            $table->index('requested_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_supervisor_reviews');
    }
};
