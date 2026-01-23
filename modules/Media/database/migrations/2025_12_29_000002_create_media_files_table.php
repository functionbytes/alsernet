<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('name');
            $table->string('mime_type');
            $table->string('type');
            $table->bigInteger('size');
            $table->string('url');
            $table->text('alt')->nullable();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('disk', 50)->default('media');
            $table->json('metadata')->nullable();
            $table->string('visibility')->default('private');
            $table->boolean('is_favorite')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('folder_id');
            $table->index('user_id');
            $table->index('mime_type');
            $table->index('type');
            $table->index('disk');

            // SQLite doesn't support fulltext indexes
            if (config('database.default') !== 'sqlite') {
                $table->fullText('name');
            }

            $table->foreign('folder_id')->references('id')->on('media_folders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
