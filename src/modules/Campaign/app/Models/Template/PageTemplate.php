<?php

namespace Modules\Campaign\Models\Template;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Campaign\Library\Contracts\TemplateSubjectInterface;
use Modules\Campaign\Library\Traits\HasUid;

/**
 * PageTemplate — wrapper sobre un Template, para plantillas de página/landing/funnel
 * expuestas a admin en /panel/campaign/page-templates. Portado de acellemail
 * (App\Model\PageTemplate).
 *
 * Solo esquema: toda la lógica de escritura (create, copy, rename, delete, save
 * content) vive en Modules\Campaign\Services\PageTemplateService. El modelo
 * guarda esquema + relaciones + scopes + helpers de solo lectura.
 *
 * Invariante wrapper-sobre-Template: cada PageTemplate posee exactamente un
 * Template vía FK template_id 1:1 (cascade delete en BD + cleanup orquestado
 * por Modules\Campaign\Services\TemplateService::for($this)).
 */
class PageTemplate extends Model implements TemplateSubjectInterface
{
    use HasUid;

    protected $table = 'campaign_page_templates';

    /** @var list<string> */
    protected $fillable = ['name', 'template_id'];

    /** Factory para una instancia en blanco. */
    public static function newDefault(): self
    {
        return new self;
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    /**
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Filtra por palabra clave (subcadena de name). Vacío = no-op.
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where('name', 'like', '%'.trim($keyword).'%');
    }

    /**
     * Filtra por UID de categoría del Template asociado (Base / Extended).
     */
    public function scopeCategoryUid(Builder $query, string $categoryUid): Builder
    {
        return $query->whereHas('template.categories', function ($q) use ($categoryUid) {
            $q->where('uid', $categoryUid);
        });
    }
}
