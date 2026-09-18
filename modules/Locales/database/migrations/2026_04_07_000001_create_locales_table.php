<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('ISO locale code (es, en, pt...)');
            $table->string('name', 100)->comment('English name (Spanish, English...)');
            $table->string('native_name', 100)->comment('Native name (Español, English...)');
            $table->string('flag', 10)->nullable()->comment('Emoji flag');
            $table->boolean('is_default')->default(false)->comment('Only one can be default');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index(['is_active', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
