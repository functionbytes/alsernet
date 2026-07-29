<?php

if (! defined('_PS_VERSION_')) {
    exit;
}

class AlsernetProductosDestacados extends Module
{
    /** Categorías fijas */
    private $fixed_categories = [3, 4, 5, 6, 7, 8, 9, 10];

    public function __construct()
    {
        $this->name = 'alsernetproductosdestacados';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Productos Destacados por Categoría - Alsernet');
        $this->description = $this->l('Asigna 3 productos destacados por cada categoría fija con orden.');
    }

    /* ================== instalación ================== */

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->registerHook('displayHome')
            && $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDb();
    }

    private function installDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'alsernet_productos_destacados` (
            `id_destacado` INT(11) NOT NULL AUTO_INCREMENT,
            `id_category` INT(11) NOT NULL,
            `id_product`  INT(11) NOT NULL,
            `position`    TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_destacado`),
            KEY `idx_cat_pos` (`id_category`,`position`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    private function uninstallDb()
    {
        $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'alsernet_productos_destacados`';

        return Db::getInstance()->execute($sql);
    }

    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJqueryUi('ui.autocomplete');
            $this->context->controller->addCSS(_PS_JS_DIR_.'jquery/ui/themes/base/jquery-ui.css');

            // URL para AJAX
            $moduleLink = $this->context->link->getAdminLink('AdminModules', true)
                .'&configure='.$this->name
                .'&ajax=1&action=ajaxProducts';

            // Pasar la URL a JS
            Media::addJsDef([
                'ajaxProductsUrl' => $moduleLink,
            ]);

            // Agregar archivo JS físico
            $this->context->controller->addJS($this->_path.'views/js/back.js');
        }
    }

    public function getContent()
    {
        // --- SOLO maneja AJAX si action=ajaxProducts ---
        if ((int) Tools::getValue('ajax') === 1 && Tools::getValue('action') === 'ajaxProducts') {
            $term = trim((string) Tools::getValue('term', ''));
            $results = [];

            if ($term !== '') {
                $idLang = (int) $this->context->language->id;
                $idShop = (int) $this->context->shop->id;

                $sql = 'SELECT p.id_product, pl.name
                    FROM '._DB_PREFIX_.'product p
                    INNER JOIN '._DB_PREFIX_.'product_lang pl
                        ON (p.id_product = pl.id_product
                            AND pl.id_lang = '.$idLang.'
                            AND pl.id_shop = '.$idShop.')
                    WHERE p.active = 1
                      AND (pl.name LIKE "%'.pSQL($term).'%" OR p.reference LIKE "%'.pSQL($term).'%" OR p.id_product LIKE "%'.pSQL($term).'%")
                    ORDER BY pl.name ASC
                    LIMIT 20';

                $products = Db::getInstance()->executeS($sql);

                foreach ((array) $products as $p) {
                    $results[] = [
                        'id' => (int) $p['id_product'],
                        'label' => $p['id_product'].' - '.$p['name'],
                        'value' => (int) $p['id_product'].' - '.$p['name'],
                    ];
                }
            }

            header('Content-Type: application/json; charset=utf-8');
            exit(json_encode($results));
        }

        // --- Flujo normal (formulario) ---
        $output = '';

        if (Tools::isSubmit('submitAlsernetProductosDestacados') && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $msg = $this->saveForm(); // devuelve mensaje con filas guardadas o error
            if ($msg === true) {
                $output .= $this->displayConfirmation($this->l('Configuración actualizada.'));
            } else {
                $output .= $this->displayError($msg); // muestra error detallado
            }
        }

        return $output.$this->renderForm();
    }

    private function saveForm()
    {

        $db = Db::getInstance();
        $ok = true;
        $errors = [];
        $inserted = 0;

        try {
            $db->execute('START TRANSACTION');

            // Limpieza segura (mejor que TRUNCATE en algunos hostings)
            $ok = $ok && $db->delete('alsernet_productos_destacados', '1=1');
            if (! $ok) {
                throw new Exception('No se pudo limpiar la tabla alsernet_productos_destacados: '.$db->getMsgError());
            }

            foreach ($this->fixed_categories as $catId) {
                for ($i = 1; $i <= 3; $i++) {
                    $prodId = (int) Tools::getValue("cat{$catId}_prod{$i}");
                    $pos = (int) Tools::getValue("cat{$catId}_pos{$i}");

                    if ($prodId > 0) {
                        // normaliza posición 1..3
                        if ($pos < 1 || $pos > 3) {
                            $pos = $i;
                        }

                        $ok = $db->insert('alsernet_productos_destacados', [
                            'id_category' => (int) $catId,
                            'id_product' => (int) $prodId,
                            'position' => (int) $pos,
                        ]);

                        if (! $ok) {
                            throw new Exception('Error insertando (cat='.$catId.', prod='.$prodId.', pos='.$pos.'): '.$db->getMsgError());
                        }
                        $inserted++;
                    }

                }

            }

            $db->execute('COMMIT');
        } catch (Exception $e) {
            $db->execute('ROLLBACK');

            return 'Error al guardar: '.$e->getMessage();
        }

        // Si llegó aquí, todo OK. Puedes también mostrar cuántas filas guardaste.
        return true;
    }

    /**
     * Construye el formulario como bloque HTML (type "free") para tener control del layout,
     * con <h3> por categoría y filas producto+orden en línea.
     */
    private function renderForm()
    {
        $categories = $this->fixed_categories; // tus IDs fijos
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Productos Destacados'),
                    'icon' => 'fa-duotone icon-cogs',
                ],
                'input' => [],
                'submit' => [
                    'title' => $this->l('Guardar'),
                    'name' => 'submitAlsernetProductosDestacados',
                ],
            ],
        ];

        $values = $this->getFieldsValue(); // traer valores guardados

        foreach ($categories as $catId) {
            $catName = Db::getInstance()->getValue('
            SELECT name FROM '._DB_PREFIX_.'category_lang
            WHERE id_category='.(int) $catId.' AND id_lang='.(int) $this->context->language->id
            );

            // Header categoría
            $fields_form['form']['input'][] = [
                'type' => 'html',
                'name' => 'header_cat_'.$catId,
                'html_content' => '<h3 style="margin-top:20px;">'.$catName.'</h3>',
            ];

            // Tres productos por categoría
            for ($i = 1; $i <= 3; $i++) {
                $prodId = isset($values["cat{$catId}_prod{$i}"]) ? $values["cat{$catId}_prod{$i}"] : '';
                $pos = isset($values["cat{$catId}_pos{$i}"]) ? $values["cat{$catId}_pos{$i}"] : $i;

                $html = '<div style="display:flex;gap:10px;align-items:center;margin-bottom:5px;">';

                // Input text para autocomplete
                $html .= '<input type="text" class="product_autocomplete" style="flex:1;" placeholder="'.$this->l('Buscar producto').'" name="cat'.$catId.'_prod'.$i.'" value="'.$this->getProductNameById($prodId).'"/>';

                // Orden
                $html .= '<input type="number" name="cat'.$catId.'_pos'.$i.'" value="'.$pos.'" min="0"; style="width:70px; border:1px solid #bbcdd2; border-radius:4px;height:39px; padding-left: 10px"  />';

                $html .= '</div>';

                $fields_form['form']['input'][] = [
                    'type' => 'html',
                    'label' => $this->l("Producto {$i} y orden"),
                    'name' => "cat{$catId}_row{$i}",
                    'html_content' => $html,
                ];
            }
        }

        $helper = new HelperForm;
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name;
        $helper->submit_action = 'submitAlsernetProductosDestacados';
        $helper->fields_value = $values;

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Retorna el nombre de un producto dado su ID
     */
    private function getProductNameById($idProduct)
    {
        if (! $idProduct) {
            return '';
        }

        return Db::getInstance()->getValue('
        SELECT CONCAT(pl.id_product,"-", pl.name) FROM '._DB_PREFIX_.'product_lang pl
        WHERE pl.id_product='.(int) $idProduct.' AND pl.id_lang='.(int) $this->context->language->id
        );
    }

    /**
     * Inicializa SIEMPRE todos los campos que el form espera para evitar notices,
     * y luego sobreescribe con lo que haya en BD.
     */
    private function getFieldsValue()
    {
        $values = [];

        // Inicialización (evita "Undefined index: cat10_pos3" y similares)
        foreach ($this->fixed_categories as $catId) {
            for ($i = 1; $i <= 3; $i++) {
                $values["cat{$catId}_prod{$i}"] = 0;
                $values["cat{$catId}_pos{$i}"] = $i;
            }
        }

        // Cargar datos guardados
        $rows = Db::getInstance()->executeS('
            SELECT id_category, id_product, position
            FROM `'._DB_PREFIX_.'alsernet_productos_destacados`'
        );

        foreach ($rows as $row) {
            $cat = (int) $row['id_category'];
            $pos = (int) $row['position'];
            if (! in_array($cat, $this->fixed_categories, true) || $pos < 1 || $pos > 3) {
                continue;
            }
            $values["cat{$cat}_prod{$pos}"] = (int) $row['id_product'];
            $values["cat{$cat}_pos{$pos}"] = $pos;
        }

        return $values;
    }

    /** Nombres de categorías por id_lang */
    private function getCategoryNames($idLang)
    {
        $names = [];
        $ids = implode(',', array_map('intval', $this->fixed_categories));
        $sql = 'SELECT id_category, name FROM `'._DB_PREFIX_.'category_lang`
                WHERE id_lang='.(int) $idLang.' AND id_category IN ('.$ids.')';
        $rows = Db::getInstance()->executeS($sql);
        foreach ($rows as $r) {
            $names[(int) $r['id_category']] = $r['name'];
        }

        return $names;
    }

    /** Lista de productos activos de una categoría (id, name, reference) ordenados por nombre */
    private function getProductsByCategory($idCategory, $idLang)
    {
        $sql = 'SELECT p.id_product, pl.name, p.reference
                FROM `'._DB_PREFIX_.'product` p
                INNER JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.id_product = p.id_product)
                INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.id_product = p.id_product AND pl.id_lang='.(int) $idLang.')
                WHERE cp.id_category='.(int) $idCategory.' AND p.active=1
                GROUP BY p.id_product
                ORDER BY pl.name ASC';

        return Db::getInstance()->executeS($sql) ?: [];
    }
}
