<?php

namespace Modules\Campaign\Models\Funnel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Campaign\Library\Traits\HasUid;
use Modules\Campaign\Models\CampaignMaillist;

/**
 * Funnel — embudo de páginas. Portado de acellemail (App\Model\Funnel),
 * global en este destino no-SaaS.
 */
class Funnel extends Model
{
    use HasUid;

    protected $table = 'campaign_funnels';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DRAFT = 'draft';

    /** @var list<string> */
    protected $fillable = ['name', 'slug', 'status', 'mail_list_id', 'settings', 'published_at'];

    protected $casts = ['settings' => 'array', 'published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Funnel $funnel) {
            if (empty($funnel->slug) && $funnel->name) {
                $funnel->slug = Str::slug($funnel->name).'-'.Str::lower(Str::random(5));
            }
        });
    }

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

    public function steps()
    {
        return $this->hasMany(FunnelStep::class)->orderBy('sort_order');
    }

    public function mailList()
    {
        return $this->belongsTo(CampaignMaillist::class, 'mail_list_id');
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

    public function deleteAndCleanup(): void
    {
        foreach ($this->steps as $step) {
            $step->deleteAndCleanup();
        }
        $this->delete();
    }

    public function duplicate(): self
    {
        $copy = self::newDefault();
        $copy->name = $this->name.' (copy)';
        $copy->mail_list_id = $this->mail_list_id;
        $copy->settings = $this->settings;
        $copy->save();

        foreach ($this->steps as $step) {
            $step->duplicateInto($copy);
        }

        return $copy;
    }
}
