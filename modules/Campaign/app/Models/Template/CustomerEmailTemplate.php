<?php

namespace Modules\Campaign\Models\Template;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Campaign\Library\Contracts\TemplateSubjectInterface;
use Modules\Campaign\Library\Traits\HasUid;
use Modules\Campaign\Services\TemplateService;

/**
 * CustomerEmailTemplate — copia de trabajo editable del usuario, creada desde
 * la galería de SystemEmailTemplate. Wrapper 1:1 sobre Template. Portado de
 * acellemail; global en este destino no-SaaS.
 */
class CustomerEmailTemplate extends Model implements TemplateSubjectInterface
{
    use HasUid;

    protected $table = 'campaign_customer_email_templates';

    /** @var list<string> */
    protected $fillable = ['name', 'template_id'];

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

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where('name', 'like', '%'.trim($keyword).'%');
    }

    public function scopeCategoryUid(Builder $query, string $categoryUid): Builder
    {
        return $query->whereHas('template.categories', function ($q) use ($categoryUid) {
            $q->where('uid', $categoryUid);
        });
    }

    public function deleteAndCleanup(): void
    {
        TemplateService::for($this)->deleteSubjectAndTemplate();
    }
}
