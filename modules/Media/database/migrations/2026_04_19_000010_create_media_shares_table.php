<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shareable_id');
            $table->string('shareable_type');
            $table->foreignId('shared_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 16)->default('view'); // view | comment | edit
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['shareable_id', 'shareable_type']);
            $table->unique(['shareable_id', 'shareable_type', 'shared_with_user_id'], 'media_shares_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_shares');
    }
};
