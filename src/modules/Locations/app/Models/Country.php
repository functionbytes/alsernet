<?php

namespace Modules\Locations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $table = 'locations_countries';

    protected $fillable = [
        'name',
        'code',
        'phone_code',
        'currency_code',
        'currency_symbol',
        'flag_emoji',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
