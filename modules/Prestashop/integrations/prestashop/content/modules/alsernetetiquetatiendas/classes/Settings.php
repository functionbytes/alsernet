<?php
class AlsernetEtiquetatiendasSettings
{
    public static function getSettings()
    {
        $row = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'alsernetetiquetatiendas_settings ORDER BY id_settings DESC');

        // Defaults seguros
        $defaults = [
            'base_image' => null,

            'color_referencia'  => '#000000',
            'color_descripcion' => '#000000',
            'color_pvprp'       => '#000000',
            'color_pvp'         => '#000000',

            'font_referencia_family'  => 'helvetica',
            'font_referencia_size'    => 12,
            'font_descripcion_family' => 'helvetica',
            'font_descripcion_size'   => 12,
            'font_pvprp_family'       => 'helvetica',
            'font_pvprp_size'         => 12,
            'font_pvp_family'         => 'helvetica',
            'font_pvp_size'           => 12,

            'label_text'        => 'Precio recomendado:',
            'color_label'       => '#000000',
            'font_label_family' => 'helvetica',
            'font_label_size'   => 12,

            // 🔽🔽 NUEVOS: horizontales 🔽🔽
            'font_referencia_h_family'  => 'helvetica',
            'font_referencia_h_size'    => 12,
            'font_descripcion_h_family' => 'helvetica',
            'font_descripcion_h_size'   => 12,
            'font_pvprp_h_family'       => 'helvetica',
            'font_pvprp_h_size'         => 12,
            'font_pvp_h_family'         => 'helvetica',
            'font_pvp_h_size'           => 12,
            'font_label_h_family'       => 'helvetica',
            'font_label_h_size'         => 12,
        ];


        // Posiciones por campo y por slot (1..4)
        $fields = ['referencia', 'descripcion', 'pvprp', 'pvp'];
        foreach ($fields as $f) {
            for ($slot = 1; $slot <= 4; $slot++) {
                // Defaults razonables (coinciden con los del install.sql)
                $defaults['pos_' . $f . '_x' . $slot] = ($f === 'referencia')  ? 50 : (($f === 'descripcion') ? 50 : (($f === 'pvprp') ? 50 : 50));
                $defaults['pos_' . $f . '_y' . $slot] = ($f === 'referencia')  ? 50 : (($f === 'descripcion') ? 80 : (($f === 'pvprp') ? 110 : 140));
            }
        }

        // Posiciones del label por slot
        for ($slot = 1; $slot <= 4; $slot++) {
            $defaults['pos_label_x' . $slot] = 50;
            $defaults['pos_label_y' . $slot] = 100;
        }

        // Mezcla backups guardados con defaults
        if (!is_array($row)) {
            $row = [];
        }
        return array_merge($defaults, $row);
    }



    public static function updateValue($key, $value)
    {
        $exists = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'alsernetetiquetatiendas_settings');
        if ($exists) {
            return Db::getInstance()->update('alsernetetiquetatiendas_settings', [$key => pSQL($value)], '1');
        } else {
            return Db::getInstance()->insert('alsernetetiquetatiendas_settings', [$key => pSQL($value)]);
        }
    }
}
