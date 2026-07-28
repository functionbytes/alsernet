<?php

namespace Modules\GiftMessage\Models;

use Illuminate\Database\Eloquent\Model;

class GiftMessageConfig extends Model
{
    protected $fillable = [
        'envelope_image',
        'card_image',
        'env_t1_x',
        'env_t1_y',
        'env_t2_x',
        'env_t2_y',
        'card_t1_x',
        'card_t1_y',
        'card_t2_x',
        'card_t2_y',
        'env_t1_font',
        'env_t1_size',
        'env_t2_font',
        'env_t2_size',
        'card_t1_font',
        'card_t1_size',
        'card_t2_font',
        'card_t2_size',
    ];

    protected function casts(): array
    {
        return [
            'env_t1_x' => 'float',
            'env_t1_y' => 'float',
            'env_t2_x' => 'float',
            'env_t2_y' => 'float',
            'card_t1_x' => 'float',
            'card_t1_y' => 'float',
            'card_t2_x' => 'float',
            'card_t2_y' => 'float',
            'env_t1_size' => 'integer',
            'env_t2_size' => 'integer',
            'card_t1_size' => 'integer',
            'card_t2_size' => 'integer',
        ];
    }

    public static function current(): self
    {
        $config = static::query()->first();

        if ($config) {
            return $config;
        }

        static::query()->create();

        return static::query()->firstOrFail();
    }
}
