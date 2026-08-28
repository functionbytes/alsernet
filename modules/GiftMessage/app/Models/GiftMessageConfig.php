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
        'env_t1_color',
        'env_t1_opacity',
        'env_t2_color',
        'env_t2_opacity',
        'card_t1_color',
        'card_t1_opacity',
        'card_t2_color',
        'card_t2_opacity',
        'env_t1_align',
        'env_t1_valign',
        'env_t2_align',
        'env_t2_valign',
        'card_t1_align',
        'card_t1_valign',
        'card_t2_align',
        'card_t2_valign',
        'paragraph_spacing',
        'min_font_size',
        'max_message_length',
        'env_t1_content',
        'card_t1_content',
        'env_t1_w',
        'env_t1_h',
        'env_t2_w',
        'env_t2_h',
        'card_t1_w',
        'card_t1_h',
        'card_t2_w',
        'card_t2_h',
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
            'env_t1_opacity' => 'integer',
            'env_t2_opacity' => 'integer',
            'card_t1_opacity' => 'integer',
            'card_t2_opacity' => 'integer',
            'min_font_size' => 'integer',
            'max_message_length' => 'integer',
            'env_t1_w' => 'float',
            'env_t1_h' => 'float',
            'env_t2_w' => 'float',
            'env_t2_h' => 'float',
            'card_t1_w' => 'float',
            'card_t1_h' => 'float',
            'card_t2_w' => 'float',
            'card_t2_h' => 'float',
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
