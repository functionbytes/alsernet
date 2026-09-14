<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailer_variable_langs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('mailer_variable_id')->constrained('mailer_variables')->onDelete('cascade');
            $table->foreignId('lang_id')->constrained('langs')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['mailer_variable_id', 'lang_id'], 'idx_34187');
            $table->index('lang_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailer_variable_langs');
    }
};
