<?php

class AlsernetBrandCategory extends ObjectModel
{
    /** @var int Manufacturer ID */
    public $id_manufacturer;
    /** @var int Category ID */
    public $id_category;

    public static $definition = [
        'table' => 'alsernet_brand_category',
        'primary' => 'id',
        'fields' => [
            'id_manufacturer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_category'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
        ],
    ];
}
