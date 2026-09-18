<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metadatos SEO de los formularios públicos.
 *
 * Sustituye a la tabla `seo_metas` del módulo Seo del proyecto de origen, que
 * aquí no existe: se queda solo con las columnas que las pantallas de Forms
 * usan (ver Modules\Forms\Models\FormSeoMeta).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_seo_metas', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('locale', 10)->nullable()->comment('null = metadatos por defecto, sin idioma');

            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('keywords')->nullable();

            $table->string('og_title', 255)->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('og_type', 50)->default('website');

            $table->string('twitter_card', 50)->default('summary');
            $table->string('twitter_title', 255)->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image', 500)->nullable();

            $table->string('canonical_url', 500)->nullable();
            $table->string('robots', 100)->default('index,follow');

            $table->timestamps();

            // updateSeoMeta() hace updateOrCreate sobre esta terna.
            $table->unique(['seoable_type', 'seoable_id', 'locale'], 'form_seo_metas_seoable_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_seo_metas');
    }
};
