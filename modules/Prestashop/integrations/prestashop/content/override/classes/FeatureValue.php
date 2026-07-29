<?php

class FeatureValue extends FeatureValueCore
{
    public static $definition = [
        'table' => 'feature_value',
        'primary' => 'id_feature_value',
        'multilang' => true,
        'fields' => [
            'id_feature' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'custom' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            // Lang fields
            'value' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 4096],
        ],
    ];
}
