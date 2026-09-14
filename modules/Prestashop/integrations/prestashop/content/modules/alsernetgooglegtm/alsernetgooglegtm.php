<?php

if (! defined('_PS_VERSION_')) {
    exit;
}

class Alsernetgooglegtm extends Module
{
    public $enabled;

    public $tag;

    public $tableName = _DB_PREFIX_.'alsernet_google_gtm';

    public function __construct()
    {
        $this->name = 'alsernetgooglegtm';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->displayName = $this->l('Alsernet - Gestionar etiquetas de Google Tag Manager');
        $this->description = $this->l('Este módulo gestiona las etiquetas para la analítica de datos con Google');
        $this->bootstrap = true;

        parent::__construct();
    }

    public function install()
    {
        if (! parent::install() ||
            ! $this->registerModuleTab() ||
            ! $this->installDb() ||
            ! $this->registerHook('displayOrderConfirmation') ||
            ! $this->registerHook('displayPaymentByBinaries') ||
            ! $this->registerHook('displayConfirmDeliveryOption') ||
            ! $this->registerHook('displayConfirmAddress') ||
            ! $this->registerHook('displayNewCustomer') ||
            ! $this->registerHook('displayShoppingCartFooter') ||
            ! $this->registerHook('displayProductAlsernet') ||
            ! $this->registerHook('displayProductAlsernetFooter') ||
            ! $this->registerHook('displayFooterCategory') ||
            ! $this->registerHook('displayHome') ||
            ! $this->registerHook('header')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        // !parent::uninstall()||
        // if  (
        // !$this->uninstallDb() ||
        // $this->unregisterModuleTab();

        Configuration::deleteByName('displayOrderConfirmation');

        Configuration::deleteByName('displayPaymentByBinaries');

        Configuration::deleteByName('displayConfirmDeliveryOption');

        Configuration::deleteByName('displayConfirmAddress');
        Configuration::deleteByName('displayNewCustomer');
        Configuration::deleteByName('displayShoppingCartFooter');
        Configuration::deleteByName('displayProductAlsernet');
        Configuration::deleteByName('displayProductAlsernetFooter');
        Configuration::deleteByName('displayFooterCategory');
        Configuration::deleteByName('displayHome');
        Configuration::deleteByName('header');
        // ){
        //     return false;
        // }

        return parent::uninstall();

        // Configuration::deleteByName('gtm_enabled');
        // Configuration::deleteByName('gtm_enabled_test');
        // Configuration::deleteByName('task_manager');
        // Configuration::deleteByName('task_manager_test');

        // return parent::uninstall();
    }

    public function installDb()
    {
        // $sql = "CREATE TABLE IF NOT EXISTS aalv_alsernet_google_gtm (
        //         id_alsernet_google_gtm INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        //         gtm_enabled INT(1) DEFAULT 0,
        //         gtm_enabled_test INT(1) DEFAULT 0,
        //         task_manager VARCHAR(255) NOT NULL,
        //         task_manager_test VARCHAR(255) NOT NULL,
        //         purchase_layer INT(1) DEFAULT 0,
        //         payment_layer INT(1) DEFAULT 0,
        //         shipping_layer INT(1) DEFAULT 0,
        //         address_layer INT(1) DEFAULT 0,
        //         checkout_layer INT(1) DEFAULT 0,
        //         cart_layer INT(1) DEFAULT 0,
        //         remove_from_cart_layer INT(1) DEFAULT 0,
        //         add_to_cart_layer INT(1) DEFAULT 0,
        //         view_item_layer INT(1) DEFAULT 0,
        //         select_item_layer INT(1) DEFAULT 0,
        //         view_item_list_layer INT(1) DEFAULT 0,
        //         select_promotion_layer INT(1) DEFAULT 0,
        //         view_promotion_layer INT(1) DEFAULT 0,
        //         PRIMARY KEY (id_alsernet_google_gtm)
        //     ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        // if (!Db::getInstance()->execute($sql)){
        //     return false;
        // }

        return true;
    }

    public function uninstallDb()
    {

        // $sql = "DROP TABLE IF EXISTS "._DB_PREFIX_."alsernet_google_gtm;";

        // if (!Db::getInstance()->execute($sql)){
        //     return false;
        // }
        return true;
    }

    public function registerModuleTab()
    {
        $tab = new Tab;
        $tab->active = 1;
        $tab->class_name = 'AlsernetGtm';
        $tab->name = [];
        $tab->icon = 'local_shipping';
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Gestión Google Tag Manager';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('DEFAULT');
        $tab->module = $this->name;

        return $tab->save();
    }

    public function unregisterModuleTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AlsernetGtm');
        if ($id_tab) {
            $tab = new Tab($id_tab);

            return $tab->delete();
        }

        return true;
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit'.$this->name)) {

            if ($this->_postValidation()) {
                if ($this->postProcess()) {
                    $output .= $this->displayConfirmation($this->l('La configuración se ha guardado'));
                }
            } else {
                $output .= $this->displayWarning($this->l('Algo ha ido mal. Por favor, revisa la configuración'));
            }
        }

        $output .= $this->displayForm();

