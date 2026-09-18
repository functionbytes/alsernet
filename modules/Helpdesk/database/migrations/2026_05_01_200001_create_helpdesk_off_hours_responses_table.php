<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_off_hours_responses', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->nullable()->comment('null = todos los canales');
            $table->text('message');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('channel');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_off_hours_responses');
    }
};
