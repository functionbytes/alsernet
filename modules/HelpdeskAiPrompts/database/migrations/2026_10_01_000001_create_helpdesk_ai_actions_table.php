<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ai_actions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 48); // slug a-z0-9_ = nombre de la herramienta
            $table->string('name', 120);
            $table->text('description'); // para la IA
            $table->string('type', 16); // builtin|bridge|http
            $table->boolean('is_active')->default(false);
            $table->json('parameters')->nullable();
            $table->json('config')->nullable();
            $table->json('response')->nullable();
            $table->json('rules')->nullable();
            $table->text('secrets')->nullable(); // cast encrypted:array
            $table->json('channels')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('key');
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ai_actions');
    }
};
