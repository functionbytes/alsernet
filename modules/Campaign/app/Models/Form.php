<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Campaign\Library\Contracts\TemplateSubjectInterface;
use Modules\Campaign\Library\Traits\HasUid;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Services\TemplateService;

/**
 * Form — formulario de suscripción. Wrapper sobre Template (editable con el
 * builder, kind=form), ligado a una lista. Portado de acellemail (App\Model\Form),
 * global en este destino no-SaaS.
 *
 * Starter de form en blanco (JSON BuilderJS + HTML mínimo) para arrancar el builder.
 */
class Form extends Model implements TemplateSubjectInterface
{
    use HasUid;

    protected $table = 'campaign_forms';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    /** @var list<string> */
    protected $fillable = ['mail_list_id', 'name', 'status', 'settings'];

    protected $casts = ['settings' => 'array'];

    public static function newDefault(): self
    {
        $f = new self;
        $f->status = self::STATUS_DRAFT;

        return $f;
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function mailList(): BelongsTo
    {
        return $this->belongsTo(CampaignMaillist::class, 'mail_list_id');
    }

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

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function getThumbnailUrl(): string
    {
        return $this->template ? $this->template->getThumbnailUrl() : asset('refactor/images/favicon.svg');
    }

    public function deleteAndCleanup(): void
    {
        TemplateService::for($this)->deleteSubjectAndTemplate();
    }

    /** HTML/JSON inicial de un form en blanco para el builder. */
    private static function starterHtml(): string
    {
        return '<form><h2>Subscribe</h2><p>Sign up to our list.</p>'
            .'<input type="email" name="EMAIL" placeholder="Email address" required>'
            .'<button type="submit">Subscribe</button></form>';
    }

    /** Crea el form desde el asistente: valida, liga a la lista y crea su Template. */
    public function createFromArray(array $params)
    {
        $validator = \Validator::make($params, [
            'name' => 'required',
            'mail_list_uid' => 'required',
        ]);

        if ($validator->fails()) {
            return $validator;
        }

        $list = CampaignMaillist::where('uid', $params['mail_list_uid'] ?? null)->first();
        if (! $list) {
            throw new \Exception('Mail list not found.');
        }

        $this->name = $params['name'];
        $this->mail_list_id = $list->id;
        $this->status = self::STATUS_DRAFT;
        $this->save();

        // Template starter en blanco (kind form) asociado vía TemplateService.
        $template = Template::createBuilderTemplate('default', $this->name, Template::DEFAULT_BUILDER_JSON, self::starterHtml());
        TemplateService::for($this)->replaceWithTemplate($template);

        return $validator;
    }
}
