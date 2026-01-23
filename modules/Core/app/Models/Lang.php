<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uid
 * @property string $title
 * @property string $iso_code
 * @property string $lenguage_code
 * @property string|null $locate
 * @property string|null $date_format_full
 * @property string|null $date_format_lite
 * @property int $available
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\System\Models\Categorie> $categories
 * @property-read int|null $categories_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang ascending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang descending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang id($id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang iso($iso)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang locate($iso)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang search($keyword)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang uid($uid)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereDateFormatFull($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereDateFormatLite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereIsoCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereLenguageCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereLocate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lang whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Lang extends Model
{
    use HasFactory;

    protected $table = 'langs';

    protected $fillable = [
        'uid',
        'title',
        'iso_code',
        'lenguage_code',
        'locate',
        'date_format_full',
        'date_format_lite',
        'available',
        'created_at',
        'updated_at',
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeId($query, $id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeIso($query, $iso)
    {
        return $query->where('iso_code', $iso)->first();
    }

    public function scopeLocate($query, $iso)
    {
        return $query->where('locate', $iso)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public static function getSelectOptions()
    {
        $options = self::available()->get()->map(function ($item) {
            return ['value' => $item->id, 'text' => $item->name];
        });

        // japan only en and ja
        if (config('custom.japan')) {
            $options = self::active()->get()->filter(function ($item) {
                return in_array($item->code, ['en', 'ja']);
            })->map(function ($item) {
                return ['value' => $item->id, 'text' => $item->name];
            });
        }

        return $options;
    }

    /**
     * Search items.
     *
     * @return collect
     */
    public function scopeSearch($query, $keyword)
    {
        // Keyword
        if (! empty(trim($keyword))) {
            $keyword = trim($keyword);
            foreach (explode(' ', $keyword) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('languages.name', 'like', '%'.$keyword.'%')
                        ->orwhere('languages.code', 'like', '%'.$keyword.'%')
                        ->orwhere('languages.region_code', 'like', '%'.$keyword.'%');
                });
            }
        }
    }

    public function getBuilderLang()
    {
        return include $this->languageDir().DIRECTORY_SEPARATOR.'builder.php';
    }

    public function languageDir()
    {
        return resource_path(join_paths('lang', $this->iso_code));
    }

    public function categories()
    {
        return $this->belongsToMany('Modules\System\Models\Categorie', 'lang_categorie', 'lang_id', 'categorie_id');
    }

    /**
     * Get the default language ID
     */
    public static function getDefaultLangId(): int
    {
        return self::available()->first()?->id ?? 1;
    }
}
