<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortcode_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug', 50)->unique();
            $table->string('label', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('shortcode_categories')->insertOrIgnore([
            ['slug' => 'estructura',  'label' => 'Estructura',  'sort_order' => 1, 'is_active' => true],
            ['slug' => 'contenido',   'label' => 'Contenido',   'sort_order' => 2, 'is_active' => true],
            ['slug' => 'media',       'label' => 'Media',       'sort_order' => 3, 'is_active' => true],
            ['slug' => 'formularios', 'label' => 'Formularios', 'sort_order' => 4, 'is_active' => true],
            ['slug' => 'tema',        'label' => 'Tema',        'sort_order' => 5, 'is_active' => true],
            ['slug' => 'otros',       'label' => 'Otros',       'sort_order' => 6, 'is_active' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shortcode_categories');
    }
};
