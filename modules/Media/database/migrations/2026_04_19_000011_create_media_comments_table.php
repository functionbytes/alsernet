<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commentable_id');
            $table->string('commentable_type');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('media_comments')->cascadeOnDelete();
            $table->text('content');
            $table->json('mentions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_id', 'commentable_type']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_comments');
    }
};
