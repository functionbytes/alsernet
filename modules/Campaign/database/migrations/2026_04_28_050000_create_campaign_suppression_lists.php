<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_suppression_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('uid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['email', 'domain', 'pattern'])->default('email');
            $table->string('value'); // email exacto, dominio, o regex pattern
            $table->boolean('is_global')->default(false); // true = aplica a TODAS las campañas
            $table->timestamps();
            $table->index(['type', 'value']);
            $table->index('is_global');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_suppression_lists');
    }
};
