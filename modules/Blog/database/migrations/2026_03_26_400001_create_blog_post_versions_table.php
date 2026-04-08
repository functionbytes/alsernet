<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->string('slug');
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->integer('version_number')->default(1);
            $table->text('change_summary')->nullable();
            $table->timestamps();

            $table->index(['blog_post_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_versions');
    }
};
