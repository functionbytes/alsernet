<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('favoritable_id');
            $table->string('favoritable_type', 64);
            $table->timestamps();

            $table->unique(
                ['user_id', 'favoritable_type', 'favoritable_id'],
                'media_favorites_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_favorites');
    }
};
