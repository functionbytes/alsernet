<?php

namespace Modules\Supplier\Models\Source;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Source\SourceContentUrlFactory;
use Modules\Supplier\Services\ContentGenerationService;

/**
 * URL de referencia donde se espera encontrar contenido/fichas del proveedor
 * (páginas de producto, catálogos, prensa...). No se usa para extracción de
 * catálogo (eso lo cubre SourceOption/configuration[urls] existente) — solo
 * se sugiere a la IA como prioridad de búsqueda al generar descripciones.
 *
 * @see ContentGenerationService::preferredSourceUrls()
 */
class SourceContentUrl extends Model
{
    use HasFactory;

    protected $table = 'supplier_source_content_urls';

    protected $fillable = [
        'source_id',
        'url',
        'note',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    protected static function newFactory(): SourceContentUrlFactory
    {
        return SourceContentUrlFactory::new();
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
