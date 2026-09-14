<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // customer|conversation
            $table->string('key'); // slug, e.g. "plan_type"
            $table->string('label');
            $table->string('type'); // text|number|date|boolean|select|multi-select
            $table->json('options')->nullable(); // for select / multi-select types
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['entity_type', 'key']);
            $table->index('entity_type');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_custom_fields');
    }
};
