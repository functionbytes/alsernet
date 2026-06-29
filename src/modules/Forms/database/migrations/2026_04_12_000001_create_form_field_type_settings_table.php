<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_field_type_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->unique();
            $table->string('group_name', 100);
            $table->smallInteger('group_order')->default(0);
            $table->string('label', 100);
            $table->string('icon', 100);
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->string('default_css_class', 255)->nullable();
            $table->string('default_placeholder', 255)->nullable();
            $table->json('default_settings')->nullable();
            $table->timestamps();

            $table->index(['group_order', 'sort_order']);
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_field_type_settings');
    }
};
