<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App id($id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App slug($slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App uid($uid)
 *
 * @mixin \Eloquent
 */
class App extends Model
{
    use HasFactory;

    protected $table = 'apps';

    protected $fillable = [
        'uid',
        'title',
        'slug',
        'reference',
        'barcode',
        'stock',
        'available',
        'created_at',
        'updated_at',
    ];

    public function scopeId($query, $id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeSlug($query, $slug)
    {
        return $query->where('slug', $slug)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public function locations()
    {
        return $this->hasMany('Modules\Catalog\Models\Location');
    }
}
