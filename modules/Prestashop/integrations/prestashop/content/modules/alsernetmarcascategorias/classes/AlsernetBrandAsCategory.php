<?php

class AlsernetBrandAsCategory extends ObjectModel
{
    /** @var int Manufacturer ID */
    public $id_manufacturer;

    public static $definition = [
        'table' => 'alsernet_brand_as_category',
        'primary' => 'id',
        'fields' => [
            'id_manufacturer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
        ],
    ];
}
