<?php

namespace Modules\Campaign\Models\Funnel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Campaign\Library\Contracts\TemplateSubjectInterface;
use Modules\Campaign\Library\Traits\HasUid;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Services\TemplateService;

/**
 * FunnelStep — paso (página) de un funnel. Wrapper sobre Template, editable con
 * el builder (kind=page). Portado de acellemail (App\Model\FunnelStep).
 */
class FunnelStep extends Model implements TemplateSubjectInterface
{
    use HasUid;

    protected $table = 'campaign_funnel_steps';

    public const TYPE_LANDING = 'landing';

    public const TYPE_OPTIN = 'optin';

    public const TYPE_THANK_YOU = 'thank_you';

    public const TYPE_SALES = 'sales';

    public const TYPE_CHECKOUT = 'checkout';

    public const TYPE_CUSTOM = 'custom';

    public static function types(): array
    {
        return [self::TYPE_LANDING, self::TYPE_OPTIN, self::TYPE_THANK_YOU, self::TYPE_SALES, self::TYPE_CHECKOUT, self::TYPE_CUSTOM];
    }

    /** @var list<string> */
    protected $fillable = ['funnel_id', 'name', 'type', 'template_id', 'sort_order', 'settings'];

    protected $casts = ['settings' => 'array'];

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function setTemplate(Template $template, string $name): void
    {
        TemplateService::for($this)->setTemplate($template, $name);
    }

    public function setTemplateContent($content, $json): void
    {
        if ($this->template) {
            $this->template->updateBuilderContent($json, $content);
        }
    }

    public function getThumbnailUrl(): string
    {
        return $this->template ? $this->template->getThumbnailUrl() : asset('refactor/images/favicon.svg');
    }

    public function deleteAndCleanup(): void
    {
        TemplateService::for($this)->deleteSubjectAndTemplate();
    }

    public function duplicateInto(Funnel $funnel): self
    {
        $copy = new self;
        $copy->funnel_id = $funnel->id;
        $copy->name = $this->name;
        $copy->type = $this->type;
        $copy->sort_order = $this->sort_order;
        $copy->settings = $this->settings;
        $copy->save();

        if ($this->template) {
            $copy->setTemplate($this->template, $this->name);
        }

        return $copy;
    }
}
