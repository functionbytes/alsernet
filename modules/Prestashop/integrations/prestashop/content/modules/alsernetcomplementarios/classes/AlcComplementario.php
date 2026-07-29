<?php
class AlcComplementario extends ObjectModel
{
    public $id_complementario;
    public $type;
    public $title;
    public $source_refs;       // TEXT, puede ir vacío según type
    public $complement_refs;   // TEXT
    public $source_ids;        // JSON string
    public $source_brand_ids;  // JSON con ids de manufacturer (solo type=category)
    public $complement_ids;    // JSON string
    public $excluded_products; // JSON string
    public $position;          // NUEVO: orden numérico
    public $date_add;

    public static $definition = [
        'table'   => 'alsernet_complementarios',
        'primary' => 'id_complementario',
        'fields'  => [
            'type'              => ['type' => self::TYPE_STRING, 'required' => true,  'validate' => 'isGenericName', 'size' => 16],
            'title'             => ['type' => self::TYPE_STRING, 'required' => false, 'validate' => 'isCleanHtml',   'size' => 255],
            'source_refs'       => ['type' => self::TYPE_HTML,   'required' => false],
            'complement_refs'   => ['type' => self::TYPE_HTML,   'required' => false],
            'source_ids'        => ['type' => self::TYPE_HTML,   'required' => true],
            'source_brand_ids'  => ['type' => self::TYPE_HTML,   'required' => false],
            'complement_ids'    => ['type' => self::TYPE_HTML,   'required' => true],
            'excluded_products' => ['type' => self::TYPE_HTML,   'required' => false],
            'position'          => ['type' => self::TYPE_INT,    'required' => false, 'validate' => 'isUnsignedInt'],
            'date_add'          => ['type' => self::TYPE_DATE,   'required' => false],
        ],
    ];

    public function add($autodate = true, $null_values = false)
    {
        if ($autodate) {
            $this->date_add = date('Y-m-d H:i:s');
        }
        // si no viene posición, la dejamos a 0
        if ($this->position === null) {
            $this->position = 0;
        }
        return parent::add($autodate, $null_values);
    }
}
