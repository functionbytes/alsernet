<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_pre_chat_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('inbox_id')->nullable()->index();
            $table->json('fields');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_pre_chat_forms');
    }
};
