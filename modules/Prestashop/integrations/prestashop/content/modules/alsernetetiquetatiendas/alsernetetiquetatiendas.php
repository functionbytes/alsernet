<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/Settings.php';


class AlsernetEtiquetatiendas extends Module
{
    public function __construct()
    {
        $this->name = 'alsernetetiquetatiendas';
        $this->tab = 'administration';
        $this->version = '0.1.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Alsernet – Etiqueta Tiendas');
        $this->description = $this->l('Sube base de imagen, define colores y posiciones, y genera PDFs desde Excel (4 etiquetas por página).');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        // SQL
        $sql = file_get_contents(__DIR__ . '/sql/install.sql');
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        if (!$sql || !Db::getInstance()->execute($sql)) {
            return false;
        }

        // Registrar pestaña de administración (uploader/preview para usuarios no superadmin)
        $tabId = (int)Tab::getIdFromClassName('AdminAlsernetEtiquetatiendas');
        if (!$tabId) {
            $tab = new Tab();
            $tab->class_name = 'AdminAlsernetEtiquetatiendas';
            $tab->module = $this->name;
            $tab->id_parent = (int)Tab::getIdFromClassName('IMPROVE'); // Menú lateral: Mejora > (puedes cambiar de padre)
            foreach (Language::getLanguages(false) as $lang) {
                $tab->name[$lang['id_lang']] = 'Etiquetas Tienda';
            }
            if (!$tab->add()) {
                return false;
            }
        }

