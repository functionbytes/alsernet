<?php
class EventModel extends ObjectModel
{

    public $id_event;
    public $title;
    public $start_at;
    public $end_at;
    public $filter_tag;
    public $management_tag;
    public $color_buttom;
    public $hover_buttom;
    public $banners;
    public $unique_banners;
    public $cms;
    public $featured;
    public $amazing;
    public $available;
    public $completed;
    public $iva;
    public $is_processing;
    public $processed;
    public $priority_flag;
    public $color_flag;
    public $banners_disabled;
    public $created_at;
    public $updated_at;

    public static $definition = [
        'table' => 'alsernet_event_manager',
        'primary' => 'id_event',
        'multilang' => true,
        'fields'    => [
            'title'           => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'start_at'        => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'end_at'          => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'filter_tag'      => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'management_tag'  => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'color_buttom'    => ['type' => self::TYPE_STRING],
            'hover_buttom'    => ['type' => self::TYPE_STRING],
            'banners'         => ['type' => self::TYPE_STRING],
            'unique_banners'  => ['type' => self::TYPE_STRING],
            'cms'             => ['type' => self::TYPE_STRING],
            'featured'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'amazing'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'available'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'completed'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'iva'             => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'is_processing'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'processed'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'priority_flag'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'color_flag'      => ['type' => self::TYPE_STRING],
            'banners_disabled'=> ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'created_at'      => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'updated_at'      => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
        ],
    ];







}
