<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_customer_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 20)->nullable()->comment('token --psc-*/--ct-* o null = color por defecto de la UI');
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('helpdesk_customer_tag_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('helpdesk_customers')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('helpdesk_customer_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_id', 'tag_id']);
            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_customer_tag_pivot');
        Schema::connection($this->connection)->dropIfExists('helpdesk_customer_tags');
    }
};