        return true;
    }

    public function uninstall()
    {
        // Borrar pestaña
        if ($id = (int)Tab::getIdFromClassName('AdminAlsernetEtiquetatiendas')) {
            $tab = new Tab($id);
            $tab->delete();
        }

        // SQL
        $sql = file_get_contents(__DIR__ . '/sql/uninstall.sql');
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        if ($sql) {
            Db::getInstance()->execute($sql);
        }

        return parent::uninstall();
    }

    /**
     * Configuración en BO > Módulos
     */
    public function getContent()
    {
        $output = '';
        if (((bool)Tools::isSubmit('submitAlsernetConfig')) === true) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Configuración guardada.'));
        }
        $this->context->controller->addJS($this->_path . 'views/js/preview.js');
        $this->context->controller->addCSS($this->_path . 'views/css/uploader.css');

        $settings = AlsernetEtiquetatiendasSettings::getSettings();

        $adminLink = $this->context->link->getAdminLink('AdminAlsernetEtiquetatiendas');

        // URLs para AJAX en el preview (guardar posiciones y generar PDF)
        $savepos_url   = $adminLink . '&ajax=1&action=SavePositions';
        $generate_url  = $adminLink . '&ajax=1&action=GeneratePdf';

        $fonts = array(
            'helvetica',
            'helveticaB',
            'helveticaI',
            'helveticaBI',
            'times',
            'timesB',
            'timesI',
            'timesBI',
            'courier',
            'courierB',
            'courierI',
            'courierBI',
            'dejavusans',
            'dejavusansb',
            'dejavusansi',
            'dejavusansbi',
            'dejavuserif',
            'dejavuserifb',
            'dejavuserifi',
            'dejavuserifbi',
            'freesans',
            'freesansb',
            'freesansi',
            'freesansbi',
            'freeserif',
            'freeserifb',
            'freeserifi',
            'freeserifbi'
        );

        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'backups'   => $settings,
            'img_abs'    => $settings['base_image'] ? _PS_BASE_URL_ . _THEME_PROD_DIR_ : '',
            'savepos_url'    => $savepos_url,
            'generate_url'   => $generate_url,
            'fonts' => $fonts,
        ]);

        return $output . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    protected function postProcess()
    {
        $errors = [];

        // === 1) Subidas de imagen base (Vertical y Horizontal) ===
        $uploadImage = function ($inputName, $settingKey) use (&$errors) {
            if (!isset($_FILES[$inputName]) || empty($_FILES[$inputName]['tmp_name'])) {
                return;
            }
            if (!is_uploaded_file($_FILES[$inputName]['tmp_name'])) {
                $errors[] = $this->l('Error al subir la imagen: ') . $inputName;
                return;
            }
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            $mime = @mime_content_type($_FILES[$inputName]['tmp_name']);
            if (!isset($allowed[$mime])) {
                $errors[] = $this->l('Formato de imagen no permitido (solo JPG/PNG) en ') . $inputName;
                return;
            }
            $ext = $allowed[$mime];
            $destDir = _PS_MODULE_DIR_ . $this->name . '/uploads/';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            $name = $settingKey . '_' . date('Ymd_His') . '.' . $ext; // p.ej. base_image_20250101_120000.jpg
            $dest = $destDir . $name;
            if (!move_uploaded_file($_FILES[$inputName]['tmp_name'], $dest)) {
                $errors[] = $this->l('No se pudo guardar la imagen para ') . $inputName;
                return;
            }
            AlsernetEtiquetatiendasSettings::updateValue($settingKey, pSQL($name));
        };

        // Vertical (ya existente)
        $uploadImage('ALSERNET_BASE_IMAGE', 'base_image');
        // Horizontal (nuevo)
        $uploadImage('ALSERNET_BASE_IMAGE_H', 'base_image_h');

        // === 2) Colores por campo ===
        $colorKeys = ['color_referencia', 'color_descripcion', 'color_pvprp', 'color_pvp', 'color_label'];
        foreach ($colorKeys as $k) {
            $val = Tools::getValue($k);
            if ($val !== null && $val !== '') {
                $val = trim($val);
                if ($val && preg_match('/^#?[0-9a-fA-F]{6}$/', $val)) {
                    if ($val[0] !== '#') {
                        $val = '#' . $val;
                    }
                    AlsernetEtiquetatiendasSettings::updateValue($k, pSQL($val));
                }
            }
        }

        // === 3) Fuentes por campo (familia + tamaño pt) ===
        $fontMap = [
            // Vertical
            'font_referencia_family',
            'font_referencia_size',
            'font_descripcion_family',
            'font_descripcion_size',
            'font_pvprp_family',
            'font_pvprp_size',
            'font_pvp_family',
            'font_pvp_size',
            'font_label_family',
            'font_label_size',

            // Horizontal
            'font_referencia_h_family',
            'font_referencia_h_size',
            'font_descripcion_h_family',
            'font_descripcion_h_size',
            'font_pvprp_h_family',
            'font_pvprp_h_size',
            'font_pvp_h_family',
            'font_pvp_h_size',
            'font_label_h_family',
            'font_label_h_size',
        ];

        foreach ($fontMap as $k) {
            $v = Tools::getValue($k);
            if ($v !== null && $v !== '') {
                if (strpos($k, '_size') !== false) {
                    AlsernetEtiquetatiendasSettings::updateValue($k, (int)$v);
                } else {
                    AlsernetEtiquetatiendasSettings::updateValue($k, pSQL(trim($v)));
                }
            }
        }

        // === 4) Texto fijo (label) ===
        $labelText = Tools::getValue('label_text');
        if ($labelText !== null) {
            AlsernetEtiquetatiendasSettings::updateValue('label_text', pSQL($labelText, true)); // permitir texto
        }

        // === 5) Tamaños de caja (px) ===
        $boxKeys = [
            'box_referencia_w',
            'box_referencia_h',
            'box_descripcion_w',
            'box_descripcion_h',
            'box_pvprp_w',
            'box_pvprp_h',
            'box_pvp_w',
            'box_pvp_h',
            'box_label_w',
            'box_label_h',
        ];
        foreach ($boxKeys as $k) {
            $v = Tools::getValue($k);
            if ($v !== null && $v !== '') {
                AlsernetEtiquetatiendasSettings::updateValue($k, (int)$v);
            }
        }

        // === 6) Timestamp de actualización ===
        AlsernetEtiquetatiendasSettings::updateValue('updated_at', date('Y-m-d H:i:s'));

        // Mostrar errores si hubieran
        if (!empty($errors)) {
            foreach ($errors as $e) {
                $this->context->controller->errors[] = $e;
            }
        }
    }
}
