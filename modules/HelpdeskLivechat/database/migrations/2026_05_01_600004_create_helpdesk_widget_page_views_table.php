<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_widget_page_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->text('url');
            $table->string('title')->nullable();
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();

            $table->foreign('session_id')
                ->references('id')
                ->on('helpdesk_widget_sessions')
                ->cascadeOnDelete();

            $table->index(['session_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_widget_page_views');
    }
};
