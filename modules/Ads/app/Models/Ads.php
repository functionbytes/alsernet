<?php

namespace Modules\Ads\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ads\Enums\AdsStatus;
use Modules\Ads\Enums\AdsType;

class Ads extends Model
{
    use HasFactory;

    protected $table = 'ads';

    protected $fillable = [
        'name',
        'key',
        'status',
        'open_in_new_tab',
        'expired_at',
        'location',
        'image',
        'tablet_image',
        'mobile_image',
        'url',
        'clicked',
        'order',
        'ads_type',
        'google_adsense_slot_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdsStatus::class,
            'ads_type' => AdsType::class,
            'expired_at' => 'date',
            'open_in_new_tab' => 'boolean',
            'clicked' => 'integer',
            'order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AdsStatus::PUBLISHED)
            ->where(function (Builder $q) {
                $q->whereNull('expired_at')
                    ->orWhereDate('expired_at', '>=', now());
            });
    }

    public function scopeByLocation(Builder $query, string $location): Builder
    {
        return $query->where('location', $location);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(AdsTranslation::class, 'ad_id');
    }

    /**
     * Get a translated field value with fallback to the base field.
     */
    public function trans(string $field, ?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();

        $translation = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();

        if ($translation && filled($translation->$field)) {
            return $translation->$field;
        }

        return $this->$field ?? null;
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AdsClick::class, 'ads_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    public function tabletImageUrl(): ?string
    {
        $img = $this->tablet_image ?: $this->image;

        return $img ? asset('storage/'.$img) : null;
    }

    public function mobileImageUrl(): ?string
    {
        $img = $this->mobile_image ?: $this->tablet_image ?: $this->image;

        return $img ? asset('storage/'.$img) : null;
    }
}
