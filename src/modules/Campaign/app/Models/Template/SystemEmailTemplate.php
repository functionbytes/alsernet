<?php

namespace Modules\Campaign\Models\Template;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Campaign\Library\Contracts\TemplateSubjectInterface;
use Modules\Campaign\Library\Traits\HasUid;

/**
 * SystemEmailTemplate — wrapper sobre un Template, para las plantillas de email
 * del admin expuestas en /panel/campaign/manager/email-templates. Portado de
 * acellemail (App\Model\SystemEmailTemplate). Mismo patrón que PageTemplate.
 */
class SystemEmailTemplate extends Model implements TemplateSubjectInterface
{
    use HasUid;

    protected $table = 'campaign_system_email_templates';

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
}
