<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Metadatos SEO de un formulario público.
 *
 * En el proyecto de origen esto vivía en el módulo Seo (tabla `seo_metas`,
 * polimórfica y compartida por Page/Post/...). Aquí no hay módulo Seo, así que
 * el módulo Forms lleva su propia tabla `form_seo_metas` con el subconjunto de
 * columnas que las pantallas de Forms usan realmente (sin A/B testing, sin
 * score, sin datos de Search Console). Se mantiene polimórfica para no cerrar
 * la puerta a que otro modelo del módulo (p. ej. FormCategory) la use.
 */
class FormSeoMeta extends Model
{
    protected $table = 'form_seo_metas';

    protected $fillable = [
        'seoable_id',
        'seoable_type',
        'locale',
        'title',
        'description',
        'keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'robots',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isIndexable(): bool
    {
        return str_contains(strtolower($this->robots ?? 'index,follow'), 'index');
    }

    public function isFollowable(): bool
    {
        return str_contains(strtolower($this->robots ?? 'index,follow'), 'follow');
    }
}
