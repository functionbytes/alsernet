<?php

class Dispatcher extends DispatcherCore
{
    public $array_deportes = [
        "es" => ['golf', 'caza', 'pesca', 'hipica', 'buceo', 'nautica', 'esqui', 'padel'],
        "pt" => ['golfe', 'caca', 'pesca', 'equitacao', 'mergulho', 'vela', 'esqui', 'padel'],
        "en" => ['golf', 'hunting', 'fishing', 'horse_riding', 'diving', 'boating', 'skiing', 'padel'],
        "fr" => ['golf', 'chasse', 'peche', 'equitation', 'plongee'],
        "de" => ['golf', 'jagd', 'angeln', 'reiten', 'tauchen'],
        "it" => ['golf', 'caccia', 'pesca', 'equitazione', 'subacquea'],
    ];

    public $array_ofertas = ["es" =>"ofertas", "pt" =>"promocoes", "en" =>"deals", "fr" =>"offres", "de"=>"angebote", "it"=> "offerte"];

    public $default_routes = array(
        'refund_rule' => array(
            'controller' => 'refund',
            'rule' => 'refund',
            'keywords' => array(),
        ),
        'refundsearch_rule' => array(
            'controller' => 'refundsearch',
            'rule' => 'refundsearch',
            'keywords' => array(),
        ),
        'refunddetails_rule' => array(
            'controller' => 'refunddetails',
            'rule' => 'refunddetails',
            'keywords' => array(),
        ),
        'refundrequest_rule' => array(
            'controller' => 'refundrequest',
            'rule' => 'refundrequest',
            'keywords' => array(),
        ),
        'offers_rule' => [
            'controller' => 'pricesdropsport',
            'rule'       => '{sport}/{offer_keyword}',
            'keywords'   => [
                'sport' => ['regexp' => '[_a-zA-Z0-9-\pL]+', 'param' => 'sport'],
                'offer_keyword' => ['regexp' => '(?:ofertas|deals|offres|promocoes|angebote|offerte)', 'param' => 'offer_keyword'],
            ],
        ],
        'wishlist_rule' => array(
            'controller' => 'wishlist',
            'rule' => 'wishlist',
            'keywords' => array(),
        ),
        'tracking_rule' => array(
            'controller' => 'tracking',
            'rule' => 'tracking',
            'keywords' => array(),
        ),
        'gdpr_rule' => array(
            'controller' => 'gdpr',
            'rule' => 'gdpr',
            'keywords' => array(),
        ),
        'cookies_rule' => array(
            'controller' => 'cookies',
            'rule' => 'cookies',
            'keywords' => array(),
        ),
        'module-ambjolisearch-jolisearch' => array(
            'controller' => 'jolisearch',
            'rule' => 'module/ambjolisearch/jolisearch',
            'keywords' => array(),
        ),
        'marcas_deporte_rule_clean' => array(
            'controller' => 'manufacturerdeporte',
            'rule' => 'listado-marcas',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'marcas_deporte_rule_es' => array(
            'controller' => 'manufacturerdeporte',
            'rule' => '{deporte}/listado-marcas',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'marcas_deporte_rule_en' => array(
            'controller' => 'manufacturerdeporte',
            'rule' => '{deporte}/brand-list',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'marcas_deporte_rule_fr' => array(
            'controller' => 'manufacturerdeporte',
            'rule' => '{deporte}/liste-marques',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'marcas_deporte_rule_pt' => array(
            'controller' => 'manufacturerdeporte',
            'rule' => '{deporte}/lista-marcas',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'marcas_deporte_rule_de' => array(
            'controller' => 'manufacturerdeporte',
            'rule' => '{deporte}/marken-liste',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'marcas_deporte_rule_it' => array(
            'controller' => 'manufacturerdeporte',
            'rule' => '{deporte}/lista-marchi',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'marca_selec_deporte_rule_es' => array(
            'controller' => 'manufacturer',
            'rule' => 'm/{nombre_manu}',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'nombre_manu' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'nombre_manu'),
                'id_manufacturer' => array('regexp' => '[0-9]+'),
            ),
        ),
        'marca_selec_deporte_rule_en' => array(
            'controller' => 'manufacturer',
            'rule' => 'm/{nombre_manu}',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'nombre_manu' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'nombre_manu'),
                'id_manufacturer' => array('regexp' => '[0-9]+'),
            ),
        ),
        'marca_selec_deporte_rule_fr' => array(
            'controller' => 'manufacturer',
            'rule' => 'm/{nombre_manu}',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'nombre_manu' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'nombre_manu'),
                'id_manufacturer' => array('regexp' => '[0-9]+'),
            ),
        ),
        'marca_selec_deporte_rule_pt' => array(
            'controller' => 'manufacturer',
            'rule' => 'm/{nombre_manu}',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'nombre_manu' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'nombre_manu'),
                'id_manufacturer' => array('regexp' => '[0-9]+'),
            ),
        ),
        'marca_selec_deporte_rule_de' => array(
            'controller' => 'manufacturer',
            'rule' => 'm/{nombre_manu}',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'nombre_manu' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'nombre_manu'),
                'id_manufacturer' => array('regexp' => '[0-9]+'),
            ),
        ),
        'marca_selec_deporte_rule_it' => array(
            'controller' => 'manufacturer',
            'rule' => 'm/{nombre_manu}',
            'keywords' => array(
                'id_deporte' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'nombre_manu' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'nombre_manu'),
                'id_manufacturer' => array('regexp' => '[0-9]+'),
            ),
        ),
        'novedades_deporte_rule_es' => array(
            'controller' => 'newproductssport',
            'rule' => '{deporte}/novedades',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'novedades_deporte_rule_en' => array(
            'controller' => 'newproductssport',
            'rule' => '{deporte}/news',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'novedades_deporte_rule_fr' => array(
            'controller' => 'newproductssport',
            'rule' => '{deporte}/nouveautes',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'novedades_deporte_rule_pt' => array(
            'controller' => 'newproductssport',
            'rule' => '{deporte}/novidades',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'novedades_deporte_rule_de' => array(
            'controller' => 'newproductssport',
            'rule' => '{deporte}/neuen',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'novedades_deporte_rule_it' => array(
            'controller' => 'newproductssport',
            'rule' => '{deporte}/novita',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'bestsale_deporte_rule_es' => array(
            'controller' => 'bestsalessport',
            'rule' => '{deporte}/lo_mas_vendido',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'bestsale_deporte_rule_en' => array(
            'controller' => 'bestsalessport',
            'rule' => '{deporte}/most_sold',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'bestsale_deporte_rule_fr' => array(
            'controller' => 'bestsalessport',
            'rule' => '{deporte}/les_plus_vendus',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'bestsale_deporte_rule_pt' => array(
            'controller' => 'bestsalessport',
            'rule' => '{deporte}/mais_vendidos',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'bestsale_deporte_rule_de' => array(
            'controller' => 'bestsalessport',
            'rule' => '{deporte}/bestseller',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'bestsale_deporte_rule_it' => array(
            'controller' => 'bestsalessport',
            'rule' => '{deporte}/i_piu_venduti',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'boletines_deporte_rule_es' => array(
            'controller' => 'boletines',
            'rule' => '{deporte}/monthlynewsletters',
            'keywords' => array(
                'id_deport' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'boletines_deporte_rule_en' => array(
            'controller' => 'boletines',
            'rule' => '{deporte}/monthlynewsletters',
            'keywords' => array(
                'id_deport' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'boletines_deporte_rule_fr' => array(
            'controller' => 'boletines',
            'rule' => '{deporte}/monthlynewsletters',
            'keywords' => array(
                'id_deport' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'boletines_deporte_rule_pt' => array(
            'controller' => 'boletines',
            'rule' => '{deporte}/monthlynewsletters',
            'keywords' => array(
                'id_deport' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'boletines_deporte_rule_de' => array(
            'controller' => 'boletines',
            'rule' => '{deporte}/monthlynewsletters',
            'keywords' => array(
                'id_deport' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'boletines_deporte_rule_it' => array(
            'controller' => 'boletines',
            'rule' => '{deporte}/monthlynewsletters',
            'keywords' => array(
                'id_deport' => array('regexp' => '[0-9]+'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'deporte'),
            ),
        ),
        'blog_category_rule' => array(
            'controller' => 'blog',
            'rule' => 'blog/{deporte}/{rewrite}',
            'keywords' => array(
                'id_category' => array('regexp' => '[0-9]+'),
                'rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'category_url_alias'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
            ),
        ),
        'manufacturer_rule' => array(
            'controller' => 'manufacturer',
            'rule' => 'm/{rewrite}',
            'keywords' => array(
                'id' => array('regexp' => '[0-9]+'),
                'id_manufacturer' => array('regexp' => '[0-9]+'),
                'rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'meta_keywords' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'meta_title' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'deporte' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
            ),
        ),
        'category_rule' => array(
            'controller' => 'category',
            'rule' => '{parent:/}{categories:-}{rewrite}',
            'keywords' => array(
                'id' => array('regexp' => '[0-9]+'),
                'rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'category_rewrite'),
                'meta_keywords' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'meta_title' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'categories' => array('regexp' => '[/_a-zA-Z0-9-\pL]*'),
                'parent' => array('regexp' => '[/_a-zA-Z0-9-\pL]*'),
            ),
        ),
        'supplier_rule' => array(
            'controller' => 'supplier',
            'rule' => 'supplier/{rewrite}',
            'keywords' => array(
                'id' => array('regexp' => '[0-9]+'),
                'rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'supplier_rewrite'),
                'meta_keywords' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'meta_title' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
            ),
        ),
        'cms_rule' => array(
            'controller' => 'cms',
            'rule' => '{rewrite}',
            'keywords' => array(
                'id' => array('regexp' => '[0-9]+'),
                'rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'cms_rewrite'),
                'meta_keywords' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'meta_title' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'category_rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
            ),
        ),
        'cms_category_rule' => array(
            'controller' => 'cms',
            'rule' => 'content/category/{rewrite}',
            'keywords' => array(
                'id' => array('regexp' => '[0-9]+'),
                'rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'cms_category_rewrite'),
                'meta_keywords' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'meta_title' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
            ),
        ),
        'module' => array(
            'controller' => null,
            'rule' => 'module/{module}{/:controller}',
            'keywords' => array(
                'module' => array('regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'module'),
                'controller' => array('regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'controller'),
            ),
            'params' => array(
                'fc' => 'module',
            ),
        ),
        'product_rule' => array(
            'controller' => 'product',
            'rule' => '{id:-}{rewrite}',
            'keywords' => array(
                'id' => array('regexp' => '[0-9]+'),
                'rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'id_product_attribute' => array('regexp' => '[0-9]+'),
                'ean13' => array('regexp' => '[0-9\pL]*'),
                'category' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'categories' => array('regexp' => '[/_a-zA-Z0-9-\pL]*'),
                'reference' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'meta_keywords' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'meta_title' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'manufacturer' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'supplier' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'price' => array('regexp' => '[0-9\.,]*'),
                'tags' => array('regexp' => '[a-zA-Z0-9-\pL]*'),
            ),
        ),

        'layered_rule' => array(
            'controller' => 'category',
            'rule' => '{rewrite}/filter{selected_filters}',
            'keywords' => array(
                'id' => array('regexp' => '[0-9]+'),

                'selected_filters' => array('regexp' => '.*', 'param' => 'selected_filters'),
                'rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'category_rewrite'),
                'meta_keywords' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                'meta_title' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
            ),
        ),
        'manufacturer_short_rule' => array(
            'controller' => 'manufacturer',
            'rule' => 'm/{rewrite}',
            'keywords' => array(
                'id_manufacturer' => array('regexp' => '[0-9]+'),
                'rewrite' => array('regexp' => '[_a-zA-Z0-9\pL]+'),
            ),
        ),

    );
    protected $empty_route;
    protected $default_controller;
    protected $use_default_controller = false;
    protected $controller_not_found = 'pagenotfound';

    protected function __construct()
    {
        parent::__construct();
        $this->use_routes = (bool)Configuration::get('PS_REWRITING_SETTINGS');

        $this->setRequestUri();

        if (defined('_PS_ADMIN_DIR_')) {
            $this->front_controller = self::FC_ADMIN;
            $this->controller_not_found = 'adminnotfound';
        } elseif (Tools::getValue('fc') == 'module') {
            $this->front_controller = self::FC_MODULE;
            $this->controller_not_found = 'pagenotfound';
        } else {
            $this->front_controller = self::FC_FRONT;
            $this->controller_not_found = 'pagenotfound';
        }

        if (in_array($this->front_controller, [self::FC_FRONT, self::FC_MODULE])) {
            Tools::switchLanguage();
        }

        if (Language::isMultiLanguageActivated()) {
            $this->multilang_activated = true;
        }

        $this->loadRoutes();
    }

    protected function loadRoutes($id_shop = null)
    {

        preg_match('/.*?\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)/', $_SERVER['REQUEST_URI'], $url_array);

        preg_match('/.*?\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)/', $_SERVER['REQUEST_URI'], $url_array);
        if (!empty($url_array)) {
            if (!strstr($_SERVER['REQUEST_URI'], '/content/')) {
                $this->default_routes['category_rule']['rule'] = '{parent:/}{categories:-}{rewrite}';
            }
        }

        preg_match('/.*?\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)/', $_SERVER['REQUEST_URI'], $pro_array);
        if (!empty($pro_array)) {
            $this->default_routes['product_rule']['rule'] = '{id:-}{rewrite}';
        }

        preg_match('/.*?([0-9]+)\_\_([_a-zA-Z0-9-\pL]*)/', $_SERVER['REQUEST_URI'], $sup_array);
        if (!empty($sup_array)) {
            $this->default_routes['supplier_rule']['rule'] = '{rewrite}';
        }

        preg_match('/.*?([0-9]+)\_([_a-zA-Z0-9-\pL]*)/', $_SERVER['REQUEST_URI'], $man_array);
        if (!empty($man_array)) {
            // $this->default_routes['manufacturer_rule']['rule'] = 'm/{rewrite}';
            $this->default_routes['manufacturer_short_rule'] = [
                'controller' => 'manufacturer',
                'rule' => 'm/{rewrite}',
                'keywords' => [
                    'id_manufacturer' => ['regexp' => '[0-9]+'],
                    'rewrite' => ['regexp' => '[_a-zA-Z0-9-\pL]*'],
                ],
            ];
        }

        preg_match('/.*?content\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)/', $_SERVER['REQUEST_URI'], $cms_array);
        if (!empty($cms_array)) {
            $this->default_routes['cms_rule']['rule'] = '{rewrite}';
        }

        preg_match('/.*?content\/category\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)/', $_SERVER['REQUEST_URI'], $cms_cat_array);
        if (!empty($cms_cat_array)) {
            if (strstr($_SERVER['REQUEST_URI'], '/content/category/'))
                $this->default_routes['cms_category_rule']['rule'] = 'content/category/{rewrite}';
        }

        $modules_routes = Hook::exec('moduleRoutes', array('id_shop' => $id_shop), null, true, false);

        if (is_array($modules_routes) && count($modules_routes)) {

            foreach ($modules_routes as $module_route) {
                if (is_array($module_route) && count($module_route)) {
                    foreach ($module_route as $route => $route_details) {
                        if (
                            array_key_exists('controller', $route_details) && array_key_exists('rule', $route_details)
                            && array_key_exists('keywords', $route_details) && array_key_exists('params', $route_details)
                        ) {
                            if (!isset($this->default_routes[$route])) {
                                $this->default_routes[$route] = array();
                            }
                            $this->default_routes[$route] = array_merge($this->default_routes[$route], $route_details);
                        }
                    }
                }
            }
        }

        foreach (Language::getLanguages() as $lang) {
            foreach ($this->default_routes as $id => $route) {
                $this->addRoute(
                    $id,
                    $route['rule'],
                    $route['controller'],
                    $lang['id_lang'],
                    $route['keywords'],
                    isset($route['params']) ? $route['params'] : array(),
                    $id_shop
                );
            }
        }

        if ($this->use_routes) {


            $sql = 'SELECT m.page, ml.url_rewrite, ml.id_lang
					FROM `' . _DB_PREFIX_ . 'meta` m
					LEFT JOIN `' . _DB_PREFIX_ . 'meta_lang` ml ON (m.id_meta = ml.id_meta' . Shop::addSqlRestrictionOnLang('ml', (int)$id_shop) . ')
					ORDER BY LENGTH(ml.url_rewrite) DESC';

            if ($results = Db::getInstance()->executeS($sql)) {
                foreach ($results as $row) {
                    if ($row['url_rewrite'])
                        $this->addRoute($row['page'], $row['url_rewrite'], $row['page'], $row['id_lang'], array(), array(), $id_shop);
                }
            }
            if (!$this->empty_route) {
                $this->empty_route = array(
                    'routeID' => 'index',
                    'rule' => '',
                    'controller' => 'index',
                );
            }

            foreach ($this->default_routes as $route_id => $route_data) {
                if ($custom_route = Configuration::get('PS_ROUTE_' . $route_id, null, null, $id_shop)) {
                    foreach (Language::getLanguages() as $lang) {
                        $this->addRoute(
                            $route_id,
                            $custom_route,
                            $route_data['controller'],
                            $lang['id_lang'],
                            $route_data['keywords'],
                            isset($route_data['params']) ? $route_data['params'] : array(),
                            $id_shop
                        );
                    }
                }
            }
        }
    }

    public function getController($id_shop = null)
    {

        if (defined('_PS_ADMIN_DIR_')) {
            $_GET['controllerUri'] = Tools::getValue('controller');
        }

        if (isset($_GET['id_cart']) && isset($_GET['id_order']) && isset($_GET['key']) && isset($_GET['id_module'])) {
            $this->controller = 'orderconfirmation';
        }

        if ($this->controller) {
            $_GET['controller'] = $this->controller;
            return $this->controller;
        }

        if (isset(Context::getContext()->shop) && $id_shop === null) {
            $id_shop = (int)Context::getContext()->shop->id;
        }

        $controller = Tools::getValue('controller');

        if (
            isset($controller)
            && is_string($controller)
            && preg_match('/^([0-9a-z_-]+)\?(.*)=(.*)$/Ui', $controller, $m)
        ) {
            $controller = $m[1];
            if (isset($_GET['controller'])) {
                $_GET[$m[2]] = $m[3];
            } elseif (isset($_POST['controller'])) {
                $_POST[$m[2]] = $m[3];
            }
        }

        if (!Validate::isControllerName($controller)) {
            $controller = false;
        }

        if ($this->use_routes && !$controller && !defined('_PS_ADMIN_DIR_')) {

            if (!$this->request_uri) {
                return Tools::strtolower($this->controller_not_found);
            }

            $controller = $this->controller_not_found;
            $test_request_uri = preg_replace('/(=http:\/\/)/', '=', $this->request_uri);

            if (!preg_match('/\.(gif|jpe?g|png|css|js|ico)$/i', parse_url($test_request_uri, PHP_URL_PATH))) {
                if ($this->empty_route) {
                    $this->addRoute($this->empty_route['routeID'], $this->empty_route['rule'], $this->empty_route['controller'], Context::getContext()->language->id, array(), array(), $id_shop);
                }
                list($uri) = explode('?', $this->request_uri);
                if (isset($this->routes[$id_shop][Context::getContext()->language->id])) {


                    foreach ($this->routes[$id_shop][Context::getContext()->language->id] as $route) {
                        if (preg_match($route['regexp'], $uri, $m)) {
                            foreach ($m as $k => $v) {
                                if (!is_numeric($k)) {
                                    $_GET[$k] = $v;
                                }
                            }
                            $controller = $route['controller'] ? $route['controller'] : $_GET['controller'];
                            if (!empty($route['params'])) {
                                foreach ($route['params'] as $k => $v) {
                                    $_GET[$k] = $v;
                                }
                            }
                            if (preg_match('#module-([a-z0-9_-]+)-([a-z0-9_]+)$#i', $controller, $m)) {
                                $_GET['module'] = $m[1];
                                $_GET['fc'] = 'module';
                                $controller = $m[2];
                            }
                            if (isset($_GET['fc']) && $_GET['fc'] == 'module') {
                                $this->front_controller = self::FC_MODULE;
                            }
                            break;
                        }
                    }
                }
            }
            $req_uri = explode('/', $this->request_uri);
            if (preg_match('/\?/', $req_uri[1])) {
                $req_uri_qmark = explode('?', $req_uri[1]);
                $req_uri[1] = $req_uri_qmark[0];
            }
            if ($controller == 'index' || preg_match('/^\/index.php(?:\?.*)?$/', $this->request_uri) || $req_uri[1] == '') {
                $controller = (_PS_VERSION_ >= '1.6.0' || _PS_VERSION_ >= '1.6.0.0') ? $this->useDefaultController() : $this->default_controller;
            }
            $check_url_type_existance = (int)$this->getKeyExistance($req_uri[1]);
            $get_controller_page = $this->getControllerPageById($check_url_type_existance);
            if ($check_url_type_existance > 0) {
                $controller = $get_controller_page;
            }
        }

        if (!defined('_PS_ADMIN_DIR_')) {

            $test_request_uri = preg_replace('/(=http:\/\/)/', '=', $this->request_uri);

            if ($controller == '404' || $controller == 404 || $controller == 'page-not-found' || $controller == 'pagenotfound' || (isset($_GET['controller']) && $_GET['controller'] == 'pagenotfound')) {
                $controller = 'pagenotfound';
            }

            if (!preg_match('/\.(gif|jpe?g|png|css|js|ico)$/i', parse_url($test_request_uri, PHP_URL_PATH))) {

                if (preg_match('/module/', $this->request_uri) && $controller == 'pagenotfound' && !isset($_GET['fc']) && preg_match('/\?/', $this->request_uri)) {
                    $_disperse_uri = explode('?', $this->request_uri);
                    $_disperse_uri = $_disperse_uri[0];
                    $three_parts = array_values(array_filter(explode('/', $_disperse_uri)));
                    $_GET['fc'] = $three_parts[0];
                    $_GET['module'] = $three_parts[1];
                    $_GET['controller'] = $three_parts[2];
                    $controller = $three_parts[2];
                    $this->front_controller = self::FC_MODULE;
                } elseif (preg_match('/module/', $this->request_uri) && preg_match('/\?/', $this->request_uri) && isset($_GET['module']) && !isset($_GET['fc']) && !isset($_GET['redirect'])) {
                    $this->front_controller = self::FC_MODULE;
                    $_GET['fc'] = 'module';
                    $_disperse_uri = explode('?', $this->request_uri);
                    $_disperse_uri = $_disperse_uri[0];
                    $three_parts = array_values(array_filter(explode('/', $_disperse_uri)));
                    $_GET['module'] = $three_parts[1];
                }
            }


            if (
                !preg_match('/^blog(\/.*)?/', $this->request_uri) &&
                preg_match('#^/?([a-zA-Z0-9_-]+)(/[a-zA-Z0-9_-]+)*(\?.*)?$#', $this->request_uri, $matches)
                && !preg_match('/^\/m\/([_a-zA-Z0-9-\pL]+)(\?.*)?$/u', $this->request_uri)
            ) {


                // Guardar la URL completa original
                $original_url = $this->request_uri;

                // Separar la URL base de los parámetros
                $url_components = parse_url($this->request_uri);
                $base_path = $url_components['path'];
                $original_query = isset($url_components['query']) ? '?' . $url_components['query'] : '';

                // Procesar solo la ruta base para getCategoryRewrite
                $category = $this->getCategoryRewrite(ltrim($base_path, '/'));

                if ($category != 0) {
                    // Mantener la URL original completa
                    $this->request_uri = $original_url;
                    $controller = 'category';

                    // Procesar los parámetros de la URL
                    if ($original_query) {
                        parse_str(ltrim($original_query, '?'), $query_params);
                        $_GET = array_merge($_GET, $query_params);
                    }

                    // Establecer los parámetros de categoría
                    $_GET['id'] = $_POST['id'] = (int)$category;
                    $_GET['id_category'] = $_POST['id_category'] = (int)$category;
                    $_GET['id_parent'] = $_POST['id_parent'] = (int)$category;
                    $_GET['controller'] = $controller;

                    $this->controller = str_replace('-', '', $controller);
                    if ($controller == "category" && $_GET['id'] == false && $_GET['id_category'] == false) {
                        $this->controller = "pagenotfound";
                    }
                    return $this->controller;

                }
            }


            if (preg_match('/iniciar-sesion/i', $this->request_uri)) {
                $controller = 'authentication';
            }

            // if (!preg_match('/^blog(\/.*)?/', $this->request_uri) && preg_match('/.*\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)(\?.*)?$/u', $this->request_uri)) {
            if (
                !preg_match('/^blog(\/.*)?/', $this->request_uri)
                && preg_match('/.*\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)(\?.*)?$/u', $this->request_uri)
                && !preg_match('/^\/m\/([_a-zA-Z0-9-\pL]+)(\?.*)?$/u', $this->request_uri)
            ) {



                $req_uri = explode('/', $this->request_uri);
                $request = end($req_uri);
                $link_rewrite = explode('-', $request);
                $rewrite = (int)$link_rewrite[0];

                $controller = 'product';
                $_POST['id'] = $rewrite;
                $_GET['id_product'] = $rewrite;
                $_GET['id_shop'] = Context::getContext()->shop->id;
                $_POST['id_shop'] = Context::getContext()->shop->id;
                $_POST['id_product'] = $rewrite;
            }

            if (isset($_POST['action']) && isset($_POST['ajax']) && isset($_GET['quantity_wanted'])) {
                $id_unique_ipa = (int)Context::getContext()->cookie->__get('id_unique_ipa');
                if ($id_unique_ipa > 0) {
                    $_POST['id_product_attribute'] = $id_unique_ipa;
                }
            } elseif (isset($_POST['action']) && isset($_POST['ajax']) && isset($_POST['quantity_wanted'])) {
                $id_unique_ipa = (int)Context::getContext()->cookie->__get('id_unique_ipa');
                if ($id_unique_ipa > 0) {
                    $_POST['id_product_attribute'] = $id_unique_ipa;
                }
            }

            if (preg_match('/module/', $this->request_uri)) {
                $disperseuri = explode('?', $this->request_uri);
                $disperseuri_1 = explode('?', $this->request_uri);
                $disperseuri = $disperseuri[0];

                $array_disperseuri = array_values(array_filter(explode('/', $disperseuri)));
                $_mod_uri = end($array_disperseuri); //Revisar

                $modules_routes = $this->getFriendlyModRoute($_mod_uri);
                if ($modules_routes) {
                    $modules_routes = explode('-', $modules_routes);
                    $_GET['fc'] = $modules_routes[0];
                    $_GET['module'] = $modules_routes[1];
                    $_GET['controller'] = $modules_routes[2];
                    $controller = $modules_routes[2];
                    $this->front_controller = self::FC_MODULE;
                } else if (isset($_GET['fc']) && isset($_GET['module']) && isset($_GET['controller'])) {
                    $controller = $_GET['controller'];
                    $this->front_controller = self::FC_MODULE;
                } else if (isset($_GET['fc']) && isset($_GET['module']) && isset($_GET['action'])) {
                    $this->controller = 'authentication';
                } else {
                    $req_uri_contact = explode('/', $disperseuri);
                    $_GET['fc'] = $req_uri_contact[1];
                    $_GET['module'] = $req_uri_contact[2];
                    $_GET['controller'] = $req_uri_contact[3];
                    $controller = $req_uri_contact[3];
                    $this->front_controller = self::FC_MODULE;
                }
            }


            if ($controller == 'newproductssport' || preg_match('/novedades/', $this->request_uri)) {

                if (!in_array('id_category', $_GET)) {
                    if (!empty($_GET['deporte'])) {
                        $_GET['id_category'] = $this->getCategoryRewrite($_GET['deporte']);
                    } else {
                        $req_uri = explode('/', $this->request_uri);
                        $_GET['deporte'] = $req_uri[1];
                        $_GET['id_category'] = $this->getCategoryRewrite($_GET['deporte']);
                    }
                }

                $_POST['id_category'] = $_GET['id_category'];
                $_POST['deporte'] = $_GET['deporte'];
                $controller = 'newproductssport';
                $_GET['controller'] = $controller;
            }

            /*$pattern = '#^/(' . implode('|', array_map('preg_quote', $this->array_deportes[$_GET['isolang']])) . ')/' . $this->array_ofertas[$_GET['isolang']] . '$#';
            if ($controller == 'pricesdropsport' || preg_match($pattern, $this->request_uri)) {

                if (!in_array('id_deporte', $_GET)) {
                    if (in_array('deporte', $_GET)) {
                        $_GET['id_deporte'] = $this->getCategoryRewrite($_GET['deporte']);
                    } else {
                        $req_uri = explode('/', $this->request_uri);
                        $_GET['deporte'] = $req_uri[1];
                        $_GET['id_deporte'] = $this->getCategoryRewrite($_GET['deporte']);
                    }
                }

                $_POST['id_deporte'] = $_GET['id_deporte'];
                $_POST['deporte'] = $_GET['deporte'];
                $controller = 'pricesdropsport';
                $_GET['controller'] = $controller;
            }*/

            if ($controller == 'bestsalessport' || preg_match('/lo_mas_vendido/', $this->request_uri)) {

                if (!in_array('id_category', $_GET)) {
                    if (!empty($_GET['deporte'])) {
                        $_GET['id_category'] = $this->getCategoryRewrite($_GET['deporte']);
                    } else {
                        $req_uri = explode('/', $this->request_uri);
                        $_GET['deporte'] = $req_uri[1];
                        $_GET['id_category'] = $this->getCategoryRewrite($_GET['deporte']);
                    }
                }

                $_POST['id_category'] = $_GET['id_category'];
                $_POST['deporte'] = $_GET['deporte'];
                $controller = 'bestsalessport';
                $_GET['controller'] = $controller;
            }

            if ($controller == 'boletines' || preg_match('/monthlynewsletters/', $this->request_uri)) {
                $module = (int)$this->getModule('alvarezboletines');

                if ($module > 0) {
                    $this->front_controller = self::FC_MODULE;
                    $_GET = array_merge($_GET, [
                        'fc' => 'module',
                        'module' => 'alvarezboletines',
                        'controller' => $controller,
                    ]);

                    if (empty($_GET['id_deport'])) {
                        $deporte = $_GET['deporte'] ?? explode('/', $this->request_uri)[1];
                        $_GET['deporte'] = $deporte;
                        $id_category = Db::getInstance()->getValue("SELECT `id_category` FROM " . _DB_PREFIX_ . "category_lang WHERE link_rewrite = '" . pSQL($deporte) . "'");
                        $_GET['id_deport'] = $id_category;
                        $_GET['id_deporte'] = Db::getInstance()->getValue("SELECT `id_deporte_origen` FROM " . _DB_PREFIX_ . "deportes WHERE id_category = " . (int)$id_category);
                    }

                    $_POST = array_merge($_POST, [
                        'id_deporte' => $_GET['id_deporte'],
                        'id_deport' => $_GET['id_deport'],
                        'deporte' => $_GET['deporte'],
                        'fc' => $_GET['fc'],
                        'module' => $_GET['module'],
                        'controller' => $_GET['controller']
                    ]);

                    $controller = 'boletines';
                    $_GET['controller'] = $controller;
                }
            }

            // if ($controller == 'manufacturerdeporte' || preg_match('/listado-marcas/', $this->request_uri)) {
            //     $controller = 'manufacturerdeporte';
            //     $requ = explode('/', $this->request_uri);

            //     if (empty($_GET['deporte']) || empty($requ[1])) {
            //         $_GET['deporte'] = null;
            //         $_GET['id_deporte'] = 0;
            //     } else {
            //         if (!isset($_GET['id_deporte'])) {
            //             $_GET['id_deporte'] = $this->getCategoryRewrite($_GET['deporte']);
            //         }
            //     }

            //     if (isset($_GET['id_manufacturer'])) {
            //         $_POST['id_manufacturer'] = $_GET['id_manufacturer'];

            //         if (empty($_GET['nombre_manu'])) {
            //             $manufacturer = new Manufacturer((int)$_GET['id_manufacturer']);
            //             $_GET['nombre_manu'] = $manufacturer->name;
            //         }

            //         $_POST['nombre_manu'] = $_GET['nombre_manu'];
            //         $_POST['id_deporte'] = $_GET['id_deporte'];
            //         $_POST['deporte'] = $_GET['deporte'];
            //         $_GET['id_manu'] = $_GET['id_manufacturer'];
            //     } else {
            //         $_POST['deporte'] = $_GET['deporte'];
            //     }
            // }

            if (
                $controller == 'manufacturer' &&
                preg_match('#^/m/([_a-zA-Z0-9-\pL]+)#u', $this->request_uri, $matches)
            ) {
                // Solo si falta el id_manufacturer, lo buscamos por rewrite
                if (!isset($_GET['id_manufacturer']) && isset($matches[1])) {
                    $rewrite = $matches[1];

                    // Buscar el id_manufacturer por rewrite
                    $id_manufacturer = (int)Db::getInstance()->getValue(
                        'SELECT m.id_manufacturer
                        FROM ' . _DB_PREFIX_ . 'manufacturer m
                        WHERE m.name LIKE "%' . pSQL($rewrite) . '%"'
                    );

                    if ($id_manufacturer > 0) {
                        // Existe en tabla auxiliar de marcas?
                        $existe_marca = (int)Db::getInstance()->getValue(
                            'SELECT id_manufacturer FROM aalv_alsernet_brand_as_category WHERE id_manufacturer = ' . $id_manufacturer
                        );
                        if ($existe_marca > 0) {
                            return $this->procesarCategoriaDesdeUrl();
                        } else {
                            // Es una marca
                            $manufacturer = new Manufacturer($id_manufacturer, Context::getContext()->language->id);
                            $_GET['id_manufacturer'] = $id_manufacturer;
                            $_GET['nombre_manu'] = $manufacturer->name;
                            $_GET['controller'] = 'manufacturer';
                            $this->controller = 'manufacturer';

                            return 'manufacturer'; // <- ¡CLAVE! Evita que otras condiciones lo pisen
                        }
                    }

                    // No existe marca, intentamos como categoría
                    return $this->procesarCategoriaDesdeUrl();
                }
            }

            if ($controller == 'manufacturer' && preg_match('/listado-marcas/', $this->request_uri)) {
                // Detectar /golf/listado-marcas sin parámetros
                if (preg_match('#^/([a-zA-Z0-9-_]+)/listado-marcas$#', $this->request_uri, $matches)) {
                    $deporte = $matches[1];
                    $id_deporte = (int)$this->getCategoryRewrite($deporte);

                    $_GET['controller'] = 'manufacturerdeporte';
                    $_GET['deporte'] = $deporte;
                    $_GET['id_deporte'] = $id_deporte;
                    $_POST['controller'] = 'manufacturerdeporte';
                    $_POST['deporte'] = $deporte;
                    $_POST['id_deporte'] = $id_deporte;

                    $controller = 'manufacturerdeporte';
                    $this->controller = $controller;
                    return $controller;
                }

                // Si se llegó con parámetros ?deporte=golf&id_deporte=3
                if (!isset($_GET['deporte']) && isset($requ[1])) {
                    $_GET['deporte'] = $requ[1];
                }

                if (!isset($_GET['id_deporte']) && isset($_GET['deporte'])) {
                    $_GET['id_deporte'] = $this->getCategoryRewrite($_GET['deporte']);
                }

                $_POST['deporte'] = $_GET['deporte'];
                $_POST['id_deporte'] = $_GET['id_deporte'];

                if (isset($_GET['id_manufacturer'])) {
                    $_POST['id_manufacturer'] = $_GET['id_manufacturer'];

                    if (empty($_GET['nombre_manu'])) {
                        $manufacturer = new Manufacturer((int)$_GET['id_manufacturer']);
                        $_GET['nombre_manu'] = $manufacturer->name;
                    }

                    $_POST['nombre_manu'] = $_GET['nombre_manu'];
                    $_GET['id_manu'] = $_GET['id_manufacturer'];
                }

                $_GET['controller'] = 'manufacturerdeporte';
                $controller = 'manufacturerdeporte';
                $this->controller = $controller;
                return $controller;
            }


            if ($controller == 'reviews' || $controller === 'module-lgcomments-reviews') {
                $module = (int)$this->getModule('lgcomments');
                if ($module > 0) {
                    $this->front_controller = self::FC_MODULE;
                    $_GET['fc'] = 'module';
                    $_GET['module'] = 'lgcomments';
                    $_GET['controller'] = 'reviews';
                    $_GET['deporte'] = '';
                    $_POST['fc'] = $_GET['fc'];
                    $_POST['module'] = $_GET['module'];
                    $_POST['controller'] = $_GET['controller'];
                    $controller = 'reviews';
                    $_GET['controller'] = $controller;
                }
            }



            if ($controller != 'category' && $controller != 'product') {
                $req_uri = explode('/', $this->request_uri);
                $url_parts = isset($req_uri[1]) ? explode('?', $req_uri[1]) : [];
                $url_cms = isset($url_parts[0]) ? $url_parts[0] : '';
                $params = isset($url_parts[1]) ? $url_parts[1] : '';

                if (!empty($params)) {
                    parse_str($params, $query_params);
                    foreach ($query_params as $key => $value) {
                        $_GET[$key] = $value;
                    }
                }

                $check_cms = (int)$this->getKeyExistanceCMS($url_cms);

                if ($check_cms > 0) {
                    $_POST['id_cms'] = $check_cms;
                    $controller = 'cms';
                    $_GET['controller'] = $controller;
                }

                $this->controller = str_replace('-', '', $controller);

                if (!empty($params)) {
                    $this->request_uri = "{$url_cms}?{$params}";
                } else {
                    $this->request_uri = $url_cms;
                }

                $_GET['controller'] = $this->controller;
                return $this->controller;

            }
        }

        $this->controller = str_replace('-', '', $controller);
        $_GET['controller'] = $this->controller;

        $id = isset($_GET['id']) ? $_GET['id'] : false;
        $id_category = isset($_GET['id_category']) ? $_GET['id_category'] : false;

        if ($controller === "category" && !$id && !$id_category) {
            $this->controller = "pagenotfound";
        }

        return $this->controller;
    }

    private function getCategoryRewrite($request)
    {
        $id_lang = (int)Context::getContext()->language->id;
        $id_shop = (int)Context::getContext()->shop->id;

        $sql = 'SELECT cl.`id_category`
            FROM `' . _DB_PREFIX_ . 'category_lang` cl
            INNER JOIN `' . _DB_PREFIX_ . 'category` c ON (cl.id_category = c.id_category)
            WHERE cl.`category_url_path` = "' . pSQL($request) . '"
            AND cl.`id_lang` = ' . $id_lang . '
            AND c.`active` = 1';


        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    private function getControllerPageById($id)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT `page`
				FROM ' . _DB_PREFIX_ . 'meta
				WHERE id_meta = ' . (int)$id);
    }

    private function getKeyExistance($req_uri)
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = Context::getContext()->shop->id;
        if (strpos($req_uri, '?')) {
            $req_uri_qmark = explode('?', $req_uri);
            $req_uri = $req_uri_qmark[0];
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT id_meta
					FROM ' . _DB_PREFIX_ . 'meta_lang
					WHERE url_rewrite = "' . pSQL($req_uri) . '"' . '
					AND `id_lang` = ' . (int)$id_lang . ' AND `id_shop` = ' . (int)$id_shop);
        } else {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT id_meta
					FROM ' . _DB_PREFIX_ . 'meta_lang
					WHERE url_rewrite = "' . pSQL($req_uri) . '"' . '
					AND `id_lang` = ' . (int)$id_lang . ' AND `id_shop` = ' . (int)$id_shop);
        }
    }

    private function getProductId($request)
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = Context::getContext()->shop->id;
        $sql = 'SELECT id_category FROM ' . _DB_PREFIX_ . 'category_lang
				WHERE link_rewrite = "' . pSQL($request) . '" AND id_lang = ' . (int)$id_lang . ' AND id_shop = ' . (int)$id_shop;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    private function getProductRewrite($request)
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = Context::getContext()->shop->id;
        $sql = 'SELECT id_category FROM ' . _DB_PREFIX_ . 'category_lang
				WHERE link_rewrite = "' . pSQL($request) . '" AND id_lang = ' . (int)$id_lang . ' AND id_shop = ' . (int)$id_shop;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    private function getProductExistance($request)
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = Context::getContext()->shop->id;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT `id_product`
		FROM ' . _DB_PREFIX_ . 'product_lang
		WHERE `link_rewrite` = "' . pSQL($request) . '"' . '
		AND `id_lang` = ' . (int)$id_lang . '
		AND `id_shop` = ' . (int)$id_shop);
    }

    private function getKeyExistanceCMS($request)
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = Context::getContext()->shop->id;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT `id_cms`
		FROM ' . _DB_PREFIX_ . 'cms_lang
		WHERE `link_rewrite` = "' . pSQL($request) . '"' . '
		AND `id_lang` = ' . (int)$id_lang . '
		AND `id_shop` = ' . (int)$id_shop);
    }

    private function getModule($module)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT `active` FROM ' . _DB_PREFIX_ . 'module WHERE `name` = "' . pSQL($module) . '"');
    }

    private function getFriendlyModRoute($uri)
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = Context::getContext()->shop->id;
        if (empty($uri)) {
            return false;
        } else {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT a.`page`
			FROM ' . _DB_PREFIX_ . 'meta a
			LEFT JOIN ' . _DB_PREFIX_ . 'meta_lang b ON (a.`id_meta` = b.`id_meta`)
			WHERE a.`page` LIKE "%module%"
			AND b.`url_rewrite` = "' . pSQL($uri) . '"
			AND b.`id_lang` = ' . (int)$id_lang . ' AND b.`id_shop` = ' . (int)$id_shop);
        }
    }

    protected function setRequestUri()
    {
        parent::setRequestUri();
        $remove_enabled = true;
        $current_iso_lang = Tools::getValue('isolang');
        if ($this->use_routes && Language::isMultiLanguageActivated() && !$current_iso_lang && $remove_enabled) {
            $_GET['isolang'] = Language::getIsoById(Configuration::get('PS_LANG_DEFAULT'));
        }
    }

    public function createUrl($route_id, $id_lang = null, array $params = [], $force_routes = false, $anchor = '', $id_shop = null)
    {
        if ($id_lang === null) {
            $id_lang = (int)Context::getContext()->language->id;
        }
        if ($id_shop === null) {
            $id_shop = (int)Context::getContext()->shop->id;
        }
        if (!isset($this->routes[$id_shop])) {
            $this->loadRoutes($id_shop);
        }
        if (!isset($this->routes[$id_shop][$id_lang][$route_id])) {
            $query = http_build_query($params, '', '&');
            $index_link = $this->use_routes ? '' : 'index.php';
            return ($route_id == 'index') ? $index_link . (($query) ? '?' . $query : '') : ((trim($route_id) == '') ? '' : 'index.php?controller=' . $route_id) . (($query) ? '&' . $query : '') . $anchor;
        }

        $route = $this->routes[$id_shop][$id_lang][$route_id];
        $query_params = isset($route['params']) ? $route['params'] : [];
        foreach ($route['keywords'] as $key => $data) {
            if (!$data['required']) {
                continue;
            }
            if (!array_key_exists($key, $params)) {
                throw new PrestaShopException('Dispatcher::createUrl() miss required parameter "' . $key . '" for route "' . $route_id . '"');
            }
            if (isset($this->default_routes[$route_id])) {
                $query_params[$this->default_routes[$route_id]['keywords'][$key]['param']] = $params[$key];
            }
        }
        if ($this->use_routes || $force_routes) {
            $url = $route['rule'];
            $add_param = [];
            foreach ($params as $key => $value) {
                if (!isset($route['keywords'][$key])) {
                    if (!isset($this->default_routes[$route_id]['keywords'][$key])) {
                        $add_param[$key] = $value;
                    }
                } else {
                    if ($params[$key]) {
                        $parameter = $params[$key];
                        if (is_array($parameter)) {
                            if (array_key_exists($id_lang, $parameter)) {
                                $parameter = $parameter[$id_lang];
                            } else {
                                $parameter = reset($parameter);
                            }
                        }
                        $replace = $route['keywords'][$key]['prepend'] . $parameter . $route['keywords'][$key]['append'];
                    } else {
                        $replace = '';
                    }
                    $url = preg_replace('#\{([^{}]*:)?' . $key . '(:[^{}]*)?\}#', $replace, $url);
                }
            }
            $url = preg_replace('#\{([^{}]*:)?[a-z0-9_]+?(:[^{}]*)?\}#', '', $url);
            if (count($add_param)) {
                $url .= '?' . http_build_query($add_param, '', '&');
            }
        } else {
            $add_params = [];
            foreach ($params as $key => $value) {
                if (!isset($route['keywords'][$key]) && !isset($this->default_routes[$route_id]['keywords'][$key])) {
                    $add_params[$key] = $value;
                }
            }
            if (!empty($route['controller'])) {
                $query_params['controller'] = $route['controller'];
            }
            $query = http_build_query(array_merge($add_params, $query_params), '', '&');
            if ($this->multilang_activated) {
                $query .= (!empty($query) ? '&' : '') . 'id_lang=' . (int)$id_lang;
            }
            $url = 'index.php?' . $query;
        }
        $ender = Tools::substr($url, -1);
        if ($ender == '/') {
            $url = rtrim($url, '/');
        }
        return str_replace('.html', '', $url . $anchor);
        return $url . $anchor;
    }

    private function procesarCategoriaDesdeUrl()
    {
        // Guardar la URL completa original
        $original_url = $this->request_uri;

        // Separar la URL base de los parámetros
        $url_components = parse_url($this->request_uri);
        $base_path = $url_components['path'];
        $original_query = isset($url_components['query']) ? '?' . $url_components['query'] : '';

        // Procesar solo la ruta base para getCategoryRewrite
        $category = $this->getCategoryRewrite(ltrim($base_path, '/'));

        if ($category != 0) {
            // Mantener la URL original completa
            $this->request_uri = $original_url;
            $controller = 'category';

            // Procesar los parámetros de la URL
            if ($original_query) {
                parse_str(ltrim($original_query, '?'), $query_params);
                $_GET = array_merge($_GET, $query_params);
            }

            // Establecer los parámetros de categoría
            $_GET['id'] = $_POST['id'] = (int)$category;
            $_GET['id_category'] = $_POST['id_category'] = (int)$category;
            $_GET['id_parent'] = $_POST['id_parent'] = (int)$category;
            $_GET['controller'] = $controller;

            $this->controller = str_replace('-', '', $controller);

            if ($controller == "category" && $_GET['id'] == false && $_GET['id_category'] == false) {
                $this->controller = "pagenotfound";
            }

            return $this->controller;
        }

        return null;
    }

}
