<?php

class ProductFilm extends ObjectModel
{
    public $id_productvideo;
    public $id_product;
    public $id_video;
    public $title;
    public $provider;
    public $url;
    public $position;
    public $available;

    public static $definition = [
        'table' => 'product_film',
        'primary' => 'id_productvideo',
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'id_video' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 322],
            'title' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'provider' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'url' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'required' => true],
            'available' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
        ],
    ];
}