        return $output;
    }

    public function displayForm()
    {
        $helper = new HelperForm;
        $helper->show_toolbar = false;
        $lang = new Language((int) Context::getContext()->language->id);
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = false;

        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->title = $this->displayName;
        $helper->submit_action = 'submit'.$this->name;

        $fields_forms = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuración'),
                    'icon' => 'fa-duotone icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar Google Tag Manager'),
                        'name' => 'gtm_enabled',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->l('Activa ON para que Google Tag Manager comience a funcionar'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Off'),
                            ],
                        ],
                    ],

                    [
                        'type' => 'switch',
                        'label' => $this->l('MODO PRUEBA - Activar Google Tag Manager en modo prueba'),
                        'name' => 'gtm_enabled_test',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->l('Activa ON para que Google Tag Manager comience a funcionar en modo prueba'),
                        'values' => [
                            [
                                'id' => 'active_on_test',
                                'value' => true,
                                'label' => $this->l('On'),
                            ],
                            [
                                'id' => 'active_off_test',
                                'value' => false,
                                'label' => $this->l('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Tag Manager ID'),
                        'name' => 'task_manager',
                        'size' => 20,
                        'required' => true,
                        'hint' => $this->l('Introduce aquí el GTM ID (GTM-XXXXXX).'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('MODO PRUEBA - Tag Manager ID'),
                        'name' => 'task_manager_test',
                        'size' => 20,
                        'required' => true,
                        'hint' => $this->l('Introduce aquí el GTM ID del MODO PRUEBA (GTM-XXXXXX).'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('COMPRA'),
                        'name' => 'purchase_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa COMPRA'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('MÉTODO DE PAGO'),
                        'name' => 'payment_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa MÉTODO DE PAGO'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('MÉTODO DE ENVÍO'),
                        'name' => 'shipping_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa MÉTODO DE ENVÍO'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('DATOS PERSONALES'),
                        'name' => 'address_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa DATOS PERSONALES'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('INICIO PROCESO DE COMPRA'),
                        'name' => 'checkout_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa INICIO PROCESO DE COMPRA'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('VISUALIZACIÓN DEL CARRITO'),
                        'name' => 'cart_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa VISUALIZACIÓN DEL CARRITO'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('BORRAR ARTÍCULO DEL CARRITO'),
                        'name' => 'remove_from_cart_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa BORRAR ARTÍCULO DEL CARRITO'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('AÑADIR AL CARRITO'),
                        'name' => 'add_to_cart_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa AÑADIR AL CARRITO'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('VISUALIZAR FICHA DEL ARTÍCULO'),
                        'name' => 'view_item_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa VISUALIZAR FICHA DEL ARTÍCULO'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('SELECCIONAR ARTÍCULO DE UN LISTADO'),
                        'name' => 'select_item_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa SELECCIONAR ARTÍCULO DE UN LISTADO'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('VISUALIZAR UN LISTADO DE ARTÍCULOS'),
                        'name' => 'view_item_list_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa VISUALIZAR UN LISTADO DE ARTÍCULOS'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('HACER CLICK SOBRE UN BANNER'),
                        'name' => 'select_promotion_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa HACER CLICK SOBRE UN BANNER'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('VISUALIZAR BANNER'),
                        'name' => 'view_promotion_layer',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Marca ON para activar la capa VISUALIZAR BANNER'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        // Load current value
        $config = $this->getInfo(); // Db::getInstance()->executeS('SELECT * FROM '. $this->tableName . ' LIMIT 1')[0];

        if (! empty($config)) {
            $helper->fields_value['gtm_enabled'] = $config['gtm_enabled'];
            $helper->fields_value['gtm_enabled_test'] = $config['gtm_enabled_test'];

            $helper->fields_value['task_manager'] = $config['task_manager'];
            $helper->fields_value['task_manager_test'] = $config['task_manager_test'];

            $helper->fields_value['purchase_layer'] = $config['purchase_layer'];
            $helper->fields_value['payment_layer'] = $config['payment_layer'];
            $helper->fields_value['shipping_layer'] = $config['shipping_layer'];
            $helper->fields_value['address_layer'] = $config['address_layer'];
            $helper->fields_value['checkout_layer'] = $config['checkout_layer'];
            $helper->fields_value['cart_layer'] = $config['cart_layer'];
            $helper->fields_value['remove_from_cart_layer'] = $config['remove_from_cart_layer'];
            $helper->fields_value['add_to_cart_layer'] = $config['add_to_cart_layer'];
            $helper->fields_value['view_item_layer'] = $config['view_item_layer'];
            $helper->fields_value['select_item_layer'] = $config['select_item_layer'];
            $helper->fields_value['view_item_list_layer'] = $config['view_item_list_layer'];
            $helper->fields_value['select_promotion_layer'] = $config['select_promotion_layer'];
            $helper->fields_value['view_promotion_layer'] = $config['view_promotion_layer'];
        }

        return $helper->generateForm([$fields_forms]);
    } // function displayForm

    protected function postProcess()
    {
        $gtmEnabled = (bool) Tools::getValue('gtm_enabled');
        $gtmEnabledTest = (bool) Tools::getValue('gtm_enabled_test');
        $taskManager = Tools::getValue('task_manager');
        $taskManagerTest = Tools::getValue('task_manager_test');
        $purchase_layer = (bool) Tools::getValue('purchase_layer');
        $payment_layer = (bool) Tools::getValue('payment_layer');
        $shipping_layer = (bool) Tools::getValue('shipping_layer');
        $address_layer = (bool) Tools::getValue('address_layer');
        $checkout_layer = (bool) Tools::getValue('checkout_layer');
        $cart_layer = (bool) Tools::getValue('cart_layer');
        $remove_from_cart_layer = (bool) Tools::getValue('remove_from_cart_layer');
        $add_to_cart_layer = (bool) Tools::getValue('add_to_cart_layer');
        $view_item_layer = (bool) Tools::getValue('view_item_layer');
        $select_item_layer = (bool) Tools::getValue('select_item_layer');
        $view_item_list_layer = (bool) Tools::getValue('view_item_list_layer');
        $select_promotion_layer = (bool) Tools::getValue('select_promotion_layer');
        $view_promotion_layer = (bool) Tools::getValue('view_promotion_layer');

        $validamos_insert = Db::getInstance()->getValue('SELECT COUNT(*) FROM '.$this->tableName);

        if ($validamos_insert == 0) {
            $updateQuery = 'INSERT INTO '.$this->tableName.'
            (gtm_enabled,gtm_enabled_test,task_manager,task_manager_test,purchase_layer,payment_layer,shipping_layer,address_layer,checkout_layer,
            cart_layer,remove_from_cart_layer,add_to_cart_layer,view_item_layer,select_item_layer,view_item_list_layer,select_promotion_layer,
            view_promotion_layer)
            VALUES
            ('.(int) $gtmEnabled.','.(int) $gtmEnabledTest.',"'.pSQL($taskManager).'","'.pSQL($taskManagerTest).'",'.(int) $purchase_layer.',
            '.(int) $payment_layer.','.(int) $shipping_layer.','.(int) $address_layer.','.(int) $checkout_layer.','.(int) $cart_layer.',
            '.(int) $remove_from_cart_layer.','.(int) $add_to_cart_layer.','.(int) $view_item_layer.','.(int) $select_item_layer.',
            '.(int) $view_item_list_layer.','.(int) $select_promotion_layer.','.(int) $view_promotion_layer.')';
        } else {
            $updateQuery = 'UPDATE '.$this->tableName.'
            SET
                gtm_enabled = '.(int) $gtmEnabled.',
                gtm_enabled_test = '.(int) $gtmEnabledTest.',
                task_manager = "'.pSQL($taskManager).'",
                task_manager_test = "'.pSQL($taskManagerTest).'",
                purchase_layer = '.(int) $purchase_layer.',
                payment_layer = '.(int) $payment_layer.',
                shipping_layer = '.(int) $shipping_layer.',
                address_layer = '.(int) $address_layer.',
                checkout_layer = '.(int) $checkout_layer.',
                cart_layer = '.(int) $cart_layer.',
                remove_from_cart_layer = '.(int) $remove_from_cart_layer.',
                add_to_cart_layer = '.(int) $add_to_cart_layer.',
                view_item_layer = '.(int) $view_item_layer.',
                select_item_layer = '.(int) $select_item_layer.',
                view_item_list_layer = '.(int) $view_item_list_layer.',
                select_promotion_layer = '.(int) $select_promotion_layer.',
                view_promotion_layer = '.(int) $view_promotion_layer.'
            WHERE id_alsernet_google_gtm = 1';
        }

        $result = Db::getInstance()->execute($updateQuery);

        if ($result) {
            $this->context->controller->confirmations[] = $this->l('Perfecto. La configuración se ha actualizado');
        } else {
            $this->context->controller->errors[] = $this->l('Ha habido un error en la configuración => '.$updateQuery);
        }
    } // function postProcess

    // purchase
    public function hookDisplayOrderConfirmation($order)
    {
        if ($this->getInfo()['purchase_layer'] != 0) {
            // cancelled, payment error, refunded
            $ids_payment_error = [6, 8, 7];

            if (isset($order['order'])) {
                $_order = $order['order'];

                $order_cart = new Cart($_order->id_cart);
                $products_cart = $order_cart->getProducts(true);

                $product_info = [];

                foreach ($products_cart as $product) {

                    $id_category = $product['id_category_default'];
                    // Crear una instancia de la clase Category
                    $categoria = new Category($id_category);

                    // Obtener la categoría padre de manera dinámica
                    while ($categoria->id_parent != 0 && $categoria->id_parent != 2) {
                        $idCategoria = $categoria->id_parent;
                        $categoria = new Category($idCategoria);
                    }

                    $lang = Context::getContext()->language->id;

                    $variants = [];

                    if ($product['id_product_attribute'] == 0) {
                        $sql_articulo = 'SELECT id_articulo FROM aalv_combinacionunica_import WHERE id_product = '.$product['id_product'];
                    } else {
                        $sql_articulo = 'SELECT id_articulo FROM aalv_combinaciones_import WHERE id_product_attribute = '.$product['id_product_attribute'];
                    }
                    $id_articulo = Db::getInstance()->getValue($sql_articulo);

                    $product_info[] = (object) [
                        'item_id' => $product['id_product'],
                        'item_unique_id' => $id_articulo,
                        'item_name' => $product['name'],
                        'item_brand' => $product['manufacturer_name'],
                        'item_category' => strtoupper($product['category']),
                        'item_variant' => $product['attributes'],
                        'item_variant2' => $product['attributes_small'],
                        'item_list_name' => $categoria->name[$lang],
                        // 'item_list_id' => $category->id_parent,
                        // 'affiliation' => '',
                        'price' => $product['total_wt'],
                        // 'coupon' =>'',
                        'discount' => $product['reduction'],
                        // 'index' => '',
                        // 'location_id' =>'',
                        'quantity' => $product['quantity'],

                    ];
                }

                $idCarrier = $_order->id_carrier;

                $carrier = new Carrier($idCarrier);

                $conf = [
                    // 'order'  => $_order,
                    // 'products_cart'  => $products_cart,
                    'user_id' => $_order->id_customer,
                    'user_type' => (! empty($_order->id_customer)) ? 'registrado' : 'invitado',
                    'country' => Context::getContext()->language->iso_code,
                    'page_type' => Context::getContext()->controller->getPageName(), // 'order-confirmation',
                    'checkout_step' => '5',
                    'payment_type' => $_order->payment,
                    'shipping_tier' => $carrier->name,
                    // 'ecommerce'
                    'transaction_id' => $_order->id,
                    'affiliation' => 'Alvarez',
                    'value' => (float) $_order->total_paid_tax_incl,
                    'tax' => (float) ($_order->total_paid_tax_incl - $_order->total_paid_tax_excl),
                    'shipping' => (float) $_order->total_shipping_tax_incl,
                    'currency' => 'EUR',
                    // ['coupon'] =>  '',
                    // 'items' =>  $product_info
                ];

                $this->context->smarty->assign('product_info', $product_info);

                $this->context->smarty->assign($conf);

                return $this->display(__FILE__, '/views/templates/hook/purchase_datalayer.tpl');
            }
        }
    }

    // add_payment_info
    public function hookDisplayPaymentByBinaries()
    {
        if ($this->getInfo()['payment_layer'] != 0) {
            $this->context->smarty->assign('product_info', $this->datoProduct($this->context->cart->getProducts()));

            $this->context->smarty->assign($this->datoConf($this->context->cart->id_customer, 4));

            return $this->display(__FILE__, '/views/templates/hook/add_payment_info_datalayer.tpl');
        }
    }

    // add_shiping_info
    public function hookDisplayConfirmDeliveryOption()
    {
        if ($this->getInfo()['shipping_layer'] != 0) {
            $this->context->smarty->assign('product_info', $this->datoProduct($this->context->cart->getProducts()));

            $this->context->smarty->assign($this->datoConf($this->context->cart->id_customer, 3));

            return $this->display(__FILE__, '/views/templates/hook/add_shipping_info_datalayer.tpl');
        }
    }

    // add_address_info
    public function hookDisplayConfirmAddress()
    {
        if ($this->getInfo()['address_layer'] != 0) {
            $this->context->smarty->assign('product_info', $this->datoProduct($this->context->cart->getProducts()));

            $this->context->smarty->assign($this->datoConf($this->context->cart->id_customer, 2));

            return $this->display(__FILE__, '/views/templates/hook/add_address_info_datalayer.tpl');
        }
    }

    // begin_checkout
    public function hookDisplayNewCustomer()
    {
        if ($this->getInfo()['checkout_layer'] != 0) {
            $this->context->smarty->assign('product_info', $this->datoProduct($this->context->cart->getProducts()));

            $this->context->smarty->assign($this->datoConf($this->context->cart->id_customer, 1));

            return $this->display(__FILE__, '/views/templates/hook/begin_checkout_datalayer.tpl');
        }
    }

    // view_cart y add_to_cart y remove_from_cart
    public function hookDisplayShoppingCartFooter()
    {
        if ($this->getInfo()['add_to_cart_layer'] != 0
            or $this->getInfo()['remove_from_cart_layer'] != 0
            or $this->getInfo()['cart_layer'] != 0) {

            $this->context->smarty->assign('bloqueo_add_to_cart_layer', $this->getInfo()['add_to_cart_layer']);

            $this->context->smarty->assign('bloqueo_remove_from_cart_layer', $this->getInfo()['remove_from_cart_layer']);

            $this->context->smarty->assign('bloqueo_cart_layer', $this->getInfo()['cart_layer']);

            $this->context->smarty->assign('product_info_shopping_cart_footer', $this->datoProduct($this->context->cart->getProducts()));

            $this->context->smarty->assign($this->datoConf($this->context->cart->id_customer));

            return $this->display(__FILE__, '/views/templates/hook/view_cart_datalayer.tpl');
        }
    }

    // add_to_cart
    public function hookDisplayProductAlsernet()
    {
        if ($this->getInfo()['add_to_cart_layer'] != 0) {
            $product = new Product((int) Tools::getValue('id_product'), false, Context::getContext()->language->id);

            $manufacturer = new Manufacturer($product->id_manufacturer);

            $id_category = $product->id_category_default;

            // Crear una instancia de la clase Category
            $categoria = new Category($id_category);
            $name_categoria = $categoria->name;

            // Obtener la categoría padre de manera dinámica
            while ($categoria->id_parent != 0 && $categoria->id_parent != 2) {
                $idCategoria = $categoria->id_parent;
                $categoria = new Category($idCategoria);
            }

            $lang = Context::getContext()->language->id;

            $datos = [];
            foreach ($product->getAttributeCombinations((int) Context::getContext()->language->id) as $value) {

                $id_articulo = Db::getInstance()->getValue('SELECT id_articulo FROM aalv_combinaciones_import WHERE id_product_attribute = '.$value['id_product_attribute']);
                $datos[] = [
                    'id_product_attribute' => $value['id_product_attribute'],
                    'attribute_name' => $value['attribute_name'],
                    'id_articulo' => $id_articulo,
                ];
            }

            if (count($datos) == 0) {
                $id_articulo = Db::getInstance()->getValue('SELECT id_articulo FROM aalv_combinacionunica_import WHERE id_product = '.(int) Tools::getValue('id_product'));
                $datos[] = [
                    'id_articulo' => $id_articulo,
                ];
            }

            $product_info[0] = [
                'item_id' => $product->id,
                'item_name' => $product->name,
                'item_brand' => $manufacturer->name,
                'item_category' => strtoupper($name_categoria[$lang]),
                // 'item_variant' => $product->attributes,
                // 'item_variant2' => $product->attributes_small,
                'item_list_name' => $categoria->name[$lang],
                // 'item_list_id' => $category->id_parent,
                // 'affiliation' => '',
                // 'price' => $product->price,
                // 'coupon' =>'',
                // 'discount'=> $product->reduction,
                // 'index' => '',
                // 'location_id' =>'',
                // 'quantity' => 1,

            ];

            $this->context->smarty->assign('product_add_to_cart', $product_info);

            $this->context->smarty->assign('articulo', $datos);

            $this->context->smarty->assign($this->datoConf($this->context->cart->id_customer));

            return $this->display(__FILE__, '/views/templates/hook/add_to_cart_datalayer.tpl');
        }
    }

    // view_item
    public function hookDisplayProductAlsernetFooter()
    {
        if ($this->getInfo()['view_item_layer'] != 0) {

            $product = new Product((int) Tools::getValue('id_product'), false, Context::getContext()->language->id);
            $id_product_attribute_default = Product::getDefaultAttribute((int) Tools::getValue('id_product'));

            if ($id_product_attribute_default == 0) {
                $sql_articulo = 'SELECT id_articulo FROM aalv_combinacionunica_import WHERE id_product = '.(int) Tools::getValue('id_product');
            } else {
                $sql_articulo = 'SELECT id_articulo FROM aalv_combinaciones_import WHERE id_product_attribute = '.$id_product_attribute_default;
            }
            $id_articulo = Db::getInstance()->getValue($sql_articulo);

            $caracteristicas_combinacion = '';
            $discount = 0;
            $manufacturer = new Manufacturer($product->id_manufacturer);

            $id_category = $product->id_category_default;

            // Crear una instancia de la clase Category
            $categoria = new Category($id_category);
            $name_categoria = $categoria->name;

            // Obtener la categoría padre de manera dinámica
            while ($categoria->id_parent != 0 && $categoria->id_parent != 2) {
                $idCategoria = $categoria->id_parent;
                $categoria = new Category($idCategoria);
            }

            $lang = Context::getContext()->language->id;

            // Obtén los precios específicos del producto utilizando una consulta SQL
            $sql = 'SELECT * FROM aalv_specific_price WHERE id_product = '.(int) $product->id;

            // Ejecuta la consulta
            $specific_prices = Db::getInstance()->executeS($sql);

            // Inicializa variables para el precio y la combinación con stock
            $precio_minimo = 10000000; // Inicializado con un valor alto
            $id_combinacion_stock = null;

            // Itera sobre los precios específicos
            foreach ($specific_prices as $specific_price) {
                $stock_combinacion = StockAvailable::getQuantityAvailableByProduct($product->id, $specific_price['id_product_attribute']);

                // Verifica si hay stock y si el precio actual es menor al mínimo
                if ($stock_combinacion > 0 && $specific_price['price'] < $precio_minimo) {
                    // Calcula el precio con IVA aplicando las reglas de impuestos
                    $precio_con_iva = Product::getPriceStatic(
                        $specific_price['id_product'],
                        true, // con impuestos
                        $specific_price['id_product_attribute'],
                        2, // precisión del precio
                        null, // fecha específica (puedes ajustar esto según tus necesidades)
                        false, // utiliza el precio reducido si está configurado
                        true, // utiliza el ecotax si está configurado
                        1 // cantidad (generalmente 1)
                        // false, // obtén el precio del producto, no el total
                        // null, // ID del cliente (puedes ajustar esto según tus necesidades)
                        // null, // ID de la tienda (puedes ajustar esto según tus necesidades)
                        // true // reglas de impuestos aplicables
                    );

                    // Actualiza el precio mínimo y la combinación correspondiente con stock
                    if ($precio_con_iva < $precio_minimo) {
                        $precio_minimo = $precio_con_iva;
                        $id_combinacion_stock = $specific_price['id_product_attribute'];

                        // Obtén las características de la combinación mediante una consulta directa
                        $sql_caracteristicas = 'select
                                                    aal.name
                                                from
                                                    aalv_product_attribute_combination apac
                                                    left join aalv_attribute_lang aal on aal.id_attribute = apac.id_attribute
                                                where
                                                    aal.id_lang = '.$lang.'
                                                    and apac.id_product_attribute ='.(int) $id_combinacion_stock;
                        $caracteristicas_combinacion = Db::getInstance()->getValue($sql_caracteristicas);
                        $descuento_combinacion = SpecificPrice::getSpecificPrice(
                            $product->id,
                            $id_shop = null,
                            $id_combinacion_stock,
                            $id_country = null,
                            $id_group = null,
                            $id_customer = null,
                            $id_currency = null,
                            $id_country = null,
                            $id_group = null,
                            $id_customer = null,
                            $id_combinacion_stock,
                            $cart_quantity = 1,
                            $real_quantity = true,
                            $id_currency = null,
                            $id_country = null,
                            $id_group = null,
                            $id_customer = null,
                            $id_shop = null
                        );
                        $reduction_sin_iva = $descuento_combinacion['reduction'];

                        $sql_rate = 'select
                                                    at2.rate
                                                from
                                                    aalv_lang al
                                                    left join aalv_country ac on UPPER(ac.iso_code) COLLATE utf8mb4_unicode_ci = UPPER(al.iso_code) COLLATE utf8mb4_unicode_ci
                                                    left join aalv_tax_rule atr on atr.id_country = ac.id_country
                                                    left join aalv_tax at2 on at2.id_tax = atr.id_tax
                                                WHERE
                                                    al.id_lang = '.$lang.'
                                                GROUP BY atr.id_country';
                        $tax_percentage = Db::getInstance()->getValue($sql_rate);
                        // Calcula el descuento con IVA
                        $aumento = $reduction_sin_iva * ($tax_percentage / 100);
                        $discount = $reduction_sin_iva + $aumento;

                    }
                }
            }

            $product_info[0] = (object) [
                'item_id' => $product->id,
                'item_unique_id' => $id_articulo,
                'item_name' => $product->name,
                'item_brand' => $manufacturer->name,
                'item_category' => strtoupper($name_categoria[$lang]),
                'item_variant' => $caracteristicas_combinacion,
                // 'item_variant2' => $product->attributes_small,
                'item_list_name' => $categoria->name[$lang],
                // 'item_list_id' => $category->id_parent,
                // 'affiliation' => '',
                'price' => $precio_minimo,
                // 'coupon' =>'',
                'discount' => $discount,
                // 'index' => '',
                // 'location_id' =>'',
                'quantity' => 1,

            ];

            $this->context->smarty->assign('product_info_view_item', $product_info);

            $this->context->smarty->assign($this->datoConf($this->context->cart->id_customer));

            return $this->display(__FILE__, '/views/templates/hook/view_item_datalayer.tpl');
        }
    }

    // view_item_list y select_item

    private function _postValidation()
    {
        if (! preg_match('/^GTM-[0-9A-Z]{6,8}$/i', Tools::getValue('task_manager'))) {
            return false;
        } else {
            return true;
        }
    }

    private function getInfo()
    {
        $info = Db::getInstance()->executeS('SELECT * FROM '.$this->tableName.' LIMIT 1;');

        $isProd = (_PS_BASE_URL_ == _PSALV_URL_PROD_);
        $dataGtm = ($isProd) ? 'gtm_enabled' : 'gtm_enabled_test';
        $dataTag = ($isProd) ? 'task_manager' : 'task_manager_test';

        if (! empty($info)) {
            $this->enabled = $info[0][$dataGtm];
            $this->tag = $info[0][$dataTag];

            return $info[0];
        } else {
            $this->enabled = $this->tag = null;

            return [];
        }
    }

    public function datoConf($id_customer, $checkout_step = '')
    {
        if (is_null($id_customer)) {
            $id_customer = 0;
        }

        return [
            'user_id' => (! empty($id_customer)) ? $id_customer : 0,
            'user_type' => (! empty($id_customer)) ? 'registrado' : 'invitado',
            'country' => Context::getContext()->language->iso_code,
            'page_type' => Context::getContext()->controller->getPageName(), // 'order-confirmation',
            'checkout_step' => ''.$checkout_step.'',
            'currency' => 'EUR',
        ];
    }

    public function datoProduct($products_cart)
    {
        $product_info = [];

        foreach ($products_cart as $product) {

            $id_category = $product['id_category_default'];
            // Crear una instancia de la clase Category
            $categoria = new Category($id_category);

            // Obtener la categoría padre de manera dinámica
            while ($categoria->id_parent != 0 && $categoria->id_parent != 2) {
                $idCategoria = $categoria->id_parent;
                $categoria = new Category($idCategoria);
            }

            $lang = Context::getContext()->language->id;

            if ($product['id_product_attribute'] == 0) {
                $sql_articulo = 'SELECT id_articulo FROM aalv_combinacionunica_import WHERE id_product = '.$product['id_product'];
            } else {
                $sql_articulo = 'SELECT id_articulo FROM aalv_combinaciones_import WHERE id_product_attribute = '.$product['id_product_attribute'];
            }
            $id_articulo = Db::getInstance()->getValue($sql_articulo);

            $product_info[] = (object) [
                'item_id' => $product['id_product'],
                'item_unique_id' => $id_articulo,
                'item_name' => $product['name'],
                'item_brand' => $product['manufacturer_name'],
                'item_category' => strtoupper($product['category']),
                'item_variant' => $product['attributes'],
                'item_variant2' => $product['attributes_small'],
                'item_list_name' => $categoria->name[$lang],
                // 'item_list_id' => $category->id_parent,
                // 'affiliation' => '',
                'price' => $product['price_wt'],
                // 'coupon' =>'',
                'discount' => $product['reduction'],
                // 'index' => '',
                // 'location_id' =>'',
                'quantity' => $product['quantity'],

            ];
        }

        return $product_info;
    }

    public function nuevoDatosProductos($product)
    {
        $lang = Context::getContext()->language->id;
        foreach ($product as $key => $value) {
            $manufacturer = new Manufacturer($value['id_manufacturer']);

            // Crear una instancia de la clase Category
            $categoria = new Category($value['id_category_default']);
            $name_categoria = $categoria->name;

            // Obtener la categoría padre de manera dinámica
            while ($categoria->id_parent != 0 && $categoria->id_parent != 2) {
                $idCategoria = $categoria->id_parent;
                $categoria = new Category($idCategoria);
            }

            // Obtén los precios específicos del producto utilizando una consulta SQL
            $sql = 'SELECT * FROM '._DB_PREFIX_.'specific_price WHERE id_product = '.(int) $value['id_product'];

            // Ejecuta la consulta
            $specific_prices = Db::getInstance()->executeS($sql);

            // Inicializa variables para el precio y la combinación con stock
            $precio_minimo = PHP_INT_MAX; // Inicializado con un valor alto
            $id_combinacion_stock = null;

            // Itera sobre los precios específicos
            foreach ($specific_prices as $specific_price) {
                $stock_combinacion = StockAvailable::getQuantityAvailableByProduct($value['id_product'], $specific_price['id_product_attribute']);

                // Verifica si hay stock y si el precio actual es menor al mínimo
                if ($stock_combinacion > 0 && $specific_price['price'] < $precio_minimo) {
                    // Calcula el precio con IVA aplicando las reglas de impuestos
                    $precio_con_iva = Product::getPriceStatic(
                        $specific_price['id_product'],
                        true, // con impuestos
                        $specific_price['id_product_attribute'],
                        2, // precisión del precio
                        null, // fecha específica (puedes ajustar esto según tus necesidades)
                        false, // utiliza el precio reducido si está configurado
                        true, // utiliza el ecotax si está configurado
                        1, // cantidad (generalmente 1)
                        false, // obtén el precio del producto, no el total
                        null, // ID del cliente (puedes ajustar esto según tus necesidades)
                        null, // ID de la tienda (puedes ajustar esto según tus necesidades)
                        $tax_rules // reglas de impuestos aplicables
                    );

                    // Actualiza el precio mínimo y la combinación correspondiente con stock
                    if ($precio_con_iva < $precio_minimo) {
                        $precio_minimo = $precio_con_iva;
                        $id_combinacion_stock = $specific_price['id_product_attribute'];

                        // Obtén las características de la combinación mediante una consulta directa
                        $sql_caracteristicas = 'select
                                                    aal.name
                                                from
                                                    aalv_product_attribute_combination apac
                                                    left join aalv_attribute_lang aal on aal.id_attribute = apac.id_attribute
                                                where
                                                    aal.id_lang = 1
                                                    and apac.id_product_attribute ='.(int) $id_combinacion_stock;
                        $caracteristicas_combinacion = Db::getInstance()->getValue($sql_caracteristicas);
                        $descuento_combinacion = SpecificPrice::getSpecificPrice(
                            $product->id,
                            $id_shop = null,
                            $id_combinacion_stock,
                            $id_country = null,
                            $id_group = null,
                            $id_customer = null,
                            $id_currency = null,
                            $id_country = null,
                            $id_group = null,
                            $id_customer = null,
                            $id_combinacion_stock,
                            $cart_quantity = 1,
                            $real_quantity = true,
                            $id_currency = null,
                            $id_country = null,
                            $id_group = null,
                            $id_customer = null,
                            $id_shop = null
                        );
                        $reduction_sin_iva = $descuento_combinacion['reduction'];

                        $sql_rate = 'select
                                                    at2.rate
                                                from
                                                    aalv_lang al
                                                    left join aalv_country ac on UPPER(ac.iso_code) COLLATE utf8mb4_unicode_ci = UPPER(al.iso_code) COLLATE utf8mb4_unicode_ci
                                                    left join aalv_tax_rule atr on atr.id_country = ac.id_country
                                                    left join aalv_tax at2 on at2.id_tax = atr.id_tax
                                                WHERE
                                                    al.id_lang = '.$lang.'
                                                GROUP BY atr.id_country';
                        $tax_percentage = Db::getInstance()->getValue($sql_rate);
                        // // Calcula el descuento con IVA
                        $aumento = $reduction_sin_iva * ($tax_percentage / 100);
                        $discount = $reduction_sin_iva + $aumento;

                    }
                }
            }
            if ($precio_minimo == '') {
                $precio_minimo = 0;
            }
            $product[$key]['item_brand'] = $manufacturer->name;
            $product[$key]['item_category'] = strtoupper($name_categoria[$lang]);
            $product[$key]['item_variant'] = $caracteristicas_combinacion;
            $product[$key]['item_list_name'] = $categoria->name[$lang];
            $product[$key]['price'] = $precio_minimo;
            $product[$key]['discount'] = $discount;
            $product[$key]['quantity'] = 1;
        }

        return $product;
    }

    public function hookHeader($params)
    {

        $this->context->controller->addJS($this->_path.'views/js/front/select_item_datalayer.js');

    }

    public function handleSelect($product_id, $category_id)
    {

        $lang = Context::getContext()->language->id;
        $order = Context::getContext()->cart;

        $product = new Product($product_id, false, $lang);
        $category = new Category($category_id, false, $lang);
        $parent = new Category($category->id_parent, false, $lang);

        $id_articulo = Db::getInstance()->getValue('
            SELECT id_articulo
            FROM (
                SELECT id_articulo FROM aalv_combinacionunica_import aci WHERE id_product = '.$product_id.'
                UNION
                SELECT aci.id_articulo
                FROM aalv_combinaciones_import aci
                LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                WHERE apa.id_product = '.$product_id.'
            ) AS produ
            GROUP BY id_articulo
        ');

        $caracteristicas_combinacion = '';

        $sql = 'SELECT * FROM aalv_specific_price WHERE id_product = '.(int) $product_id;

        $specific_prices = Db::getInstance()->executeS($sql);

        $precio_minimo = 10000000;
        $id_combinacion_stock = null;

        foreach ($specific_prices as $specific_price) {

            $stock_combinacion = StockAvailable::getQuantityAvailableByProduct($product_id, $specific_price['id_product_attribute']);

            // Verifica si hay stock y si el precio actual es menor al mínimo
            if ($stock_combinacion > 0 && $specific_price['price'] < $precio_minimo) {
                // Calcula el precio con IVA aplicando las reglas de impuestos
                $precio_con_iva = Product::getPriceStatic(
                    $specific_price['id_product'],
                    true, // con impuestos
                    $specific_price['id_product_attribute'],
                    2, // precisión del precio
                    null, // fecha específica (puedes ajustar esto según tus necesidades)
                    false, // utiliza el precio reducido si está configurado
                    true, // utiliza el ecotax si está configurado
                    1 // cantidad (generalmente 1)
                    // false, // obtén el precio del producto, no el total
                    // null, // ID del cliente (puedes ajustar esto según tus necesidades)
                    // null, // ID de la tienda (puedes ajustar esto según tus necesidades)
                    // true // reglas de impuestos aplicables
                );

                if ($precio_con_iva < $precio_minimo) {
                    $precio_minimo = $precio_con_iva;
                    $id_combinacion_stock = $specific_price['id_product_attribute'];

                    $sql_caracteristicas = 'select
                                                aal.name
                                            from
                                                aalv_product_attribute_combination apac
                                                left join aalv_attribute_lang aal on aal.id_attribute = apac.id_attribute
                                            where
                                                aal.id_lang = '.$lang.'
                                                and apac.id_product_attribute ='.(int) $id_combinacion_stock;
                    $caracteristicas_combinacion = Db::getInstance()->getValue($sql_caracteristicas);
                    $descuento_combinacion = SpecificPrice::getSpecificPrice(
                        $product->id,
                        $id_shop = null,
                        $id_combinacion_stock,
                        $id_country = null,
                        $id_group = null,
                        $id_customer = null,
                        $id_currency = null,
                        $id_country = null,
                        $id_group = null,
                        $id_customer = null,
                        $id_combinacion_stock,
                        $cart_quantity = 1,
                        $real_quantity = true,
                        $id_currency = null,
                        $id_country = null,
                        $id_group = null,
                        $id_customer = null,
                        $id_shop = null
                    );
                    $reduction_sin_iva = $descuento_combinacion['reduction'];

                    $sql_rate = 'select
                                                at2.rate
                                            from
                                                aalv_lang al
                                                left join aalv_country ac on UPPER(ac.iso_code) COLLATE utf8mb4_unicode_ci = UPPER(al.iso_code) COLLATE utf8mb4_unicode_ci
                                                left join aalv_tax_rule atr on atr.id_country = ac.id_country
                                                left join aalv_tax at2 on at2.id_tax = atr.id_tax
                                            WHERE
                                                al.id_lang = '.$lang.'
                                            GROUP BY atr.id_country';
                    $tax_percentage = Db::getInstance()->getValue($sql_rate);
                    // Calcula el descuento con IVA
                    $aumento = $reduction_sin_iva * ($tax_percentage / 100);
                    $discount = $reduction_sin_iva + $aumento;

                }
            }
        }

        $product_analytics = (object) [
            'item_id' => $product->id,
            'item_unique_id' => $id_articulo,
            'item_name' => $product->name,
            'item_brand' => $product->manufacturer_name,
            'item_category' => $category->name,
            'item_variant' => $caracteristicas_combinacion,
            'item_list_name' => $parent->name,
            'item_list_id' => strtolower($parent->name),
            'price' => $precio_minimo,
            'discount' => $discount,
            'quantity' => 1,
        ];

        $customer_analytics = (object) [
            'user_id' => (! empty($order->id_customer)) ? $order->id_customer : 0,
            'user_type' => (! empty($order->id_customer)) ? 'registrado' : 'invitado',
            'country' => Context::getContext()->language->iso_code,
            'page_type' => 'categoria', // 'order-confirmation',
            'payment_type' => (! empty($order->payment)) ? $order->payment : '',
            'currency' => 'EUR',
        ];

        return [
            'product_analytics' => $product_analytics,
            'customer_analytics' => $customer_analytics,
        ];

    }
}
