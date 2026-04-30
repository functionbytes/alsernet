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
        Schema::create('chat_helpcenter_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->integer('position')->default(0);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_section')->default(false);
            $table->string('visible_to_role')->nullable();
            $table->string('managed_by_role')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['parent_id', 'is_section', 'position'], 'hc_cat_parent_section_pos_idx');
            $table->index('is_section');
            $table->index('position');

            // Foreign key
            $table->foreign('parent_id', 'chat_helpcenter_categories_parent_id_foreign')
                ->references('id')
                ->on('chat_helpcenter_categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_helpcenter_categories');
    }
};
