<?php

namespace App\Models\Comparator;

use App\Models\Lang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comparator extends Model
{
    use HasFactory;

    protected $table = 'comparators';

    protected $fillable = [
        'uid',
        'title',
        'api_key',
        'api_secret',
        'api_username',
        'api_config',
        'available',
        'created_at',
        'updated_at'
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }



    public function configurationForLang()
    {
        return $this->hasOne(ComparatorConfiguration::class, 'comparator_id')
            ->whereHas('lang', function ($q) {
                $q->where('iso_code', 'es'); // o parámetro si lo haces dinámico
            });
    }

    public function scopeWithLangConfiguration($query, string $langCode = 'es')
    {
        return $query->with(['configurationForLang' => function ($q) use ($langCode) {
            $q->whereHas('lang', fn($lq) => $lq->where('code', $langCode));
        }]);
    }

    public function scopeWithConfigurationForLangByUid($query, string $uid, string $iso)
    {
        return $query->where('uid', $uid)
            ->with([
                'langs' => function ($q) use ($iso) {
                    $q->where('iso_code', $iso);
                },
                'configurations' => function ($q) use ($iso) {
                    $q->whereHas('lang', function ($langQuery) use ($iso) {
                        $langQuery->where('iso_code', $iso);
                    });
                },
            ]);
    }

    public function langs(): BelongsToMany
    {
        return $this->belongsToMany(
            Lang::class,
            'comparators_lang',
            'comparator_id',
            'lang_id'
        );
    }

    public function comparatorLangs(): HasMany
    {
        return $this->hasMany(ComparatorLang::class, 'comparator_id');
    }


    public function scopeWithApiKeyByLangIso($query, string $iso)
    {
        return $query->whereHas('comparatorLangs.lang', function ($q) use ($iso) {
            $q->where('iso_code', $iso);
        })->with(['comparatorLangs' => function ($q) use ($iso) {
            $q->whereHas('lang', fn($q2) => $q2->where('iso_code', $iso))->with('lang');
        }]);
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(ComparatorConfiguration::class, 'comparator_id');
    }

    public function getConfigurationForLangAttribute()
    {
        return $this->configurations->first();
    }



}
