<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_page_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('page_url');
            $table->string('page_title')->nullable();
            $table->string('referrer')->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->unsignedTinyInteger('scroll_depth')->nullable();
            $table->timestamp('visited_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('customer_id');
            $table->index('session_id');
            $table->index('visited_at');
            $table->index('created_at');
            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText(['page_url', 'page_title']);
            }

            // Foreign keys
            $table->foreign('customer_id', 'chat_page_visits_customer_id_foreign')
                ->references('id')
                ->on('chat_customers')
                ->onDelete('cascade');

            $table->foreign('session_id', 'chat_page_visits_session_id_foreign')
                ->references('id')
                ->on('chat_customer_sessions')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_page_visits');
    }
};
