<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection('helpdesk')->hasTable('helpdesk_ticket_categories')) {
            return;
        }

        Schema::connection('helpdesk')->create('helpdesk_ticket_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('priority_id')->nullable();
            $table->json('fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')
                ->on('helpdesk_ticket_categories')
                ->nullOnDelete();

            $table->foreign('priority_id')
                ->references('id')
                ->on('helpdesk_priorities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_ticket_templates');
    }
};
