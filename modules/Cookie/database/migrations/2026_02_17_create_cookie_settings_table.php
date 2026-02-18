<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cookie_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'cookie_enabled', 'cookie_consent_style'
            $table->text('value')->nullable(); // JSON for complex values
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_settings');
    }
};
