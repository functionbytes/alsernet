<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ticket_category_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_category_id');
            $table->string('type', 50)->default('text');
            $table->string('key', 100);
            $table->string('label');
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->string('default_value')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->string('width', 20)->default('full');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('ticket_category_id')
                ->references('id')
                ->on('helpdesk_ticket_categories')
                ->cascadeOnDelete();

            $table->index(['ticket_category_id', 'sort_order'], 'htcf_category_sort_idx');
            $table->unique(['ticket_category_id', 'key'], 'htcf_category_key_unique');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_category_fields');
    }
};
