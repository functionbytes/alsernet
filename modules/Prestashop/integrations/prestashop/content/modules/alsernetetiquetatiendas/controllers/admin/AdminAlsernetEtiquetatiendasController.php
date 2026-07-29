<?php
class AdminAlsernetEtiquetatiendasController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct(); // inicializa Context y el traductor

        $this->display = 'view';
        // usa $this->module->l() o llama a l() después del parent
        $this->meta_title = $this->module->l(
            'Etiquetas Tienda – Cargar y Previsualizar',
            'AdminAlsernetEtiquetatiendasController'
        );
    }

    public function initContent()
    {
        parent::initContent();
        $settings = AlsernetEtiquetatiendasSettings::getSettings();
        $this->context->controller->addJS($this->module->getPathUri() . 'views/js/preview.js');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/uploader.css');
        $this->context->smarty->assign([
            'backups' => $settings,
            'base_image_url' => $settings['base_image'] ? $this->module->getPathUri() . 'uploads/' . $settings['base_image'] : null,
            'generate_url' => self::$currentIndex . '&token=' . $this->token . '&ajax=1&action=GeneratePdf',
            'savepos_url' => self::$currentIndex . '&token=' . $this->token . '&ajax=1&action=SavePositions',
        ]);

        $this->setTemplate('uploader.tpl');
    }

    public function ajaxProcessSavePositions()
    {
        $fields = ['label', 'referencia', 'descripcion', 'pvprp', 'pvp'];

        // VERTICAL (1..4)
        for ($slot = 1; $slot <= 4; $slot++) {
            foreach ($fields as $key) {
                $x = (int)Tools::getValue('pos_' . $key . '_' . $slot . '_x');
                $y = (int)Tools::getValue('pos_' . $key . '_' . $slot . '_y');
                if ($x !== 0 || $y !== 0) {
                    AlsernetEtiquetatiendasSettings::updateValue('pos_' . $key . '_x' . $slot, $x);
                    AlsernetEtiquetatiendasSettings::updateValue('pos_' . $key . '_y' . $slot, $y);
                }
            }
        }

        // HORIZONTAL (1..8)
        for ($slot = 1; $slot <= 8; $slot++) {
            foreach ($fields as $key) {
                $x = (int)Tools::getValue('pos_' . $key . '_hx' . $slot . '_x');
                $y = (int)Tools::getValue('pos_' . $key . '_hx' . $slot . '_y');
                if ($x !== 0 || $y !== 0) {
                    AlsernetEtiquetatiendasSettings::updateValue('pos_' . $key . '_hx' . $slot, $x);
                    AlsernetEtiquetatiendasSettings::updateValue('pos_' . $key . '_hy' . $slot, $y);
                }
            }
        }

        die(json_encode(['success' => true]));
    }




    public function ajaxProcessGeneratePdf()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            // --- Validar archivo subido ---
            if (empty($_FILES['excel_file']) || empty($_FILES['excel_file']['tmp_name'])) {
                throw new Exception($this->module->l('Debes subir un archivo XLSX/XLS con el nombre de campo "excel_file".', 'AdminAlsernetEtiquetatiendasController'));
            }

            $origName = (string)$_FILES['excel_file']['name'];
            $tmpPath  = (string)$_FILES['excel_file']['tmp_name'];
            $ext      = Tools::strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, array('xlsx', 'xls'))) {
                throw new Exception($this->module->l('Formato no soportado. Usa archivos .xlsx o .xls.', 'AdminAlsernetEtiquetatiendasController'));
            }

            // --- Cargar vendor ---
            $vendor = _PS_MODULE_DIR_ . $this->module->name . '/vendor/autoload.php';
            if (!is_file($vendor)) {
                throw new Exception($this->module->l('No se encontró vendor/autoload.php. Ejecuta "composer install" dentro del módulo.', 'AdminAlsernetEtiquetatiendasController'));
            }
            require_once $vendor;

            // --- Leer configuración ---
            $settings = AlsernetEtiquetatiendasSettings::getSettings();
            if (!$settings) {
                throw new Exception($this->module->l('No se pudo cargar la configuración del módulo.', 'AdminAlsernetEtiquetatiendasController'));
            }

            // --- Leer Excel ---
            $importer = new \Alsernet\Etiquetas\ExcelImporter();
            $rows = $importer->read($tmpPath);
            if (empty($rows)) {
                throw new Exception($this->module->l('El archivo Excel está vacío o no tiene el formato esperado (A: REFERENCIA, B: DESCRIPCION, C: PVP RECOM PROV, D: PVP).', 'AdminAlsernetEtiquetatiendasController'));
            }

            // --- Directorio de salida ---
            $uploadDir = _PS_MODULE_DIR_ . $this->module->name . '/uploads/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            if (!is_writable($uploadDir)) {
                throw new Exception($this->module->l('La carpeta de salida no existe o no tiene permisos de escritura: ', 'AdminAlsernetEtiquetatiendasController') . $uploadDir);
            }

            $stamp       = date('Ymd_His');
            $outVertFs   = $uploadDir . 'etiquetas_vertical_' . $stamp . '.pdf';
            $outHorzFs   = $uploadDir . 'etiquetas_horizontal_' . $stamp . '.pdf';
            $baseUri     = $this->module->getPathUri() . 'uploads/';
            $resp        = array('success' => true);
            $generatedV  = false;
            $generatedH  = false;

            // --- Vertical ---
            if (!empty($settings['base_image'])) {
                $bgV = _PS_MODULE_DIR_ . $this->module->name . '/uploads/' . $settings['base_image'];
                if (is_file($bgV)) {
                    $builderV = new \Alsernet\Etiquetas\PdfBuilder($settings);
                    $builderV->buildVertical($rows, $bgV, $outVertFs);
                    if (is_file($outVertFs)) {
                        $resp['pdf_vertical'] = $baseUri . basename($outVertFs);
                        $generatedV = true;
                    }
                }
            }

            // --- Horizontal ---
            if (!empty($settings['base_image_h'])) {
                $bgH = _PS_MODULE_DIR_ . $this->module->name . '/uploads/' . $settings['base_image_h'];
                if (is_file($bgH)) {
                    $builderH = new \Alsernet\Etiquetas\PdfBuilder($bgH, $settings);
                    if (method_exists($builderH, 'buildHorizontal')) {
                        $builderH->buildHorizontal($rows, $outHorzFs);
                        if (is_file($outHorzFs)) {
                            $resp['pdf_horizontal'] = $baseUri . basename($outHorzFs);
                            $generatedH = true;
                        }
                    }
                }
            }

            if (!$generatedV && !$generatedH) {
                throw new Exception($this->module->l('No se generó ningún PDF. Verifica que exista al menos una imagen base configurada (vertical u horizontal).', 'AdminAlsernetEtiquetatiendasController'));
            }

            die(json_encode($resp));
        } catch (Exception $e) {
            http_response_code(400);
            die(json_encode(array(
                'success' => false,
                'error'   => $e->getMessage()
            )));
        }
    }




    public function getContent()
    {
        $output = '';

        if (((bool)Tools::isSubmit('submitAlsernetConfig')) === true) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Configuración guardada.'));
        }

        // Cargar JS/CSS del preview también en configuración
        $this->context->controller->addJS($this->_path . 'views/js/preview.js');
        $this->context->controller->addCSS($this->_path . 'views/css/uploader.css');

        // Settings
        $settings = AlsernetEtiquetatiendasSettings::getSettings();

        // 🔗 Construir enlaces al Admin controller con token válido
        // getAdminLink ya devuelve index.php?controller=...&token=...
        $adminLink = $this->context->link->getAdminLink('AdminAlsernetEtiquetatiendas');

        // URLs para AJAX en el preview (guardar posiciones y generar PDF)
        $savepos_url   = $adminLink . '&ajax=1&action=SavePositions';
        $generate_url  = $adminLink . '&ajax=1&action=GeneratePdf';

        // Si tenías base_image_url en configure, lo armamos aquí también
        $base_image_url = $settings['base_image']
            ? $this->_path . 'uploads/' . $settings['base_image']
            : null;

        // Pasar variables al TPL de configuración (que ahora incluye el preview)
        $this->context->smarty->assign([
            'module_dir'     => $this->_path,
            'backups'       => $settings,
            'base_image_url' => $base_image_url,
            'savepos_url'    => $savepos_url,
            'generate_url'   => $generate_url,
        ]);


        return $output . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }
}
