<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained('helpdesk_custom_fields')->cascadeOnDelete();
            $table->string('entity_type'); // customer|conversation
            $table->unsignedBigInteger('entity_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->unique(['field_id', 'entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_custom_field_values');
    }
};
