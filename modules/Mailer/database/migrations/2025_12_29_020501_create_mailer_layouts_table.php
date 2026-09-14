<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailer_layouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('alias')->unique();
            $table->string('group_name');
            $table->string('code');
            $table->string('type');
            $table->boolean('is_protected')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index('alias');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailer_layouts');
    }
};
