<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->nullable(); // e.g. 'empresa.com' for auto-linking
            $table->string('industry')->nullable();
            $table->string('size')->nullable(); // 1-10, 11-50, 51-200, 201-1000, 1000+
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->tinyInteger('health_score')->nullable(); // 0-100
            $table->decimal('total_revenue', 15, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_companies');
    }
};
