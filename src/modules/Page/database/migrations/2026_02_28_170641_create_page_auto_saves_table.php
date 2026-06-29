<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('page_auto_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('data')->comment('Contenido, título, slug, etc. guardados automáticamente');
            $table->string('status')->default('draft')->comment('draft, published, pending');
            $table->longText('content')->nullable()->comment('Contenido en borrador');
            $table->timestamp('saved_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->comment('Cuándo expira este borrador');
            $table->timestamps();

            $table->index('page_id');
            $table->index('user_id');
            $table->index('saved_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_auto_saves');
    }
};
