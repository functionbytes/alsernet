<?php

class FeatureValue extends FeatureValueCore
{

public static $definition = array(
    'table' => 'feature_value',
    'primary' => 'id_feature_value',
    'multilang' => true,
    'fields' => array(
        'id_feature' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
        'custom' =>     array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),

        // Lang fields
        'value' =>      array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 4096),
    ),
);
}