<?php

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Alsernetcontents extends Module implements WidgetInterface
{

    public function __construct()
    {

        $this->name = 'alsernetcontents';
        $this->author = 'Alsernet';
        $this->version = '2.0.4';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = "Alsernet contents";
        $this->description = $this->getTranslator()->trans('Make your customers feel at home on your store, invite them to sign in!', [], 'modules.Customersignin.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayHome')
            && $this->registerHook('displayFooter')
            && $this->registerHook('displayFooterBefore')
            && $this->registerHook('displayFooterAfter')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('displayNav1')
            && $this->registerHook('displayBanner')
            && $this->registerHook('displayNav2')
            && $this->registerHook('displayTop')
            && $this->registerHook('displayPages')
            && $this->registerHook('header');
    }

    private function getNameSimple($name)
    {
        return preg_replace('/\s\(.*\)$/', '', $name);
    }

    function getImageUrl()
    {
        $http = Tools::getCurrentUrlProtocolPrefix();
        return $http . Tools::getMediaServer(_THEME_LANG_DIR_) . _THEME_LANG_DIR_;
    }

    public function getWidgetVariables($hookName, array $configuration)
    {

        return [];
    }

    public function getWidgetVariablesAuth($hookName, array $configuration)
    {

        $logged = $this->context->customer->isLogged();
        $link = $this->context->link;
        $iso = $this->context->language->iso_code;

        return [
            'sticky' => $this->context->smarty->getTemplateVars('sticky'),
            'logged' => $logged,
            'iso' => $iso,
            'links' => $logged ? $link->getPageLink('my-account', true) : $link->getPageLink('iniciar-sesion', true),
        ];
    }


    public function renderWidget($hookName, array $configuration)
    {

        if ($hookName == 'displayPages') {

            if (isset($configuration['cms']) && $configuration['cms'] != 0) {

                switch ($configuration['cms']['id']) {

                    case 99:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/black.tpl');
                        break;

                    case 19:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/huntinginsurance.tpl');
                        break;

                    case 24:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/golfdiagnosis.tpl');
                        break;

                    case 14:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/exchangesandreturns.tpl');
                        break;

                    case 1:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/shipping_' . $this->context->language->iso_code . '.tpl');
                        break;

                    case 21:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/gunsmithworkshop.tpl');
                        break;

                    case 13:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/paymentandfinancing_' . $this->context->language->iso_code . '.tpl');
                        break;

                    case 101:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/trail.tpl');
                        break;

                    case 70:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/internalinformationsystem.tpl');
                        break;

                    case 15:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/customeradvocate.tpl');
                        break;

                    case 12:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/workwithus.tpl');
                        break;

                    case 121:

                        $email = Tools::getValue('email');
                        $this->context->smarty->assign('emailcampaigns', $email);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/giftvoucher.tpl');
                        break;

                    case 124:

                        $email = Tools::getValue('email');
                        $this->context->smarty->assign('emailcampaigns', $email);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/customizeyourexperience.tpl');
                        break;

                    case 131:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/subscribers.tpl');
                        break;

                    case 130:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/unsubscribers.tpl');
                        break;

                    case 134:

                        $data = $this->assign_template_values("special");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/special.tpl');
                        break;

                    case 50:

                        $data = $this->assign_template_values("outlets");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/outlets.tpl');
                        break;

                    case 68:

                        $data = $this->assign_template_values("general");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/blackfriday.tpl');
                        break;

                    case 103:

                        $data = $this->assign_template_values("caza");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/blackfriday_sports.tpl');
                        break;

                    case 104:

                        $data = $this->assign_template_values("golf");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/blackfriday_sports.tpl');
                        break;

                    case 105:

                        $data = $this->assign_template_values("pesca");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/blackfriday_sports.tpl');
                        break;

                    case 106:

                        $data = $this->assign_template_values("hipica");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/blackfriday_sports.tpl');
                        break;

                    case 107:

                        $data = $this->assign_template_values("buceo");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/blackfriday_sports.tpl');
                        break;

                    case 108:

                        $data = $this->assign_template_values("esqui");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/blackfriday_sports.tpl');
                        break;

                    case 109:

                        $data = $this->assign_template_values("nautica");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/blackfriday_sports.tpl');
                        break;


                    case 110:

                        $data = $this->assign_template_values("padel");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/blackfriday_sports.tpl');
                        break;

                    case 43:

                        $name = Tools::getValue('referrer-name');
                        $model = Tools::getValue('referrer-model');
                        $id = Tools::getValue('referrer-id');

                        $this->context->smarty->assign('financingname', $name);
                        $this->context->smarty->assign('financingmodel', $model);
                        $this->context->smarty->assign('financingid', $id);

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/interestfreefinancing.tpl');
                        break;

                    case 17:

                        $name = Tools::getValue('referrer-name');
                        $model = Tools::getValue('referrer-model');
                        $id = Tools::getValue('referrer-id');
                        $texts = [
                            "es" => "Nuestro compromiso es ofrecerle el mejor precio del mercado. Por lo tanto, si usted ha visto el producto que usted desea a un PVP más bajo, díganoslo y trataremos de mejorarlo.",
                            "pt" => "O nosso desejo é oferecer o melhor preço do mercado. Por isso, se viu este produto a um PVP inferior, avise-nos e tentaremos melhorá-lo.",
                            "fr" => "Notre compromis est de vous offrir le meilleur prix du marché. Donc, si vous avez vu le produit que vous désirez à un PVP inférieur, dites-le nous et nous essayerons de l’améliorer.",
                            "en" => "Our target is to offer the best price of the market. So, if you have seen this product at a lower RP, let us know and we'll try to improve it.",
                            "de" => "Unser wunsch ist es, die besten marktpreis anzubieten. also, wenn sie zu einem niedrigeren listenpreis dieses produkt gesehen haben, lassen sie uns wissen und wir werden versuchenes zu verbessern.",
                            "it" => "Il nostro impegno è offrirti il miglior prezzo sul mercato.Se hai trovato il prodotto che desideri a un prezzo inferiore altrove, faccelo sapere: faremo il possibile per offrirti un prezzo ancora più vantaggioso."
                        ];

                        $this->context->smarty->assign('bestpricename', $name);
                        $this->context->smarty->assign('bestpricemodel', $model);
                        $this->context->smarty->assign('bestpriceid', $id);
                        $this->context->smarty->assign('texts', $texts);

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/commitmentbestprice.tpl');
                        break;


                    case 111:
                        $data = $this->assign_template_values("megaofertas");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/megaofertas.tpl');
                        break;

                    case 115:

                        $data = $this->assign_template_values("ideasregalo");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/ideasregalo.tpl');
                        break;

                    case 120:

                        $data = $this->assign_template_values("RebajasInv25");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/rebajas.tpl');
                        break;

                    case 121:

                        $data = $this->assign_template_values("chequeregalo");
                        $this->context->smarty->assign($data);
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/chequeregalo.tpl');
                        break;

                    case 91:
                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/tiendas.tpl');

                    case 136:

                        return $this->fetch('module:alsernetcontents/views/templates/hook/pages/cms/documents.tpl');
                        break;

                    default:
                }
            }
        } elseif ($hookName == 'displayFooter') {

            $data = $this->getWidgetVariablesFooter($hookName, $configuration);
            $nums = count($data['footers']);
            $size = 9 / $nums;

            $this->context->smarty->assign([
                'footers' => $data['footers'],
                'size' => $size,
                'languages' => $this->getWidgetVariablesLanguage($hookName, $configuration)
            ]);

            return $this->fetch('module:alsernetcontents/views/templates/hook/footer/footer-middle.tpl');
        } elseif ($hookName == 'displayBeforeBodyClosingTag') {

            $logged = $this->context->customer->isLogged();
            $this->smarty->assign($this->getWidgetVariablesAuth($hookName, $configuration));

            if ($logged) {

                $wishlist_product = 0;

                $wishlist = WishList::existsLang($this->context->customer->id, 1);

                if ($wishlist) {
                    $wishlist_product = WishList::getProductByIdWishlist((int)$wishlist['id_wishlist'], $this->context->customer->id, 1);
                }

                $this->smarty->assign(array(
                    'isloggedwishlist' => true,
                    'wishlist_product' => count($wishlist_product),
                ));
            }

            return $this->fetch('module:alsernetcontents/views/templates/hook/partial/sticky.tpl');
        } elseif ($hookName == 'displayFooterAfter') {
            return $this->fetch('module:alsernetcontents/views/templates/hook/footer/footer-after.tpl');
        } elseif ($hookName == 'displayFooterBefore') {
            return $this->fetch('module:alsernetcontents/views/templates/hook/footer/footer-before.tpl');
        } elseif ($hookName == 'displayBanner') {

            $this->context->smarty->assign([
                'languages' => $this->getWidgetVariablesLanguage($hookName, $configuration)
            ]);

            return $this->fetch('module:alsernetcontents/views/templates/hook/header/header-banner.tpl');
        } elseif ($hookName == 'displayNav1') {

            return $this->fetch('module:alsernetcontents/views/templates/hook/header/header-middle-top.tpl');
        } elseif ($hookName == 'displayNav2') {

            $this->context->smarty->assign([
                'iso' => $this->context->language->iso_code,
            ]);

            return $this->fetch('module:alsernetcontents/views/templates/hook/header/header-middle.tpl');
        } elseif ($hookName == 'displayTop') {
            return $this->fetch('module:alsernetcontents/views/templates/hook/header/header-middle-bottom.tpl');
        } elseif ($hookName == 'displayHome') {

            if (isset($configuration['smarty'])) {

                $id_lang = $this->context->language->id;
                $category_ids = range(3, 11);
                $categorias = [];

                $cate_incluido = [
                    1 => "3,4,5,6,7,8,9,10,11",
                    2 => "3,4,5,6,7,8,9,10,11",
                    3 => "3,4,5,6,7,8,9,10,11",
                    4 => "3,4,5,6,7,8,9,10,11",
                    5 => "3,4,5,6,7,8,9,10,11",
                    6 => "3,4,5,6,7,8,9,10,11",
                ];

                $img_extras = [
                    1 => "",
                    2 => "",
                    3 => "",
                    4 => "",
                    5 => "",
                    6 => "",
                ];

                foreach ($category_ids as $id_category) {

                    $category = new Category($id_category, $id_lang);
                    $datos_incluido = explode(',', $cate_incluido[$id_lang]);
                    if (in_array($category->id, $datos_incluido)) {
                        $image_urls = $this->context->link->getCatImageLink($category->link_rewrite, $category->id);
                        //  $parsed_url = parse_url($image_urls);
                        //  $image_url = $parsed_url['path'];

                        if (Validate::isLoadedObject($category)) {
                            $categorias[] = [
                                'url' => $category->getLink(),
                                'nombre' => $category->name,
                                'img' => $image_urls,
                                'class' => 'overlay'
                            ];
                        }
                    } else {

                        $categorias[] = [
                            'url' => '',
                            'nombre' => '',
                            'img' => $img_extras[$id_lang],
                            'class' => ''
                        ];
                        break;
                    }
                }

                $this->context->smarty->assign('categorias', $categorias);

                return $this->fetch('module:alsernetcontents/views/templates/hook/pages/home.tpl');
            }
        }
    }

    public function getWidgetVariablesLanguage($hookName = null, array $configuration = [])
    {

        $languages = Language::getLanguages(true, $this->context->shop->id);

        foreach ($languages as &$language) {
            $language['name_simple'] = $this->getNameSimple($language['name']);
            $language['lang_url'] = $this->getImageUrl();
        }

        return array(
            'languages' => $languages,
            'current_language' => array(
                'id_lang' => $this->context->language->id,
                'name' => $this->context->language->name,
                'name_simple' => $this->getNameSimple($this->context->language->name),
                'iso_code' => $this->context->language->iso_code,
                'lang_url' => $this->getImageUrl(),
            )
        );
    }

    public function getWidgetVariablesFooter()
    {

        $iso_lang = Context::getContext()->language->iso_code;
        $jsonFilePath = _PS_MODULE_DIR_ . 'alsernetcontents/json/' . $iso_lang . '/footer.json';


        if (file_exists($jsonFilePath)) {

            $jsonContent = file_get_contents($jsonFilePath);


            $footers = json_decode($jsonContent, true);

            $filteredFooters = array_filter($footers, function ($footer) {
                return $footer['id'] != 0;
            });

            usort($filteredFooters, function ($a, $b) {
                return $a['position'] <=> $b['position'];
            });
        } else {

            $filteredFooters = array();
        }

        $data = [
            'footers' => $filteredFooters,
        ];

        return $data;
    }

    public function hookDisplayHome($params)
    {
        return $this->renderWidget('displayHome', $params);
    }

    public function hookDisplayPages($params)
    {
        return $this->renderWidget('displayPages', $params);
    }

    public function hookDisplayFooter($params)
    {
        return $this->renderWidget('displayFooter', $params);
    }

    public function hookDisplayFooterAfter($params)
    {
        return $this->renderWidget('displayFooterAfter', $params);
    }

    public function hookDisplayBeforeBodyClosingTag($params)
    {
        return $this->renderWidget('displayBeforeBodyClosingTag', $params);
    }

    public function hookDisplayFooterBefore($params)
    {
        return $this->renderWidget('displayFooterBefore', $params);
    }

    public function hookDisplayBanner($params)
    {
        return $this->renderWidget('displayBanner', $params);
    }

    public function hookDisplayNav1($params)
    {
        return $this->renderWidget('displayNav1', $params);
    }

    public function hookDisplayNav2($params)
    {
        return $this->renderWidget('displayNav2', $params);
    }

    public function hookDisplayTop($params)
    {
        return $this->renderWidget('displayTop', $params);
    }

    public function hookHeader($params)
    {

        $iso_code = $this->context->language->iso_code;

        $this->context->controller->registerJavascript(
            'alsernet-recaptcha',
            'https://www.google.com/recaptcha/api.js?onload=recaptchaOnLoad&render=explicit&hl=' . $iso_code,
            [
                'server' => 'remote',
                'position' => 'head',
                'priority' => 100, // asegúrate que tu propio JS (con recaptchaOnLoad) tenga prioridad < 100
                'attributes' => 'async defer' // 🔑 esto es lo que permite añadir async y defer como en HTML
            ]
        );

        $this->context->controller->addCSS($this->_path . 'views/vendor/magnific-popup/magnific-popup.min.css');
        $this->context->controller->addCSS($this->_path . 'views/vendor/select2/select2.css');
        $this->context->controller->addCSS($this->_path . 'views/vendor/dropzone/dist/dropzone.css');
        $this->context->controller->addCSS($this->_path . 'views/vendor/fontawesome/css/all.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/toastr.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/theme.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/special.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/style.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/main.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/form.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/trail.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/megaofertas.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/blackfriday.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/shipping.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/ideasregalo.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/tiendas.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/rebajas.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/subscribers.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/pages.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/campaigns.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/documents.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/ofertas.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/boletines.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front/outlets.css');

        //js
        $this->context->controller->registerJavascript(
            'alsernet-bootstrap',
            $this->_path . 'views/vendor/bootstrap/bootstrap.js',
            ['position' => 'bottom', 'priority' => 1]
        );

        $this->context->controller->registerJavascript(
            'alsernet-validate',
            $this->_path . 'views/js/vendor/validate/validate.js',
            ['position' => 'bottom', 'priority' => 2]
        );

        $this->context->controller->registerJavascript(
            'alsernet-toastr',
            $this->_path . 'views/js/vendor/toastr/toastr.min.js',
            ['position' => 'bottom', 'priority' => 1]
        );

        $this->context->controller->registerJavascript(
            'alsernet-toastr',
            $this->_path . 'views/vendor/dropzone/dist/dropzone.js',
            ['position' => 'bottom', 'priority' => 4]
        );

        $this->context->controller->registerJavascript(
            'alsernet-select2',
            $this->_path . 'views/vendor/select2/select2.js',
            ['position' => 'bottom', 'priority' => 5]
        );

        $this->context->controller->registerJavascript(
            'alsernet-backups',
            $this->_path . 'views/js/front/backups.js',
            ['position' => 'bottom', 'priority' => 6]
        );

        $this->context->controller->registerJavascript(
            'alsernet-scripts',
            $this->_path . 'views/js/front/scripts.js',
            ['position' => 'bottom', 'priority' => 7]
        );

        $this->context->controller->registerJavascript(
            'alsernet-main',
            $this->_path . 'views/js/front/main.js',
            ['position' => 'bottom', 'priority' => 8]
        );

        $this->context->controller->addJS($this->_path . 'views/js/front/campaigns/megaofertas.js');
        $this->context->controller->addJS($this->_path . 'views/js/front/campaigns/tiendas.js');
        $this->context->controller->addJS($this->_path . 'views/js/front/campaigns/trail.js');
    }

    public function assign_template_values($deporte)
    {
        $data = [];
        //LANDING CHEQUE REGALO
        if ($deporte == "chequeregalo") {
            $data = [
                "titles" => [
                    "es" => "Cheque regalo",
                    "pt" => "Vales de oferta",
                    "fr" => "Chèques-cadeaux",
                    "en" => "",
                    "de" => "",
                    "it" => ""
                ],
                "texts" => [
                    "es" => "RECIBE AHORA TU CHEQUE REGALO!!!",
                    "pt" => "ADQUIRA JÁ O SEU VALE DE OFERTA!!!",
                    "fr" => "OBTENEZ VOTRE CHÈQUE-CADEAU MAINTENANT!!!",
                    "en" => "",
                    "de" => "",
                    "it" => ""
                ],
                "descriptions" => [
                    "es" => [
                        "caza" => "CAZA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "HÍPICA",
                        "buceo" => "BUCEO",
                        "nautica" => "NAUTICA",
                        "esqui" => "ESQUÍ",
                        "padel" => "PADEL",
                    ],
                    "pt" => [
                        "caza" => "CAÇA",
                        "golf" => "GOLFE",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAÇAO",
                        "buceo" => "MERGULHO",
                        "nautica" => "VELA",
                        "esqui" => "ESQUI",
                        "padel" => "PADEL",
                    ],
                    "fr" => [
                        "caza" => "CHASSE",
                        "golf" => "GOLF",
                        "pesca" => "PÊCHE",
                        "hipica" => "ÈQUITATION",
                        "buceo" => "PLONGÈE",
                        "nautica" => "NAUTIQUE",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "en" => [
                        "caza" => "HUNTING",
                        "golf" => "GOLF",
                        "pesca" => "FISHING",
                        "hipica" => "RIDING",
                        "buceo" => "DIVING",
                        "nautica" => "BOATING",
                        "esqui" => "SKIING",
                        "padel" => "PADEL",
                    ],
                    "de" => [
                        "caza" => "JAGD",
                        "golf" => "GOLF",
                        "pesca" => "ANGELN",
                        "hipica" => "REITEN",
                        "buceo" => "TAUCHEN",
                        "nautica" => "NAUTIK",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "it" => [
                        "caza" => "CACCIA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAZIONE",
                        "buceo" => "SUBACQUEA",
                        "nautica" => "NAUTICA",
                        "esqui" => "SCI",
                        "padel" => "PADEL",
                    ],
                ],
                'urls' => [
                    "es" => [
                        "caza" => "/caza/ideas_para_regalar_caza",
                        "golf" => "/golf/ideas_para_regalar_golf",
                        "pesca" => "/pesca/ideas_para_regalar_pesca",
                        "hipica" => "/hipica/ideas_para_regalar_hipica",
                        "buceo" => "/buceo/ideas_para_regalar_buceo",
                        "nautica" => "/nautica/ideas_regalo_navidad",
                        "esqui" => "/esqui/ideas_regalo_navidad",
                        "padel" => "/padel/ideas_regalo_navidad",
                    ],
                    "pt" => [
                        "caza" => "/pt/caca/ideias_para_oferecer_caca",
                        "golf" => "/pt/golfe/ideias_para_oferecer_golfe",
                        "pesca" => "/pt/pesca/ideias_para_oferecer_pesca",
                        "hipica" => "/pt/equitacao/ideias_para_oferecer_equitacao",
                        "buceo" => "/pt/mergulho/ideias_para_oferecer_mergulho",
                        "nautica" => "/pt/vela/ideias_para_oferecer_vela",
                        "esqui" => "/pt/esqui/ideias_para_oferecer_esqui",
                        "padel" => "/pt/padel/ideias_presente_natal",
                    ],
                    "fr" => [
                        "caza" => "/fr/chasse/idees_cadeaux_chasse",
                        "golf" => "/fr/golf/idees_cadeaux_golf",
                        "pesca" => "/fr/peche/idees_cadeaux_peche",
                        "hipica" => "/fr/equitation/idees_cadeaux_equitation",
                        "buceo" => "/fr/plongee/idees_cadeaux_plongee",
                        "nautica" => "/fr/nautique/idees_cadeaux_nautique",
                        "esqui" => "/fr/plongee/idees_cadeaux_noel",
                        "padel" => "/fr/plongee/idees_cadeaux_noel",
                    ],
                    "en" => [
                        "caza" => "/en/hunting/gift_ideas_hunting",
                        "golf" => "/en/golf/gift_ideas_golf",
                        "pesca" => "/en/fishing/gift_ideas_fishing",
                        "hipica" => "/en/horse_riding/gift_ideas_horse_riding",
                        "buceo" => "/en/diving/gift_ideas_diving",
                        "nautica" => "/en/boating/gift_ideas_boating",
                        "esqui" => "/en/skiing/gift_ideas_skiing",
                        "padel" => "/en/padel/gift_ideas",
                    ],
                    "de" => [
                        "caza" => "/de/jagd/geschenkideen_jagd",
                        "golf" => "/de/golf/geschenkideen_golf",
                        "pesca" => "/de/angeln/geschenkideen_angeln",
                        "hipica" => "/de/reiten/geschenkideen_reiten",
                        "buceo" => "/de/tauchen/geschenkideen_tauchen",
                        "nautica" => "/de/nautik/geschenkideen_nautik",
                        "esqui" => "/de/ski/geschenkideen_ski",
                        "padel" => "/de/padel/geschenkideen",
                    ],
                    "it" => [
                        "caza" => "/it/caccia/idee_regalo_caccia",
                        "golf" => "/it/golf/idee_regalo_golf",
                        "pesca" => "/it/pesca/idee_regalo_pesca",
                        "hipica" => "/it/equitazione/idee_regalo_equitazione",
                        "buceo" => "/it/subacquea/idee_regalo_subacquea",
                        "nautica" => "/it/nautica/idee_regalo_nautica",
                        "esqui" => "/it/sci/idee_regalo_sci",
                        "padel" => "/it/padel/ideas_regalo_navidad",
                    ],
                ]
            ];
        }

        // LANDING GENERAL IDEAS REGALO
        if ($deporte == "ideasregalo") {
            $data = [
                "texts" => [
                    "es" => "Nos gusta ayudarte! Por eso te ofrecemos cientos de <strong>IDEAS REGALO para Navidad</strong> o para cualquier otra fecha en la que necesites sorprender y agradar a alguien.
                        Regalos originales para todo tipo de personas y presupuestos, con nuestro <strong>Compromiso Álvarez del mejor precio</strong>.
                        Durante Navidad y para tu tranquilidad, <strong>ampliamos el periodo de devoluciones hasta el 31 de enero de 2026 y, además, ¡¡GRATIS!!</strong> Así que, si no aciertas ¡no pasa nada! <strong>podrás cambiar o devolver tu compra sin coste </strong>(consulta condiciones).
                        Si estás buscando algún Regalo Especial o no localizas lo que necesitas, envíanos un email a: web@a-alvarez.com; estamos encantados de ayudarte.",
                    "pt" => "Adoramos ajudá-lo! Por isso, oferecemos centenas de <strong>IDEIAS DE PRESENTES para o Natal</strong> ou para qualquer outra ocasião em que precise de surpreender e encantar alguém.
                        Presentes originais para todos os gostos e carteiras, com o nosso <strong>Compromisso Álvarez do melhor preço</strong>.
                        Durante a época natalícia, para sua tranquilidade, <strong>prolongámos o prazo de devolução até 31 de janeiro de 2026 e, melhor ainda, GRÁTIS!</strong> Por isso, se não encontrar o presente perfeito, não há problema! <strong>Pode trocar ou devolver a sua compra sem qualquer custo</strong> (consulte os termos e condições).
                        Se procura um presente especial ou não encontra o que precisa, envie um e-mail para: webportugal@a-alvarez.com; Teremos todo o gosto em ajudar.",
                    "fr" => "Nous aimons vous aider ! C’est pourquoi nous vous proposons des centaines <strong>d'IDÉES CADEAUX pour Noël</strong> ou pour toute autre occasion où vous souhaitez surprendre et faire plaisir à quelqu’un.
                        Des cadeaux originaux pour tous les types de personnes et de budgets, avec notre <strong>Engagement Alvarez du meilleur prix</strong>.
                        Pendant Noël et pour votre tranquillité, <strong>nous prolongeons la période de retour jusqu’au 31 janvier 2026</strong>. Donc, si vous vous trompez, pas de souci ! <strong>Vous pourrez échanger ou retourner votre achat</strong> (voir conditions).
                        i vous cherchez un Cadeau Spécial ou ne trouvez pas ce dont vous avez besoin, envoyez-nous un e-mail à : web@a-alvarez.com ; nous serons ravis de vous aider.",
                    "en" => "We love helping you! That’s why we offer you hundreds of <strong>GIFT IDEAS for Christmas</strong> or any other occasion when you want to surprise and delight someone.
                        Original gifts for all kinds of people and budgets, with our <strong>Alvarez Best Price Commitment</strong>.
                        During Christmas, and for your peace of mind, <strong>we extend the return period until 31 January 2026</strong> So if you don’t get it right, no worries! <strong>You can exchange or return your purchase </strong> (see terms).
                        If you’re looking for a Special Gift or can’t find what you need, send us an email to: web@a-alvarez.com; we’ll be happy to help.",
                    "de" => "Wir helfen dir gern! Deshalb bieten wir dir Hunderte von <strong>GESCHENKIDEEN für Weihnachten</strong> oder jeden anderen Anlass, bei dem du jemanden überraschen und Freude bereiten möchtest.
                        Originelle Geschenke für jede Art von Person und jedes Budget, mit unserem <strong>Alvarez-Versprechen zum besten Preis</strong>.
                        Während der Weihnachtszeit und zu deiner Beruhigung <strong>verlängern wir die Rückgabefrist bis zum 31. Januar 2026</strong>. Also, wenn es nicht passt, kein Problem! <strong>Du kannst deinen Einkauf umtauschen oder zurückgeben</strong> (Bedingungen einsehen).
                        Wenn du nach einem besonderen Geschenk suchst oder nicht findest, was du brauchst, sende uns eine E-Mail an: web@a-alvarez.com;  wir helfen dir gern.",
                    "it" => "Ci piace aiutarti! Per questo ti offriamo centinaia di <strong>IDEE REGALO per Natale</strong> o per qualsiasi altra occasione in cui vuoi sorprendere e fare felice qualcuno.
                        Regali originali per ogni tipo di persona e budget, con il nostro <strong>Impegno Álvarez al miglior prezzo</strong>.
                        Durante il periodo natalizio, e per la tua tranquillità, <strong>estendiamo il periodo dei resi fino al 31 gennaio 2026.</strong> Quindi, se non azzecchi il regalo, nessun problema! <strong>Potrai cambiare o restituire il tuo acquisto </strong>(consulta le condizioni).
                        Se stai cercando un Regalo Speciale o non trovi quello che ti serve, inviaci un’email a: web@a-alvarez.com; siamo felici di aiutarti.",

                ],
                "titles" => [
                    "es" => "IDEAS REGALO",
                    "pt" => "IDEIAS PRESENTE",
                    "fr" => "IDEES CADEAUX",
                    "en" => "GIFT IDEAS",
                    "de" => "GESCHENK IDEEN",
                    "it" => "IDEE REGALO",
                ],
                "h1" => [
                    "es" => "IDEAS REGALO 2025",
                    "pt" => "IDEIAS PRESENTE 2025",
                    "fr" => "IDEES CADEAU 2025",
                    "en" => "GIFT IDEAS 2025",
                    "de" => "GESCHENK IDEEN 2025",
                    "it" => "IDEE REGALO 2025",
                ],
                "descriptions" => [
                    "es" => [
                        "caza" => "CAZA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "HÍPICA",
                        "buceo" => "BUCEO",
                        "nautica" => "NAUTICA",
                        "esqui" => "ESQUÍ",
                        "padel" => "PADEL",
                    ],
                    "pt" => [
                        "caza" => "CAÇA",
                        "golf" => "GOLFE",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAÇAO",
                        "buceo" => "MERGULHO",
                        "nautica" => "VELA",
                        "esqui" => "ESQUI",
                        "padel" => "PADEL",
                    ],
                    "fr" => [
                        "caza" => "CHASSE",
                        "golf" => "GOLF",
                        "pesca" => "PÊCHE",
                        "hipica" => "ÈQUITATION",
                        "buceo" => "PLONGÈE",
                        "nautica" => "NAUTIQUE",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "en" => [
                        "caza" => "HUNTING",
                        "golf" => "GOLF",
                        "pesca" => "FISHING",
                        "hipica" => "RIDING",
                        "buceo" => "DIVING",
                        "nautica" => "BOATING",
                        "esqui" => "SKIING",
                        "padel" => "PADEL",
                    ],
                    "de" => [
                        "caza" => "JAGD",
                        "golf" => "GOLF",
                        "pesca" => "ANGELN",
                        "hipica" => "REITEN",
                        "buceo" => "TAUCHEN",
                        "nautica" => "NAUTIK",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "it" => [
                        "caza" => "CACCIA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAZIONE",
                        "buceo" => " SUBACQUEA",
                        "nautica" => "NAUTICA",
                        "esqui" => "SCI",
                        "padel" => "PADEL",
                    ],
                ],
                'urls' => [
                    "es" => [
                        "caza" => "/caza/ideas_para_regalar_caza",
                        "golf" => "/golf/ideas_para_regalar_golf",
                        "pesca" => "/pesca/ideas_para_regalar_pesca",
                        "hipica" => "/hipica/ideas_para_regalar_hipica",
                        "buceo" => "/buceo/ideas_para_regalar_buceo",
                        "nautica" => "/nautica/ideas_para_regalar_nautica",
                        "esqui" => "/esqui/ideas_para_regalar_esqui",
                        "padel" => "/padel/ideas_regalo_navidad",
                    ],
                    "pt" => [
                        "caza" => "/pt/caca/ideias_para_oferecer_caca",
                        "golf" => "/pt/golfe/ideias_para_oferecer_golfe",
                        "pesca" => "/pt/pesca/ideias_para_oferecer_pesca",
                        "hipica" => "/pt/equitacao/ideias_para_oferecer_equitacao",
                        "buceo" => "/pt/mergulho/ideias_para_oferecer_mergulho",
                        "nautica" => "/pt/vela/ideias_para_oferecer_vela",
                        "esqui" => "/pt/esqui/ideias_para_oferecer_esqui",
                        "padel" => "/pt/padel/ideias_presente_natal",
                    ],
                    "fr" => [
                        "caza" => "/fr/chasse/idees_cadeaux_chasse",
                        "golf" => "/fr/golf/idees_cadeaux_golf",
                        "pesca" => "/fr/peche/idees_cadeaux_peche",
                        "hipica" => "/fr/equitation/idees_cadeaux_equitation",
                        "buceo" => "/fr/plongee/idees_cadeaux_plongee",
                        "nautica" => "/fr/nautique/idees_cadeaux_nautique",
                        "esqui" => "/fr/ski/idees_cadeaux_ski",
                        "padel" => "/fr/plongee/idees_cadeaux_noel",
                    ],
                    "en" => [
                        "caza" => "/en/hunting/gift_ideas_hunting",
                        "golf" => "/en/golf/gift_ideas_golf",
                        "pesca" => "/en/fishing/gift_ideas_fishing",
                        "hipica" => "/en/horse_riding/gift_ideas_horse_riding",
                        "buceo" => "/en/diving/gift_ideas_diving",
                        "nautica" => "/en/boating/gift_ideas_boating",
                        "esqui" => "/en/skiing/gift_ideas_skiing",
                        "padel" => "/en/padel/gift_ideas",
                    ],
                    "de" => [
                        "caza" => "/de/jagd/geschenkideen_jagd",
                        "golf" => "/de/golf/geschenkideen_golf",
                        "pesca" => "/de/angeln/geschenkideen_angeln",
                        "hipica" => "/de/reiten/geschenkideen_reiten",
                        "buceo" => "/de/tauchen/geschenkideen_tauchen",
                        "nautica" => "/de/nautik/geschenkideen_nautik",
                        "esqui" => "/de/ski/geschenkideen_ski",
                        "padel" => "/de/padel/geschenkideen",
                    ],
                    "it" => [
                        "caza" => "/it/caccia/idee_regalo_caccia",
                        "golf" => "/it/golf/idee_regalo_golf",
                        "pesca" => "/it/pesca/idee_regalo_pesca",
                        "hipica" => "/it/equitazione/idee_regalo_equitazione",
                        "buceo" => "/it/subacquea/idee_regalo_subacquea",
                        "nautica" => "/it/nautica/idee_regalo_nautica",
                        "esqui" => "/it/sci/idee_regalo_sci",
                        "padel" => "/it/padel/ideas_regalo_navidad",
                    ],
                ]
            ];
        }
        // LANDING GENERAL MEGAOFERTAS
        if ($deporte == "megaofertas") {
            $data = [
                "texts" => [
                    "es" => "<strong>Adelántate y consigue los mejores descuentos de noviembre.</strong>
                        Hemos seleccionado algunos de los “productos estrella” de nuestros deportes y te los ofrecemos a precio especial por tiempo limitado: desde el 11 al 17 de noviembre de 2025
                        No dejes pasar la oportunidad y aprovecha ahora, antes de que se agoten!",
                    "pt" => "<strong>Antecipe-se e aproveite os melhores descontos de novembro.</strong>
                        Selecionámos alguns dos nossos «produtos estrela» para desportos e oferecemos-lhos a um preço especial por tempo limitado: de 11 a 17 de novembro de 2025.
                        Não perca esta oportunidade e aproveite agora, antes que esgotem!",
                    "fr" => "<strong>Profite en avant-première des meilleures réductions de novembre !</strong>
                        Nous avons sélectionné quelques-uns des “produits phares” de nos sports et nous te les proposons à prix spécial pendant une durée limitée : du 11 au 17 novembre 2025.
                        Ne laisse pas passer cette occasion et profites-en dès maintenant avant qu’il ne soit trop tard !",
                    "en" => "<strong>Get ahead and grab the best discounts of November.</strong>
                        We have selected some of our sports ‘star inventaries’ and are offering them at a special price for a limited time: from 11 to 17 November 2025.
                        Don't miss out on this opportunity and take advantage now, before they sell out!",
                    "de" => "<strong>Sei der Erste und sichere dir die besten November-Rabatte!</strong>
                        Wir haben einige unserer „Top-Produkte“ ausgewählt und bieten sie dir für kurze Zeit zu einem Sonderpreis an – vom 11. bis 17. November 2025.
                        Verpasse nicht die Chance und nutze das Angebot jetzt, bevor alles ausverkauft ist!",
                    "it" => "<strong>Anticipa i migliori sconti di novembre!</strong>
                        Abbiamo selezionato alcuni dei “prodotti di punta” dei nostri sport e te li offriamo a un prezzo speciale per un periodo limitato: dall’11 al 17 novembre 2025.
                        Non perdere l’occasione e approfittane subito, prima che vadano a ruba!",

                ],
                "titles" => [
                    "es" => "MEGA OFERTAS",
                    "pt" => "MEGA OFERTAS",
                    "fr" => "MEGA OFFRES",
                    "en" => "MEGA DEALS",
                    "de" => "MEGA ANGEBOTE",
                    "it" => "MEGA OFFERTE",
                ],
                "h1" => [
                    "es" => "MEGA OFERTAS Álvarez 2025",
                    "pt" => "MEGA OFERTAS Álvarez 2025",
                    "fr" => "MEGA OFFRES Álvarez 2025",
                    "en" => "MEGA DEALS Álvarez 2025",
                    "de" => "MEGA ANGEBOTE Álvarez 2025",
                    "it" => "MEGA OFFERTE Álvarez 2025",
                ],
                "descriptions" => [
                    "es" => [
                        "caza" => "CAZA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "HÍPICA",
                        "buceo" => "BUCEO",
                        "nautica" => "NAUTICA",
                        "esqui" => "ESQUÍ",
                        "padel" => "PADEL",
                    ],
                    "pt" => [
                        "caza" => "CAÇA",
                        "golf" => "GOLFE",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAÇAO",
                        "buceo" => "MERGULHO",
                        "nautica" => "VELA",
                        "esqui" => "ESQUI",
                        "padel" => "PADEL",
                    ],
                    "fr" => [
                        "caza" => "CHASSE",
                        "golf" => "GOLF",
                        "pesca" => "PÊCHE",
                        "hipica" => "ÈQUITATION",
                        "buceo" => "PLONGÈE",
                        "nautica" => "NAUTIQUE",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "en" => [
                        "caza" => "HUNTING",
                        "golf" => "GOLF",
                        "pesca" => "FISHING",
                        "hipica" => "RIDING",
                        "buceo" => "DIVING",
                        "nautica" => "BOATING",
                        "esqui" => "SKIING",
                        "padel" => "PADEL",
                    ],
                    "de" => [
                        "caza" => "JAGD",
                        "golf" => "GOLF",
                        "pesca" => "ANGELN",
                        "hipica" => "REITEN",
                        "buceo" => "TAUCHEN",
                        "nautica" => "NAUTIK",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "it" => [
                        "caza" => "CACCIA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAZIONE",
                        "buceo" => " SUBACQUEA",
                        "nautica" => "NAUTICA",
                        "esqui" => "SCI",
                        "padel" => "PADEL",
                    ],
                ],
                'urls' => [
                    "es" => [
                        "caza" => "/caza/megaofertas_noviembre_caza",
                        "golf" => "/golf/megaofertas_noviembre_golf",
                        "pesca" => "/pesca/megaofertas_noviembre_pesca",
                        "hipica" => "/hipica/megaofertas_noviembre_equitacion",
                        "buceo" => "/buceo/megaofertas_noviembre_buceo",
                        "nautica" => "/nautica/megaofertas_noviembre_nautica",
                        "esqui" => "/esqui/megaofertas_noviembre_esqui",
                        "padel" => "/padel/megaofertas_noviembre_padel",
                    ],
                    "pt" => [
                        "caza" => "/pt/caca/megaofertas_de_caca_novembro",
                        "golf" => "/pt/golfe/megaofertas_de_golfe_novembro",
                        "pesca" => "/pt/pesca/megaofertas_de_pesca_novembro",
                        "hipica" => "/pt/equitacao/megaofertas_de_equitacao_novembro",
                        "buceo" => "/pt/mergulho/megaofertas_de_mergulho_novembro",
                        "nautica" => "/pt/vela/megaofertas_noviembre_nautica",
                        "esqui" => "/pt/esqui/megaofertas_de_esqui_novembro",
                        "padel" => "/pt/padel/megaofertas_noviembre_padel",
                    ],
                    "fr" => [
                        "caza" => "/fr/chasse/mega_offres_chasse_novembre",
                        "golf" => "/fr/golf/mega_offres_golf_novembre",
                        "pesca" => "/fr/peche/mega_offres_peche_novembre",
                        "hipica" => "/fr/equitation/mega_offres_equitation_novembre",
                        "buceo" => "/fr/plongee/mega_offres_plongee_novembre",
                        "nautica" => "/fr/nautique/megaofertas_noviembre_nautica",
                        "esqui" => "/fr/ski/mega_offres_ski_novembre",
                        "padel" => "/fr/padel/megaofertas_noviembre_padel",
                    ],
                    "en" => [
                        "caza" => "/en/hunting/november_mega_hunting_deals",
                        "golf" => "/en/golf/november_golf_megadeals",
                        "pesca" => "/en/fishing/november_mega_fishing_deals",
                        "hipica" => "/en/horse_riding/november_horse_riding_megadeals",
                        "buceo" => "/en/diving/november_diving_megadeals",
                        "nautica" => "/en/boating/megaofertas_noviembre_nautica",
                        "esqui" => "/en/skiing/november_ski_megadeals",
                        "padel" => "/en/padel/megaofertas_noviembre_padel",
                    ],
                    "de" => [
                        "caza" => "/de/jagd/mega_angebote_fuer_die_jagd_november",
                        "golf" => "/de/golf/mega_golfangebote_november",
                        "pesca" => "/de/angeln/mega_angebote_fuer_angler_november",
                        "hipica" => "/de/reiten/mega_angebote_fuer_reitsport_november",
                        "buceo" => "/de/tauchen/mega_tauchangebote_november",
                        "nautica" => "/de/nautik/megaofertas_noviembre_nautica",
                        "esqui" => "/de/ski/mega_skiangebote_november",
                        "padel" => "/de/padel/megaofertas_noviembre_padel",
                    ],
                    "it" => [
                        "caza" => "/it/caccia/megaofferte_caccia_novembre",
                        "golf" => "/it/golf/megaofferte_golf_novembre",
                        "pesca" => "/it/pesca/megaofferte_pesca_novembre",
                        "hipica" => "/it/equitazione/megaofferte_equitazione_novembre",
                        "buceo" => "/it/subacquea/megaofferte_immersioni_novembre",
                        "nautica" => "/it/nautica/megaofertas_noviembre_nautica",
                        "esqui" => "/it/sci/megaofferte_sci_novembre",
                        "padel" => "/it/padel/ideas_regalo_navidad",
                    ],
                ]
            ];
        }
        if ($deporte == "special") {
            $data = [
                "texts" => [
                    "es" => "En Álvarez queremos que la Primera Comunión sea un día inolvidable. <br>Por eso hemos seleccionado una serie de productos que son IDEAS DE REGALO DIFERENTES para nuestros pequeños y con las que estamos seguros de que sorprenderás y acertarás.",
                    "pt" => "Na Álvarez queremos que a Primeira Comunhão seja um dia inesquecível.<br>Por isso, selecionámos uma série de produtos que são IDEIAS DE PRESENTES DIFERENTES para os nossos pequenos e com os quais temos a certeza de que irá surpreender e ter sucesso.",
                    "fr" => "Chez Alvarez, nous voulons que la Première Communion reste un souvenir exceptionnel.<br>C’est pourquoi nous avons sélectionné une série de produits qui sont de , pensées pour nos petits, avec lesquelles vous êtes sûr de faire plaisir et de surprendre.",
                    "en" => "",
                    "de" => "",
                    "it" => "In Alvarez vogliamo che la Prima Comunione sia un giorno indimenticabile.<br>Per questo abbiamo selezionato una serie di prodotti che sono VERI E PROPRI REGALI ORIGINALI, pensati per i nostri piccoli, con cui siamo sicuri che sorprenderai e farai felice.",
                ],
                "titles" => [
                    "es" => "IDEAS REGALO",
                    "pt" => "IDEIAS PRESENTES",
                    "fr" => "IDÉES CADEAUX",
                    "en" => "",
                    "de" => "",
                    "it" => "IDEE REGALO",
                ],
                "h1" => [
                    "es" => "ESPECIAL COMUNIONES 2025",
                    "pt" => "ESPECIAL COMUNHÕES 2025",
                    "fr" => "SPÉCIAL COMMUNIONS 2025",
                    "en" => "",
                    "de" => "",
                    "it" => "PRIMI COMUNIONI 2025",
                ],
                "status" => [
                    "es" => true,
                    "pt" => true,
                    "fr" => true,
                    "en" => false,
                    "de" => false,
                    "it" => true,
                ],
                "descriptions" => [
                    "es" => [
                        "caza" => "CAZA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "HÍPICA",
                        "buceo" => "BUCEO",
                        "nautica" => "NAUTICA",
                        "esqui" => "ESQUÍ",
                        "padel" => "PADEL",
                    ],
                    "pt" => [
                        "caza" => "CAÇA",
                        "golf" => "GOLFE",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAÇAO",
                        "buceo" => "MERGULHO",
                        "nautica" => "VELA",
                        "esqui" => "ESQUI",
                        "padel" => "PADEL",
                    ],
                    "fr" => [
                        "caza" => "CHASSE",
                        "golf" => "GOLF",
                        "pesca" => "PÊCHE",
                        "hipica" => "ÈQUITATION",
                        "buceo" => "PLONGÈE",
                        "nautica" => "NAUTIQUE",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "en" => [
                        "caza" => "",
                        "golf" => "",
                        "pesca" => "",
                        "hipica" => "",
                        "buceo" => " ",
                        "nautica" => "",
                        "esqui" => "",
                        "padel" => "",
                    ],
                    "de" => [
                        "caza" => "",
                        "golf" => "",
                        "pesca" => "",
                        "hipica" => "",
                        "buceo" => " ",
                        "nautica" => "",
                        "esqui" => "",
                        "padel" => "",
                    ],
                    "it" => [
                        "caza" => "CACCIA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAZIONE",
                        "buceo" => " SUBACQUEA",
                        "nautica" => "NAUTICA",
                        "esqui" => "SCI",
                        "padel" => "PADEL",
                    ],
                ],
                'urls' => [
                    "es" => [
                        "caza" => "/caza/regalos_primera_comunion",
                        "golf" => "/golf/regalos_primera_comunion",
                        "pesca" => "/pesca/regalos_primera_comunion",
                        "hipica" => "/hipica/regalos_primera_comunion",
                        "buceo" => "/buceo/regalos_primera_comunion",
                        "nautica" => "/nautica/regalos_primera_comunion",
                        "esqui" => "/esqui/regalos_primera_comunion",
                        "padel" => "/padel/regalos_primera_comunion",
                    ],
                    "pt" => [
                        "caza" => "/pt/caca/presentes_primeira_comunhao",
                        "golf" => "/pt/golfe/presentes_primeira_comunhao",
                        "pesca" => "/pt/pesca/presentes_primeira_comunhao",
                        "hipica" => "/pt/equitacao/presentes_primeira_comunhao",
                        "buceo" => "/pt/mergulho/presentes_primeira_comunhao",
                        "nautica" => "/pt/vela/presentes_primeira_comunhao",
                        "esqui" => "/pt/esqui/presentes_primeira_comunhao",
                        "padel" => "/pt/padel/presentes_primeira_comunhao",
                    ],
                    "fr" => [
                        "caza" => "/fr/chasse/cadeaux_communions",
                        "golf" => "/fr/golf/cadeaux_communions",
                        "pesca" => "/fr/peche/cadeaux_communions",
                        "hipica" => "/fr/equitation/cadeaux_communions",
                        "buceo" => "/fr/plongee/cadeaux_communions",
                        "nautica" => "",
                        "esqui" => "",
                        "padel" => "",
                    ],
                    "en" => [
                        "caza" => "",
                        "golf" => "",
                        "pesca" => "",
                        "hipica" => "",
                        "buceo" => "",
                        "nautica" => "",
                        "esqui" => "",
                        "padel" => "",
                    ],
                    "de" => [
                        "caza" => "",
                        "golf" => "",
                        "pesca" => "",
                        "hipica" => "",
                        "buceo" => "",
                        "nautica" => "",
                        "esqui" => "",
                        "padel" => "",
                    ],
                    "it" => [
                        "caza" => "/it/caccia/regali_prima_comunione",
                        "golf" => "/it/golf/regali_prima_comunione",
                        "pesca" => "/it/pesca/regali_prima_comunione",
                        "hipica" => "/it/equitazione/regali_prima_comunione",
                        "buceo" => "/it/subacquea/regali_prima_comunione",
                    ],
                ]
            ];
        }


        // LANDING GENERAL BLACK
        if ($deporte == "general") {
            $data = [
                "texts" => [
                    "es" => "<p>Ya es Black Friday en “Álvarez, deporte y tiempo libre” y para este día tan especial hemos reunido cientos de ofertas en todos nuestros deportes que te dejarán con la boca abierta.
                            El Black Friday 2025 viene cargado de grandes descuentos en cientos de productos: simplemente selecciona tu deporte favorito y accede a la categoría Black Friday de ese deporte para ver todos los artículos con su descuento correspondiente. Encuentra las mejores ofertas en caza, golf, esquí, hípica, pesca, pádel, buceo…
                            ¡No esperes más y aprovecha los precios únicos de este gran día para hacer tus compras de navidad!</p>
                            <h2>Mejores descuentos y ofertas de Black Friday en material deportivo y outdoor</h2><p>El Black Friday en Álvarez es sinónimo de calidad al mejor precio: descuentos y ofertas en miles de artículos de <b>marcas de primera línea</b> a nivel mundial, líderes en sus respectivos sectores.
                                Estos precios especiales <b>sólo se verán durante el Black Friday de Álvarez</b>, lo que lo convierte en el momento perfecto para renovar tu equipación, darte ese capricho o adelantar tus compras de Navidad.
                                Podrás encontrar ofertas en tus marcas favoritas, incluyendo:
                            </p>
                            <ul>
                                <li><a href='https://www.a-alvarez.com/m/Taylormade'><u>Taylormade:</u></a> Material de Golf de alto rendimiento.</li>
                                <li><a href='https://www.a-alvarez.com/m/Chiruca'><u>Chiruca:</u></a> Calzado y ropa técnica para trekking y outdoor.</li>
                                <li><a href='https://www.a-alvarez.com/m/Callaway'><u>Callaway:</u></a> Lo último en equipamiento de Golf.</li>
                                <li><a href='https://www.a-alvarez.com/m/Shimano'><u>Shimano:</u></a> Equipos de Pesca y Ciclismo.</li>
                                <li><a href='https://www.a-alvarez.com/m/Hikmicro'><u>Hikmicro:</u></a> Visión térmica y nocturna.</li>
                                <li><a href='https://www.a-alvarez.com/m/Beretta'><u>Beretta:</u></a> Caza, tiro deportivo y ropa técnica.</li>
                                <li><a href='https://www.a-alvarez.com/m/Aqualung'><u>Aqualung:</u></a> Equipos de buceo y snorkel.</li>
                                <li><a href='https://www.a-alvarez.com/m/Swarovski'><u>Swarovski:</u></a> Óptica deportiva y de observación.</li>
                                <li><a href='https://www.a-alvarez.com/m/Hart'><u>Hart:</u></a> Material de Pesca y Caza.</li>
                                <li><a href='https://www.a-alvarez.com/m/Salomon'><u>Salomon:</u></a> Zapatillas, ropa y material de montaña.</li>
                                <li><a href='https://www.a-alvarez.com/m/Cressi'><u>Cressi:</u></a> Equipamiento de submarinismo.</li>
                                <li><a href='https://www.a-alvarez.com/m/Atomic'><u>Atomic:</u></a> Material de esquí y deportes de invierno.</li>
                            </ul>
                            <p>¡Y muchas más! Navega por la web y descubre las sorpresas que tenemos preparadas.</p>
                            <hr>
                            <p><b>Preguntas Frecuentes sobre el Black Friday en Álvarez (FAQ)</b>

                            Hemos recopilado las preguntas más habituales para que planifiques tus compras con total confianza.

                            <b>¿Qué es el Black Friday 2025?</b>

                            El Black Friday es la jornada de rebajas y ofertas más importante del año, originada en Estados Unidos y celebrada el día después de Acción de Gracias. Marca el inicio de las compras navideñas. En Álvarez, lo celebramos con promociones que abarcan prácticamente todo nuestro catálogo, ofreciendo precios únicos en material deportivo, caza, pesca, outdoor y mucho más.

                            <b>¿Qué productos y marcas estarán en oferta?</b>
                            <b>Habrá representación de todas nuestras categorías y marcas</b> principales. Estamos trabajando intensamente para que el <b>Black Friday 2025 en Álvarez sea inigualable</b>. Para ello, hemos negociado con los principales fabricantes y distribuidores de cada deporte y afición. Prepárate para encontrar descuentos en material de golf, caza, tiro deportivo, pesca, equitación, submarinismo, esquí, montaña y mucho más, con la calidad garantizada de las marcas líderes del mercado.

                            <b>¿Cuánto tardan en llegar los productos durante la campaña?</b>

                            Nuestro compromiso habitual es servir la gran mayoría de nuestros productos en un plazo de <b>48 horas</b>.

                            No obstante, ten en cuenta que durante el Black Friday pueden producirse algunos retrasos. El volumen de pedidos que gestionamos es el más alto del año, y tanto los fabricantes para reponer stock como las agencias de transportes pueden llegar a estar saturados. Te recomendamos hacer tus pedidos con la máxima antelación posible. Te mantendremos informado en todo momento sobre el estado de tu envío.

                            <b>Devoluciones: ¿Cuál es el periodo durante el Black Friday?</b>

                            Queremos que aproveches el Black Friday para realizar tus <b>compras de Navidad</b> con total tranquilidad. Por ello, en Álvarez <b>ampliamos el periodo de devoluciones</b> para todos los pedidos realizados durante la campaña, <b>¡hasta el 31 de enero de 2026!</b>

                            Puedes comprar regalos con confianza, sabiendo que la persona que los reciba tendrá margen más que suficiente para realizar un cambio o devolución si lo necesita.</p>",
                    "pt" => "<p>Já é Black Friday em “Álvarez, desporto e lazer” e, para este dia especial, reunimos centenas de ofertas em todos os nossos desportos que o vão deixar de boca aberta.
                            A Black Friday 2025 está repleta de grandes descontos em centenas de produtos: basta selecionar o seu desporto preferido e ir à categoria Black Friday desse desporto para ver todos os artigos com o desconto correspondente. Encontre as melhores ofertas em caça, golfe, esqui, equitação, pesca, paddle, mergulho...
                            Não espere mais e aproveite os preços únicos deste grande dia para fazer as suas compras de Natal!</p>
                            <h2>Melhores descontos e ofertas de Black Friday em material desportivo e outdoor</h2><p>A Black Friday na Álvarez é sinónimo de qualidade ao melhor preço: descontos e ofertas em milhares de artigos de <b>marcas de primeira linha a nível mundial,</b> líderes nos respetivos setores.
                                Estes preços especiais <b>só serão vistos durante a Black Friday da Álvarez</b>, o que a torna o momento perfeito para renovar o seu equipamento, dar-se esse capricho ou antecipar as suas compras de Natal.
                                Poderá encontrar ofertas nas suas marcas favoritas, incluindo:
                            </p>
                            <ul>
                                <li><a href='https://www.a-alvarez.com/pt/m/Taylormade'><u>Taylormade:</u></a> Material de Golfe de alto desempenho.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Chiruca'><u>Chiruca:</u></a> Calçado e vestuário técnico para trekking e outdoor.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Callaway'><u>Callaway:</u></a> O mais recente em equipamento de Golfe.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Shimano'><u>Shimano:</u></a> Equipamentos de Pesca e Ciclismo.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Hikmicro'><u>Hikmicro:</u></a> Visão térmica e noturna.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Beretta'><u>Beretta:</u></a> Caça, tiro desportivo e vestuário técnico.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Aqualung'><u>Aqualung:</u></a> Equipamentos de mergulho e snorkel.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Swarovski'><u>Swarovski:</u></a> Ótica desportiva e de observação.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Hart'><u>Hart:</u></a> Material de Pesca e Caça.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Salomon'><u>Salomon:</u></a> Sapatilhas, vestuário e material de montanha.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Cressi'><u>Cressi:</u></a> Equipamento de submarinismo.</li>
                                <li><a href='https://www.a-alvarez.com/pt/m/Atomic'><u>Atomic:</u></a> Material de esqui e desportos de inverno.</li>
                            </ul>
                            <p>E muitas mais! Navegue no site e descubra as surpresas que temos preparadas.</p>
                            <hr>
                            <p><b>Perguntas Frequentes sobre a Black Friday na Álvarez (FAQ)</b>

                            Reunimos as perguntas mais habituais para que planeie as suas compras com total confiança.

                            <b>O que é a Black Friday 2025?</b>

                            A Black Friday é a jornada de saldos e ofertas mais importante do ano, originada nos Estados Unidos e celebrada no dia a seguir ao Dia de Ação de Graças. Marca o início das compras de Natal. Na Álvarez, celebramo-la com promoções que abrangem praticamente todo o nosso catálogo, oferecendo <b>preços únicos</b> em material desportivo, caça, pesca, outdoor e muito mais.

                            <br><b>Que produtos e marcas estarão em oferta?</b>
                            Haverá <b>representação de todas as nossas categorias e marcas principais.</b> Estamos a trabalhar intensamente para que a Black Friday 2025 na Álvarez seja <b>inigualável.</b> Para tal, negociámos com os principais fabricantes e distribuidores de cada desporto e hobby. Prepare-se para encontrar descontos em material de golfe, caça, tiro desportivo, pesca, equitação, submarinismo, esqui, montanha e muito mais, com a qualidade garantida das marcas líderes de mercado.

                            <b>Quanto tempo demoram os produtos a chegar durante a campanha?</b>

                            O nosso compromisso habitual é servir a grande maioria dos nossos produtos num prazo de <b>48 horas</b>.

                            No entanto, tenha em conta que durante a Black Friday poderão ocorrer alguns atrasos. O volume de pedidos que gerimos é o mais alto do ano, e tanto os fabricantes para repor stock como as transportadoras podem ficar sobrecarregados. Recomendamos que faça os seus pedidos com a máxima antecedência possível. Iremos mantê-lo(a) informado(a) em todos os momentos sobre o estado da sua encomenda.

                            <b>Devoluções: Qual é o período durante a Black Friday?</b>

                            Queremos que aproveite a Black Friday para realizar as suas <b>compras de Natal</b> com total tranquilidade. Por isso, na Álvarez <b>alargamos o período de devoluções</b> para todos os pedidos feitos durante a campanha, <b>até 31 de janeiro de 2026!</b>

                            Pode comprar presentes com confiança, sabendo que a pessoa que os receber terá margem mais do que suficiente para realizar uma troca ou devolução, se necessário.</p>",
                    "fr" => "<p>C'est déjà le Black Friday à « Álvarez, sport et loisirs » et pour cette journée spéciale, nous avons rassemblé des centaines d'offres dans tous nos sports qui vous laisseront bouche bée.
                            Le Black Friday 2025 regorge de réductions sur des centaines de produits : il vous suffit de sélectionner votre sport préféré et de vous rendre dans la catégorie Black Friday de ce sport pour voir tous les articles avec la réduction correspondante. Trouvez les meilleures offres sur la chasse, le golf, le ski, l'équitation, la pêche, le paddle, la plongée...
                            N'attendez plus et profitez des prix uniques de ce grand jour pour faire vos achats de Noël !</p>
                            <h2>Meilleures réductions et offres du Black Friday sur les équipements de sport et d'outdoor</h2><p>Le Black Friday chez Álvarez est synonyme de qualité au meilleur prix : des réductions et des offres sur des milliers d'articles de <b>marques de premier plan au niveau mondial,</b> leaders dans leurs secteurs respectifs.
                                Ces prix spéciaux ne seront vus <b>que pendant le Black Friday d'Álvarez</b>, ce qui en fait le moment idéal pour renouveler votre équipement, vous faire plaisir ou anticiper vos achats de Noël.
                                Vous trouverez des offres sur vos marques préférées, notamment :
                            </p>
                            <ul>
                                <li><a href='https://www.a-alvarez.com/fr/m/Taylormade'><u>Taylormade :</u></a> Matériel de Golf haute performance.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Chiruca'><u>Chiruca :</u></a> Chaussures et vêtements techniques de trekking et outdoor.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Callaway'><u>Callaway :</u></a> Le nec plus ultra en équipement de Golf.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Shimano'><u>Shimano :</u></a> Équipements de Pêche et Cyclisme.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Hikmicro'><u>Hikmicro :</u></a> Vision thermique et nocturne.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Beretta'><u>Beretta :</u></a> Chasse, tir sportif et vêtements techniques.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Aqualung'><u>Aqualung :</u></a> Équipements de plongée et snorkeling.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Swarovski'><u>Swarovski :</u></a> Optiques sportives et d'observation.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Hart'><u>Hart :</u></a> Matériel de Pêche et Chasse.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Salomon'><u>Salomon :</u></a> Chaussures, vêtements et matériel de montagne.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Cressi'><u>Cressi :</u></a> Équipement de plongée sous-marine.</li>
                                <li><a href='https://www.a-alvarez.com/fr/m/Atomic'><u>Atomic :</u></a> Matériel de ski et sports d'hiver.</li>
                            </ul>
                            <p>Et bien d'autres encore ! Naviguez sur le site et découvrez les surprises que nous avons préparées.</p>
                            <hr>
                            <p><b>Questions Fréquemment Posées sur le Black Friday chez Álvarez (FAQ)</b>

                            Nous avons rassemblé les questions les plus courantes pour que vous planifiez vos achats en toute confiance.

                            <b>Qu'est-ce que le Black Friday 2025 ?</b>

                            Le Black Friday est le jour de soldes et d'offres le plus important de l'année, originaire des États-Unis et célébré le lendemain de Thanksgiving. Il marque le début des achats de Noël. Chez Álvarez, nous le célébrons avec des promotions qui couvrent pratiquement tout notre catalogue, offrant des <b>prix uniques</b> sur le matériel sportif, la chasse, la pêche, l'outdoor et bien plus encore.

                            <br><b>Quels produits et marques seront en promotion ?</b>
                            <b>Toutes nos catégories et marques principales seront représentées.</b> Nous travaillons intensément pour que le Black Friday 2025 chez Álvarez soit <b>inégalable</b>. Pour ce faire, nous avons négocié avec les principaux fabricants et distributeurs de chaque sport et loisir. Préparez-vous à trouver des réductions sur le matériel de golf, chasse, tir sportif, pêche, équitation, plongée sous-marine, ski, montagne et bien plus encore, avec la qualité garantie des marques leaders du marché.

                            <b>Quel est le délai de livraison des produits pendant la campagne ?</b>

                            Notre engagement habituel est de livrer la grande majorité de nos produits dans un délai de <b>48 heures</b>.

                            Cependant, veuillez noter que des retards peuvent survenir pendant le Black Friday. Le volume de commandes que nous traitons est le plus élevé de l'année, et tant les fabricants pour le réapprovisionnement des stocks que les agences de transport peuvent être saturés. Nous vous recommandons de passer vos commandes le plus tôt possible. Nous vous tiendrons informé(e) en tout temps de l'état de votre envoi.

                            <b>Retours : Quelle est la période pendant le Black Friday ?</b>

                            Nous souhaitons que vous profitiez du Black Friday pour effectuer vos <b>achats de Noël</b> en toute sérénité. C'est pourquoi, chez Álvarez, nous <b>prolongeons la période de retours</b> pour toutes les commandes passées pendant la campagne, <b>jusqu'au 31 janvier 2026 !</b>

                            Vous pouvez acheter des cadeaux en toute confiance, sachant que la personne qui les recevra aura une marge plus que suffisante pour effectuer un échange ou un retour si nécessaire.</p>",
                    "en" => "<p>It's already Black Friday in “Alvarez, sport and leisure” and for this special day we have gathered hundreds of offers in all our sports that will leave you with your mouth open.
                            Black Friday 2025 comes loaded with great discounts on hundreds of inventaries: simply select your favorite sport and access the Black Friday category of that sport to see all the items with their corresponding discount. Find the best deals on hunting, golf, skiing, horse riding,fishing, paddle tennis, diving...
                            Don't wait any longer and take advantage of the unique prices of this great day to do your Christmas shopping!</p>
                            <h2>Best Black Friday discounts and offers on sports and outdoor equipment</h2><p>Black Friday at Álvarez is synonymous with quality at the best price: discounts and offers on thousands of items from <b>world-class, top-tier brands</b>, leaders in their respective sectors.
                                These special prices will <b>only be seen during the Álvarez Black Friday</b>, making it the perfect time to renew your gear, treat yourself, or get a head start on your Christmas shopping.
                                You will find offers on your favourite brands, including:
                            </p>
                            <ul>
                                <li><a href='https://www.a-alvarez.com/en/m/Taylormade'><u>Taylormade:</u></a> High-performance Golf equipment.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Chiruca'><u>Chiruca:</u></a> Technical footwear and clothing for trekking and outdoor.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Callaway'><u>Callaway:</u></a> The latest in Golf equipment.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Shimano'><u>Shimano:</u></a> Fishing and Cycling gear.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Hikmicro'><u>Hikmicro:</u></a> Thermal and night vision.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Beretta'><u>Beretta:</u></a> Hunting, sport shooting, and technical clothing.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Aqualung'><u>Aqualung:</u></a> Diving and snorkeling equipment.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Swarovski'><u>Swarovski:</u></a> Sports and observation optics.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Hart'><u>Hart:</u></a> Fishing and Hunting gear.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Salomon'><u>Salomon:</u></a> Footwear, apparel, and mountain equipment.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Cressi'><u>Cressi:</u></a> Scuba diving equipment.</li>
                                <li><a href='https://www.a-alvarez.com/en/m/Atomic'><u>Atomic:</u></a> Ski and winter sports equipment.</li>
                            </ul>
                            <p>And many more! Browse the website and discover the surprises we have prepared.</p>
                            <hr>
                            <p><b>Frequently Asked Questions about Black Friday at Álvarez (FAQ)</b>

                            We have compiled the most common questions so you can plan your purchases with complete confidence.

                            <b>What is Black Friday 2025?</b>

                            Black Friday is the most important sales and discount day of the year, originating in the United States and celebrated the day after Thanksgiving. It marks the start of the Christmas shopping season. At Álvarez, we celebrate it with promotions covering practically our entire catalogue, offering <b>unique prices</b> on sports equipment, hunting, fishing, outdoor, and much more.

                            <br><b>What inventaries and brands will be on offer?</b>
                            <b>All our main categories and brands will be represented.</b> We are working hard to make Black Friday 2025 at Álvarez <b>unbeatable.</b> To achieve this, we have negotiated with the main manufacturers and distributors of every sport and hobby. Get ready to find discounts on golf, hunting, sport shooting, fishing, equestrian, diving, skiing, mountain gear, and much more, with the guaranteed quality of market-leading brands.

                            <b>How long does it take for inventaries to arrive during the campaign?</b>

                            Our usual commitment is to ship the vast majority of our inventaries within <b>48 hours.</b>

                            However, please note that some delays may occur during Black Friday. The volume of orders we manage is the highest of the year, and both manufacturers (for stock replenishment) and transport agencies may become saturated. We recommend placing your orders as early as possible. We will keep you informed at all times about the status of your shipment.

                            <b>Return: What is the period during Black Friday?</b>

                            We want you to take advantage of Black Friday to do your <b>Christmas shopping</b> with total peace of mind. Therefore, at Álvarez, we are <b>extending the returns period</b> for all orders placed during the campaign, <b>until January 31, 2026!</b>

                            You can buy gifts with confidence, knowing that the recipient will have more than enough time to make an exchange or return if needed.</p>",
                    "de" => "<p>Es ist bereits Black Friday bei „Álvarez, Sport und Freizeit“ und für diesen besonderen Tag haben wir Hunderte von Angeboten in allen unseren Sportarten zusammengestellt, die Ihnen den Mund offen stehen lassen werden.
                            Der Black Friday 2025 ist vollgepackt mit tollen Rabatten auf Hunderte von Produkten: Wählen Sie einfach Ihre Lieblingssportart aus und gehen Sie in die Black Friday-Kategorie für diese Sportart, um alle Artikel mit dem entsprechenden Rabatt zu sehen. Finden Sie die besten Angebote für Jagd, Golf, Skifahren, Reiten, Angeln, Paddle-Tennis, Tauchen...
                            Warten Sie nicht länger und nutzen Sie die einmaligen Preise dieses tollen Tages für Ihre Weihnachtseinkäufe!</p><h2>Beste Rabatte und Angebote am Black Friday für Sport- und Outdoor-Ausrüstung</h2>
                            <p>Der Black Friday bei Álvarez ist gleichbedeutend mit Qualität zum besten Preis: Rabatte und Angebote auf Tausende von Artikeln von <b>weltweit führenden Premium-Marken</b> in ihren jeweiligen Sektoren.
                                Diese Sonderpreise gelten <b>nur während des Álvarez Black Friday</b> – dies macht ihn zum perfekten Zeitpunkt, um Ihre Ausrüstung zu erneuern, sich diesen lang ersehnten Wunsch zu erfüllen oder Ihre Weihnachtseinkäufe vorzuziehen.
                                Sie finden Angebote für Ihre Lieblingsmarken, darunter:
                            </p>
                            <ul>
                                <li><a href='https://www.a-alvarez.com/de/m/Taylormade'><u>Taylormade:</u></a> Hochleistungs-Golfausrüstung.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Chiruca'><u>Chiruca:</u></a> Schuhe und Funktionskleidung für Trekking und Outdoor.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Callaway'><u>Callaway:</u></a> Das Neueste an Golfausrüstung.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Shimano'><u>Shimano:</u></a> Angel- und Fahrradausrüstung.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Hikmicro'><u>Hikmicro:</u></a> Thermo- und Nachtsichtgeräte.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Beretta'><u>Beretta:</u></a> Jagd, Sportschießen und Funktionskleidung.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Aqualung'><u>Aqualung:</u></a> Tauch- und Schnorchelausrüstung.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Swarovski'><u>Swarovski:</u></a> Sport- und Beobachtungsoptik.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Hart'><u>Hart:</u></a> Angel- und Jagdausrüstung.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Salomon'><u>Salomon:</u></a> Sportschuhe, Bekleidung und Bergsportausrüstung.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Cressi'><u>Cressi:</u></a> Tauchausrüstung.</li>
                                <li><a href='https://www.a-alvarez.com/de/m/Atomic'><u>Atomic:</u></a> Ski- und Wintersportausrüstung.</li>
                            </ul>
                            <p>Und viele mehr! Durchstöbern Sie die Website und entdecken Sie die Überraschungen, die wir für Sie bereithalten.</p>
                            <hr>
                            <p><b>Häufig gestellte Fragen zum Black Friday bei Álvarez (FAQ)</b>

                            Wir haben die häufigsten Fragen gesammelt, damit Sie Ihre Einkäufe mit vollem Vertrauen planen können.

                            <b>Was ist der Black Friday 2025?</b>

                            Der Black Friday ist der wichtigste Verkaufs- und Aktionstag des Jahres. Er stammt ursprünglich aus den USA und findet am Tag nach Thanksgiving statt. Er markiert den Beginn der Weihnachtseinkäufe. Bei Álvarez feiern wir ihn mit Aktionen, die praktisch unseren gesamten Katalog umfassen und <b>einzigartige Preise</b> für Sportausrüstung, Jagd, Angeln, Outdoor und vieles mehr bieten.

                            <br><b>Welche Produkte und Marken werden im Angebot sein?</b>
                            <b>Alle unsere Hauptkategorien und Marken werden vertreten sein.</b> Wir arbeiten intensiv daran, den Black Friday 2025 bei Álvarez <b>unvergleichlich</b> zu gestalten. Dafür haben wir mit den führenden Herstellern und Händlern jeder Sportart und jedes Hobbys verhandelt. Freuen Sie sich auf Rabatte auf Golf-, Jagd-, Schießsport-, Angel-, Reit-, Tauch-, Ski- und Bergsportausrüstung und vieles mehr, mit der garantierten Qualität der Marktführer.

                            <b>Wie lange dauert die Lieferung der Produkte während der Aktion?</b>

                            Unser übliches Versprechen ist, die meisten unserer Produkte innerhalb von <b>48 Stunden</b> zu liefern.

                            Beachten Sie jedoch, dass es während des Black Friday zu einigen Verzögerungen kommen kann. Das Bestellvolumen, das wir bearbeiten, ist das höchste des Jahres, und sowohl die Hersteller beim Auffüllen der Lagerbestände als auch die Transportunternehmen können überlastet sein. Wir empfehlen Ihnen, Ihre Bestellungen so früh wie möglich aufzugeben. Wir werden Sie jederzeit über den Status Ihrer Sendung informieren.

                            <b>Rücksendungen: Wie lange ist die Frist während des Black Friday?</b>

                            Wir möchten, dass Sie den Black Friday nutzen, um Ihre <b>Weihnachtseinkäufe</b> in aller Ruhe zu tätigen. Deshalb <b>verlängern wir bei Álvarez die Rückgabefrist</b> für alle während der Aktion getätigten Bestellungen <b>bis zum 31. Januar 2026!</b>

                            Sie können Geschenke mit Vertrauen kaufen, in dem Wissen, dass die beschenkte Person mehr als genug Zeit hat, um einen Umtausch oder eine Rückgabe vorzunehmen, falls dies nötig ist.</p>",
                    "it" => "<p>È già Black Friday da “Alvarez, sport e tempo libero” e per questo giorno speciale abbiamo raccolto centinaia di offerte in tutti i nostri sport che ti lasceranno a bocca aperta.
                            Il Black Friday 2025 arriva carico di grandi sconti su centinaia di prodotti: basta selezionare il tuo sport preferito e accedere alla categoria Black Friday di quello sport per vedere tutti gli articoli con il relativo sconto. Trova le migliori offerte su caccia, golf, sci, equitazione, pesca, paddle tennis, subacquea...
                            Non aspettare oltre e approfitta dei prezzi unici di questo grande giorno per fare i tuoi acquisti natalizi!</p>
                            <h2>I migliori sconti e offerte del Black Friday su attrezzatura sportiva e outdoor</h2><p>Il Black Friday da Álvarez è sinonimo di qualità al miglior prezzo: sconti e offerte su migliaia di articoli di <b>marchi di prima linea a livello mondiale,</b> leader nei rispettivi settori.
                                Questi prezzi speciali saranno visibili <b>solo durante il Black Friday di Álvarez</b>, il che lo rende il momento perfetto per rinnovare la vostra attrezzatura, togliervi quello sfizio o anticipare i vostri acquisti di Natale.
                                Potrete trovare offerte sui vostri marchi preferiti, tra cui:
                            </p>
                            <ul>
                                <li><a href='https://www.a-alvarez.com/it/m/Taylormade'><u>Taylormade:</u></a> Attrezzatura da Golf ad alte prestazioni.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Chiruca'><u>Chiruca:</u></a> Calzature e abbigliamento tecnico per trekking e outdoor.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Callaway'><u>Callaway:</u></a> Il meglio dell'attrezzatura da Golf.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Shimano'><u>Shimano:</u></a> Attrezzature per la Pesca e il Ciclismo.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Hikmicro'><u>Hikmicro:</u></a> Visione termica e notturna.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Beretta'><u>Beretta:</u></a> Caccia, tiro sportivo e abbigliamento tecnico.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Aqualung'><u>Aqualung:</u></a> Attrezzature per l'immersione e lo snorkel.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Swarovski'><u>Swarovski:</u></a> Ottica sportiva e da osservazione.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Hart'><u>Hart:</u></a> Materiale per la Pesca e la Caccia.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Salomon'><u>Salomon:</u></a> Scarpe, abbigliamento e materiale da montagna.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Cressi'><u>Cressi:</u></a> Attrezzature per la subacquea.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Atomic'><u>Atomic:</u></a> Materiale per lo sci e gli sport invernali.</li>
                            </ul>
                            <p>E molti altri! Navigate sul sito e scoprite le sorprese che abbiamo preparato.</p>
                            <hr>
                            <p><b>Domande Frequenti sul Black Friday da Álvarez (FAQ)</b>

                                Abbiamo raccolto le domande più comuni per aiutarvi a pianificare i vostri acquisti con totale fiducia.

                                <b>Cos'è il Black Friday 2025?</b>

                                Il Black Friday è la giornata di saldi e offerte più importante dell'anno, originata negli Stati Uniti e celebrata il giorno dopo il Giorno del Ringraziamento. Segna l'inizio degli acquisti natalizi. Da Álvarez, lo celebriamo con promozioni che coprono praticamente tutto il nostro catalogo, offrendo <b>prezzi unici</b> su attrezzatura sportiva, caccia, pesca, outdoor e molto altro.

                                <br><b>Quali prodotti e marchi saranno in offerta?</b>
                                <b>Ci sarà la rappresentanza di tutte le nostre categorie e marchi principali.</b> Stiamo lavorando intensamente affinché il Black Friday 2025 da Álvarez sia <b>ineguagliabile</b>. A tal fine, abbiamo negoziato con i principali produttori e distributori di ogni sport e hobby. Preparatevi a trovare sconti su attrezzatura da golf, caccia, tiro sportivo, pesca, equitazione, subacquea, sci, montagna e molto altro, con la qualità garantita dei marchi leader del mercato.

                                <b>Quanto tempo impiegano i prodotti ad arrivare durante la campagna?</b>

                                Il nostro impegno abituale è quello di spedire la maggior parte dei nostri prodotti entro <b>48 ore</b>.

                                Tuttavia, tenete presente che durante il Black Friday potrebbero verificarsi alcuni ritardi. Il volume di ordini che gestiamo è il più alto dell'anno e sia i produttori (per il rifornimento delle scorte) sia le agenzie di trasporto possono arrivare alla saturazione. Vi consigliamo di effettuare i vostri ordini con il massimo anticipo possibile. Vi terremo informati in ogni momento sullo stato della vostra spedizione.

                                <b>Resi: Qual è il periodo durante il Black Friday?</b>

                                Vogliamo che approfittiate del Black Friday per effettuare i vostri <b>acquisti di Natale</b> in totale tranquillità. Per questo, da Álvarez <b>ampliamo il periodo di reso</b> per tutti gli ordini effettuati durante la campagna, <b>fino al 31 gennaio 2026!</b>

                                Potete acquistare regali con fiducia, sapendo che la persona che li riceverà avrà un margine più che sufficiente per effettuare un cambio o un reso, se necessario.</p>"
                ],
                "texts_after" => [
                    "es" => "<p>Prepárate para la mayor fiesta de descuentos del año. En Álvarez, el Black Friday 2026 promete ser un evento inigualable con ofertas espectaculares en miles de productos de primeras marcas de golf, caza, pesca, equitación, buceo, esquí, outdoor y mucho más. No pierdas la oportunidad de conseguir ese equipo que tanto deseas al mejor precio.</p>
                        <hr>
                        <h2>¿Cuándo comienza el Black Friday en Álvarez?</h2>
                        <p>Este año estamos preparando algo <strong>verdaderamente especial</strong> y sin precedentes.
                            <b>Nuestro objetivo es ofrecerte los mejores precios de la historia!!</b>

                            Para ser el primero en conocer la fecha exacta y tener acceso prioritario a los descuentos más importantes, te recomendamos:
                        <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                            <li><b>Estar atento a nuestra web</b> y a las notificaciones que publicaremos en los días previos.</li>
                            <li><b>Suscribirte a nuestra newsletter</b> para recibir información exclusiva directamente en tu correo. Puedes hacerlo <a href='https://www.a-alvarez.com/subscribers'><u>PULSANDO AQUÍ</u></a></li>
                        </ul>
                        <p>¡Te aseguramos que la espera merecerá la pena!</p>
                        <h2>Mejores descuentos y ofertas de Black Friday en material deportivo y outdoor</h2>
                        <p>El Black Friday en Álvarez es sinónimo de calidad al mejor precio: descuentos y ofertas en miles de artículos de <b>marcas de primera línea</b> a nivel mundial, líderes en sus respectivos sectores.

                            Estos precios especiales <b>sólo se verán durante el Black Friday de Álvarez</b>, lo que lo convierte en el momento perfecto para renovar tu equipación, darte ese capricho o adelantar tus compras de Navidad.

                            Podrás encontrar ofertas en tus marcas favoritas, incluyendo:
                        </p>
                        <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                            <li><a href='https://www.a-alvarez.com/m/Taylormade'><u>Taylormade:</u></a> Material de Golf de alto rendimiento.</li>
                            <li><a href='https://www.a-alvarez.com/m/Chiruca'><u>Chiruca:</u></a> Calzado y ropa técnica para trekking y outdoor.</li>
                            <li><a href='https://www.a-alvarez.com/m/Callaway'><u>Callaway:</u></a> Lo último en equipamiento de Golf.</li>
                            <li><a href='https://www.a-alvarez.com/m/Shimano'><u>Shimano:</u></a> Equipos de Pesca y Ciclismo.</li>
                            <li><a href='https://www.a-alvarez.com/m/Hikmicro'><u>Hikmicro:</u></a> Visión térmica y nocturna.</li>
                            <li><a href='https://www.a-alvarez.com/m/Beretta'><u>Beretta:</u></a> Caza, tiro deportivo y ropa técnica.</li>
                            <li><a href='https://www.a-alvarez.com/m/Aqualung'><u>Aqualung:</u></a> Equipos de buceo y snorkel.</li>
                            <li><a href='https://www.a-alvarez.com/m/Swarovski'><u>Swarovski:</u></a> Óptica deportiva y de observación.</li>
                            <li><a href='https://www.a-alvarez.com/m/Hart'><u>Hart:</u></a>: Material de Pesca y Caza.</li>
                            <li><a href='https://www.a-alvarez.com/m/Salomon'><u>Salomon:</u></a> Zapatillas, ropa y material de montaña.</li>
                            <li><a href='https://www.a-alvarez.com/m/Cressi'><u>Cressi:</u></a> Equipamiento de submarinismo.</li>
                            <li><a href='https://www.a-alvarez.com/m/Atomic'><u>Atomic:</u></a> Material de esquí y deportes de invierno.</li>
                        </ul>

                        <p>¡Y muchas más! Navega por la web y descubre las sorpresas que tenemos preparadas.</p>
                        <hr>
                        <p><b>5 Trucos para no perderte las ofertas de Black Friday en Álvarez</b>

                            Para garantizar que consigues los mejores productos antes de que se agoten y aprovechas cada euro, te sugerimos seguir estos sencillos pasos:
                        <ol style='color:#FFF'>
                            <li style='display: list-item;'><b>Regístrate ya</b>: Crea una cuenta de cliente en a-alvarez.com con antelación. Ahorrarás tiempo en el proceso de compra, especialmente cuando el tráfico sea alto.</li>
                            <li style='display: list-item;'><b>Suscríbete a la Newsletter</b>: Es la vía principal para recibir las fechas de inicio, los adelantos de ofertas y, en ocasiones, acceso exclusivo ¡PERMANECE ATENTO A TU EMAIL!</li>
                            <li style='display: list-item;'><b>Crea tu 'Lista de Deseos'</b>: Empieza a añadir los productos que te interesan a tu lista. Cuando comiencen las ofertas, solo tendrás que moverlos al carrito.</li>
                            <li style='display: list-item;'><b>Revisa tus datos de envío y pago</b>: Asegúrate de que tus direcciones y métodos de pago habituales están actualizados para que el checkout sea instantáneo.</li>
                            <li style='display: list-item;'><b>Sé madrugador</b>: Los artículos más populares y con los mejores descuentos suelen ser los primeros en agotarse. Conéctate a primera hora del día de inicio para asegurarte tu compra.</li>
                        </ol>
                        <hr>
                        <p><b>Preguntas Frecuentes sobre el Black Friday en Álvarez (FAQ)</b>

                            Hemos recopilado las preguntas más habituales para que planifiques tus compras con total confianza.

                            <b>¿Qué es el Black Friday 2026?</b>

                            El Black Friday es la jornada de rebajas y ofertas más importante del año, originada en Estados Unidos y celebrada el día después de Acción de Gracias. Marca el inicio de las compras navideñas. En Álvarez, lo celebramos con promociones que abarcan prácticamente todo nuestro catálogo, ofreciendo precios únicos en material deportivo, caza, pesca, outdoor y mucho más.

                            <br>¿Qué productos y marcas estarán en oferta?</b>
                            <b>Habrá representación de todas nuestras categorías y marcas</b> principales. Estamos trabajando intensamente para que el <b>Black Friday 2026 en Álvarez sea inigualable</b>. Para ello, hemos negociado con los principales fabricantes y distribuidores de cada deporte y afición. Prepárate para encontrar descuentos en material de golf, caza, tiro deportivo, pesca, equitación, submarinismo, esquí, montaña y mucho más, con la calidad garantizada de las marcas líderes del mercado.

                            <b>¿Cuánto tardan en llegar los productos durante la campaña?</b>

                            Nuestro compromiso habitual es servir la gran mayoría de nuestros productos en un plazo de <b>48 horas</b>.

                            No obstante, ten en cuenta que durante el Black Friday pueden producirse algunos retrasos. El volumen de pedidos que gestionamos es el más alto del año, y tanto los fabricantes para reponer stock como las agencias de transportes pueden llegar a estar saturados. Te recomendamos hacer tus pedidos con la máxima antelación posible. Te mantendremos informado en todo momento sobre el estado de tu envío.

                            <b>Devoluciones: ¿Cuál es el periodo durante el Black Friday?</b>

                            Queremos que aproveches el Black Friday para realizar tus <b>compras de Navidad</b> con total tranquilidad. Por ello, en Álvarez <b>ampliamos el periodo de devoluciones</b> para todos los pedidos realizados durante la campaña, <b>¡hasta el 31 de enero de 2026!</b>

                            Puedes comprar regalos con confianza, sabiendo que la persona que los reciba tendrá margen más que suficiente para realizar un cambio o devolución si lo necesita.</p>",
                    "pt" => "<p>Prepare-se para a maior festa de descontos do ano. Na Álvarez, a Black Friday 2026 promete ser um evento inigualável com <b>ofertas espetaculares</b> em milhares de produtos das principais marcas de golfe, caça, pesca, equitação, mergulho, esqui, outdoor e muito mais. Não perca a oportunidade de adquirir o equipamento que tanto deseja ao <b>melhor preço.</b></p>
                        <hr>
                        <h2>Quando começa a Black Friday na Álvarez?</h2>
                        <p>Este ano, estamos a preparar algo <strong>verdadeiramente especial e sem precedentes.</strong>
                            O nosso objetivo é oferecer-lhe os <b>melhores preços de sempre!</b>

                            Para ser o primeiro a saber a data exata e ter acesso prioritário aos descontos mais importantes, recomendamos-lhe:
                        <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                            <li><b>Estar atento ao nosso site</b> e às notificações que publicaremos nos dias anteriores.</li>
                            <li><b>Subscrever a nossa newsletter</b> para receber informação exclusiva diretamente no seu e-mail. Pode fazê-lo <a href='https://www.a-alvarez.com/pt/subscribers'><u>CLICANDO AQUI</u></a></li>
                        </ul>
                        <p>Garantimos-lhe que a espera valerá a pena!</p>
                        <h2>Melhores descontos e ofertas de Black Friday em material desportivo e outdoor</h2>
                        <p>A Black Friday na Álvarez é sinónimo de qualidade ao melhor preço: descontos e ofertas em milhares de artigos de <b>marcas de primeira linha a nível mundial,</b> líderes nos respetivos setores.

                            Estes preços especiais <b>só serão vistos durante a Black Friday da Álvarez</b>, o que a torna o momento perfeito para renovar o seu equipamento, dar-se esse capricho ou antecipar as suas compras de Natal.

                            Poderá encontrar ofertas nas suas marcas favoritas, incluindo:
                        </p>
                        <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                            <li><a href='https://www.a-alvarez.com/pt/m/Taylormade'><u>Taylormade:</u></a> Material de Golfe de alto desempenho.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Chiruca'><u>Chiruca:</u></a> Calçado e vestuário técnico para trekking e outdoor.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Callaway'><u>Callaway:</u></a> O mais recente em equipamento de Golfe.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Shimano'><u>Shimano:</u></a> Equipamentos de Pesca e Ciclismo.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Hikmicro'><u>Hikmicro:</u></a> Visão térmica e noturna.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Beretta'><u>Beretta:</u></a> Caça, tiro desportivo e vestuário técnico.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Aqualung'><u>Aqualung:</u></a> Equipamentos de mergulho e snorkel.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Swarovski'><u>Swarovski:</u></a> Ótica desportiva e de observação.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Hart'><u>Hart:</u></a> Material de Pesca e Caça.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Salomon'><u>Salomon:</u></a> Sapatilhas, vestuário e material de montanha.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Cressi'><u>Cressi:</u></a> Equipamento de submarinismo.</li>
                            <li><a href='https://www.a-alvarez.com/pt/m/Atomic'><u>Atomic:</u></a> Material de esqui e desportos de inverno.</li>
                        </ul>

                        <p>E muitas mais! Navegue no site e descubra as surpresas que temos preparadas.</p>
                        <hr>
                        <p><b>5 Dicas para não perder as ofertas de Black Friday na Álvarez</b>

                            Para garantir que consegue os melhores produtos antes que esgotem e que aproveita cada euro, sugerimos que siga estes passos simples:
                        <ol style='color:#FFF'>
                            <li style='display: list-item;'><b>Registe-se já:</b> Crie uma conta de cliente em a-alvarez.com com antecedência. Poupará tempo no processo de compra, especialmente quando o tráfego for elevado.</li>
                            <li style='display: list-item;'><b>Subscreva a Newsletter:</b> É a principal forma de receber as datas de início, os avanços das ofertas e, ocasionalmente, acesso exclusivo. <b>FIQUE ATENTO AO SEU E-MAIL!</b></li>
                            <li style='display: list-item;'><b>Crie a sua 'Lista de Desejos':</b> Comece a adicionar os produtos que lhe interessam à sua lista. Quando as ofertas começarem, só terá de os mover para o carrinho.</li>
                            <li style='display: list-item;'><b>Verifique os seus dados de envio e pagamento:</b> Certifique-se de que os seus endereços e métodos de pagamento habituais estão atualizados para que o checkout seja instantâneo.</li>
                            <li style='display: list-item;'><b>Seja madrugador/a:</b> Os artigos mais populares e com os melhores descontos tendem a ser os primeiros a esgotar. Ligue-se logo pela manhã do dia de início para garantir a sua compra.</li>
                        </ol>
                        <hr>
                        <p><b>Perguntas Frequentes sobre a Black Friday na Álvarez (FAQ)</b>

                            Reunimos as perguntas mais habituais para que planeie as suas compras com total confiança.

                            <b>O que é a Black Friday 2026?</b>

                            A Black Friday é a jornada de saldos e ofertas mais importante do ano, originada nos Estados Unidos e celebrada no dia a seguir ao Dia de Ação de Graças. Marca o início das compras de Natal. Na Álvarez, celebramo-la com promoções que abrangem praticamente todo o nosso catálogo, oferecendo <b>preços únicos</b> em material desportivo, caça, pesca, outdoor e muito mais.

                            <br><b>Que produtos e marcas estarão em oferta?</b>
                            Haverá <b>representação de todas as nossas categorias e marcas principais.</b> Estamos a trabalhar intensamente para que a Black Friday 2026 na Álvarez seja <b>inigualável.</b> Para tal, negociámos com os principais fabricantes e distribuidores de cada desporto e hobby. Prepare-se para encontrar descontos em material de golfe, caça, tiro desportivo, pesca, equitação, submarinismo, esqui, montanha e muito mais, com a qualidade garantida das marcas líderes de mercado.

                            <b>Quanto tempo demoram os produtos a chegar durante a campanha?</b>

                            O nosso compromisso habitual é servir a grande maioria dos nossos produtos num prazo de <b>48 horas</b>.

                            No entanto, tenha em conta que durante a Black Friday poderão ocorrer alguns atrasos. O volume de pedidos que gerimos é o mais alto do ano, e tanto os fabricantes para repor stock como as transportadoras podem ficar sobrecarregados. Recomendamos que faça os seus pedidos com a máxima antecedência possível. Iremos mantê-lo(a) informado(a) em todos os momentos sobre o estado da sua encomenda.

                            <b>Devoluções: Qual é o período durante a Black Friday?</b>

                            Queremos que aproveite a Black Friday para realizar as suas <b>compras de Natal</b> com total tranquilidade. Por isso, na Álvarez <b>alargamos o período de devoluções</b> para todos os pedidos feitos durante a campanha, <b>até 31 de janeiro de 2026!</b>

                            Pode comprar presentes com confiança, sabendo que a pessoa que os receber terá margem mais do que suficiente para realizar uma troca ou devolução, se necessário.</p>",
                    "fr" => "<p>Préparez-vous pour la plus grande fête des réductions de l'année. Chez Álvarez, le Black Friday 2026 promet d'être un événement inégalable avec des <b>offres spectaculaires</b> sur des milliers de produits des plus grandes marques de golf, chasse, pêche, équitation, plongée, ski, outdoor et bien plus encore. Ne manquez pas l'opportunité d'acquérir l'équipement tant désiré au <b>meilleur prix.</b></p>
                    <hr>
                    <h2>Quand commence le Black Friday chez Álvarez ?</h2>
                    <p>Nous savons que l'attente est grande, et nous vous assurons que cette année, nous préparons quelque chose de <strong>vraiment spécial et sans précédent.</strong>
                        Notre objectif est de vous offrir les <b>meilleurs prix de notre histoire !</b>

                        Pour être le premier à connaître la date exacte et bénéficier d'un accès prioritaire aux réductions les plus importantes, nous vous recommandons :
                    <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                        <li><b>De rester attentif à notre site web</b> et aux notifications que nous publierons dans les jours précédant l'événement.</li>
                        <li><b>De vous inscrire à notre newsletter</b> pour recevoir des informations exclusives directement dans votre boîte mail. Vous pouvez le faire <a href='https://www.a-alvarez.com/fr/subscribers'><u>EN CLIQUANT ICI</u></a></li>
                    </ul>
                    <p>Nous vous assurons que l'attente en vaudra la peine !</p>
                    <h2>Meilleures réductions et offres du Black Friday sur les équipements de sport et d'outdoor</h2>
                    <p>Le Black Friday chez Álvarez est synonyme de qualité au meilleur prix : des réductions et des offres sur des milliers d'articles de <b>marques de premier plan au niveau mondial,</b> leaders dans leurs secteurs respectifs.

                        Ces prix spéciaux ne seront vus <b>que pendant le Black Friday d'Álvarez</b>, ce qui en fait le moment idéal pour renouveler votre équipement, vous faire plaisir ou anticiper vos achats de Noël.

                        Vous trouverez des offres sur vos marques préférées, notamment :
                    </p>
                    <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                        <li><a href='https://www.a-alvarez.com/fr/m/Taylormade'><u>Taylormade :</u></a> Matériel de Golf haute performance.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Chiruca'><u>Chiruca :</u></a> Chaussures et vêtements techniques de trekking et outdoor.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Callaway'><u>Callaway :</u></a> Le nec plus ultra en équipement de Golf.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Shimano'><u>Shimano :</u></a> Équipements de Pêche et Cyclisme.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Hikmicro'><u>Hikmicro :</u></a> Vision thermique et nocturne.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Beretta'><u>Beretta :</u></a> Chasse, tir sportif et vêtements techniques.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Aqualung'><u>Aqualung :</u></a> Équipements de plongée et snorkeling.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Swarovski'><u>Swarovski :</u></a> Optiques sportives et d'observation.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Hart'><u>Hart :</u></a> Matériel de Pêche et Chasse.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Salomon'><u>Salomon :</u></a> Chaussures, vêtements et matériel de montagne.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Cressi'><u>Cressi :</u></a> Équipement de plongée sous-marine.</li>
                        <li><a href='https://www.a-alvarez.com/fr/m/Atomic'><u>Atomic :</u></a> Matériel de ski et sports d'hiver.</li>
                    </ul>

                    <p>Et bien d'autres encore ! Naviguez sur le site et découvrez les surprises que nous avons préparées.</p>
                    <hr>
                    <p><b>5 Astuces pour ne pas manquer les offres du Black Friday chez Álvarez</b>

                        Pour vous assurer d'obtenir les meilleurs produits avant qu'ils ne soient épuisés et de profiter au maximum de chaque euro, nous vous suggérons de suivre ces étapes simples :
                    <ol style='color:#FFF'>
                        <li style='display: list-item;'><b>Inscrivez-vous dès maintenant</b> : Créez un compte client sur a-alvarez.com à l'avance. Vous gagnerez du temps lors du processus d'achat, surtout lorsque le trafic est élevé.</li>
                        <li style='display: list-item;'><b>Abonnez-vous à la Newsletter</b> : C'est le principal moyen de recevoir les dates de début, les aperçus des offres et, parfois, un accès exclusif. <b>RESTEZ À L'AFFÛT DE VOS E-MAILS !</b></li>
                        <li style='display: list-item;'><b>Créez votre 'Liste de Souhaits'</b> : Commencez à ajouter les produits qui vous intéressent à votre liste. Lorsque les offres commenceront, vous n'aurez plus qu'à les déplacer vers le panier.</li>
                        <li style='display: list-item;'><b>Vérifiez vos données d'expédition et de paiement</b> : Assurez-vous que vos adresses et méthodes de paiement habituelles sont à jour pour que le checkout (passage en caisse) soit instantané.</li>
                        <li style='display: list-item;'><b>Soyez matinal(e)</b> : Les articles les plus populaires et les meilleurs rabais sont souvent les premiers à s'épuiser. Connectez-vous dès la première heure du jour de lancement pour garantir votre achat.</li>
                    </ol>
                    <hr>
                    <p><b>Questions Fréquemment Posées sur le Black Friday chez Álvarez (FAQ)</b>

                        Nous avons rassemblé les questions les plus courantes pour que vous planifiez vos achats en toute confiance.

                        <b>Qu'est-ce que le Black Friday 2026 ?</b>

                        Le Black Friday est le jour de soldes et d'offres le plus important de l'année, originaire des États-Unis et célébré le lendemain de Thanksgiving. Il marque le début des achats de Noël. Chez Álvarez, nous le célébrons avec des promotions qui couvrent pratiquement tout notre catalogue, offrant des <b>prix uniques</b> sur le matériel sportif, la chasse, la pêche, l'outdoor et bien plus encore.

                        <br><b>Quels produits et marques seront en promotion ?</b>
                        <b>Toutes nos catégories et marques principales seront représentées.</b> Nous travaillons intensément pour que le Black Friday 2026 chez Álvarez soit <b>inégalable</b>. Pour ce faire, nous avons négocié avec les principaux fabricants et distributeurs de chaque sport et loisir. Préparez-vous à trouver des réductions sur le matériel de golf, chasse, tir sportif, pêche, équitation, plongée sous-marine, ski, montagne et bien plus encore, avec la qualité garantie des marques leaders du marché.

                        <b>Quel est le délai de livraison des produits pendant la campagne ?</b>

                        Notre engagement habituel est de livrer la grande majorité de nos produits dans un délai de <b>48 heures</b>.

                        Cependant, veuillez noter que des retards peuvent survenir pendant le Black Friday. Le volume de commandes que nous traitons est le plus élevé de l'année, et tant les fabricants pour le réapprovisionnement des stocks que les agences de transport peuvent être saturés. Nous vous recommandons de passer vos commandes le plus tôt possible. Nous vous tiendrons informé(e) en tout temps de l'état de votre envoi.

                        <b>Retours : Quelle est la période pendant le Black Friday ?</b>

                        Nous souhaitons que vous profitiez du Black Friday pour effectuer vos <b>achats de Noël</b> en toute sérénité. C'est pourquoi, chez Álvarez, nous <b>prolongeons la période de retours</b> pour toutes les commandes passées pendant la campagne, <b>jusqu'au 31 janvier 2026 !</b>

                        Vous pouvez acheter des cadeaux en toute confiance, sachant que la personne qui les recevra aura une marge plus que suffisante pour effectuer un échange ou un retour si nécessaire.</p>",
                    "en" => "<p>Get ready for the biggest discount event of the year. At Álvarez, Black Friday 2026 promises to be an unparalleled event with <b>spectacular offers</b> on thousands of inventaries from top brands in golf, hunting, fishing, equestrian, diving, skiing, outdoor, and much more. Don't miss the opportunity to get that gear you've been wanting at the <b>best price.</b></p>
                        <hr>
                        <h2>When does Black Friday start at Álvarez?</h2>
                        <p>We know the anticipation is high, and we assure you that this year we are preparing something <strong>truly special and unprecedented.</strong>
                            Our goal is to offer you the <b>best prices in history!</b>

                            To be the first to know the exact date and get priority access to the most important discounts, we recommend:
                        <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                            <li><b>Keeping an eye on our website</b> and the notifications we will publish in the days leading up to the event.</li>
                            <li><b>Subscribing to our newsletter</b> to receive exclusive information directly in your email. You can do so by <a href='https://www.a-alvarez.com/en/subscribers'><u>CLICKING HERE</u></a></li>
                        </ul>
                        <p>We guarantee that the wait will be worth it!</p>
                        <h2>Best Black Friday discounts and offers on sports and outdoor equipment</h2>
                        <p>Black Friday at Álvarez is synonymous with quality at the best price: discounts and offers on thousands of items from <b>world-class, top-tier brands</b>, leaders in their respective sectors.

                            These special prices will <b>only be seen during the Álvarez Black Friday</b>, making it the perfect time to renew your gear, treat yourself, or get a head start on your Christmas shopping.

                            You will find offers on your favourite brands, including:
                        </p>
                        <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                            <li><a href='https://www.a-alvarez.com/en/m/Taylormade'><u>Taylormade:</u></a> High-performance Golf equipment.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Chiruca'><u>Chiruca:</u></a> Technical footwear and clothing for trekking and outdoor.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Callaway'><u>Callaway:</u></a> The latest in Golf equipment.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Shimano'><u>Shimano:</u></a> Fishing and Cycling gear.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Hikmicro'><u>Hikmicro:</u></a> Thermal and night vision.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Beretta'><u>Beretta:</u></a> Hunting, sport shooting, and technical clothing.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Aqualung'><u>Aqualung:</u></a> Diving and snorkeling equipment.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Swarovski'><u>Swarovski:</u></a> Sports and observation optics.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Hart'><u>Hart:</u></a> Fishing and Hunting gear.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Salomon'><u>Salomon:</u></a> Footwear, apparel, and mountain equipment.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Cressi'><u>Cressi:</u></a> Scuba diving equipment.</li>
                            <li><a href='https://www.a-alvarez.com/en/m/Atomic'><u>Atomic:</u></a> Ski and winter sports equipment.</li>
                        </ul>

                        <p>And many more! Browse the website and discover the surprises we have prepared.</p>
                        <hr>
                        <p><b>5 Tips to make sure you don't miss the Black Friday offers at Álvarez</b>

                            To guarantee you get the best inventaries before they sell out and make the most of every euro, we suggest following these simple steps:
                        <ol style='color:#FFF'>
                            <li style='display: list-item;'><b>Register Now</b>: Create a customer account on a-alvarez.com in advance. You'll save time during the purchasing process, especially when traffic is high.</li>
                            <li style='display: list-item;'><b>Subscribe to the Newsletter</b>: This is the main channel to receive start dates, offer previews, and, occasionally, exclusive access. <b>PAY ATTENTION TO YOUR EMAIL!</b></li>
                            <li style='display: list-item;'><b>Create your 'Wish List'</b>: Start adding the inventaries you are interested in to your list. When the offers begin, you just have to move them to the cart.</li>
                            <li style='display: list-item;'><b>Review your shipping and payment details</b>: Make sure your addresses and usual payment methods are updated so that checkout is instant.</li>
                            <li style='display: list-item;'><b>Be an Early Bird</b>: The most popular items with the best discounts are usually the first to sell out. Log in early on the start day to secure your purchase.</li>
                        </ol>
                        <hr>
                        <p><b>Frequently Asked Questions about Black Friday at Álvarez (FAQ)</b>

                            We have compiled the most common questions so you can plan your purchases with complete confidence.

                            <b>What is Black Friday 2026?</b>

                            Black Friday is the most important sales and discount day of the year, originating in the United States and celebrated the day after Thanksgiving. It marks the start of the Christmas shopping season. At Álvarez, we celebrate it with promotions covering practically our entire catalogue, offering <b>unique prices</b> on sports equipment, hunting, fishing, outdoor, and much more.

                            <br><b>What inventaries and brands will be on offer?</b>
                            <b>All our main categories and brands will be represented.</b> We are working hard to make Black Friday 2026 at Álvarez <b>unbeatable.</b> To achieve this, we have negotiated with the main manufacturers and distributors of every sport and hobby. Get ready to find discounts on golf, hunting, sport shooting, fishing, equestrian, diving, skiing, mountain gear, and much more, with the guaranteed quality of market-leading brands.

                            <b>How long does it take for inventaries to arrive during the campaign?</b>

                            Our usual commitment is to ship the vast majority of our inventaries within <b>48 hours.</b>

                            However, please note that some delays may occur during Black Friday. The volume of orders we manage is the highest of the year, and both manufacturers (for stock replenishment) and transport agencies may become saturated. We recommend placing your orders as early as possible. We will keep you informed at all times about the status of your shipment.

                            <b>Return: What is the period during Black Friday?</b>

                            We want you to take advantage of Black Friday to do your <b>Christmas shopping</b> with total peace of mind. Therefore, at Álvarez, we are <b>extending the returns period</b> for all orders placed during the campaign, <b>until January 31, 2026!</b>

                            You can buy gifts with confidence, knowing that the recipient will have more than enough time to make an exchange or return if needed.</p>",
                    "de" => "<p>Machen Sie sich bereit für das größte Rabatt-Event des Jahres. Bei Álvarez verspricht der Black Friday 2026 ein unvergleichliches Ereignis zu werden, mit <b>spektakulären Angeboten</b> auf Tausende von Produkten der Top-Marken aus den Bereichen Golf, Jagd, Angeln, Reiten, Tauchen, Ski, Outdoor und vielem mehr. Verpassen Sie nicht die Gelegenheit, die gewünschte Ausrüstung zum <b>besten Preis</b> zu erhalten.</p>
                        <hr>
                        <h2>Wann beginnt der Black Friday bei Álvarez?</h2>
                        <p>Wir wissen, dass die Vorfreude groß ist, und wir versichern Ihnen, dass wir dieses Jahr etwas <strong>wirklich Besonderes und noch nie Dagewesenes vorbereiten.</strong>
                            Unser Ziel ist es, Ihnen die <b>besten Preise aller Zeiten anzubieten!</b>

                            Um als Erster das genaue Datum zu erfahren und <b>priorisierten Zugang</b> zu den wichtigsten Rabatten zu erhalten, empfehlen wir Ihnen:
                        <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                            <li><b>Achten Sie auf unsere Website</b> und die Benachrichtigungen, die wir in den Tagen zuvor veröffentlichen werden.</li>
                            <li><b>Abonnieren Sie unseren Newsletter</b>, um exklusive Informationen direkt in Ihr Postfach zu erhalten. Sie können dies <a href='https://www.a-alvarez.com/de/subscribers'><u>HIER KLICKEN</u></a></li>
                        </ul>
                        <p>Wir versichern Ihnen, dass sich das Warten lohnen wird!</p>
                        <h2>Beste Rabatte und Angebote am Black Friday für Sport- und Outdoor-Ausrüstung</h2>
                        <p>Der Black Friday bei Álvarez ist gleichbedeutend mit Qualität zum besten Preis: Rabatte und Angebote auf Tausende von Artikeln von <b>weltweit führenden Premium-Marken</b> in ihren jeweiligen Sektoren.

                            Diese Sonderpreise gelten <b>nur während des Álvarez Black Friday</b> – dies macht ihn zum perfekten Zeitpunkt, um Ihre Ausrüstung zu erneuern, sich diesen lang ersehnten Wunsch zu erfüllen oder Ihre Weihnachtseinkäufe vorzuziehen.

                            Sie finden Angebote für Ihre Lieblingsmarken, darunter:
                        </p>
                        <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                            <li><a href='https://www.a-alvarez.com/de/m/Taylormade'><u>Taylormade:</u></a> Hochleistungs-Golfausrüstung.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Chiruca'><u>Chiruca:</u></a> Schuhe und Funktionskleidung für Trekking und Outdoor.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Callaway'><u>Callaway:</u></a> Das Neueste an Golfausrüstung.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Shimano'><u>Shimano:</u></a> Angel- und Fahrradausrüstung.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Hikmicro'><u>Hikmicro:</u></a> Thermo- und Nachtsichtgeräte.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Beretta'><u>Beretta:</u></a> Jagd, Sportschießen und Funktionskleidung.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Aqualung'><u>Aqualung:</u></a> Tauch- und Schnorchelausrüstung.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Swarovski'><u>Swarovski:</u></a> Sport- und Beobachtungsoptik.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Hart'><u>Hart:</u></a> Angel- und Jagdausrüstung.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Salomon'><u>Salomon:</u></a> Sportschuhe, Bekleidung und Bergsportausrüstung.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Cressi'><u>Cressi:</u></a> Tauchausrüstung.</li>
                            <li><a href='https://www.a-alvarez.com/de/m/Atomic'><u>Atomic:</u></a> Ski- und Wintersportausrüstung.</li>
                        </ul>

                        <p>Und viele mehr! Durchstöbern Sie die Website und entdecken Sie die Überraschungen, die wir für Sie bereithalten.</p>
                        <hr>
                        <p><b>5 Tipps, um die Black Friday-Angebote bei Álvarez nicht zu verpassen</b>

                            Um sicherzustellen, dass Sie die besten Produkte ergattern, bevor sie ausverkauft sind, und jeden Euro optimal nutzen, empfehlen wir Ihnen, diese einfachen Schritte zu befolgen:
                        <ol style='color:#FFF'>
                            <li style='display: list-item;'><b>Jetzt registrieren</b>: Erstellen Sie im Voraus ein Kundenkonto auf a-alvarez.com. Dies spart Zeit beim Kaufvorgang, insbesondere wenn der Traffic hoch ist.</li>
                            <li style='display: list-item;'><b>Den Newsletter abonnieren</b>: Dies ist der wichtigste Weg, um Startdaten, Angebotsvorschauen und manchmal auch exklusiven Zugang zu erhalten. <b>ACHTEN SIE AUF IHRE E-MAILS!</b></li>
                            <li style='display: list-item;'><b>Erstellen Sie Ihre 'Wunschliste'</b>: Beginnen Sie, die Produkte, die Sie interessieren, Ihrer Liste hinzuzufügen. Sobald die Angebote starten, müssen Sie sie nur noch in den Warenkorb verschieben.</li>
                            <li style='display: list-item;'><b>Überprüfen Sie Ihre Liefer- und Zahlungsdaten</b>: Stellen Sie sicher, dass Ihre Adressen und üblichen Zahlungsmethoden aktuell sind, damit der Checkout (Kaufabschluss) sofort erfolgen kann.</li>
                            <li style='display: list-item;'><b>Seien Sie frühzeitig dabei</b>: Die beliebtesten Artikel mit den besten Rabatten sind oft zuerst ausverkauft. Loggen Sie sich gleich zu Beginn des Aktionstages ein, um Ihren Kauf zu sichern.</li>
                        </ol>
                        <hr>
                        <p><b>Häufig gestellte Fragen zum Black Friday bei Álvarez (FAQ)</b>

                            Wir haben die häufigsten Fragen gesammelt, damit Sie Ihre Einkäufe mit vollem Vertrauen planen können.

                            <b>Was ist der Black Friday 2026?</b>

                            Der Black Friday ist der wichtigste Verkaufs- und Aktionstag des Jahres. Er stammt ursprünglich aus den USA und findet am Tag nach Thanksgiving statt. Er markiert den Beginn der Weihnachtseinkäufe. Bei Álvarez feiern wir ihn mit Aktionen, die praktisch unseren gesamten Katalog umfassen und <b>einzigartige Preise</b> für Sportausrüstung, Jagd, Angeln, Outdoor und vieles mehr bieten.

                            <br><b>Welche Produkte und Marken werden im Angebot sein?</b>
                            <b>Alle unsere Hauptkategorien und Marken werden vertreten sein.</b> Wir arbeiten intensiv daran, den Black Friday 2026 bei Álvarez <b>unvergleichlich</b> zu gestalten. Dafür haben wir mit den führenden Herstellern und Händlern jeder Sportart und jedes Hobbys verhandelt. Freuen Sie sich auf Rabatte auf Golf-, Jagd-, Schießsport-, Angel-, Reit-, Tauch-, Ski- und Bergsportausrüstung und vieles mehr, mit der garantierten Qualität der Marktführer.

                            <b>Wie lange dauert die Lieferung der Produkte während der Aktion?</b>

                            Unser übliches Versprechen ist, die meisten unserer Produkte innerhalb von <b>48 Stunden</b> zu liefern.

                            Beachten Sie jedoch, dass es während des Black Friday zu einigen Verzögerungen kommen kann. Das Bestellvolumen, das wir bearbeiten, ist das höchste des Jahres, und sowohl die Hersteller beim Auffüllen der Lagerbestände als auch die Transportunternehmen können überlastet sein. Wir empfehlen Ihnen, Ihre Bestellungen so früh wie möglich aufzugeben. Wir werden Sie jederzeit über den Status Ihrer Sendung informieren.

                            <b>Rücksendungen: Wie lange ist die Frist während des Black Friday?</b>

                            Wir möchten, dass Sie den Black Friday nutzen, um Ihre <b>Weihnachtseinkäufe</b> in aller Ruhe zu tätigen. Deshalb <b>verlängern wir bei Álvarez die Rückgabefrist</b> für alle während der Aktion getätigten Bestellungen <b>bis zum 31. Januar 2026!</b>

                            Sie können Geschenke mit Vertrauen kaufen, in dem Wissen, dass die beschenkte Person mehr als genug Zeit hat, um einen Umtausch oder eine Rückgabe vorzunehmen, falls dies nötig ist.</p>",
                    "it" => "<p>Preparatevi per la più grande festa di sconti dell'anno. Presso Álvarez, il Black Friday 2026 promette di essere un evento ineguagliabile con <b>offerte spettacolari</b> su migliaia di prodotti dei principali marchi di golf, caccia, pesca, equitazione, subacquea, sci, outdoor e molto altro ancora. Non perdete l'opportunità di acquistare l'attrezzatura che tanto desiderate al <b>miglior prezzo.</b></p>
                            <hr>
                            <h2>Quando inizia il Black Friday da Álvarez?</h2>
                            <p>Sappiamo che l'attesa è alta e vi assicuriamo che quest'anno stiamo preparando qualcosa di <strong>veramente speciale</strong> e senza precedenti.
                                Il nostro obiettivo è offrirvi i <b>migliori prezzi della storia!</b>

                                Per essere i primi a conoscere la data esatta e avere accesso prioritario agli sconti più importanti, vi consigliamo di:
                            <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                                <li><b>Tenere d'occhio il nostro sito web</b> e le notifiche che pubblicheremo nei giorni precedenti.</li>
                                <li><b>Iscrivervi alla nostra newsletter</b> per ricevere informazioni esclusive direttamente nella vostra casella di posta. Potete farlo <a href='https://www.a-alvarez.com/it/subscribers'><u>CLICCANDO QUI</u></a></li>
                            </ul>
                            <p>Vi assicuriamo che l'attesa varrà la pena!</p>
                            <h2>I migliori sconti e offerte del Black Friday su attrezzatura sportiva e outdoor</h2>
                            <p>Il Black Friday da Álvarez è sinonimo di qualità al miglior prezzo: sconti e offerte su migliaia di articoli di <b>marchi di prima linea a livello mondiale,</b> leader nei rispettivi settori.

                                Questi prezzi speciali saranno visibili <b>solo durante il Black Friday di Álvarez</b>, il che lo rende il momento perfetto per rinnovare la vostra attrezzatura, togliervi quello sfizio o anticipare i vostri acquisti di Natale.

                                Potrete trovare offerte sui vostri marchi preferiti, tra cui:
                            </p>
                            <ul style='margin: 0;padding-left: 40px;display: flow-root;color:#FFF'>
                                <li><a href='https://www.a-alvarez.com/it/m/Taylormade'><u>Taylormade:</u></a> Attrezzatura da Golf ad alte prestazioni.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Chiruca'><u>Chiruca:</u></a> Calzature e abbigliamento tecnico per trekking e outdoor.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Callaway'><u>Callaway:</u></a> Il meglio dell'attrezzatura da Golf.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Shimano'><u>Shimano:</u></a> Attrezzature per la Pesca e il Ciclismo.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Hikmicro'><u>Hikmicro:</u></a> Visione termica e notturna.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Beretta'><u>Beretta:</u></a> Caccia, tiro sportivo e abbigliamento tecnico.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Aqualung'><u>Aqualung:</u></a> Attrezzature per l'immersione e lo snorkel.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Swarovski'><u>Swarovski:</u></a> Ottica sportiva e da osservazione.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Hart'><u>Hart:</u></a> Materiale per la Pesca e la Caccia.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Salomon'><u>Salomon:</u></a> Scarpe, abbigliamento e materiale da montagna.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Cressi'><u>Cressi:</u></a> Attrezzature per la subacquea.</li>
                                <li><a href='https://www.a-alvarez.com/it/m/Atomic'><u>Atomic:</u></a> Materiale per lo sci e gli sport invernali.</li>
                            </ul>

                            <p>E molti altri! Navigate sul sito e scoprite le sorprese che abbiamo preparato.</p>
                            <hr>
                            <p><b>5 Trucchi per non perdere le offerte del Black Friday da Álvarez</b>

                                Per garantirvi di acquistare i migliori prodotti prima che si esauriscano e di approfittare al meglio di ogni euro, vi suggeriamo di seguire questi semplici passaggi:
                            <ol style='color:#FFF'>
                                <li style='display: list-item;'><b>Registratevi subito</b>: Create un account cliente su a-alvarez.com in anticipo. Risparmierete tempo nel processo di acquisto, specialmente quando il traffico è elevato.</li>
                                <li style='display: list-item;'><b>Iscrivetevi alla Newsletter</b>: È il canale principale per ricevere le date di inizio, le anteprime delle offerte e, in alcune occasioni, l'accesso esclusivo. <b>RESTATE ATTENTI ALLA VOSTRA E-MAIL!</b></li>
                                <li style='display: list-item;'><b>Create la vostra 'Lista dei Desideri'</b>: Iniziate ad aggiungere i prodotti che vi interessano alla vostra lista. Quando inizieranno le offerte, dovrete solo spostarli nel carrello.</li>
                                <li style='display: list-item;'><b>Controllate i vostri dati di spedizione e pagamento</b>: Assicuratevi che i vostri indirizzi e metodi di pagamento abituali siano aggiornati, in modo che il checkout (il completamento dell'ordine) sia istantaneo.</li>
                                <li style='display: list-item;'><b>Siate mattinieri/e</b>: Gli articoli più popolari e con i migliori sconti sono spesso i primi a esaurirsi. Connettetevi nelle prime ore del giorno di inizio per assicurarvi il vostro acquisto.</li>
                            </ol>
                            <hr>
                            <p><b>Domande Frequenti sul Black Friday da Álvarez (FAQ)</b>

                                Abbiamo raccolto le domande più comuni per aiutarvi a pianificare i vostri acquisti con totale fiducia.

                                <b>Cos'è il Black Friday 2026?</b>

                                Il Black Friday è la giornata di saldi e offerte più importante dell'anno, originata negli Stati Uniti e celebrata il giorno dopo il Giorno del Ringraziamento. Segna l'inizio degli acquisti natalizi. Da Álvarez, lo celebriamo con promozioni che coprono praticamente tutto il nostro catalogo, offrendo <b>prezzi unici</b> su attrezzatura sportiva, caccia, pesca, outdoor e molto altro.

                                <br><b>Quali prodotti e marchi saranno in offerta?</b>
                                <b>Ci sarà la rappresentanza di tutte le nostre categorie e marchi principali.</b> Stiamo lavorando intensamente affinché il Black Friday 2026 da Álvarez sia <b>ineguagliabile</b>. A tal fine, abbiamo negoziato con i principali produttori e distributori di ogni sport e hobby. Preparatevi a trovare sconti su attrezzatura da golf, caccia, tiro sportivo, pesca, equitazione, subacquea, sci, montagna e molto altro, con la qualità garantita dei marchi leader del mercato.

                                <b>Quanto tempo impiegano i prodotti ad arrivare durante la campagna?</b>

                                Il nostro impegno abituale è quello di spedire la maggior parte dei nostri prodotti entro <b>48 ore</b>.

                                Tuttavia, tenete presente che durante il Black Friday potrebbero verificarsi alcuni ritardi. Il volume di ordini che gestiamo è il più alto dell'anno e sia i produttori (per il rifornimento delle scorte) sia le agenzie di trasporto possono arrivare alla saturazione. Vi consigliamo di effettuare i vostri ordini con il massimo anticipo possibile. Vi terremo informati in ogni momento sullo stato della vostra spedizione.

                                <b>Resi: Qual è il periodo durante il Black Friday?</b>

                                Vogliamo che approfittiate del Black Friday per effettuare i vostri <b>acquisti di Natale</b> in totale tranquillità. Per questo, da Álvarez <b>ampliamo il periodo di reso</b> per tutti gli ordini effettuati durante la campagna, <b>fino al 31 gennaio 2026!</b>

                                Potete acquistare regali con fiducia, sapendo che la persona che li riceverà avrà un margine più che sufficiente per effettuare un cambio o un reso, se necessario.</p>",

                ],
                "titles" => [
                    "es" => "OFERTAS BLACKFRIDAY",
                    "pt" => "OFERTAS BLACKFRIDAY",
                    "fr" => "OFFRES BLACKFRIDAY",
                    "en" => "BLACKFRIDAY OFFER",
                    "de" => "BLACKFRIDAY ANGEBOT",
                    "it" => "OFFERTE BLACKFRIDAY",
                ],
                "h1" => [
                    "es" => "Black Friday Álvarez: ¡Los Mejores Descuentos del Año!",
                    "pt" => "Black Friday Álvarez: Os Melhores Descontos do Ano!",
                    "fr" => "Black Friday Álvarez : Les Meilleures Réductions de l'Année !",
                    "en" => "Black Friday Álvarez: The Best Discounts of the Year!",
                    "de" => "Black Friday Álvarez: Die besten Rabatte des Jahres!",
                    "it" => "Black Friday Álvarez: I Migliori Sconti dell'Anno!",
                ],
                "descriptions" => [
                    "es" => [
                        "caza" => "CAZA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "HÍPICA",
                        "buceo" => "BUCEO",
                        "nautica" => "NAUTICA",
                        "esqui" => "ESQUÍ",
                        "padel" => "PADEL",
                    ],
                    "pt" => [
                        "caza" => "CAÇA",
                        "golf" => "GOLFE",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAÇAO",
                        "buceo" => "MERGULHO",
                        "nautica" => "VELA",
                        "esqui" => "ESQUI",
                        "padel" => "PADEL",
                    ],
                    "fr" => [
                        "caza" => "CHASSE",
                        "golf" => "GOLF",
                        "pesca" => "PÊCHE",
                        "hipica" => "ÈQUITATION",
                        "buceo" => "PLONGÈE",
                        "nautica" => "NAUTIQUE",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "en" => [
                        "caza" => "HUNTING",
                        "golf" => "GOLF",
                        "pesca" => "FISHING",
                        "hipica" => "RIDING",
                        "buceo" => "DIVING",
                        "nautica" => "BOATING",
                        "esqui" => "SKIING",
                        "padel" => "PADEL",
                    ],
                    "de" => [
                        "caza" => "JAGD",
                        "golf" => "GOLF",
                        "pesca" => "ANGELN",
                        "hipica" => "REITEN",
                        "buceo" => "TAUCHEN",
                        "nautica" => "NAUTIK",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "it" => [
                        "caza" => "CACCIA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAZIONE",
                        "buceo" => " SUBACQUEA",
                        "nautica" => "NAUTICA",
                        "esqui" => "SCI",
                        "padel" => "PADEL",
                    ],
                ],
                'urls' => [
                    "es" => [
                        "caza" => "/blackfriday_caza",
                        "golf" => "/blackfriday_golf",
                        "pesca" => "/blackfriday_pesca",
                        "hipica" => "/blackfriday_hipica",
                        "buceo" => "/blackfriday_buceo",
                        "nautica" => "/blackfriday_nautica",
                        "esqui" => "/blackfriday_esqui",
                        "padel" => "/blackfriday_padel",
                    ],
                    "pt" => [
                        "caza" => "/pt/blackfriday_caca",
                        "golf" => "/pt/blackfriday_golfe",
                        "pesca" => "/pt/blackfriday_pesca",
                        "hipica" => "/pt/blackfriday_equitacao",
                        "buceo" => "/pt/blackfriday_mergulho",
                        "nautica" => "/pt/blackfriday_vela",
                        "esqui" => "/pt/blackfriday_esqui",
                        "padel" => "/pt/blackfriday_padel",
                    ],
                    "fr" => [
                        "caza" => "/fr/blackfriday_chasse",
                        "golf" => "/fr/blackfriday_golf",
                        "pesca" => "/fr/blackfriday_peche",
                        "hipica" => "/fr/blackfriday_equitation",
                        "buceo" => "/fr/blackfriday_plongee",
                        "nautica" => "/fr/blackfriday_nautique",
                        "esqui" => "/fr/blackfriday_ski",
                        "padel" => "/fr/blackfriday_padel",
                    ],
                    "en" => [
                        "caza" => "/en/blackfriday_hunting",
                        "golf" => "/en/blackfriday_golf",
                        "pesca" => "/en/blackfriday_fishing",
                        "hipica" => "/en/blackfriday_horse_riding",
                        "buceo" => "/en/blackfriday_diving",
                        "nautica" => "/en/blackfriday_boating",
                        "esqui" => "/en/blackfriday_skiing",
                        "padel" => "/en/blackfriday_padel",
                    ],
                    "de" => [
                        "caza" => "/de/blackfriday_jagd",
                        "golf" => "/de/blackfriday_golf",
                        "pesca" => "/de/blackfriday_angeln",
                        "hipica" => "/de/blackfriday_reiten",
                        "buceo" => "/de/blackfriday_tauchen",
                        "nautica" => "/de/blackfriday_nautik",
                        "esqui" => "/de/blackfriday_ski",
                        "padel" => "/de/blackfriday_padel",
                    ],
                    "it" => [
                        "caza" => "/it/blackfriday_caccia",
                        "golf" => "/it/blackfriday_golf",
                        "pesca" => "/it/blackfriday_pesca",
                        "hipica" => "/it/blackfriday_equitazione",
                        "buceo" => "/it/blackfriday_subacquea",
                        "nautica" => "/it/blackfriday_nautica",
                        "esqui" => "/it/blackfriday_sci",
                        "padel" => "/it/blackfriday_padel",
                    ],
                ]
            ];
        }

        // LANDING GENERAL OUTLETS
        if ($deporte == "outlets") {
            $data = [
                "texts" => [
                    "es" => "Bienvenido al <b>Outlet de Álvarez</b>, el mejor lugar para encontrar <b>material deportivo de primeras marcas al mejor precio</b>. En esta sección de <b>liquidación</b>, descubrirás las mejores ofertas y descuentos en productos para tus deportes favoritos: <b>golf, caza, pesca, hípica, pádel, buceo, náutica, esquí y outdoor.</b><br/>
                            En nuestro catálogo de liquidación, encontrarás <b>artículos de alta calidad</b> a precios irresistibles. Añadimos <b>nuevos productos en oferta</b> de forma constante, por lo que te animamos a visitar esta sección frecuentemente para no perderte la oportunidad de conseguir ese equipo que tanto deseas a un precio increíble. <br/>
                            ¡La próxima gran oferta podría estar aquí esperándote!",
                    "pt" => "Bem-vindo ao <b>Outlet da Álvarez</b>, o seu destino para encontrar <b>material desportivo de primeiras marcas ao melhor preço</b>. Nesta secção de <b>liquidação</b>, irá descobrir as melhores ofertas e descontos em produtos para os seus desportos favoritos: <b>golfe, caça, pesca, hipismo, padel, mergulho, náutica, esqui e outdoor.</b><br/>
                            No nosso catálogo de liquidação, encontrará <b>artigos de alta qualidade</b> a preços irresistíveis. Adicionamos <b>novos produtos em oferta</b> constantemente, por isso, incentivamo-lo a visitar esta secção frequentemente para não perder a oportunidade de conseguir o equipamento que tanto deseja a um preço incrível. <br/>
                            A próxima grande oferta pode estar aqui à sua espera!",
                    "fr" => "Bienvenue à <b>l'Outlet Álvarez</b>, votre destination pour trouver du <b>matériel sportif de premières marques au meilleur prix</b>. Dans cette section de <b>liquidation</b>, vous découvrirez les meilleures offres et réductions sur des produits pour vos sports préférés : <b>golf, chasse, pêche, équitation, padel, plongée sous-marine, nautisme, ski et outdoor.</b><br/>
                            Dans notre catalogue de liquidation, vous trouverez des<b> articles de haute qualité </b>à des prix irrésistibles. Nous <b>ajoutons constamment de nouveaux produits en promotion</b>, nous vous encourageons donc à visiter fréquemment cette section pour ne pas manquer l'occasion d'obtenir l'équipement que vous désirez tant à un prix incroyable.
                            La prochaine grande offre pourrait vous attendre ici !",
                    "de" => "Willkommen im <b>Álvarez Outlet</b>, Ihrem Ziel, um<b> Sportartikel von Top-Marken zum besten Preis zu finden</b>. In diesem Ausverkauf entdecken Sie die besten Angebote und Rabatte auf Produkte für Ihre Lieblingssportarten: <b>Golf, Jagd, Angeln, Reitsport, Padel, Tauchen, Wassersport, Skifahren und Outdoor.</b><br/>
                            In unserem Ausverkaufskatalog finden Sie hochwertige <b>Artikel zu unwiderstehlichen Preisen</b>. <b>Wir fügen ständig neue Produkte im Angebot hinzu</b>. Wir ermutigen Sie daher, diesen Bereich regelmäßig zu besuchen, um die Gelegenheit nicht zu verpassen, die Ausrüstung, die Sie sich so sehr wünschen, zu einem unglaublichen Preis zu bekommen. <br/>
                            Das nächste tolle Angebot könnte hier auf Sie warten!",
                    "it" => "Benvenuti <b>all'Outlet di Álvarez</b>, la vostra destinazione per trovare <b>attrezzatura sportiva di prima scelta al miglior prezzo</b>. In questa sezione di <b>liquidazione</b>, scoprirete le migliori offerte e sconti sui prodotti per i vostri sport preferiti: <b>golf, caccia, pesca, equitazione, padel, subacquea, nautica, sci e outdoor.</b><br/>
                            Nel nostro catalogo di liquidazione, troverete <b>articoli di alta qualità a prezzi irresistibili</b>. Aggiungiamo <b>costantemente nuovi prodotti in oferta</b>, quindi vi invitiamo a visitare frequentemente questa sezione per non perdere l'opportunità di ottenere l'attrezzatura che tanto desiderate a un prezzo incredibile. <br/>
                            La prossima grande offerta potrebbe essere qui ad aspettarvi!",
                    "en" => "Welcome to the <b>Álvarez Outlet</b>, your go-to destination for finding<b> top-brand sports gear at the best Price</b>. In this <b>clearance section</b>, you'll discover the best deals and discounts on inventaries for your favorite sports:<b> golf, hunting, fishing, equestrian, padel, diving, nautical, skiing, and outdoor.</b><br/>
                            In our clearance catalog, you'll find <b>high-quality ítems</b> at irresistible prices. We are constantly adding <b>new inventaries on sale</b>, so we encourage you to visit this section frequently to not miss the opportunity to get the gear you've been wanting at an incredible price.<br/>
                            The next great deal could be waiting for you right here!",
                ],
                "titles" => [
                    "es" => "OUTLET",
                    "pt" => "OUTLET",
                    "fr" => "OUTLET",
                    "en" => "OUTLET",
                    "de" => "OUTLET",
                    "it" => "OUTLET",
                ],
                "h1" => [
                    "es" => "",
                    "pt" => "",
                    "fr" => "",
                    "en" => "",
                    "de" => "",
                    "it" => "",
                ],
                "descriptions" => [
                    "es" => [
                        "caza" => "CAZA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "HÍPICA",
                        "buceo" => "BUCEO",
                        "nautica" => "NAUTICA",
                        "esqui" => "ESQUÍ",
                        "padel" => "PADEL",
                    ],
                    "pt" => [
                        "caza" => "CAÇA",
                        "golf" => "GOLFE",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAÇAO",
                        "buceo" => "MERGULHO",
                        "nautica" => "VELA",
                        "esqui" => "ESQUI",
                        "padel" => "PADEL",
                    ],
                    "fr" => [
                        "caza" => "CHASSE",
                        "golf" => "GOLF",
                        "pesca" => "PÊCHE",
                        "hipica" => "ÈQUITATION",
                        "buceo" => "PLONGÈE",
                        "nautica" => "nautique",
                        "esqui" => "ski",
                        "padel" => "padel",
                    ],
                    "de" => [
                        "caza" => "JAGD",
                        "golf" => "GOLF",
                        "pesca" => "ANGELN",
                        "hipica" => "REITEN",
                        "buceo" => "TAUCHEN",
                        "nautica" => "NAUTIK",
                        "esqui" => "ski",
                        "padel" => "padel",
                    ],
                    "it" => [
                        "caza" => "CACCIA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAZIONE",
                        "buceo" => "SUBACQUEA",
                        "nautica" => "nautica",
                        "esqui" => "sci",
                        "padel" => "padel",
                    ],
                    "en" => [
                        "caza" => "HUNTING",
                        "golf" => "GOLF",
                        "pesca" => "FISHING",
                        "hipica" => "RIDING",
                        "buceo" => "DIVING",
                        "nautica" => "BOATING",
                        "esqui" => "SKIING",
                        "padel" => "PADEL",
                    ],
                ],
                'urls' => [
                    "es" => [
                        "caza" => "/caza/outlet_y_liquidaciones",
                        "golf" => "/golf/outlet_y_liquidaciones",
                        "pesca" => "/pesca/outlet_y_liquidaciones",
                        "hipica" => "/hipica/outlet_y_liquidaciones",
                        "buceo" => "/buceo/outlet_y_liquidaciones",
                        "nautica" => "/nautica/outlet_y_liquidaciones",
                        "esqui" => "/esqui/outlet_y_liquidaciones",
                        "padel" => "/padel/outlet_y_liquidaciones",
                    ],
                    "pt" => [
                        "caza" => "/pt/caca/outlet_e_liquidacoes",
                        "golf" => "/pt/golfe/outlet_e_liquidacoes",
                        "pesca" => "/pt/pesca/outlet_e_liquidacoes",
                        "hipica" => "/pt/equitacao/outlet_e_liquidacoes",
                        "buceo" => "/pt/mergulho/outlet_e_liquidacoes",
                        "nautica" => "/pt/vela/outlet_e_liquidacoes",
                        "esqui" => "/pt/esqui/outlet_e_liquidacoes",
                        "padel" => "/pt/padel/outlet_e_liquidacoes",
                    ],
                    "fr" => [
                        "caza" => "/fr/chasse/outlet_et_liquidations",
                        "golf" => "/fr/golf/outlet_et_liquidations",
                        "pesca" => "/fr/peche/outlet_et_liquidations",
                        "hipica" => "/fr/equitation/outlet_et_liquidations",
                        "buceo" => "/fr/plongee/outlet_et_liquidations",
                        "nautica" => "/fr/nautique/outlet_et_liquidation",
                        "esqui" => "/fr/ski/outlet_et_liquidation",
                        "padel" => "/fr/padle/outlet_et_liquidation",
                    ],
                    "de" => [
                        "caza" => "/de/jagd/outlet_und_ausverkauf",
                        "golf" => "/de/golf/outlet_und_ausverkauf",
                        "pesca" => "/de/angeln/outlet_und_ausverkauf",
                        "hipica" => "/de/reiten/outlet_und_ausverkauf",
                        "buceo" => "/de/tauchen/outlet_und_ausverkauf",
                        "nautica" => "/de/nautik/outlet_und_ausverkauf",
                        "esqui" => "/de/ski/outlet_und_ausverkauf",
                        "padel" => "/de/padel/outlet_und_ausverkauf",
                    ],
                    "it" => [
                        "caza" => "/it/caccia/outlet_e_liquidazioni",
                        "golf" => "/it/golf/outlet_e_liquidazioni",
                        "pesca" => "/it/pesca/outlet_e_liquidazioni",
                        "hipica" => "/it/equitazione/outlet_e_liquidazioni",
                        "buceo" => "/it/subacquea/outlet_e_liquidazioni",
                        "nautica" => "/it/nautica/outlet_e_liquidazioni",
                        "esqui" => "/it/sci/outlet_e_liquidazioni",
                        "padel" => "/it/padel/outlet_e_liquidazioni",
                    ],
                    "en" => [
                        "caza" => "/en/hunting/outlet_and_liquidations",
                        "golf" => "/en/golf/outlet_and_liquidations",
                        "pesca" => "/en/fishing/outlet_and_liquidations",
                        "hipica" => "/en/horse_riding/outlet_and_liquidations",
                        "buceo" => "/en/diving/outlet_and_liquidations",
                        "nautica" => "/en/boating/outlet_and_liquidations",
                        "esqui" => "/en/skiing/outlet_and_liquidations",
                        "padel" => "/en/padel/outlet_and_liquidations",
                    ],
                ]
            ];
        }

        if ($deporte == "caza") {
            $data = [
                "botones" => [
                    "es" => [
                        "texto" => "VER TODO BLACK FRIDAY CAZA",
                        "url" => "/caza",
                    ],
                    "en" => [
                        "texto" => "SEE ALL BLACK FRIDAY HUNTING",
                        "url" => "/en/hunting",
                    ],
                    "pt" => [
                        "texto" => "VER TUDO BLACK FRIDAY CAÇA",
                        "url" => "/pt/caca",
                    ],
                    "fr" => [
                        "texto" => "VOIR LE BLACK FRIDAY CHASSE",
                        "url" => "/fr/chasse",
                    ],
                    "de" => [
                        "texto" => "SEHEN BLACK FRIDAY JAGD",
                        "url" => "/de/jagd",
                    ],
                    "it" => [
                        "texto" => "VEDI BLACK FRIDAY CACCIA",
                        "url" => "/it/caccia",
                    ],
                ],
                "deporte" => "caza",
                "h1" => [
                    "es" => "Black Friday de CAZA en Álvarez",
                    "en" => "HUNTING Black Friday at Álvarez",
                    "pt" => "Black Friday de CAÇA na Álvarez",
                    "fr" => "Black Friday CHASSE chez Álvarez",
                    "de" => "JAGD Black Friday bei Álvarez",
                    "it" => "Black Friday di CACCIA da Álvarez",

                ],
                "after" => [
                    "es" => "Estamos preparando el próximo Black Friday; mientras tanto, aquí tienes una selección de nuestros mejores productos:",
                    "en" => "We are preparing the next Black Friday; in the meantime, here’s a selection of our best inventaries:",
                    "pt" => "Estamos a preparar o próximo Black Friday; entretanto, aqui tem uma seleção dos nossos melhores produtos:",
                    "fr" => "Nous préparons le prochain Black Friday ; en attendant, voici une sélection de nos meilleurs produits :",
                    "de" => "Wir bereiten den nächsten Black Friday vor; in der Zwischenzeit findest du hier eine Auswahl unserer besten Produkte:",
                    "it" => "Stiamo preparando il prossimo Black Friday; nel frattempo, ecco una selezione dei nostri migliori prodotti:",

                ],
                "texts" => [
                    "es" => "Desde el 21 de noviembre hasta el 01 de diciembre llega el <strong>Black Friday 2025 a la sección de CAZA de Álvarez.</strong>
                            Disfruta de una amplia selección de productos de caza a precios increíblemente rebajados. Aprovecha los descuentos Black Friday en rifles, escopetas, armas de balines, trípodes, fundas, productos para el perro, ropa de caza… y en una gran variedad de accesorios y complementos para practicar la caza.
                            Primeras marcas, CON LOS PRECIOS MÁS BAJOS DEL AÑO: <a href='/m/chiruca'>CHIRUCA</a> , <a href='/m/beretta'>BERETTA</a>, <a href='/m/gamo'>GAMO</a>, <a href='/m/hickmicro'>HICKMICRO</a>, <a href='/m/bushnell'>BUSHNELL</a>, <a href='/m/leica'>LEICA</a>, <a href='/m/muela'>MUELA</a>, <a href='/m/pard'>PARD</a>, <a href='/m/swarovski'>SWAROVSKI</a>, <a href='/m/zeiss'>ZEISS</a>…
                            No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de caza en Álvarez.
                            Ampliamos el periodo de devoluciones hasta el 31 de enero de 2026.</strong>",
                    "pt" => "De 21 de novembro a 1 de dezembro, a <strong>Black Friday 2025 chega à secção CAÇA da Álvarez</strong>.
                            Desfrute de uma vasta seleção de produtos de caça a preços incrivelmente reduzidos. Aproveite os descontos da Black Friday em espingardas, caçadeiras, pistolas de pellets, tripés, coldres tripés, coldres, coldres para cães, roupa de caça... e uma grande variedade de acessórios e complementos para a caça.
                            Marcas de topo, AOS MENORES PREÇOS DO ANO: <a href='/pt/m/chiruca'>CHIRUCA</a> , <a href='/pt/m/beretta'>BERETTA</a>, <a href='/pt/m/gamo'>GAMO</a>, <a href='/pt/m/hickmicro'>HICKMICRO</a>, <a href='/pt/m/bushnell'>BUSHNELL</a>, <a href='/pt/m/leica'>LEICA</a>, <a href='/pt/m/muela'>MUELA</a>, <a href='/pt/m/pard'>PARD</a>, <a href='/pt/m/swarovski'>SWAROVSKI</a>, <a href='/pt/m/zeiss'>ZEISS</a>...
                            Não perca esta oportunidade de se <strong>adiantar às suas compras de Natal e obter os melhores preços com as ofertas de caça da Black Friday na Álvarez.
                            Prolongamos o período de devoluções até 31 de janeiro de 2026.</strong>",
                    "fr" => "Du 21 novembre au 1 décembre, le <strong>Black Friday 2025 arrive dans la section CHASSE d'Álvarez</strong>.
                            Profitez d'une large sélection de produits de chasse à des prix incroyablement réduits. Profitez des réductions du Black Friday sur les fusils à plomb, trépieds, étuis, étuis pour chiens, etc. trépieds, étuis, étuis pour chiens, vêtements de chasse... et une grande variété d'accessoires et de compléments pour la chasse.
                            Grandes marques, aux prix les plus bas de l'année : <a href='/fr/m/chiruca'>CHIRUCA</a> , <a href='/fr/m/beretta'>BERETTA</a>, <a href='/fr/m/gamo'>GAMO</a>, <a href='/fr/m/hickmicro'>HICKMICRO</a>, <a href='/fr/m/bushnell'>BUSHNELL</a>, <a href='/fr/m/leica'>LEICA</a>, <a href='/fr/m/muela'>MUELA</a>, <a href='/fr/m/pard'>PARD</a>, <a href='/fr/m/swarovski'>SWAROVSKI</a>, <a href='/fr/m/zeiss'>ZEISS</a>...
                            Ne manquez pas l'occasion de prendre de <strong>l'avance sur vos achats de Noël et d'obtenir les meilleurs prix avec les offres de chasse du Black Friday chez Álvarez.
                            Nous prolongeons la période de retour jusqu'au 31 janvier 2026.</strong>",
                    "de" => "Vom 21. November bis zum 1. Dezember findet der <strong>Black Friday 2025 in der Abteilung JAGD von Álvarez statt. .</strong>
                            Profitieren Sie von einer großen Auswahl an Jagdprodukten zu unglaublich reduzierten Preisen. Nutzen Sie die Black Friday-Rabatte auf Luftgewehre, Stative, Taschen, Produkte für Hunde, Jagdbekleidung ... und eine große Auswahl an Zubehör und Accessoires für die Jagd.
                            Führende Marken zu den niedrigsten Preisen des Jahres: <a href='/m/chiruca'>CHIRUCA</a> , <a href='/m/beretta'>BERETTA</a>, <a href='/m/gamo'>GAMO</a>, <a href='/m/hickmicro'>HICKMICRO</a>, <a href='/m/bushnell'>BUSHNELL</a>, <a href='/m/leica'>LEICA</a>, <a href='/m/muela'>MUELA</a>, <a href='/m/pard'>PARD</a>, <a href='/m/swarovski'>SWAROVSKI</a>, <a href='/m/zeiss'>ZEISS</a>…
                            <strong>Verpassen Sie nicht diese Gelegenheit, Ihre Weihnachtseinkäufe vorzuziehen und mit den Black Friday-Angeboten für die Jagd bei Álvarez die besten Preise zu erzielen.
                            Wir verlängern die Rückgabefrist bis zum 31. Januar 2026. </strong>",
                    "it" => "Dal 21 novembre al 1° dicembre arriva il <strong>Black Friday 2025 nella sezione CACCIA di Álvarez.</strong>
                            Approfitta di un'ampia selezione di prodotti per la caccia a prezzi incredibilmente scontati. Approfitta degli sconti del Black Friday su fucili, carabine, armi a pallini, treppiedi, custodie, prodotti per cani, abbigliamento da caccia... e su una vasta gamma di accessori e complementi per la caccia.
                            Le migliori marche, AI PREZZI PIÙ BASSI DELL'ANNO: <a href='/m/chiruca'>CHIRUCA</a> , <a href='/m/beretta'>BERETTA</a>, <a href='/m/gamo'>GAMO</a>, <a href='/m/hickmicro'>HICKMICRO</a>, <a href='/m/bushnell'>BUSHNELL</a>, <a href='/m/leica'>LEICA</a>, <a href='/m/muela'>MUELA</a>, <a href='/m/pard'>PARD</a>, <a href='/m/swarovski'>SWAROVSKI</a>, <a href='/m/zeiss'>ZEISS</a>…
                            Non lasciarti sfuggire questa occasione per <strong>anticipare i tuoi acquisti natalizi e ottenere i prezzi migliori con le offerte del Black Friday della caccia da Álvarez.
                            Abbiamo esteso il periodo di restituzione fino al 31 gennaio 2026. </strong>",
                    "en" => "From 21 November to 1 December, <strong>Black Friday 2025 arrives at Álvarez's HUNTING section.</strong>
                            Enjoy a wide selection of hunting inventaries at incredibly discounted prices. Take advantage of Black Friday discounts on pellet guns, tripods, cases, dog inventaries, hunting clothing... and a wide variety of accessories and complements for hunting.
                            Top brands, WITH THE LOWEST PRICES OF THE YEAR: <a href='/m/chiruca'>CHIRUCA</a> , <a href='/m/beretta'>BERETTA</a>, <a href='/m/gamo'>GAMO</a>, <a href='/m/hickmicro'>HICKMICRO</a>, <a href='/m/bushnell'>BUSHNELL</a>, <a href='/m/leica'>LEICA</a>, <a href='/m/muela'>MUELA</a>, <a href='/m/pard'>PARD</a>, <a href='/m/swarovski'>SWAROVSKI</a>, <a href='/m/zeiss'>ZEISS</a>…
                            Don't miss this opportunity to <strong>get your Christmas shopping done early and get the best prices with Álvarez's Black Friday hunting offers.
                            We are extending the return period until 31 January 2026. </strong>"
                ],
                "texts_after" => [
                    "es" => "Cada Black Friday te traemos la más amplia selección de productos de caza a precios increíblemente rebajados.
                            Aprovecha los descuentos del Black Friday en rifles, escopetas, carabinas de aire, trípodes, fundas, productos para el perro, ropa de caza… y en una gran variedad de accesorios y complementos para practicar la caza.
                            Permanece atento al próximo Black Friday. ¡No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de caza en Álvarez.</strong>",

                    "en" => "Every Black Friday we bring you the widest selection of hunting inventaries at unbeatable prices.
                            Take advantage of Black Friday deals on rifles, shotguns, air guns, tripods, cases, dog accessories, hunting clothing… and a wide range of equipment and gear for every hunter.
                            Stay tuned for the next Black Friday! Don’t miss this chance to <strong>get ahead on your Christmas shopping and grab the best prices with Álvarez’s Black Friday hunting offers.</strong>",

                    "pt" => "Em cada Black Friday trazemos-lhe a mais ampla seleção de produtos de caça a preços incrivelmente baixos.
                            Aproveite os descontos da Black Friday em espingardas, caçadeiras, carabinas de ar, tripés, coldres, produtos para cães, roupa de caça… e numa grande variedade de acessórios e complementos para praticar a caça.
                            Fique atento à próxima Black Friday! Não perca a oportunidade de <strong>adiantar as suas compras de Natal e conseguir os melhores preços com as ofertas de caça da Black Friday na Álvarez.</strong>",

                    "fr" => "Chaque Black Friday, nous vous proposons la plus large sélection de produits de chasse à des prix incroyablement réduits.
                            Profitez des réductions du Black Friday sur les fusils, carabines à air, trépieds, housses, produits pour chiens, vêtements de chasse… ainsi que sur une grande variété d’accessoires et d’équipements pour la chasse.
                            Restez attentif au prochain Black Friday ! Ne manquez pas cette occasion de <strong>prendre de l’avance sur vos achats de Noël et d’obtenir les meilleurs prix avec les offres de chasse du Black Friday chez Álvarez.</strong>",

                    "de" => "An jedem Black Friday bieten wir dir die größte Auswahl an Jagdprodukten zu unglaublich reduzierten Preisen.
                            Nutze die Black-Friday-Rabatte auf Gewehre, Flinten, Luftdruckwaffen, Stative, Hüllen, Hundezubehör, Jagdbekleidung… sowie auf eine Vielzahl von Accessoires und Ausrüstung für die Jagd.
                            Bleib dran für den nächsten Black Friday! Verpasse nicht die Gelegenheit, <strong>deine Weihnachtseinkäufe frühzeitig zu erledigen und die besten Preise mit den Black-Friday-Angeboten für Jagd bei Álvarez zu sichern.</strong>",

                    "it" => "Ogni Black Friday ti offriamo la più ampia selezione di prodotti da caccia a prezzi incredibilmente scontati.
                            Approfitta degli sconti del Black Friday su fucili, carabine ad aria, treppiedi, custodie, prodotti per cani, abbigliamento da caccia… e su una vasta gamma di accessori e attrezzature per praticare la caccia.
                            Rimani aggiornato sul prossimo Black Friday! Non perdere l’occasione di <strong>anticipare i tuoi acquisti di Natale e ottenere i migliori prezzi con le offerte Black Friday di caccia da Álvarez.</strong>",
                ],
                "imagenes" => [
                    "es" => [
                        [
                            "title" => "ESCOPETAS",
                            "image" => "escopetas.webp",
                            "url" => "/caza/escopetas"
                        ],
                        [
                            "title" => "RIFLES",
                            "image" => "rifles.webp",
                            "url" => "/caza/rifles"
                        ],
                        [
                            "title" => "ARMAS DE BALINES",
                            "image" => "balines.webp",
                            "url" => "/caza/armas_de_balines"
                        ],
                        [
                            "title" => "TÉRMICOS Y NOCTURNOS",
                            "image" => "termica.webp",
                            "url" => "/caza/vision_termica_y_nocturna"
                        ],
                        [
                            "title" => "ARMEROS",
                            "image" => "armeros.webp",
                            "url" => "/caza/armeros_de_seguridad"
                        ],
                        [
                            "title" => "TRÍPODES",
                            "image" => "tripodes.webp",
                            "url" => "/caza/tripodes_horquillas_y_bipodes"
                        ],
                        [
                            "title" => "ROPA DE CAZA",
                            "image" => "ropa.webp",
                            "url" => "/caza/ropa_y_complementos"
                        ],
                        [
                            "title" => "BOTAS DE CAZA",
                            "image" => "botas.webp",
                            "url" => "/caza/calzado"
                        ],
                        [
                            "title" => "TODO PARA TU PERRO",
                            "image" => "perros.webp",
                            "url" => "/caza/productos_para_el_perro"
                        ],
                        [
                            "title" => "CUCHILLOS",
                            "image" => "cuchillos.webp",
                            "url" => "/caza/cuchillos"
                        ],
                        [
                            "title" => "LINTERNAS Y FOCOS",
                            "image" => "linterna.webp",
                            "url" => "/caza/linternas_y_focos"
                        ],

                        [
                            "title" => "COMPETICIÓN Y TIRO",
                            "image" => "tiro.webp",
                            "url" => "/caza/competicion_y_tiro"
                        ],
                        [
                            "title" => "PRISMÁTICOS",
                            "image" => "prismaticos.webp",
                            "url" => "/caza/prismaticos"
                        ],
                        [
                            "title" => "CÁMARAS",
                            "image" => "camaras.webp",
                            "url" => "/caza/camaras"
                        ],
                        [
                            "title" => "VISORES",
                            "image" => "visores.webp",
                            "url" => "/caza/visores"
                        ],
                        [
                            "title" => "2",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],

                    ],
                    "en" => [
                        [
                            "title" => "BB Guns",
                            "image" => "balines.webp",
                            "url" => "/en/hunting/bb_guns"
                        ],
                        [
                            "title" => "Thermal and Night Vision",
                            "image" => "termica.webp",
                            "url" => "/en/hunting/thermal_and_night_vision"
                        ],
                        [
                            "title" => "Tripods Forks and Bipods",
                            "image" => "tripodes.webp",
                            "url" => "/en/hunting/tripods_forks_and_bipods"
                        ],
                        [
                            "title" => "Clothes and complements",
                            "image" => "ropa.webp",
                            "url" => "/en/hunting/clothes_and_complements"
                        ],
                        [
                            "title" => "FOOTWEAR",
                            "image" => "botas.webp",
                            "url" => "/en/hunting/footwear"
                        ],
                        [
                            "title" => "Dog Products",
                            "image" => "perros.webp",
                            "url" => "/en/hunting/dog_products"
                        ],
                        [
                            "title" => "Knives",
                            "image" => "cuchillos.webp",
                            "url" => "/en/hunting/knives"
                        ],
                        [
                            "title" => "Flashlights and Spotlights",
                            "image" => "linterna.webp",
                            "url" => "/en/hunting/flashlights_and_spotlights"
                        ],
                        [
                            "title" => "Competition and Shooting",
                            "image" => "tiro.webp",
                            "url" => "/en/hunting/competition_and_shooting"
                        ],
                        [
                            "title" => "BINOCULARS",
                            "image" => "prismaticos.webp",
                            "url" => "/en/hunting/binoculars"
                        ],
                        [
                            "title" => "CAMERAS",
                            "image" => "camaras.webp",
                            "url" => "/en/hunting/cameras"
                        ],
                        [
                            "title" => "SCOPES",
                            "image" => "visores.webp",
                            "url" => "/en/hunting/hunting_scopes"
                        ],
                    ],
                    "pt" => [
                        [
                            "title" => "ARMAS DE CHUMBOS",
                            "image" => "balines.webp",
                            "url" => "/pt/caca/armas_de_chumbos"
                        ],
                        [
                            "title" => "VISÃO TÉRMICA E NOTURNA",
                            "image" => "termica.webp",
                            "url" => "/pt/caca/visao_termica_e_noturna"
                        ],
                        [
                            "title" => "TRIPÉS",
                            "image" => "tripodes.webp",
                            "url" => "/pt/caca/tripes_monopes_e_bipes"
                        ],
                        [
                            "title" => "ROUPA DE CAÇA",
                            "image" => "ropa.webp",
                            "url" => "/pt/caca/roupa_e_complementos"
                        ],
                        [
                            "title" => "CALÇADO DE CAÇA",
                            "image" => "botas.webp",
                            "url" => "/pt/caca/calcado"
                        ],
                        [
                            "title" => "PRODUTOS PARA O CÃO",
                            "image" => "perros.webp",
                            "url" => "/pt/caca/produtos_para_o_cao"
                        ],
                        [
                            "title" => "FACAS",
                            "image" => "cuchillos.webp",
                            "url" => "/pt/caca/facas"
                        ],
                        [
                            "title" => "LANTERNAS E FOCOS",
                            "image" => "linterna.webp",
                            "url" => "/pt/caca/competicao_e_tiro"
                        ],
                        [
                            "title" => "COMPETIÇÃO E TIRO",
                            "image" => "tiro.webp",
                            "url" => "/pt/caca/competicao_e_tiro"
                        ],
                        [
                            "title" => "BINÓCULOS",
                            "image" => "prismaticos.webp",
                            "url" => "/pt/caca/binoculos"
                        ],
                        [
                            "title" => "CÂMARAS",
                            "image" => "camaras.webp",
                            "url" => "/pt/caca/camaras"
                        ],
                        [
                            "title" => "MIRAS",
                            "image" => "visores.webp",
                            "url" => "/pt/caca/miras"
                        ],
                    ],
                    "fr" => [
                        [
                            "title" => "ARMES À AIR",
                            "image" => "balines.webp",
                            "url" => "/fr/chasse/armes_a_air"
                        ],
                        [
                            "title" => "THERMIQUES - NOCTURNES",
                            "image" => "termica.webp",
                            "url" => "/fr/chasse/vision_thermique_et_nocturne"
                        ],
                        [
                            "title" => "TRÉPIEDS",
                            "image" => "tripodes.webp",
                            "url" => "/fr/chasse/trepieds_de_chasse"
                        ],
                        [
                            "title" => "VETEMENTS",
                            "image" => "ropa.webp",
                            "url" => "/fr/chasse/vetements_et_accessoires"
                        ],
                        [
                            "title" => "CHAUSSURES",
                            "image" => "botas.webp",
                            "url" => "/fr/chasse/chaussures"
                        ],
                        [
                            "title" => "ARTICLES POUR CHIENS",
                            "image" => "perros.webp",
                            "url" => "/fr/chasse/articles_pour_chiens"
                        ],
                        [
                            "title" => "COUTEAUX",
                            "image" => "cuchillos.webp",
                            "url" => "/fr/chasse/couteaux"
                        ],
                        [
                            "title" => "LAMPES",
                            "image" => "linterna.webp",
                            "url" => "/fr/chasse/lampes"
                        ],
                        [
                            "title" => "TIR SPORTIF",
                            "image" => "tiro.webp",
                            "url" => "/fr/chasse/tir_sportif"
                        ],
                        [
                            "title" => "JUMELLES",
                            "image" => "prismaticos.webp",
                            "url" => "/fr/chasse/jumelles"
                        ],
                        [
                            "title" => "CAMÉRAS",
                            "image" => "camaras.webp",
                            "url" => "/fr/chasse/cameras"
                        ],
                        [
                            "title" => "LUNETTES",
                            "image" => "visores.webp",
                            "url" => "/fr/chasse/lunettes_de_visee"
                        ],
                    ],
                    "de" => [
                        [
                            "title" => "Luftdruckwaffen",
                            "image" => "balines.webp",
                            "url" => "/de/jagd/luftdruckwaffen"
                        ],
                        [
                            "title" => "Wärmebild & Nachtsicht",
                            "image" => "termica.webp",
                            "url" => "/de/jagd/waermebild_und_nachtsichtgeraete"
                        ],
                        [
                            "title" => "Sicherheit Waffenschmiede",
                            "image" => "armeros.webp",
                            "url" => "/de/jagd/sicherheit_waffenschmiede"
                        ],
                        [
                            "title" => "Jagd stative",
                            "image" => "tripodes.webp",
                            "url" => "/de/jagd/jagd_stative"
                        ],
                        [
                            "title" => "Bekleidung und Accesoires",
                            "image" => "ropa.webp",
                            "url" => "/de/jagd/bekleidung_und_accesoires"
                        ],
                        [
                            "title" => "Schuhe",
                            "image" => "botas.webp",
                            "url" => "/de/jagd/schuhe"
                        ],
                        [
                            "title" => "Hundeausrüstung",
                            "image" => "perros.webp",
                            "url" => "/de/jagd/hundezubehoer_hundebedarf"
                        ],
                        [
                            "title" => "Messer",
                            "image" => "cuchillos.webp",
                            "url" => "/de/jagd/messer"
                        ],
                        [
                            "title" => "Taschenlampen",
                            "image" => "linterna.webp",
                            "url" => "/de/jagd/taschenlampen_und_handscheinwerfern"
                        ],
                        [
                            "title" => "Sportschießen",
                            "image" => "tiro.webp",
                            "url" => "/de/jagd/wettbewerb_und_schiesen"
                        ],
                        [
                            "title" => "FERNGLÄSER",
                            "image" => "prismaticos.webp",
                            "url" => "/de/jagd/fernglaeser"
                        ],
                        [
                            "title" => "KAMERAS",
                            "image" => "camaras.webp",
                            "url" => "/de/jagd/kameras"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "ZIELFERNROHRE",
                            "image" => "visores.webp",
                            "url" => "/de/jagd/jagd_zielfernrohre"
                        ],

                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],

                    ],
                    "it" => [
                        [
                            "title" => "Armi ad Aria Compressa",
                            "image" => "balines.webp",
                            "url" => "/it/caccia/armi_ad_aria_compressa"
                        ],
                        [
                            "title" => "Visione Termica e Notturna",
                            "image" => "termica.webp",
                            "url" => "/it/caccia/visione_termica_e_notturna"
                        ],
                        [
                            "title" => "Treppiedi da Caccia",
                            "image" => "tripodes.webp",
                            "url" => "/it/caccia/treppiedi_da_caccia"
                        ],
                        [
                            "title" => "Abbigliamento e Accessori",
                            "image" => "ropa.webp",
                            "url" => "/it/caccia/abbigliamento_e_accessori"
                        ],
                        [
                            "title" => "Calzature",
                            "image" => "botas.webp",
                            "url" => "/it/caccia/calzature"
                        ],
                        [
                            "title" => "Prodotti per Cani",
                            "image" => "perros.webp",
                            "url" => "/it/caccia/prodotti_per_cani"
                        ],
                        [
                            "title" => "Coltelli",
                            "image" => "cuchillos.webp",
                            "url" => "/it/caccia/coltelli"
                        ],
                        [
                            "title" => "Torce e Fari",
                            "image" => "linterna.webp",
                            "url" => "/it/caccia/torce_e_fari"
                        ],
                        [
                            "title" => "Competizione e Tiro",
                            "image" => "tiro.webp",
                            "url" => "/it/caccia/competizione_e_tiro"
                        ],
                        [
                            "title" => "BINOCOLI",
                            "image" => "prismaticos.webp",
                            "url" => "/it/caccia/binocoli"
                        ],
                        [
                            "title" => "FOTOCAMERE",
                            "image" => "camaras.webp",
                            "url" => "/it/caccia/fotocamere"
                        ],
                        [
                            "title" => "VISORI",
                            "image" => "visores.webp",
                            "url" => "/it/caccia/visori_da_caccia"
                        ],
                    ],
                ],
            ];
        }
        if ($deporte == "golf") {
            $data = [
                "botones" => [
                    "es" => [
                        "texto" => "VER TODO BLACK FRIDAY GOLF",
                        "url" => "/golf",
                    ],
                    "en" => [
                        "texto" => "SEE ALL BLACK FRIDAY GOLF",
                        "url" => "/en/golf",
                    ],
                    "pt" => [
                        "texto" => "VER TUDO BLACK FRIDAY GOLFE",
                        "url" => "/pt/golfe",
                    ],
                    "fr" => [
                        "texto" => "VOIR LE BLACK FRIDAY GOLF",
                        "url" => "/fr/golf",
                    ],
                    "de" => [
                        "texto" => "SEHEN BLACK FRIDAY GOLF",
                        "url" => "/de/golf",
                    ],
                    "it" => [
                        "texto" => "VEDI BLACK FRIDAY GOLF",
                        "url" => "/it/golf",
                    ]
                ],
                "deporte" => "golf",
                "h1" => [
                    "es" => "Black Friday de GOLF en Álvarez",
                    "en" => "GOLF Black Friday at Álvarez",
                    "pt" => "Black Friday de GOLFE na Álvarez",
                    "fr" => "Black Friday GOLF chez Álvarez",
                    "de" => "GOLF Black Friday bei Álvarez",
                    "it" => "Black Friday di GOLF da Álvarez",

                ],
                "after" => [
                    "es" => "Estamos preparando el próximo Black Friday; mientras tanto, aquí tienes una selección de nuestros mejores productos:",
                    "en" => "We are preparing the next Black Friday; in the meantime, here’s a selection of our best inventaries:",
                    "pt" => "Estamos a preparar o próximo Black Friday; entretanto, aqui tem uma seleção dos nossos melhores produtos:",
                    "fr" => "Nous préparons le prochain Black Friday ; en attendant, voici une sélection de nos meilleurs produits :",
                    "de" => "Wir bereiten den nächsten Black Friday vor; in der Zwischenzeit findest du hier eine Auswahl unserer besten Produkte:",
                    "it" => "Stiamo preparando il prossimo Black Friday; nel frattempo, ecco una selezione dei nostri migliori prodotti:",

                ],
                "texts" => [
                    "es" => "Desde el 21 de noviembre hasta el 01 de diciembre llega el <strong>Black Friday 2025 a la sección de GOLF de Álvarez.</strong>
                            Disfruta de una amplia selección de productos de golf a precios increíblemente rebajados. Aprovecha los descuentos Black Friday en drivers, híbridos, maderas de calle, sets de hierros, bolas de golf, carros de golf, medidores de distancia, zapatos y ropa de golf… y en una gran variedad de accesorios y complementos para jugar al golf.
                            Primeras marcas, CON LOS PRECIOS MÁS BAJOS DEL AÑO: <a href='/m/taylormade'>TAYLORMADE</a>, <a href='/m/callaway'>CALLAWAY</a>, <a href='/m/ping'>PING</a>, <a href='/m/titleist'>TITLEIST</a>, <a href='/m/footjoy'>FOOTJOY</a>, <a href='/m/puma'>PUMA</a>, <a href='/m/srixon'>SRIXON</a>, <a href='/m/mizuno'>MIZUNO</a>, <a href='/m/odyssey'>ODYSSEY</a>, <a href='/m/skechers'>SKECHERS</a>, <a href='/m/wilson'>WILSON</a>…
                            No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de golf en Álvarez.
                            Ampliamos el periodo de devoluciones hasta el 31 de enero de 2026.</strong>",
                    "pt" => "De 21 de novembro a 2 de dezembro, a <strong>Black Friday 2025 chega à secção GOLFE da Álvarez</strong>.
                            Desfrute de uma vasta seleção de produtos de golfe a preços incrivelmente reduzidos. Aproveite os descontos da Black Friday em drivers, híbridos, madeiras fairway, conjuntos de ferros, bolas de golfe, carrinhos de golfe, medidores de distância, sapatos e vestuário de golfe... e uma grande variedade de acessórios e complementos de golfe.
                            As melhores marcas, AOS MENORES PREÇOS DO ANO: <a href='/pt/m/taylormade'>TAYLORMADE</a>, <a href='/pt/m/callaway'>CALLAWAY</a>, <a href='/pt/m/ping'>PING</a>, <a href='/pt/m/titleist'>TITLEIST</a>, <a href='/pt/m/footjoy'>FOOTJOY</a>, <a href='/pt/m/puma'>PUMA</a>, <a href='/pt/m/srixon'>SRIXON</a>, <a href='/pt/m/mizuno'>MIZUNO</a>, <a href='/pt/m/odyssey'>ODYSSEY</a>, <a href='/pt/m/skechers'>SKECHERS</a>, <a href='/pt/m/wilson'>WILSON</a>...
                            Não perca esta oportunidade de se <strong>adiantar às suas compras de Natal e obter os melhores preços com as ofertas de golfe da Black Friday na Álvarez.
                            Prolongamos o período de devoluções até 31 de janeiro de 2026.</strong>",
                    "fr" => "Du 21 novembre au 2 décembre, le <strong>Black Friday 2025 arrive dans la section GOLF d'Álvarez</strong>.
                            Profitez d'une large sélection de produits de golf à des prix incroyablement réduits. Profitez des réductions du Black Friday sur les drivers, hybrides, bois de parcours, ensembles de fers, balles de golf, voiturettes de golf, distancemètres, chaussures et vêtements de golf... et une grande variété d'accessoires et de compléments de golf.
                            Grandes marques, aux prix les plus bas de l'année : <a href='/fr/m/taylormade'>TAYLORMADE</a>, <a href='/fr/m/callaway'>CALLAWAY</a>, <a href='/fr/m/ping'>PING</a>, <a href='/fr/m/titleist'>TITLEIST</a>, <a href='/fr/m/footjoy'>FOOTJOY</a>, <a href='/fr/m/puma'>PUMA</a>, <a href='/fr/m/srixon'>SRIXON</a>, <a href='/fr/m/mizuno'>MIZUNO</a>, <a href='/fr/m/odyssey'>ODYSSEY</a>, <a href='/fr/m/skechers'>SKECHERS</a>, <a href='/fr/m/wilson'>WILSON</a>...
                            Ne manquez pas l'occasion de prendre de <strong>l'avance sur vos achats de Noël et d'obtenir les meilleurs prix avec les offres de golf du Black Friday chez Álvarez.
                            Nous prolongeons la période de retour jusqu'au 31 janvier 2026.</strong>",
                    "de" => "Vom 21. November bis zum 1. Dezember findet der <strong>Black Friday 2025 in der GOLF-Abteilung von Álvarez statt. </strong>
                            Profitieren Sie von einer großen Auswahl an Golfprodukten zu unglaublich reduzierten Preisen. Nutzen Sie die Black Friday-Rabatte auf Driver, Hybride, Fairway-Hölzer, Eisensätze, Golfbälle, Golfwagen, Entfernungsmesser, Golfschuhe und -bekleidung sowie auf eine Vielzahl von Accessoires und Zubehör für den Golfsport.
                            Top-Marken zu den niedrigsten Preisen des Jahres:<a href='/m/taylormade'>TAYLORMADE</a>, <a href='/m/callaway'>CALLAWAY</a>, <a href='/m/ping'>PING</a>, <a href='/m/titleist'>TITLEIST</a>, <a href='/m/footjoy'>FOOTJOY</a>, <a href='/m/puma'>PUMA</a>, <a href='/m/srixon'>SRIXON</a>, <a href='/m/mizuno'>MIZUNO</a>, <a href='/m/odyssey'>ODYSSEY</a>, <a href='/m/skechers'>SKECHERS</a>, <a href='/m/wilson'>WILSON</a>…
                            <strong>Verpassen Sie nicht diese Gelegenheit, Ihre Weihnachtseinkäufe vorzuziehen und mit den Black Friday-Angeboten für Golf bei Álvarez die besten Preise zu erzielen.
                            Wir verlängern die Rückgabefrist bis zum 31. Januar 2026. </strong>",
                    "it" => "Dal 21 novembre al 1° dicembre arriva il <strong>Black Friday 2025 nella sezione GOLF di Álvarez. </strong>
                            Approfitta di un'ampia selezione di prodotti per il golf a prezzi incredibilmente scontati. Approfitta degli sconti del Black Friday su driver, ibridi, legni da strada, set di ferri, palline da golf, carrelli da golf, misuratori di distanza, scarpe e abbigliamento da golf... e su una vasta gamma di accessori e complementi per giocare a golf.
                            Le migliori marche, AI PREZZI PIÙ BASSI DELL'ANNO: <a href='/m/taylormade'>TAYLORMADE</a>, <a href='/m/callaway'>CALLAWAY</a>, <a href='/m/ping'>PING</a>, <a href='/m/titleist'>TITLEIST</a>, <a href='/m/footjoy'>FOOTJOY</a>, <a href='/m/puma'>PUMA</a>, <a href='/m/srixon'>SRIXON</a>, <a href='/m/mizuno'>MIZUNO</a>, <a href='/m/odyssey'>ODYSSEY</a>, <a href='/m/skechers'>SKECHERS</a>, <a href='/m/wilson'>WILSON</a>…
                            Non perdere questa occasione per <strong>anticipare i tuoi acquisti natalizi e ottenere i prezzi migliori con le offerte del Black Friday dedicate al golf di Álvarez.
                            Abbiamo esteso il periodo di restituzione fino al 31 gennaio 2026. </strong>",
                    "en" => "From 21 November to 1 December, <strong>Black Friday 2025 arrives at Álvarez's GOLF section. </strong>
                            Enjoy a wide selection of golf inventaries at incredibly discounted prices. Take advantage of Black Friday discounts on drivers, hybrids, fairway woods, iron sets, golf balls, golf trolleys, rangefinders, golf shoes and clothing... and a wide variety of golf accessories and equipment.
                            Top brands, WITH THE LOWEST PRICES OF THE YEAR: <a href='/m/taylormade'>TAYLORMADE</a>, <a href='/m/callaway'>CALLAWAY</a>, <a href='/m/ping'>PING</a>, <a href='/m/titleist'>TITLEIST</a>, <a href='/m/footjoy'>FOOTJOY</a>, <a href='/m/puma'>PUMA</a>, <a href='/m/srixon'>SRIXON</a>, <a href='/m/mizuno'>MIZUNO</a>, <a href='/m/odyssey'>ODYSSEY</a>, <a href='/m/skechers'>SKECHERS</a>, <a href='/m/wilson'>WILSON</a>…
                            Don't miss this opportunity to <strong>get your Christmas shopping done early and get the best prices with Álvarez's Black Friday golf offers.
                            We are extending the returns period until 31 January 2026. </strong>",

                ],
                "texts_after" => [
                    "es" => "Cada Black Friday te traemos la más amplia selección de productos de golf a precios increíblemente rebajados.
                            Disfruta de una gran variedad de artículos de golf a precios irresistibles. Aprovecha los descuentos del Black Friday en drivers, híbridos, maderas de calle, sets de hierros, bolas de golf, carros, medidores de distancia, zapatos, ropa y mucho más. Todo lo que necesitas para practicar tu mejor swing, al mejor precio.
                            Permanece atento al próximo Black Friday. ¡No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de golf en Álvarez.</strong>",

                    "en" => "Every Black Friday we bring you the widest selection of golf inventaries at unbeatable prices.
                            Enjoy a wide range of golf equipment at incredible discounts. Take advantage of Black Friday deals on drivers, hybrids, fairway woods, iron sets, golf balls, trolleys, rangefinders, shoes, clothing, and more — everything you need to play your best game at the best price.
                            Stay tuned for the next Black Friday! Don’t miss this chance to <strong>get ahead on your Christmas shopping and grab the best prices with Álvarez’s Black Friday golf offers.</strong>",

                    "pt" => "Em cada Black Friday trazemos-lhe a mais ampla seleção de produtos de golfe a preços incrivelmente baixos.
                            Desfrute de uma grande variedade de artigos de golfe a preços imperdíveis. Aproveite os descontos da Black Friday em drivers, híbridos, madeiras de fairway, conjuntos de ferros, bolas, carrinhos, medidores de distância, calçado e vestuário de golfe… Tudo o que precisa para o seu jogo, ao melhor preço.
                            Fique atento à próxima Black Friday! Não perca a oportunidade de <strong>adiantar as suas compras de Natal e conseguir os melhores preços com as ofertas de golfe da Black Friday na Álvarez.</strong>",

                    "fr" => "Chaque Black Friday, nous vous proposons la plus large sélection de produits de golf à des prix incroyablement réduits.
                            Profitez d’un vaste choix d’articles de golf à des tarifs exceptionnels. Bénéficiez des réductions du Black Friday sur les drivers, hybrides, bois de parcours, séries de fers, balles de golf, chariots, télémètres, chaussures et vêtements de golf… Tout ce qu’il vous faut pour jouer au meilleur niveau, au meilleur prix.
                            Restez attentif au prochain Black Friday ! Ne manquez pas cette occasion de <strong>prendre de l’avance sur vos achats de Noël et d’obtenir les meilleurs prix avec les offres de golf du Black Friday chez Álvarez.</strong>",

                    "de" => "An jedem Black Friday bieten wir dir die größte Auswahl an Golfprodukten zu unglaublich reduzierten Preisen.
                            Genieße eine große Auswahl an Golfausrüstung zu fantastischen Preisen. Nutze die Black-Friday-Rabatte auf Driver, Hybride, Fairwayhölzer, Eisensätze, Golfbälle, Trolleys, Entfernungsmesser, Schuhe, Kleidung und vieles mehr – alles, was du für dein bestes Spiel brauchst, zum besten Preis.
                            Bleib dran für den nächsten Black Friday! Verpasse nicht die Gelegenheit, <strong>deine Weihnachtseinkäufe frühzeitig zu erledigen und die besten Preise mit den Black-Friday-Angeboten für Golf bei Álvarez zu sichern.</strong>",

                    "it" => "Ogni Black Friday ti offriamo la più ampia selezione di prodotti da golf a prezzi incredibilmente scontati.
                            Goditi un’ampia gamma di articoli da golf a prezzi imperdibili. Approfitta degli sconti del Black Friday su driver, ibridi, legni da fairway, set di ferri, palline da golf, carrelli, telemetri, scarpe, abbigliamento e molto altro. Tutto ciò di cui hai bisogno per giocare al meglio, al miglior prezzo.
                            Rimani aggiornato sul prossimo Black Friday! Non perdere l’occasione di <strong>anticipare i tuoi acquisti di Natale e ottenere i migliori prezzi con le offerte Black Friday di golf da Álvarez.</strong>",

                ],
                "imagenes" => [
                    "es" => [
                        [
                            "title" => "PALOS DE GOLF",
                            "image" => "palos.webp",
                            "url" => "/golf/palos_de_golf"
                        ],
                        [
                            "title" => "BOLAS DE GOLF",
                            "image" => "bolas.webp",
                            "url" => "/golf/bolas_de_golf_y_accesorios"
                        ],
                        [
                            "title" => "BOLSAS DE GOLF",
                            "image" => "bolsas.webp",
                            "url" => "/golf/bolsas_de_golf"
                        ],
                        [
                            "title" => "CARROS DE GOLF",
                            "image" => "carros.webp",
                            "url" => "/golf/carros_de_golf"
                        ],
                        [
                            "title" => "ROPA DE GOLF",
                            "image" => "ropa.webp",
                            "url" => "/golf/ropa"
                        ],
                        [
                            "title" => "CALZADO DE GOLF",
                            "image" => "calzado.webp",
                            "url" => "/golf/calzado_de_golf"
                        ],
                        [
                            "title" => "MEDIDORES DE DISTANCIA",
                            "image" => "medidores.webp",
                            "url" => "/golf/medidores_de_distancia"
                        ],
                        [
                            "title" => "GUANTES DE GOLF",
                            "image" => "guante.webp",
                            "url" => "/golf/guantes_de_golf"
                        ],
                        [
                            "title" => "RELOJES Y SIMULADORES",
                            "image" => "relojes-gps.webp",
                            "url" => "/golf/relojes_gps_y_simuladores_de_golf"
                        ],
                        [
                            "title" => "2",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "en" => [
                        [
                            "title" => "Golf clubs",
                            "image" => "palos.webp",
                            "url" => "/en/golf/golfclubs"
                        ],
                        [
                            "title" => "Golf Balls",
                            "image" => "bolas.webp",
                            "url" => "/en/golf/golf_balls_and_accessories"
                        ],
                        [
                            "title" => "Golf bags",
                            "image" => "bolsas.webp",
                            "url" => "/en/golf/golf_bags"
                        ],
                        [
                            "title" => "Golf Carts",
                            "image" => "carros.webp",
                            "url" => "/en/golf/golf_carts"
                        ],
                        [
                            "title" => "Golf Clothing",
                            "image" => "ropa.webp",
                            "url" => "/en/golf/clothing"
                        ],
                        [
                            "title" => "Golf shoes",
                            "image" => "calzado.webp",
                            "url" => "/en/golf/golf_shoes"
                        ],
                        [
                            "title" => "DISTANCE METERS",
                            "image" => "medidores.webp",
                            "url" => "/en/golf/distance_meters"
                        ],
                        [
                            "title" => "Golf gloves",
                            "image" => "guante.webp",
                            "url" => "/en/golf/golf_gloves"
                        ],
                        [
                            "title" => "GPS GOLF WATCHES",
                            "image" => "relojes-gps.webp",
                            "url" => "/en/golf/golf_gps_watches"
                        ],
                        [
                            "title" => "2",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "pt" => [
                        [
                            "title" => "TACOS DE GOLFE",
                            "image" => "palos.webp",
                            "url" => "/pt/golfe/tacos_de_golfe"
                        ],
                        [
                            "title" => "BOLAS DE GOLFE",
                            "image" => "bolas.webp",
                            "url" => "/pt/golfe/bolas_de_golfe_e_acessorios"
                        ],
                        [
                            "title" => "SACOS DE GOLFE",
                            "image" => "bolsas.webp",
                            "url" => "/pt/golfe/sacos_de_golfe"
                        ],
                        [
                            "title" => "CARROS DE GOLFE",
                            "image" => "carros.webp",
                            "url" => "/pt/golfe/carros_de_golfe"
                        ],
                        [
                            "title" => "ROUPA DE GOLF",
                            "image" => "ropa.webp",
                            "url" => "/pt/golfe/roupa"
                        ],
                        [
                            "title" => "CALÇADO DE GOLFE",
                            "image" => "calzado.webp",
                            "url" => "/pt/golfe/calcado_de_golfe"
                        ],
                        [
                            "title" => "MEDIDORES DE DISTÂNCIA",
                            "image" => "medidores.webp",
                            "url" => "/pt/golfe/medidores_de_distancia"
                        ],
                        [
                            "title" => "LUVAS DE GOLFE",
                            "image" => "guante.webp",
                            "url" => "/pt/golfe/luvas_de_golfe"
                        ],
                        [
                            "title" => "RELÓGIOS y simuladores",
                            "image" => "relojes-gps.webp",
                            "url" => "/pt/golfe/relogios_gps_de_golfe"
                        ],
                        [
                            "title" => "2",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "fr" => [
                        [
                            "title" => "CLUBS DE GOLF",
                            "image" => "palos.webp",
                            "url" => "/fr/golf/clubs_de_golf"
                        ],
                        [
                            "title" => "BALLES DE GOLF",
                            "image" => "bolas.webp",
                            "url" => "/fr/golf/balles_de_golf_et_accessoires"
                        ],
                        [
                            "title" => "SACS DE GOLF",
                            "image" => "bolsas.webp",
                            "url" => "/fr/golf/sacs_de_golf"
                        ],
                        [
                            "title" => "CHARIOTS DE GOLF",
                            "image" => "carros.webp",
                            "url" => "/fr/golf/chariots_de_golf"
                        ],
                        [
                            "title" => "VETEMENTS",
                            "image" => "ropa.webp",
                            "url" => "/fr/golf/vetements"
                        ],
                        [
                            "title" => "CHAUSSURES DE GOLF",
                            "image" => "calzado.webp",
                            "url" => "/fr/golf/chaussures_de_golf"
                        ],
                        [
                            "title" => "TÉLÉMÈTRES",
                            "image" => "medidores.webp",
                            "url" => "/fr/golf/telemetres"
                        ],
                        [
                            "title" => "GANTS DE GOLF",
                            "image" => "guante.webp",
                            "url" => "/fr/golf/gants_de_golf"
                        ],
                        [
                            "title" => "MONTRES GPS DE GOLF",
                            "image" => "relojes-gps.webp",
                            "url" => "/fr/golf/montres_gps_de_golf"
                        ],
                        [
                            "title" => "2",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "de" => [
                        [
                            "title" => "Golfschläger",
                            "image" => "palos.webp",
                            "url" => "/de/golf/golfschlaeger"
                        ],
                        [
                            "title" => "Golfbälle",
                            "image" => "bolas.webp",
                            "url" => "/de/golf/golfballe_und_zubehor"
                        ],
                        [
                            "title" => "Golfbags",
                            "image" => "bolsas.webp",
                            "url" => "/de/golf/golfbags"
                        ],
                        [
                            "title" => "Golftrolleys",
                            "image" => "carros.webp",
                            "url" => "/de/golf/golftrolleys"
                        ],
                        [
                            "title" => "Golfbekleidung",
                            "image" => "ropa.webp",
                            "url" => "/de/golf/kleidung"
                        ],
                        [
                            "title" => "Golfschuhe",
                            "image" => "calzado.webp",
                            "url" => "/de/golf/golfschuhe"
                        ],
                        [
                            "title" => "ENTFERNUNGSMESSERN",
                            "image" => "medidores.webp",
                            "url" => "/de/golf/entfernungsmesser"
                        ],
                        [
                            "title" => "Handschuhe",
                            "image" => "guante.webp",
                            "url" => "/de/golf/handschuhe"
                        ],
                        [
                            "title" => "GPS-UHREN FÜR GOLF",
                            "image" => "relojes-gps.webp",
                            "url" => "/de/golf/golfgpsuhren"
                        ],
                        [
                            "title" => "2",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "it" => [
                        [
                            "title" => "Bastoni da Golf",
                            "image" => "palos.webp",
                            "url" => "/it/golf/bastoni_da_golf"
                        ],
                        [
                            "title" => "Palline da Golf",
                            "image" => "bolas.webp",
                            "url" => "/it/golf/palline_da_golf_accessori"
                        ],
                        [
                            "title" => "Sacche da Golf",
                            "image" => "bolsas.webp",
                            "url" => "/it/golf/sacche_da_golf"
                        ],
                        [
                            "title" => "Carrelli da Golf",
                            "image" => "carros.webp",
                            "url" => "/it/golf/carrelli_da_golf"
                        ],
                        [
                            "title" => "Abbigliamento da Golf",
                            "image" => "ropa.webp",
                            "url" => "/it/golf/abbigliamento_da_golf"
                        ],
                        [
                            "title" => "Scarpe da Golf",
                            "image" => "calzado.webp",
                            "url" => "/it/golf/scarpe_da_golf"
                        ],
                        [
                            "title" => "MISURATORI DI DISTANZA",
                            "image" => "medidores.webp",
                            "url" => "/it/golf/telemetri_da_golf"
                        ],
                        [
                            "title" => "Guanti da Golf",
                            "image" => "guante.webp",
                            "url" => "/it/golf/guanti_da_golf"
                        ],
                        [
                            "title" => "OROLOGI GPS DA GOLF",
                            "image" => "relojes-gps.webp",
                            "url" => "/it/golf/orologi_gps_da_golf"
                        ],
                        [
                            "title" => "2",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                ],
            ];
        }
        if ($deporte == "pesca") {
            $data = [
                "botones" => [
                    "es" => [
                        "texto" => "VER TODO BLACK FRIDAY PESCA",
                        "url" => "/pesca",
                    ],
                    "en" => [
                        "texto" => "SEE ALL BLACK FRIDAY FISHING",
                        "url" => "/en/fishing",
                    ],
                    "pt" => [
                        "texto" => "VER TUDO BLACK FRIDAY PESCA",
                        "url" => "/pt/pesca",
                    ],
                    "fr" => [
                        "texto" => "VOIR LE BLACK FRIDAY PÊCHE",
                        "url" => "/fr/peche",
                    ],
                    "de" => [
                        "texto" => "SEHEN BLACK FRIDAY ANGELN",
                        "url" => "/de/angeln",
                    ],
                    "it" => [
                        "texto" => "VEDI BLACK FRIDAY PESCA",
                        "url" => "/it/pesca",
                    ],

                ],
                "deporte" => "pesca",
                "h1" => [
                    "es" => "Black Friday de PESCA en Álvarez",
                    "en" => "FISHING Black Friday at Álvarez",
                    "pt" => "Black Friday de PESCA na Álvarez",
                    "fr" => "Black Friday PÊCHE chez Álvarez",
                    "de" => "ANGELN Black Friday bei Álvarez",
                    "it" => "Black Friday di PESCA da Álvarez",

                ],
                "after" => [
                    "es" => "Estamos preparando el próximo Black Friday; mientras tanto, aquí tienes una selección de nuestros mejores productos:",
                    "en" => "We are preparing the next Black Friday; in the meantime, here’s a selection of our best inventaries:",
                    "pt" => "Estamos a preparar o próximo Black Friday; entretanto, aqui tem uma seleção dos nossos melhores produtos:",
                    "fr" => "Nous préparons le prochain Black Friday ; en attendant, voici une sélection de nos meilleurs produits :",
                    "de" => "Wir bereiten den nächsten Black Friday vor; in der Zwischenzeit findest du hier eine Auswahl unserer besten Produkte:",
                    "it" => "Stiamo preparando il prossimo Black Friday; nel frattempo, ecco una selezione dei nostri migliori prodotti:",

                ],
                "texts" => [
                    "es" => "Desde el 21 de noviembre hasta el 01 de diciembre llega el <strong>Black Friday 2025 a la sección de PESCA de Álvarez.</strong>
                            Disfruta de una amplia selección de productos para la pesca a precios increíblemente rebajados. Aprovecha los descuentos Black Friday en cañas, carretes, hilos de pesca, señuelos, peces artificiales, vadeadores… y en una gran variedad de accesorios y complementos para pescar, sea cual sea la modalidad de pesca que practiques.
                            Primeras marcas, CON LOS PRECIOS MÁS BAJOS DEL AÑO: <a href='/m/shimano'>SHIMANO</a>, <a href='/m/daiwa'>DAIWA</a>, <a href='/m/hart'>HART</a>, <a href='/m/mitchell'>MITCHELL</a>, <a href='/m/lineaeffe'>LINEAEFFE</a>, <a href='/m/abu_garcia'>ABU GARCÍA</a>, <a href='/m/kali_kunnan'>KALI KUNNAN</a>, <a href='/m/nomura'>NOMURA</a>, <a href='/m/evia'>EVIA</a> …
                            No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de pesca en Álvarez.
                            Ampliamos el periodo de devoluciones hasta el 31 de enero de 2026.</strong>",
                    "pt" => "De 21 de novembro a 1 de dezembro, a <strong>Black Friday 2025 chega à secção PESCA da Álvarez</strong>.
                            Desfrute de uma vasta seleção de produtos de pesca a preços incrivelmente reduzidos. Aproveite os descontos da Black Friday em canas, carretos, linhas de pesca, iscos, peixes artificiais, vadeadores... e uma grande variedade de acessórios e complementos para a pesca, qualquer que seja o tipo de pesca que pratica.
                            Marcas de topo, AOS PREÇOS MAIS BAIXOS DO ANO: <a href='/pt/m/shimano'>SHIMANO</a>, <a href='/pt/m/daiwa'>DAIWA</a>, <a href='/pt/m/hart'>HART</a>, <a href='/pt/m/mitchell'>MITCHELL</a>, <a href='/pt/m/lineaeffe'>LINEAEFFE</a>, <a href='/pt/m/abu_garcia'>ABU GARCÍA</a>, <a href='/pt/m/kali_kunnan'>KALI KUNNAN</a>, <a href='/pt/m/nomura'>NOMURA</a>, <a href='/pt/m/evia'>EVIA</a>...
                            Não perca esta oportunidade de se <strong>adiantar às suas compras de Natal e obter os melhores preços com as ofertas de pesca da Black Friday na Álvarez.
                            Prolongamos o período de devoluções até 31 de janeiro de 2026.</strong>",
                    "fr" => "Du 21 novembre au 1 décembre, le <strong>Black Friday 2025 arrive dans la section PECHE d'Álvarez</strong>.
                            Profitez d'une large sélection de produits de pêche à des prix incroyablement réduits. Profitez des réductions du Black Friday sur les cannes à pêche, les moulinets, les lignes de pêche, les leurres, les poissons artificiels, les waders... et une grande variété d'accessoires et d'accessoires de pêche, quel que soit le type de pêche que vous pratiquez.
                            Les plus grandes marques, aux prix les plus bas de l'année : <a href='/fr/m/shimano'>SHIMANO</a>, <a href='/fr/m/daiwa'>DAIWA</a>, <a href='/fr/m/hart'>HART</a>, <a href='/fr/m/mitchell'>MITCHELL</a>, <a href='/fr/m/lineaeffe'>LINEAEFFE</a>, <a href='/fr/m/abu_garcia'>ABU GARCÍA</a>, <a href='/fr/m/kali_kunnan'>KALI KUNNAN</a>, <a href='/fr/m/nomura'>NOMURA</a>, <a href='/fr/m/evia'>EVIA</a>...
                            Ne manquez pas l'occasion de prendre de <strong>l'avance sur vos achats de Noël et d'obtenir les meilleurs prix avec les offres de pêche du Black Friday chez Álvarez.
                            Nous prolongeons la période de retour jusqu'au 31 janvier 2026.</strong>",
                    "de" => "Vom 21. November bis zum 1. Dezember findet der <strong>,Black Friday 2025 in der Abteilung FISCHEREI von Álvarez statt. </strong>
                            Profitieren Sie von einer großen Auswahl an Angelprodukten zu unglaublich reduzierten Preisen. Nutzen Sie die Black Friday-Rabatte auf Angelruten, Rollen, Angelschnüre, Köder, Kunstköder, Wathosen ... und eine große Auswahl an Zubehör und Accessoires für alle Arten des Angelns.
                            Führende Marken zu den NIEDRIGSTEN PREISEN DES JAHRES: <a href='/m/shimano'>SHIMANO</a>, <a href='/m/daiwa'>DAIWA</a>, <a href='/m/hart'>HART</a>, <a href='/m/mitchell'>MITCHELL</a>, <a href='/m/lineaeffe'>LINEAEFFE</a>, <a href='/m/abu_garcia'>ABU GARCÍA</a>, <a href='/m/kali_kunnan'>KALI KUNNAN</a>, <a href='/m/nomura'>NOMURA</a>, <a href='/m/evia'>EVIA</a> …
                            <strong>Verpassen Sie nicht diese Gelegenheit, Ihre Weihnachtseinkäufe vorzuziehen und mit den Black Friday-Angeboten für Angelbedarf bei Álvarez die besten Preise zu erzielen.
                            Wir verlängern die Rückgabefrist bis zum 31. Januar 2026. </strong>",
                    "it" => "Dal 21 novembre al 1° dicembre arriva il <strong>  Black Friday 2025 nella sezione PESCA di Álvarez. </strong>
                            Approfitta di un'ampia selezione di prodotti per la pesca a prezzi incredibilmente scontati. Approfitta degli sconti del Black Friday su canne, mulinelli, lenze, esche, pesci artificiali, stivali da pesca... e su una vasta gamma di accessori e complementi per la pesca, qualunque sia la modalità di pesca che pratichi.
                            Le migliori marche, AI PREZZI PIÙ BASSI DELL'ANNO: <a href='/m/shimano'>SHIMANO</a>, <a href='/m/daiwa'>DAIWA</a>, <a href='/m/hart'>HART</a>, <a href='/m/mitchell'>MITCHELL</a>, <a href='/m/lineaeffe'>LINEAEFFE</a>, <a href='/m/abu_garcia'>ABU GARCÍA</a>, <a href='/m/kali_kunnan'>KALI KUNNAN</a>, <a href='/m/nomura'>NOMURA</a>, <a href='/m/evia'>EVIA</a> …
                            Non perdere questa occasione per <strong>anticipare i tuoi acquisti natalizi e ottenere i prezzi migliori con le offerte del Black Friday della pesca da Álvarez.
                            Abbiamo esteso il periodo di restituzione fino al 31 gennaio 2026. </strong>",
                    "en" => "From 21 November to 1 December, <strong>  Black Friday 2025 arrives at Álvarez's FISHING section. </strong>
                            Enjoy a wide selection of fishing inventaries at incredibly discounted prices. Take advantage of Black Friday discounts on rods, reels, fishing lines, lures, artificial bait, waders... and a wide variety of fishing accessories and equipment, whatever type of fishing you enjoy.
                            Top brands, WITH THE LOWEST PRICES OF THE YEAR: <a href='/m/shimano'>SHIMANO</a>, <a href='/m/daiwa'>DAIWA</a>, <a href='/m/hart'>HART</a>, <a href='/m/mitchell'>MITCHELL</a>, <a href='/m/lineaeffe'>LINEAEFFE</a>, <a href='/m/abu_garcia'>ABU GARCÍA</a>, <a href='/m/kali_kunnan'>KALI KUNNAN</a>, <a href='/m/nomura'>NOMURA</a>, <a href='/m/evia'>EVIA</a> …
                            Don't miss this opportunity to <strong>get a head start on your Christmas shopping and get the best prices with Álvarez's Black Friday fishing offers.
                            We are extending the return period until 31 January 2026. </strong>",
                ],
                "texts_after" => [
                    "es" => "Cada Black Friday te traemos la más amplia selección de productos de pesca a precios increíblemente rebajados.
                            Disfruta de una gran variedad de artículos para la pesca a precios irresistibles. Aprovecha los descuentos del Black Friday en cañas, carretes, hilos de pesca, señuelos, peces artificiales, vadeadores… y en una amplia gama de accesorios y complementos para pescar, sea cual sea la modalidad que practiques.
                            Permanece atento al próximo Black Friday. ¡No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de pesca en Álvarez.</strong>",

                    "en" => "Every Black Friday we bring you the widest selection of fishing inventaries at unbeatable prices.
                            Enjoy a wide range of fishing gear at incredible discounts. Take advantage of Black Friday deals on rods, reels, fishing lines, lures, artificial baits, waders… and a great variety of accessories and tackle for any type of fishing you practice.
                            Stay tuned for the next Black Friday! Don’t miss this chance to <strong>get ahead on your Christmas shopping and grab the best prices with Álvarez’s Black Friday fishing offers.</strong>",

                    "pt" => "Em cada Black Friday trazemos-lhe a mais ampla seleção de produtos de pesca a preços incrivelmente baixos.
                            Desfrute de uma grande variedade de artigos de pesca a preços imperdíveis. Aproveite os descontos da Black Friday em canas, carretos, linhas de pesca, iscos, peixes artificiais, vadeadores… e numa vasta gama de acessórios e complementos para pescar, qualquer que seja a modalidade que pratique.
                            Fique atento à próxima Black Friday! Não perca a oportunidade de <strong>adiantar as suas compras de Natal e conseguir os melhores preços com as ofertas de pesca da Black Friday na Álvarez.</strong>",

                    "fr" => "Chaque Black Friday, nous vous proposons la plus large sélection de produits de pêche à des prix incroyablement réduits.
                            Profitez d’un vaste choix d’articles de pêche à des tarifs exceptionnels. Bénéficiez des réductions du Black Friday sur les cannes, moulinets, fils de pêche, leurres, poissons artificiels, waders… ainsi que sur une grande variété d’accessoires et d’équipements pour toutes les techniques de pêche.
                            Restez attentif au prochain Black Friday ! Ne manquez pas cette occasion de <strong>prendre de l’avance sur vos achats de Noël et d’obtenir les meilleurs prix avec les offres de pêche du Black Friday chez Álvarez.</strong>",

                    "de" => "An jedem Black Friday bieten wir dir die größte Auswahl an Angelprodukten zu unglaublich reduzierten Preisen.
                            Genieße eine große Auswahl an Angelausrüstung zu fantastischen Preisen. Nutze die Black-Friday-Rabatte auf Angelruten, Rollen, Angelschnüre, Köder, Kunstfische, Wathosen… sowie auf eine Vielzahl von Zubehör und Ausrüstung für jede Angelmethode.
                            Bleib dran für den nächsten Black Friday! Verpasse nicht die Gelegenheit, <strong>deine Weihnachtseinkäufe frühzeitig zu erledigen und die besten Preise mit den Black-Friday-Angeboten für Angeln bei Álvarez zu sichern.</strong>",

                    "it" => "Ogni Black Friday ti offriamo la più ampia selezione di prodotti per la pesca a prezzi incredibilmente scontati.
                            Goditi un’ampia gamma di articoli per la pesca a prezzi imperdibili. Approfitta degli sconti del Black Friday su canne, mulinelli, fili da pesca, esche, pesci artificiali, waders… e su un’ampia varietà di accessori e attrezzature per qualsiasi tipo di pesca.
                            Rimani aggiornato sul prossimo Black Friday! Non perdere l’occasione di <strong>anticipare i tuoi acquisti di Natale e ottenere i migliori prezzi con le offerte Black Friday di pesca da Álvarez.</strong>",

                ],
                "imagenes" => [
                    "es" => [
                        [
                            "title" => "CAÑAS",
                            "image" => "canhas.webp",
                            "url" => "/pesca/canas"
                        ],
                        [
                            "title" => "CARRETES",
                            "image" => "carrete.webp",
                            "url" => "/pesca/carretes"
                        ],
                        [
                            "title" => "HILOS DE PESCA",
                            "image" => "hilos.webp",
                            "url" => "/pesca/hilos"
                        ],
                        [
                            "title" => "SEÑUELOS",
                            "image" => "senhuelos.webp",
                            "url" => "/pesca/peces_artificiales_y_senuelos_pesca"
                        ],
                        [
                            "title" => "PATOS",
                            "image" => "pato.webp",
                            "url" => "/pesca/patos_de_pesca"
                        ],
                        [
                            "title" => "BOTAS Y VADEADORES",
                            "image" => "botas.webp",
                            "url" => "/pesca/botas_y_vadeadores"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "ROPA DE PESCA",
                            "image" => "ropa.webp",
                            "url" => "/pesca/ropa_y_complementos"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "en" => [
                        [
                            "title" => "Rods",
                            "image" => "canhas.webp",
                            "url" => "/en/fishing/rods"
                        ],
                        [
                            "title" => "Reels",
                            "image" => "carrete.webp",
                            "url" => "/en/fishing/reels"
                        ],
                        [
                            "title" => "Fishing lines",
                            "image" => "hilos.webp",
                            "url" => "/en/fishing/fishing_lines"
                        ],
                        [
                            "title" => "Artificial fish",
                            "image" => "senhuelos.webp",
                            "url" => "/en/fishing/artificial_fish_and_fishing_lures"
                        ],
                        [
                            "title" => "Float Tubes",
                            "image" => "pato.webp",
                            "url" => "/en/fishing/float_tubes"
                        ],
                        [
                            "title" => "Boots and Waders",
                            "image" => "botas.webp",
                            "url" => "/en/fishing/boots_and_waders"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "Clothes",
                            "image" => "ropa.webp",
                            "url" => "/en/fishing/clothes_and_complements"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "pt" => [
                        [
                            "title" => "CANAS",
                            "image" => "canhas.webp",
                            "url" => "/pt/pesca/canas"
                        ],
                        [
                            "title" => "CARRETOS",
                            "image" => "carrete.webp",
                            "url" => "/pt/pesca/carretos"
                        ],
                        [
                            "title" => "LINHAS",
                            "image" => "hilos.webp",
                            "url" => "/pt/pesca/linhas"
                        ],
                        [
                            "title" => "ISCOS",
                            "image" => "senhuelos.webp",
                            "url" => "/pt/pesca/iscos_e_peixes_artificiais"
                        ],
                        [
                            "title" => "PATOS",
                            "image" => "pato.webp",
                            "url" => "/pt/pesca/patos"
                        ],
                        [
                            "title" => "BOTAS E VADEADORES",
                            "image" => "botas.webp",
                            "url" => "/pt/pesca/botas_e_vadeadores"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "ROUPA",
                            "image" => "ropa.webp",
                            "url" => "/pt/pesca/roupa_e_complementos"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "fr" => [
                        [
                            "title" => "CANNES",
                            "image" => "canhas.webp",
                            "url" => "/fr/peche/cannes_a_peche"
                        ],
                        [
                            "title" => "MOULINETS",
                            "image" => "carrete.webp",
                            "url" => "/fr/peche/moulinets"
                        ],
                        [
                            "title" => "FILS",
                            "image" => "hilos.webp",
                            "url" => "/fr/peche/fils_de_peche"
                        ],
                        [
                            "title" => "LEURRES",
                            "image" => "senhuelos.webp",
                            "url" => "/fr/peche/poissons_artificiels_et_leurres_de_peche"
                        ],
                        [
                            "title" => "FLOAT TUBES",
                            "image" => "pato.webp",
                            "url" => "/fr/peche/float_tubes"
                        ],
                        [
                            "title" => "BOTTES E WADERS",
                            "image" => "botas.webp",
                            "url" => "/fr/peche/bottes_et_waders"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "VETEMENTS",
                            "image" => "ropa.webp",
                            "url" => "/fr/peche/vetements_et_accessoires"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "de" => [
                        [
                            "title" => "Angelruten",
                            "image" => "canhas.webp",
                            "url" => "/de/angeln/angelruten"
                        ],
                        [
                            "title" => "Angelrollen",
                            "image" => "carrete.webp",
                            "url" => "/de/angeln/angelrollen"
                        ],
                        [
                            "title" => "Angelschnur",
                            "image" => "hilos.webp",
                            "url" => "/de/angeln/angelschnur"
                        ],
                        [
                            "title" => "Kunstköder",
                            "image" => "senhuelos.webp",
                            "url" => "/de/angeln/kunstkoeder_und_angelkoeder"
                        ],
                        [
                            "title" => "Belly Boote",
                            "image" => "pato.webp",
                            "url" => "/de/angeln/belly_boote"
                        ],
                        [
                            "title" => "Wathosen",
                            "image" => "botas.webp",
                            "url" => "/de/angeln/wathosen"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "Angelbekleidung",
                            "image" => "ropa.webp",
                            "url" => "/de/angeln/angelbekleidung_und_accessoires"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "it" => [
                        [
                            "title" => "Canne da Pesca",
                            "image" => "canhas.webp",
                            "url" => "/it/pesca/canne_da_pesca"
                        ],
                        [
                            "title" => "Mulinelli da Pesca",
                            "image" => "carrete.webp",
                            "url" => "/it/pesca/mulinelli_da_pesca"
                        ],
                        [
                            "title" => "Fili da Pesca",
                            "image" => "hilos.webp",
                            "url" => "/it/pesca/fili_da_pesca"
                        ],
                        [
                            "title" => "Esche e artificiali",
                            "image" => "senhuelos.webp",
                            "url" => "/it/pesca/esche_e_artificiali_per_la_pesca"
                        ],
                        [
                            "title" => "Belly Boat da Pesca",
                            "image" => "pato.webp",
                            "url" => "/it/pesca/belly_boat_da_pesca_"
                        ],
                        [
                            "title" => "Waders da Pesca",
                            "image" => "botas.webp",
                            "url" => "/it/pesca/waders_da_pesca"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "Abbigliamento",
                            "image" => "ropa.webp",
                            "url" => "/it/pesca/abbigliamento_e_accessori"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],

                ],
            ];
        }
        if ($deporte == "hipica") {
            $data = [
                "botones" => [
                    "es" => [
                        "texto" => "VER TODO BLACK FRIDAY HÍPICA",
                        "url" => "/hipica",
                    ],
                    "en" => [
                        "texto" => "SEE BLACK FRIDAY EQUESTRIAN",
                        "url" => "/en/horse_riding",
                    ],
                    "pt" => [
                        "texto" => "VER BLACK FRIDAY EQUITAÇÃO",
                        "url" => "/pt/equitacao",
                    ],
                    "fr" => [
                        "texto" => "VOIR LE BLACK FRIDAY ÉQUITATION",
                        "url" => "/fr/equitation",
                    ],
                    "de" => [
                        "texto" => "SEHEN BLACK FRIDAY REITSPORT",
                        "url" => "/de/reiten",
                    ],
                    "it" => [
                        "texto" => "VEDI BLACK FRIDAY EQUITAZIONE",
                        "url" => "/it/equitazione",
                    ],

                ],
                "deporte" => "hipica",
                "h1" => [
                    "es" => "Black Friday de HÍPICA en Álvarez",
                    "en" => "EQUESTRIAN Black Friday at Álvarez",
                    "pt" => "Black Friday de EQUITAÇÃO na Álvarez",
                    "fr" => "Black Friday ÉQUITATION chez Álvarez",
                    "de" => "REITSPORT Black Friday bei Álvarez",
                    "it" => "Black Friday di EQUITAZIONE da Álvarez",

                ],
                "after" => [
                    "es" => "Estamos preparando el próximo Black Friday; mientras tanto, aquí tienes una selección de nuestros mejores productos:",
                    "en" => "We are preparing the next Black Friday; in the meantime, here’s a selection of our best inventaries:",
                    "pt" => "Estamos a preparar o próximo Black Friday; entretanto, aqui tem uma seleção dos nossos melhores produtos:",
                    "fr" => "Nous préparons le prochain Black Friday ; en attendant, voici une sélection de nos meilleurs produits :",
                    "de" => "Wir bereiten den nächsten Black Friday vor; in der Zwischenzeit findest du hier eine Auswahl unserer besten Produkte:",
                    "it" => "Stiamo preparando il prossimo Black Friday; nel frattempo, ecco una selezione dei nostri migliori prodotti:",

                ],
                "texts" => [
                    "es" => "Desde el 21 de noviembre hasta el 01 de diciembre llega el <strong>Black Friday 2025 a la sección de HIPICA de Álvarez.</strong>
                            Disfruta de una amplia selección de productos para la práctica de la equitación a precios increíblemente rebajados. Aprovecha los descuentos Black Friday en sillas de montar, mantas para caballo, sudaderos, cinchas, ropa de equitación, cascos para el jinete… Todo lo que necesita el jinete y el caballo al mejor precio.
                            Primeras marcas, CON LOS PRECIOS MÁS BAJOS DEL AÑO: <a href='/m/zaldi'>ZALDI</a>, <a href='/m/marjoman'>MARJOMAN</a>, <a href='/m/equitheme'>EQUITHEME</a>, <a href='/m/kingsland'>KINGSLAND</a>, <a href='/m/br_esquestrian'>BR EQUESTRIAN</a>, <a href='/m/hit_air'>HIT AIR</a>, <a href='/m/horze'>HORZE</a>, <a href='/m/kep_italia'>KEP ITALIA</a>, <a href='/m/eskadron'>ESKADRON</a>, <a href='/m/anky'>ANKY</a> …
                            No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de hipica en Álvarez.
                            Ampliamos el periodo de devoluciones hasta el 31 de enero de 2026.</strong>",
                    "pt" => "De 21 de novembro a 2 de dezembro, a <strong>Black Friday 2025 chega à secção EQUITAÇÃO da Álvarez</strong>.
                            Desfrute de uma vasta seleção de produtos para equitação a preços incrivelmente baixos. Aproveite os descontos da Black Friday em selas, mantas para cavalos, almofadas de sela, cintos de sela, vestuário de equitação, capacetes para o cavaleiro... Tudo o que o cavaleiro e o cavalo precisam ao melhor preço.
                            Marcas de topo, AOS PREÇOS MAIS BAIXOS DO ANO: <a href='/pt/m/zaldi'>ZALDI</a>, <a href='/pt/m/marjoman'>MARJOMAN</a>, <a href='/pt/m/equitheme'>EQUITHEME</a>, <a href='/pt/m/kingsland'>KINGSLAND</a>, <a href='/pt/m/br_esquestrian'>BR EQUESTRIAN</a>, <a href='/pt/m/hit_air'>HIT AIR</a>, <a href='/pt/m/horze'>HORZE</a>, <a href='/pt/m/kep_italia'>KEP ITALIA</a>, <a href='/pt/m/eskadron'>ESKADRON</a>, <a href='/pt/m/anky'>ANKY</a> ...
                            Não perca esta oportunidade de se <strong>adiantar às suas compras de Natal e obter os melhores preços com as ofertas de equitação da Black Friday na Álvarez.
                            Prolongamos o período de devoluções até 31 de janeiro de 2026.</strong>",
                    "fr" => "Du 21 novembre au 2 décembre, le <strong>Black Friday 2025 arrive dans la section ÉQUITATION d'Álvarez</strong>.
                            Profitez d'une large sélection de produits pour l'équitation à des prix incroyablement réduits. Profitez des réductions du Black Friday sur les selles, les couvertures pour chevaux,  les tapis de selle, les sangles, les vêtements d'équitation, les casques pour le cavalier... Tout ce dont le cavalier et le cheval ont besoin au meilleur prix.
                            Grandes marques, aux prix les plus bas de l'année : <a href='/fr/m/zaldi'>ZALDI</a>, <a href='/fr/m/marjoman'>MARJOMAN</a>, <a href='/fr/m/equitheme'>EQUITHEME</a>, <a href='/fr/m/kingsland'>KINGSLAND</a>, <a href='/fr/m/br_esquestrian'>BR EQUESTRIAN</a>, <a href='/fr/m/hit_air'>HIT AIR</a>, <a href='/fr/m/horze'>HORZE</a>, <a href='/fr/m/kep_italia'>KEP ITALIA</a>, <a href='/fr/m/eskadron'>ESKADRON</a>, <a href='/fr/m/anky'>ANKY</a> ...
                            Ne manquez pas l'occasion de prendre de <strong>l'avance sur vos achats de Noël et d'obtenir les meilleurs prix avec les offres de équitation du Black Friday chez Álvarez.
                            Nous prolongeons la période de retour jusqu'au 31 janvier 2026.</strong>",
                    "de" => "Vom 21. November bis zum 1. Dezember findet der <strong>Black Friday 2025 in der Reitsportabteilung von Álvarez statt.</strong>
                            Genießen Sie eine große Auswahl an Produkten für den Reitsport zu unglaublich reduzierten Preisen. Profitieren Sie von den Black Friday-Rabatten auf Sättel, Pferdedecken, Schweißdecken, Sattelgurte, Reitbekleidung, Reithelme ... Alles, was Reiter und Pferd brauchen, zum besten Preis.
                            Führende Marken, ZU DEN NIEDRIGSTEN PREISEN DES JAHRES: <a href='/m/zaldi'>ZALDI</a>, <a href='/m/marjoman'>MARJOMAN</a>, <a href='/m/equitheme'>EQUITHEME</a>, <a href='/m/kingsland'>KINGSLAND</a>, <a href='/m/br_esquestrian'>BR EQUESTRIAN</a>, <a href='/m/hit_air'>HIT AIR</a>, <a href='/m/horze'>HORZE</a>, <a href='/m/kep_italia'>KEP ITALIA</a>, <a href='/m/eskadron'>ESKADRON</a>, <a href='/m/anky'>ANKY</a> …
                            <strong>Verpassen Sie nicht diese Gelegenheit, Ihre Weihnachtseinkäufe vorzuziehen und die besten Preise mit den Black Friday-Angeboten für Reitsport bei Álvarez zu erhalten.
                            Wir verlängern die Rückgabefrist bis zum 31. Januar 2026.</strong>",
                    "it" => "Dal 21 novembre al 1° dicembre arriva il <strong>Black Friday 2025 nella sezione EQUITAZIONE di Álvarez. </strong>
                            Approfitta di un'ampia selezione di prodotti per l'equitazione a prezzi incredibilmente scontati. Approfitta degli sconti del Black Friday su selle, coperte per cavalli, sudari, cinture, abbigliamento da equitazione, caschi per cavalieri... Tutto ciò di cui hanno bisogno il cavaliere e il cavallo al miglior prezzo.
                            Marche leader, CON I PREZZI PIÙ BASSI DELL'ANNO: <a href='/m/zaldi'>ZALDI</a>, <a href='/m/marjoman'>MARJOMAN</a>, <a href='/m/equitheme'>EQUITHEME</a>, <a href='/m/kingsland'>KINGSLAND</a>, <a href='/m/br_esquestrian'>BR EQUESTRIAN</a>, <a href='/m/hit_air'>HIT AIR</a>, <a href='/m/horze'>HORZE</a>, <a href='/m/kep_italia'>KEP ITALIA</a>, <a href='/m/eskadron'>ESKADRON</a>, <a href='/m/anky'>ANKY</a> …
                            Non perdere questa occasione per <strong>anticipare i tuoi acquisti natalizi e ottenere i prezzi migliori con le offerte del Black Friday dell'equitazione di Álvarez.
                            Abbiamo esteso il periodo di restituzione fino al 31 gennaio 2026.</strong>",
                    "en" => "From 21 November to 1 December, <strong>,Black Friday 2025 arrives at Álvarez's EQUESTRIAN section.</strong>
                            Enjoy a wide selection of horse riding inventaries at incredibly discounted prices. Take advantage of Black Friday discounts on saddles, horse blankets, sweat pads, girths, riding apparel, riding helmets... Everything the rider and horse need at the best price.
                            Top brands, WITH THE LOWEST PRICES OF THE YEAR: <a href='/m/zaldi'>ZALDI</a>, <a href='/m/marjoman'>MARJOMAN</a>, <a href='/m/equitheme'>EQUITHEME</a>, <a href='/m/kingsland'>KINGSLAND</a>, <a href='/m/br_esquestrian'>BR EQUESTRIAN</a>, <a href='/m/hit_air'>HIT AIR</a>, <a href='/m/horze'>HORZE</a>, <a href='/m/kep_italia'>KEP ITALIA</a>, <a href='/m/eskadron'>ESKADRON</a>, <a href='/m/anky'>ANKY</a> …
                            Don't miss this opportunity to <strong>get your Christmas shopping done early and get the best prices with Álvarez's Black Friday equestrian offers.
                            We are extending the returns period until 31 January 2026.</strong>",
                ],
                "texts_after" => [
                    "es" => "Cada Black Friday te traemos la más amplia selección de productos de hípica a precios increíblemente rebajados.
                            Disfruta de una gran variedad de artículos para la práctica de la equitación a precios irresistibles. Aprovecha los descuentos del Black Friday en sillas de montar, mantas para caballo, sudaderos, cinchas, ropa de equitación, cascos para el jinete… Todo lo que necesitan el jinete y el caballo al mejor precio.
                            Permanece atento al próximo Black Friday. ¡No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de hípica en Álvarez.</strong>",

                    "en" => "Every Black Friday we bring you the widest selection of equestrian inventaries at unbeatable prices.
                            Enjoy a wide range of items for horse riding at incredible discounts. Take advantage of Black Friday deals on saddles, horse blankets, saddle pads, girths, riding apparel, helmets… Everything the rider and horse need, at the best price.
                            Stay tuned for the next Black Friday! Don’t miss this chance to <strong>get ahead on your Christmas shopping and grab the best prices with Álvarez’s Black Friday equestrian offers.</strong>",

                    "pt" => "Em cada Black Friday trazemos-lhe a mais ampla seleção de produtos equestres a preços incrivelmente baixos.
                            Desfrute de uma grande variedade de artigos para equitação a preços imperdíveis. Aproveite os descontos da Black Friday em selas, mantas para cavalos, almofadas de sela, cintos, roupas de equitação e capacetes… Tudo o que o cavaleiro e o cavalo precisam ao melhor preço.
                            Fique atento à próxima Black Friday! Não perca a oportunidade de <strong>adiantar as suas compras de Natal e conseguir os melhores preços com as ofertas de equitação da Black Friday na Álvarez.</strong>",

                    "fr" => "Chaque Black Friday, nous vous proposons la plus large sélection de produits équestres à des prix incroyablement réduits.
                            Profitez d’un vaste choix d’articles pour l’équitation à des tarifs exceptionnels. Bénéficiez des réductions du Black Friday sur les selles, couvertures pour chevaux, tapis de selle, sangles, vêtements d’équitation, casques… Tout ce dont le cavalier et le cheval ont besoin, au meilleur prix.
                            Restez attentif au prochain Black Friday ! Ne manquez pas cette occasion de <strong>prendre de l’avance sur vos achats de Noël et d’obtenir les meilleurs prix avec les offres équestres du Black Friday chez Álvarez.</strong>",

                    "de" => "An jedem Black Friday bieten wir dir die größte Auswahl an Reitsportprodukten zu unglaublich reduzierten Preisen.
                            Genieße eine große Auswahl an Artikeln für den Reitsport zu fantastischen Preisen. Nutze die Black-Friday-Rabatte auf Sättel, Pferdedecken, Schabracken, Sattelgurte, Reitbekleidung, Helme… Alles, was Reiter und Pferd brauchen, zum besten Preis.
                            Bleib dran für den nächsten Black Friday! Verpasse nicht die Gelegenheit, <strong>deine Weihnachtseinkäufe frühzeitig zu erledigen und die besten Preise mit den Black-Friday-Angeboten für Reitsport bei Álvarez zu sichern.</strong>",

                    "it" => "Ogni Black Friday ti offriamo la più ampia selezione di prodotti di equitazione a prezzi incredibilmente scontati.
                            Goditi un’ampia gamma di articoli per la pratica dell’equitazione a prezzi imperdibili. Approfitta degli sconti del Black Friday su selle, coperte per cavalli, sottosella, cinghie, abbigliamento da equitazione e caschi… Tutto ciò di cui cavallo e cavaliere hanno bisogno, al miglior prezzo.
                            Rimani aggiornato sul prossimo Black Friday! Non perdere l’occasione di <strong>anticipare i tuoi acquisti di Natale e ottenere i migliori prezzi con le offerte Black Friday di equitazione da Álvarez.</strong>",

                ],
                "imagenes" => [
                    "es" => [
                        [
                            "title" => "SILLAS DE MONTAR",
                            "image" => "sillas.webp",
                            "url" => "/hipica/sillas_de_montar"
                        ],
                        [
                            "title" => "PANTALONES DE MONTAR",
                            "image" => "pantalones.webp",
                            "url" => "/hipica/ropa_y_complementos-pantalones"
                        ],
                        [
                            "title" => "CALZADO HÍPICO",
                            "image" => "botas.webp",
                            "url" => "/hipica/calzado_hipica"
                        ],
                        [
                            "title" => "CASCOS",
                            "image" => "cascos.webp",
                            "url" => "/hipica/cascos"
                        ],
                        [
                            "title" => "HIGIENE Y SALUD",
                            "image" => "limpieza.webp",
                            "url" => "/hipica/higiene_y_salud"
                        ],
                        [
                            "title" => "TODO PARA EL CABALLO",
                            "image" => "todo-caballo.webp",
                            "url" => "/hipica/equipo_del_caballo"
                        ],
                        [
                            "title" => "CHALECOS PROTECTORES",
                            "image" => "chalecos.webp",
                            "url" => "/hipica/chalecos_protectores"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "MANTAS",
                            "image" => "mantas.webp",
                            "url" => "/hipica/mantas"
                        ],

                    ],
                    "en" => [
                        [
                            "title" => "Saddles",
                            "image" => "sillas.webp",
                            "url" => "/en/horse_riding/saddles"
                        ],
                        [
                            "title" => "Pants",
                            "image" => "pantalones.webp",
                            "url" => "/en/horse_riding/clothes_and_complements-pants"
                        ],
                        [
                            "title" => "Equestrian Footwear",
                            "image" => "botas.webp",
                            "url" => "/en/horse_riding/equestrian_footwear"
                        ],
                        [
                            "title" => "Helmets",
                            "image" => "cascos.webp",
                            "url" => "/en/horse_riding/helmets"
                        ],
                        [
                            "title" => "Hygiene and health",
                            "image" => "limpieza.webp",
                            "url" => "/en/horse_riding/hygiene_and_health"
                        ],
                        [
                            "title" => "Horse Equipment",
                            "image" => "todo-caballo.webp",
                            "url" => "/en/horse_riding/horse_equipment"
                        ],
                        [
                            "title" => "PROTECTIVE VESTS",
                            "image" => "chalecos.webp",
                            "url" => "/en/horse_riding/protective_vests"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "BLANKETS",
                            "image" => "mantas.webp",
                            "url" => "/en/horse_riding/blankets"
                        ],

                    ],
                    "pt" => [
                        [
                            "title" => "SELAS",
                            "image" => "sillas.webp",
                            "url" => "/pt/equitacao/selas_e_selins"
                        ],
                        [
                            "title" => "CALÇAS EQUITAÇÃO",
                            "image" => "pantalones.webp",
                            "url" => "/pt/equitacao/roupa_e_complementos-calcas"
                        ],
                        [
                            "title" => "CALÇADO EQUITAÇÃO",
                            "image" => "botas.webp",
                            "url" => "/pt/equitacao/calcado_equitacao"
                        ],
                        [
                            "title" => "TOQUES EQUITAÇÃO",
                            "image" => "cascos.webp",
                            "url" => "/pt/equitacao/toques_e_complementos"
                        ],
                        [
                            "title" => "HIGIENE E SAÚDE",
                            "image" => "limpieza.webp",
                            "url" => "/pt/equitacao/higiene_e_saude"
                        ],
                        [
                            "title" => "EQUIPAMENTO DO CAVALO",
                            "image" => "todo-caballo.webp",
                            "url" => "/pt/equitacao/equipamento_do_cavalo"
                        ],
                        [
                            "title" => "COLETES PROTETORES",
                            "image" => "chalecos.webp",
                            "url" => "/pt/equitacao/coletes_protetores"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "COBREJÕES",
                            "image" => "mantas.webp",
                            "url" => "/pt/equitacao/mantas_cobrejoes"
                        ],

                    ],
                    "fr" => [
                        [
                            "title" => "SELLES",
                            "image" => "sillas.webp",
                            "url" => "/fr/equitation/selles"
                        ],
                        [
                            "title" => "PANTALON",
                            "image" => "pantalones.webp",
                            "url" => "/fr/equitation/vetements_et_accessoires-pantalons"
                        ],
                        [
                            "title" => "CHAUSSURES D'ÉQUITATION",
                            "image" => "botas.webp",
                            "url" => "/fr/equitation/chaussures_d_equitation"
                        ],
                        [
                            "title" => "CASQUES",
                            "image" => "cascos.webp",
                            "url" => "/fr/equitation/casques"
                        ],
                        [
                            "title" => "HYGIÈNE ET SANTÉ",
                            "image" => "limpieza.webp",
                            "url" => "/fr/equitation/hygiene_et_sante"
                        ],
                        [
                            "title" => "ÉQUIPEMENT DU CHEVAL",
                            "image" => "todo-caballo.webp",
                            "url" => "/fr/equitation/equipement_pour_le_cheval"
                        ],
                        [
                            "title" => "GILETS DE PROTECTION",
                            "image" => "chalecos.webp",
                            "url" => "/fr/equitation/gilets_de_protection"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "COUVERTURES",
                            "image" => "mantas.webp",
                            "url" => "/fr/equitation/couvertures"
                        ],

                    ],
                    "de" => [
                        [
                            "title" => "Sättel",
                            "image" => "sillas.webp",
                            "url" => "/de/reiten/sattel"
                        ],
                        [
                            "title" => "Reithosen",
                            "image" => "pantalones.webp",
                            "url" => "/de/reiten/bekleidung_und_accesoires-reithosen"
                        ],
                        [
                            "title" => "Reitschuhe",
                            "image" => "botas.webp",
                            "url" => "/de/reiten/reitschuhe"
                        ],
                        [
                            "title" => "Reithelme",
                            "image" => "cascos.webp",
                            "url" => "/de/reiten/reithelme"
                        ],
                        [
                            "title" => "Hygiene und Gesundheit",
                            "image" => "limpieza.webp",
                            "url" => "/de/reiten/hygiene_und_gesundheit"
                        ],
                        [
                            "title" => "Pferdeausrüstung",
                            "image" => "todo-caballo.webp",
                            "url" => "/de/reiten/pferdeausrustung"
                        ],
                        [
                            "title" => "SICHERHEITSWESTEN",
                            "image" => "chalecos.webp",
                            "url" => "/de/reiten/sicherheitswesten"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "PFERDEDECKEN",
                            "image" => "mantas.webp",
                            "url" => "/de/reiten/pferdedecken"
                        ],

                    ],
                    "it" => [
                        [
                            "title" => "Selle",
                            "image" => "sillas.webp",
                            "url" => "/it/equitazione/selle"
                        ],
                        [
                            "title" => "Pantaloni",
                            "image" => "pantalones.webp",
                            "url" => "/it/equitazione/abbigliamento_e_accessori-pantaloni"
                        ],
                        [
                            "title" => "Calzature per Equitazione",
                            "image" => "botas.webp",
                            "url" => "/it/equitazione/calzature_per_equitazione"
                        ],
                        [
                            "title" => "Caschi da Equitazione",
                            "image" => "cascos.webp",
                            "url" => "/it/equitazione/caschi_da_equitazione"
                        ],
                        [
                            "title" => "Salute e Igiene",
                            "image" => "limpieza.webp",
                            "url" => "/it/equitazione/salute_e_igiene"
                        ],
                        [
                            "title" => "Attrezzatura cavallo",
                            "image" => "todo-caballo.webp",
                            "url" => "/it/equitazione/equipaggiamento_per_il_cavallo"
                        ],
                        [
                            "title" => "CORPETTO PROTETTIVO",
                            "image" => "chalecos.webp",
                            "url" => "/it/equitazione/corpetto_protettivo_da_equitazione"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "COPERTE",
                            "image" => "mantas.webp",
                            "url" => "/it/equitazione/coperte"
                        ],

                    ],
                ],
            ];
        }
        if ($deporte == "buceo") {
            $data = [
                "botones" => [
                    "es" => [
                        "texto" => "VER TODO BLACK FRIDAY BUCEO",
                        "url" => "/buceo",
                    ],
                    "en" => [
                        "texto" => "SEE ALL BLACK FRIDAY DIVING",
                        "url" => "/en/diving",
                    ],
                    "pt" => [
                        "texto" => "VER BLACK FRIDAY MERGULHO",
                        "url" => "/pt/mergulho",
                    ],
                    "fr" => [
                        "texto" => "VOIR LE BLACK FRIDAY PLONGÉE",
                        "url" => "/fr/plongee",
                    ],
                    "de" => [
                        "texto" => "SEHEN BLACK FRIDAY TAUCHEN",
                        "url" => "/de/tauchen",
                    ],
                    "it" => [
                        "texto" => "VEDI BLACK FRIDAY SUBACQUEA",
                        "url" => "/it/subacquea",
                    ],

                ],
                "deporte" => "buceo",
                "h1" => [
                    "es" => "Black Friday de BUCEO en Álvarez",
                    "en" => "DIVING Black Friday at Álvarez",
                    "pt" => "Black Friday de MERGULHO na Álvarez",
                    "fr" => "Black Friday PLONGÉE chez Álvarez",
                    "de" => "TAUCHEN Black Friday bei Álvarez",
                    "it" => "Black Friday di IMMERSIONI da Álvarez",

                ],
                "after" => [
                    "es" => "Estamos preparando el próximo Black Friday; mientras tanto, aquí tienes una selección de nuestros mejores productos:",
                    "en" => "We are preparing the next Black Friday; in the meantime, here’s a selection of our best inventaries:",
                    "pt" => "Estamos a preparar o próximo Black Friday; entretanto, aqui tem uma seleção dos nossos melhores produtos:",
                    "fr" => "Nous préparons le prochain Black Friday ; en attendant, voici une sélection de nos meilleurs produits :",
                    "de" => "Wir bereiten den nächsten Black Friday vor; in der Zwischenzeit findest du hier eine Auswahl unserer besten Produkte:",
                    "it" => "Stiamo preparando il prossimo Black Friday; nel frattempo, ecco una selezione dei nostri migliori prodotti:",

                ],
                "texts" => [
                    "es" => "Desde el 21 de noviembre hasta el 01 de diciembre llega el <strong>Black Friday 2025 a la sección de BUCEO de Álvarez.</strong>
                            Disfruta de una amplia selección de productos para la práctica del buceo, snorkel o submarinismo, a precios increíblemente rebajados. Aprovecha los descuentos Black Friday en trajes de buceo, jackets, reguladores, ordenadores, octopus, aletas, máscaras de buceo… Así como todo tipo de complementos necesarios para bucear, al mejor precio.
                            Primeras marcas, CON LOS PRECIOS MÁS BAJOS DEL AÑO: <a href='/m/aqualung'>AQUALUNG</a>, <a href='/m/cressi'>CRESSI</a>, <a href='/m/mares'>MARES</a>, <a href='/m/seac'>SEAC</a>, <a href='/m/scubapro'>SCUBAPRO</a>, <a href='/m/omer'>OMER</a>…
                            No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de buceo en Álvarez.
                            Ampliamos el periodo de devoluciones hasta el 31 de enero de 2026.</strong>",
                    "pt" => "De 21 de novembro a 2 de dezembro, a <strong>Black Friday 2025 chega à secção MERGULHO da Álvarez</strong>.
                            Desfrute de uma vasta seleção de produtos para mergulho, snorkelling e mergulho com escafandro, a preços incrivelmente reduzidos. Aproveite os descontos da Black Friday em fatos de mergulho, coletes, reguladores, computadores, octopus, barbatanas, máscaras de mergulho... Assim como todo o tipo de acessórios necessários para mergulhar, ao melhor preço.
                            Marcas de topo, AOS MENORES PREÇOS DO ANO: <a href='/pt/m/aqualung'>AQUALUNG</a>, <a href='/pt/m/cressi'>CRESSI</a>, <a href='/pt/m/mares'>MARES</a>, <a href='/pt/m/seac'>SEAC</a>, <a href='/pt/m/scubapro'>SCUBAPRO</a>, <a href='/pt/m/omer'>OMER</a>…
                            Não perca esta oportunidade de se <strong>adiantar às suas compras de Natal e obter os melhores preços com as ofertas de mergulho da Black Friday na Álvarez.
                            Prolongamos o período de devoluções até 31 de janeiro de 2026.</strong>",
                    "fr" => "Du 21 novembre au 2 décembre, le <strong>Black Friday 2025 arrive dans la section PLONGÉE d'Álvarez</strong>.
                            Profitez d'une large sélection de produits pour la plongée sous-marine, l'apnée et la plongée avec bouteille, à des prix incroyablement réduits. Profitez des réductions du  Black Friday sur les combinaisons, gilets, détendeurs, ordinateurs, octopus, palmes, masques de plongée... Ainsi que sur toutes sortes d'accessoires nécessaires à la plongée, au meilleur prix.
                            Grandes marques, aux prix les plus bas de l'année : <a href='/fr/m/aqualung'>AQUALUNG</a>, <a href='/fr/m/cressi'>CRESSI</a>, <a href='/fr/m/mares'>MARES</a>, <a href='/fr/m/seac'>SEAC</a>, <a href='/fr/m/scubapro'>SCUBAPRO</a>, <a href='/fr/m/omer'>OMER</a>…
                            Ne manquez pas l'occasion de prendre de <strong>l'avance sur vos achats de Noël et d'obtenir les meilleurs prix avec les offres de plongée du Black Friday chez Álvarez.
                            Nous prolongeons la période de retour jusqu'au 31 janvier 2026.</strong>",
                    "de" => "Vom 21. November bis zum 1. Dezember findet der <strong>Black Friday 2025 in der Tauchabteilung von Álvarez statt. </strong>
                            Genießen Sie eine große Auswahl an Produkten zum Tauchen, Schnorcheln oder Unterwassertauchen zu unglaublich reduzierten Preisen. Profitieren Sie von den Black Friday-Rabatten auf Tauchanzüge, Jackets, Atemregler, Computer, Oktopusse, Flossen, Tauchmasken ... sowie auf alle Arten von Zubehör, das Sie zum Tauchen benötigen, zum besten Preis.
                            Top-Marken zu den niedrigsten Preisen des Jahres: <a href='/m/aqualung'>AQUALUNG</a>, <a href='/m/cressi'>CRESSI</a>, <a href='/m/mares'>MARES</a>, <a href='/m/seac'>SEAC</a>, <a href='/m/scubapro'>SCUBAPRO</a>, <a href='/m/omer'>OMER</a>…
                            <strong>Verpassen Sie nicht diese Gelegenheit, Ihre Weihnachtseinkäufe vorzuziehen und die besten Preise mit den Black Friday-Angeboten für Taucher bei Álvarez zu erhalten.
                            Wir verlängern die Rückgabefrist bis zum 31. Januar 2026. </strong>",
                    "it" => "Dal 21 novembre al 1° dicembre arriva il <strong>Black Friday 2025 nella sezione IMMERSIONI di Álvarez. </strong>
                            Approfitta di un'ampia selezione di prodotti per la pratica delle immersioni, dello snorkeling o delle immersioni subacquee a prezzi incredibilmente scontati. Approfitta degli sconti del Black Friday su mute, giacche, erogatori, computer, octopus, pinne, maschere da immersione... E su tutti i tipi di accessori necessari per le immersioni, al miglior prezzo.
                            Le migliori marche, AI PREZZI PIÙ BASSI DELL'ANNO: <a href='/m/aqualung'>AQUALUNG</a>, <a href='/m/cressi'>CRESSI</a>, <a href='/m/mares'>MARES</a>, <a href='/m/seac'>SEAC</a>, <a href='/m/scubapro'>SCUBAPRO</a>, <a href='/m/omer'>OMER</a>…
                            Non perdere questa occasione per <strong>anticipare i tuoi acquisti natalizi e ottenere i prezzi migliori con le offerte del Black Friday per le immersioni da Álvarez.
                            Abbiamo esteso il periodo di restituzione fino al 31 gennaio 2026.</strong>",
                    "en" => "From 21 November to 1 December, <strong>Black Friday 2025 arrives at Álvarez's DIVING section. </strong>
                            Enjoy a wide selection of inventaries for diving, snorkelling or scuba diving at incredibly discounted prices. Take advantage of Black Friday discounts on wetsuits, jackets, regulators, computers, octopus, fins, diving masks... As well as all kinds of accessories you need for diving, at the best price.
                            Top brands, WITH THE LOWEST PRICES OF THE YEAR: <a href='/m/aqualung'>AQUALUNG</a>, <a href='/m/cressi'>CRESSI</a>, <a href='/m/mares'>MARES</a>, <a href='/m/seac'>SEAC</a>, <a href='/m/scubapro'>SCUBAPRO</a>, <a href='/m/omer'>OMER</a>…
                            Don't miss this opportunity to <strong>get your Christmas shopping done early and get the best prices with Álvarez's Black Friday diving offers.
                            We are extending the returns period until 31 January 2026. </strong>",
                ],
                "texts_after" => [
                    "es" => "Cada Black Friday te traemos la más amplia selección de productos de buceo a precios increíblemente rebajados.
                            Disfruta de una gran variedad de productos para practicar buceo, snorkel o submarinismo, a precios irresistibles. Aprovecha los descuentos del Black Friday en trajes de buceo, jackets, reguladores, ordenadores, octopus, aletas, máscaras de buceo… así como todo tipo de complementos necesarios para bucear al mejor precio.
                            Permanece atento al próximo Black Friday. ¡No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de buceo en Álvarez.</strong>",

                    "en" => "Every Black Friday we bring you the widest selection of diving inventaries at unbeatable prices.
                            Enjoy a wide range of gear for scuba diving, snorkeling, or underwater exploration at incredible discounts. Take advantage of Black Friday deals on wetsuits, jackets, regulators, dive computers, octopuses, fins, masks… as well as all the accessories you need to dive at the best price.
                            Stay tuned for the next Black Friday! Don’t miss this chance to <strong>get ahead on your Christmas shopping and grab the best prices with Álvarez’s Black Friday diving offers.</strong>",

                    "pt" => "Em cada Black Friday trazemos-lhe a mais ampla seleção de produtos de mergulho a preços incrivelmente baixos.
                            Desfrute de uma grande variedade de artigos para mergulho, snorkeling ou mergulho autónomo, a preços imperdíveis. Aproveite os descontos da Black Friday em fatos de mergulho, coletes, reguladores, computadores de mergulho, octopus, barbatanas, máscaras… bem como todos os acessórios necessários para mergulhar ao melhor preço.
                            Fique atento à próxima Black Friday! Não perca a oportunidade de <strong>adiantar as suas compras de Natal e conseguir os melhores preços com as ofertas de mergulho da Black Friday na Álvarez.</strong>",

                    "fr" => "Chaque Black Friday, nous vous proposons la plus large sélection de produits de plongée à des prix incroyablement réduits.
                            Profitez d’un vaste choix d’articles pour la plongée sous-marine, le snorkeling ou la plongée en apnée, à des prix exceptionnels. Bénéficiez des réductions du Black Friday sur les combinaisons, gilets stabilisateurs, détendeurs, ordinateurs de plongée, octopus, palmes, masques… ainsi que sur tous les accessoires nécessaires pour plonger au meilleur prix.
                            Restez attentif au prochain Black Friday ! Ne manquez pas cette occasion de <strong>prendre de l’avance sur vos achats de Noël et d’obtenir les meilleurs prix avec les offres de plongée du Black Friday chez Álvarez.</strong>",

                    "de" => "An jedem Black Friday bieten wir dir die größte Auswahl an Tauchprodukten zu unglaublich reduzierten Preisen.
                            Genieße eine große Auswahl an Ausrüstung für Tauchen, Schnorcheln oder Unterwassererkundung zu fantastischen Preisen. Nutze die Black-Friday-Rabatte auf Tauchanzüge, Jackets, Regler, Tauchcomputer, Octopus, Flossen, Masken… sowie alle Accessoires, die du brauchst, um das Tauchen zum besten Preis zu genießen.
                            Bleib dran für den nächsten Black Friday! Verpasse nicht die Gelegenheit, <strong>deine Weihnachtseinkäufe frühzeitig zu erledigen und die besten Preise mit den Black-Friday-Angeboten für Tauchen bei Álvarez zu sichern.</strong>",

                    "it" => "Ogni Black Friday ti offriamo la più ampia selezione di prodotti per immersioni a prezzi incredibilmente scontati.
                            Goditi una grande varietà di articoli per immersioni, snorkeling o subacquea, a prezzi imperdibili. Approfitta degli sconti del Black Friday su mute da sub, jacket, erogatori, computer subacquei, octopus, pinne, maschere… oltre a tutti gli accessori necessari per immergerti al miglior prezzo.
                            Rimani aggiornato sul prossimo Black Friday! Non perdere l’occasione di <strong>anticipare i tuoi acquisti di Natale e ottenere i migliori prezzi con le offerte Black Friday di immersioni da Álvarez.</strong>",

                ],
                "imagenes" => [
                    "es" => [
                        [
                            "title" => "TRAJES DE BUCEO",
                            "image" => "traje.webp",
                            "url" => "/buceo/trajes_de_buceo"
                        ],
                        [
                            "title" => "JACKETS DE BUCEO",
                            "image" => "jacket.webp",
                            "url" => "/buceo/chalecos_jackets"
                        ],
                        [
                            "title" => "ORDENADORES DE BUCEO",
                            "image" => "ordenador.webp",
                            "url" => "/buceo/ordenadoresinterfaz"
                        ],
                        [
                            "title" => "REGULADORES DE BUCEO",
                            "image" => "regulador.webp",
                            "url" => "/buceo/reguladores"
                        ],
                        [
                            "title" => "MÁSCARAS DE BUCEO",
                            "image" => "mascara.webp",
                            "url" => "/buceo/mascaras_buceo"
                        ],
                        [
                            "title" => "ALETAS DE BUCEO",
                            "image" => "aletas.webp",
                            "url" => "/buceo/aletas"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "PESCA SUBMARINA",
                            "image" => "pesca-submarina.webp",
                            "url" => "/buceo/pesca_submarina"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],

                    ],
                    "en" => [
                        [
                            "title" => "Diving Suits",
                            "image" => "traje.webp",
                            "url" => "/en/diving/diving_suits"
                        ],
                        [
                            "title" => "Jackets Vests",
                            "image" => "jacket.webp",
                            "url" => "/en/diving/jackets_vests"
                        ],
                        [
                            "title" => "Computers / Interface",
                            "image" => "ordenador.webp",
                            "url" => "/en/diving/computers_interface"
                        ],
                        [
                            "title" => "Regulators",
                            "image" => "regulador.webp",
                            "url" => "/en/diving/regulators"
                        ],
                        [
                            "title" => "Diving masks",
                            "image" => "mascara.webp",
                            "url" => "/en/diving/diving_masks"
                        ],
                        [
                            "title" => "Fins",
                            "image" => "aletas.webp",
                            "url" => "/en/diving/fins"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "Underwater fishing",
                            "image" => "pesca-submarina.webp",
                            "url" => "/en/diving/underwater_fishing"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],

                    ],
                    "pt" => [
                        [
                            "title" => "FATOS MERGULHO",
                            "image" => "traje.webp",
                            "url" => "/pt/mergulho/fatos_de_mergulho"
                        ],
                        [
                            "title" => "JACKETS MERGULHO",
                            "image" => "jacket.webp",
                            "url" => "/pt/mergulho/jackets"
                        ],
                        [
                            "title" => "Computadores",
                            "image" => "ordenador.webp",
                            "url" => "/pt/mergulho/computadoresinterface"
                        ],
                        [
                            "title" => "REGULADORES",
                            "image" => "regulador.webp",
                            "url" => "/pt/mergulho/reguladores"
                        ],
                        [
                            "title" => "Máscaras de mergulho",
                            "image" => "mascara.webp",
                            "url" => "/pt/mergulho/mascaras_de_mergulho"
                        ],
                        [
                            "title" => "BARBATANAS",
                            "image" => "aletas.webp",
                            "url" => "/pt/mergulho/barbatanas"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "PESCA SUBMARINA",
                            "image" => "pesca-submarina.webp",
                            "url" => "/pt/mergulho/pesca_submarina"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "fr" => [
                        [
                            "title" => "COMBINAISONS DE PLONGÉE",
                            "image" => "traje.webp",
                            "url" => "/fr/plongee/combinaisons_de_plongee"
                        ],
                        [
                            "title" => "GILETS STABILISATEURS",
                            "image" => "jacket.webp",
                            "url" => "/fr/plongee/gilets_stabilisateurs"
                        ],
                        [
                            "title" => "ORDINATEURS",
                            "image" => "ordenador.webp",
                            "url" => "/fr/plongee/ordinateurs"
                        ],
                        [
                            "title" => "DÉTENDEURS",
                            "image" => "regulador.webp",
                            "url" => "/fr/plongee/detendeurs"
                        ],
                        [
                            "title" => "MASQUES",
                            "image" => "mascara.webp",
                            "url" => "/fr/plongee/masques_"
                        ],
                        [
                            "title" => "PALMES",
                            "image" => "aletas.webp",
                            "url" => "/fr/plongee/palmes"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "CHASSE SOUS-MARINE",
                            "image" => "pesca-submarina.webp",
                            "url" => "/fr/plongee/chasse_sous_marine"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],

                    ],
                    "de" => [
                        [
                            "title" => "Tauchanzüge",
                            "image" => "traje.webp",
                            "url" => "/de/tauchen/tauchanzuge"
                        ],
                        [
                            "title" => "Jackets",
                            "image" => "jacket.webp",
                            "url" => "/de/tauchen/jackets"
                        ],
                        [
                            "title" => "Tauchcomputer",
                            "image" => "ordenador.webp",
                            "url" => "/de/tauchen/tauchcomputer_und_interface"
                        ],
                        [
                            "title" => "Atemregler",
                            "image" => "regulador.webp",
                            "url" => "/de/tauchen/atemregler"
                        ],
                        [
                            "title" => "Tauchermaske",
                            "image" => "mascara.webp",
                            "url" => "/de/tauchen/tauchermaske"
                        ],
                        [
                            "title" => "Tauchflossen",
                            "image" => "aletas.webp",
                            "url" => "/de/tauchen/tauchflossen"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "Speerfischen",
                            "image" => "pesca-submarina.webp",
                            "url" => "/de/tauchen/speerfischen"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],

                    ],
                    "it" => [
                        [
                            "title" => "Mute da Immersione",
                            "image" => "traje.webp",
                            "url" => "/it/subacquea/mute_da_immersione"
                        ],
                        [
                            "title" => "Giubbotti Jackets",
                            "image" => "jacket.webp",
                            "url" => "/it/subacquea/giubbotti_jackets"
                        ],
                        [
                            "title" => "Computer/interfaccia",
                            "image" => "ordenador.webp",
                            "url" => "/it/subacquea/computer_interfaccia"
                        ],
                        [
                            "title" => "Erogatori",
                            "image" => "regulador.webp",
                            "url" => "/it/subacquea/erogatori"
                        ],
                        [
                            "title" => "Maschere Subacquea",
                            "image" => "mascara.webp",
                            "url" => "/it/subacquea/maschere_subacquea"
                        ],
                        [
                            "title" => "Pinne",
                            "image" => "aletas.webp",
                            "url" => "/it/subacquea/pinne"
                        ],
                        [
                            "title" => "0",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                        [
                            "title" => "Pesca Subacquea",
                            "image" => "pesca-submarina.webp",
                            "url" => "/it/subacquea/pesca_subacquea"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],

                    ],
                ],
            ];
        }
        if ($deporte == "nautica") {
            $data = [
                "botones" => [
                    "es" => [
                        "texto" => "VER TODO BLACK FRIDAY NÁUTICA",
                        "url" => "/nautica",
                    ],
                    "en" => [
                        "texto" => "SEE ALL BLACK FRIDAY BOATING",
                        "url" => "/en/boating",
                    ],
                    "pt" => [
                        "texto" => "VER TUDO BLACK FRIDAY VELA",
                        "url" => "/pt/vela",
                    ],
                    "fr" => [
                        "texto" => "VOIR LE BLACK FRIDAY NAUTIQUE",
                        "url" => "/fr/nautique",
                    ],
                    "de" => [
                        "texto" => "SEHEN BLACK FRIDAY NAUTIK",
                        "url" => "/de/nautik",
                    ],
                    "it" => [
                        "texto" => "VEDI BLACK FRIDAY NAUTICA",
                        "url" => "/it/nautica",
                    ],

                ],
                "deporte" => "nautica",
                "h1" => [
                    "es" => "Black Friday de NÁUTICA en Álvarez",
                    "en" => "NAUTICAL Black Friday at Álvarez",
                    "pt" => "Black Friday de NÁUTICA na Álvarez",
                    "fr" => "Black Friday NÁUTIQUE chez Álvarez",
                    "de" => "NAUTIK Black Friday bei Álvarez",
                    "it" => "Black Friday di NAUTICA da Álvarez",

                ],
                "after" => [
                    "es" => "Estamos preparando el próximo Black Friday; mientras tanto, aquí tienes una selección de nuestros mejores productos:",
                    "en" => "We are preparing the next Black Friday; in the meantime, here’s a selection of our best inventaries:",
                    "pt" => "Estamos a preparar o próximo Black Friday; entretanto, aqui tem uma seleção dos nossos melhores produtos:",
                    "fr" => "Nous préparons le prochain Black Friday ; en attendant, voici une sélection de nos meilleurs produits :",
                    "de" => "Wir bereiten den nächsten Black Friday vor; in der Zwischenzeit findest du hier eine Auswahl unserer besten Produkte:",
                    "it" => "Stiamo preparando il prossimo Black Friday; nel frattempo, ecco una selezione dei nostri migliori prodotti:",

                ],
                "texts" => [
                    "es" => "Desde el 21 de noviembre hasta el 01 de diciembre llega el <strong>Black Friday 2025 a la sección de NAUTICA de Álvarez.</strong>
                            Disfruta de una amplia selección de productos para tu embarcación, a precios increíblemente rebajados. Aprovecha los descuentos Black Friday en prismáticos náuticos, chalecos de seguridad, ropa y calzado náutico, artículos para el fondeo, GPS, Sondas, Compases… Así como todo tipo de complementos necesarios para disfrutar de la navegación, al mejor precio.
                            Primeras marcas, CON LOS PRECIOS MÁS BAJOS DEL AÑO: <a href='/m/lalizas'>LALIZAS</a>, <a href='/m/izas'>IZAS</a>, <a href='/m/jobe'>JOBE</a>, <a href='/m/north_sails'>NORTH SAILS</a>, <a href='/m/ocean'>OCEAN</a>, <a href='/m/aquapac'>AQUAPAC</a>…
                            No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de nautica en Álvarez.
                            Ampliamos el periodo de devoluciones hasta el 31 de enero de 2026.</strong>",
                    "pt" => "De 21 de novembro a 1 de dezembro, a <strong>Black Friday 2025 chega à secção NÁUTICA da Álvarez</strong>.
                            Desfrute de uma vasta seleção de produtos para o seu barco, a preços incrivelmente reduzidos. Aproveite os descontos da Black Friday em binóculos náuticos, coletes de segurança,  vestuário e calçado náutico, artigos para ancoragem, GPS, sondas, bússolas... Assim como todo o tipo de acessórios necessários para desfrutar da navegação, ao melhor preço.
                            Marcas de topo, COM OS PREÇOS MAIS BAIXOS DO ANO: <a href='/pt/m/lalizas'>LALIZAS</a>, <a href='/pt/m/izas'>IZAS</a>, <a href='/m/jobe'>JOBE</a>, <a href='/pt/m/north_sails'>NORTH SAILS</a>, <a href='/pt/m/ocean'>OCEAN</a>, <a href='/pt/m/aquapac'>AQUAPAC</a>…
                            Não perca esta oportunidade de se <strong>adiantar às suas compras de Natal e obter os melhores preços com as ofertas de nautica da Black Friday na Álvarez.
                            Prolongamos o período de devoluções até 31 de janeiro de 2026.</strong>",
                    "fr" => "Du 21 novembre au 1er décembre, le <strong>Black Friday 2025 arrive dans la section NAUTICA d'Álvarez. </strong>
                            Profitez d'une large sélection de produits pour votre bateau, à des prix incroyablement réduits. Profitez des réductions du Black Friday sur les jumelles nautiques, les gilets de sécurité, les vêtements et chaussures nautiques, les articles pour le mouillage, les GPS, les sondes, les compas... Ainsi que tous les types d'accessoires nécessaires pour profiter de la navigation, au meilleur prix.
                            Les meilleures marques, AUX PRIX LES PLUS BAS DE L'ANNÉE : <a href='/m/lalizas'>LALIZAS</a>, <a href='/m/izas'>IZAS</a>, <a href='/m/jobe'>JOBE</a>, <a href='/m/north_sails'>NORTH SAILS</a>, <a href='/m/ocean'>OCEAN</a>, <a href='/m/aquapac'>AQUAPAC</a>…
                            Ne manquez pas cette occasion de <strong>prendre de l'avance sur vos achats de Noël et d'obtenir les meilleurs prix grâce aux offres Black Friday de la section NAUTIQUE chez Álvarez.
                            Nous prolongeons la période de retour jusqu'au 31 janvier 2026.</strong>",
                    "de" => "Vom 21. November bis zum 1. Dezember findet der <strong>Black Friday 2025 in der NAUTICA-Abteilung von Álvarez statt. </strong>
                            Genießen Sie eine große Auswahl an Produkten für Ihr Boot zu unglaublich reduzierten Preisen. Profitieren Sie von den Black Friday-Rabatten auf nautische Ferngläser, Schwimmwesten, nautische Bekleidung und Schuhe, Ankerzubehör, GPS-Geräte, Echolote, Kompasse ... sowie alle Arten von Zubehör, das Sie zum Segeln benötigen, zum besten Preis.
                            Führende Marken zu den niedrigsten Preisen des Jahres: <a href='/m/lalizas'>LALIZAS</a>, <a href='/m/izas'>IZAS</a>, <a href='/m/jobe'>JOBE</a>, <a href='/m/north_sails'>NORTH SAILS</a>, <a href='/m/ocean'>OCEAN</a>, <a href='/m/aquapac'>AQUAPAC</a>…
                            <strong>Verpassen Sie nicht diese Gelegenheit, Ihre Weihnachtseinkäufe vorzuziehen und die besten Preise mit den Black Friday-Angeboten für Nautik bei Álvarez zu erhalten.
                            Wir verlängern die Rückgabefrist bis zum 31. Januar 2026. </strong>",
                    "it" => "Dal 21 novembre al 1° dicembre arriva il <strong>Black Friday 2025 nella sezione NAUTICA di Álvarez. </strong>
                            Approfitta di un'ampia selezione di prodotti per la tua imbarcazione a prezzi incredibilmente scontati. Approfitta degli sconti del Black Friday su binocoli nautici, giubbotti di sicurezza, abbigliamento e calzature nautiche, articoli per l'ancoraggio, GPS, ecoscandagli, bussole... E tutti i tipi di accessori necessari per goderti la navigazione, al miglior prezzo.
                            Le migliori marche, AI PREZZI PIÙ BASSI DELL'ANNO: <a href='/m/lalizas'>LALIZAS</a>, <a href='/m/izas'>IZAS</a>, <a href='/m/jobe'>JOBE</a>, <a href='/m/north_sails'>NORTH SAILS</a>, <a href='/m/ocean'>OCEAN</a>, <a href='/m/aquapac'>AQUAPAC</a>…
                            Non lasciarti sfuggire questa occasione per <strong>anticipare i tuoi acquisti natalizi e ottenere i prezzi migliori con le offerte del Black Friday nautico di Álvarez.
                            Abbiamo esteso il periodo di restituzione fino al 31 gennaio 2026.</strong>",
                    "en" => "From 21 November to 1 December, <strong>Black Friday 2025 arrives at Álvarez's NAUTICA section.</strong>
                            Enjoy a wide selection of inventaries for your boat at incredibly discounted prices. Take advantage of Black Friday discounts on nautical binoculars, safety vests, nautical clothing and footwear, anchoring equipment, GPS, depth sounders, compasses... As well as all kinds of accessories you need to enjoy sailing, at the best price.
                            Leading brands, WITH THE LOWEST PRICES OF THE YEAR: <a href='/m/lalizas'>LALIZAS</a>, <a href='/m/izas'>IZAS</a>, <a href='/m/jobe'>JOBE</a>, <a href='/m/north_sails'>NORTH SAILS</a>, <a href='/m/ocean'>OCEAN</a>, <a href='/m/aquapac'>AQUAPAC</a>…
                            Don't miss this opportunity to <strong>get your Christmas shopping done early and get the best prices with Álvarez's Black Friday nautical offers.
                            We are extending the returns period until 31 January 2026. </strong>",
                ],
                "texts_after" => [
                    "es" => "Cada Black Friday te traemos la más amplia selección de productos de náutica a precios increíblemente rebajados.
                            Disfruta de una gran variedad de artículos para tu embarcación, a precios irresistibles. Aprovecha los descuentos del Black Friday en prismáticos náuticos, chalecos salvavidas, ropa y calzado náutico, artículos para el fondeo, GPS, sondas, compases… Así como todo tipo de complementos necesarios para disfrutar de la navegación al mejor precio.
                            Permanece atento al próximo Black Friday. ¡No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de náutica en Álvarez.</strong>",

                    "en" => "Every Black Friday we bring you the widest selection of nautical inventaries at unbeatable prices.
                            Enjoy a wide range of items for your boat at incredible discounts. Take advantage of Black Friday deals on marine binoculars, life jackets, nautical clothing and footwear, anchoring equipment, GPS, depth sounders, compasses… as well as all the accessories you need to enjoy sailing at the best prices.
                            Stay tuned for the next Black Friday! Don’t miss this chance to <strong>get ahead on your Christmas shopping and grab the best prices with Álvarez’s Black Friday nautical offers.</strong>",

                    "pt" => "Em cada Black Friday trazemos-lhe a mais ampla seleção de produtos náuticos a preços incrivelmente baixos.
                            Desfrute de uma grande variedade de artigos para o seu barco, a preços imperdíveis. Aproveite os descontos da Black Friday em binóculos náuticos, coletes salva-vidas, vestuário e calçado náutico, artigos para ancoragem, GPS, sondas, bússolas… bem como todos os acessórios necessários para desfrutar da navegação ao melhor preço.
                            Fique atento à próxima Black Friday! Não perca a oportunidade de <strong>adiantar as suas compras de Natal e obter os melhores preços com as ofertas de náutica da Black Friday na Álvarez.</strong>",

                    "fr" => "Chaque Black Friday, nous vous proposons la plus large sélection de produits nautiques à des prix incroyablement réduits.
                            Profitez d’un vaste choix d’articles pour votre bateau à des tarifs exceptionnels. Bénéficiez des réductions du Black Friday sur les jumelles nautiques, les gilets de sauvetage, les vêtements et chaussures nautiques, les accessoires d’ancrage, les GPS, les sondeurs, les compas… ainsi que sur tous les accessoires indispensables pour profiter pleinement de la navigation, au meilleur prix.
                            Restez attentif au prochain Black Friday ! Ne manquez pas cette occasion de <strong>prendre de l’avance sur vos achats de Noël et d’obtenir les meilleurs prix avec les offres nautiques du Black Friday chez Álvarez.</strong>",

                    "de" => "An jedem Black Friday bieten wir dir die größte Auswahl an Nautik-Produkten zu unglaublich reduzierten Preisen.
                            Genieße eine große Auswahl an Artikeln für dein Boot zu fantastischen Preisen. Nutze die Black-Friday-Rabatte auf Marineferngläser, Schwimmwesten, nautische Kleidung und Schuhe, Ankerzubehör, GPS, Echolote, Kompasse… sowie alle Accessoires, die du brauchst, um das Segeln in vollen Zügen zu genießen – zum besten Preis.
                            Bleib dran für den nächsten Black Friday! Verpasse nicht die Gelegenheit, <strong>deine Weihnachtseinkäufe frühzeitig zu erledigen und die besten Preise mit den Black-Friday-Angeboten für Nautik bei Álvarez zu sichern.</strong>",

                    "it" => "Ogni Black Friday ti offriamo la più ampia selezione di prodotti nautici a prezzi incredibilmente scontati.
                            Goditi un’ampia gamma di articoli per la tua imbarcazione a prezzi imperdibili. Approfitta degli sconti del Black Friday su binocoli nautici, giubbotti di salvataggio, abbigliamento e calzature nautiche, articoli per l’ancoraggio, GPS, ecoscandagli, bussole… oltre a tutti gli accessori necessari per goderti la navigazione al miglior prezzo.
                            Rimani aggiornato sul prossimo Black Friday! Non perdere l’occasione di <strong>anticipare i tuoi acquisti di Natale e ottenere i migliori prezzi con le offerte Black Friday di nautica da Álvarez.</strong>",

                ],
                "imagenes" => [
                    "es" => [
                        [
                            "title" => "CHALECOS SALVAVIDAS",
                            "image" => "chalecos.webp",
                            "url" => "/nautica/chalecos_salvavidas"
                        ],
                        [
                            "title" => "ROPA NÁUTICA",
                            "image" => "ropa.webp",
                            "url" => "/nautica/ropa_nautica"
                        ],
                        [
                            "title" => "CALZADO NÁUTICO",
                            "image" => "calzado.webp",
                            "url" => "/nautica/calzado_nautico"
                        ],
                        [
                            "title" => "FONDEO",
                            "image" => "fondeo.webp",
                            "url" => "/nautica/fondeo"
                        ],
                        [
                            "title" => "EQUIPO DE CUBIERTA",
                            "image" => "equipo-cubierta.webp",
                            "url" => "/nautica/equipo_de_cubierta"
                        ],
                        [
                            "title" => "TODO PARA TU CONFORT",
                            "image" => "confort.webp",
                            "url" => "/nautica/confort"
                        ],

                    ],
                    "en" => [
                        [
                            "title" => "Lifevest",
                            "image" => "chalecos.webp",
                            "url" => "/en/boating/lifevest"
                        ],
                        [
                            "title" => "Nautical clothing",
                            "image" => "ropa.webp",
                            "url" => "/en/boating/nautical_clothing"
                        ],
                        [
                            "title" => "Nautical footwear",
                            "image" => "calzado.webp",
                            "url" => "/en/boating/nautical_footwear"
                        ],
                        [
                            "title" => "FONDEO",
                            "image" => "fondeo.webp",
                            "url" => "/en/boating/anchoring"
                        ],
                        [
                            "title" => "Deck Equipment",
                            "image" => "equipo-cubierta.webp",
                            "url" => "/en/boating/deck_equipment"
                        ],
                        [
                            "title" => "Comfort",
                            "image" => "confort.webp",
                            "url" => "/en/boating/comfort"
                        ],

                    ],
                    "pt" => [
                        [
                            "title" => "COLETES SALVA-VIDAS",
                            "image" => "chalecos.webp",
                            "url" => "/pt/vela/coletes_salvavidas"
                        ],
                        [
                            "title" => "ROUPAS NÁUTICAS",
                            "image" => "ropa.webp",
                            "url" => "/pt/vela/roupas_nauticas"
                        ],
                        [
                            "title" => "CALÇADO NÁUTICO",
                            "image" => "calzado.webp",
                            "url" => "/pt/vela/calcado_nautico"
                        ],
                        [
                            "title" => "AMARRAÇÃO E ANCORAGEM",
                            "image" => "fondeo.webp",
                            "url" => "/pt/vela/amarracao_e_ancoragem"
                        ],
                        [
                            "title" => "EQUIPAMENTO DE CONVÉS",
                            "image" => "equipo-cubierta.webp",
                            "url" => "/pt/vela/equipamento_de_conves"
                        ],
                        [
                            "title" => "UTILIDADES E CONFORTO",
                            "image" => "confort.webp",
                            "url" => "/pt/vela/utilidades_e_conforto"
                        ],

                    ],
                    "fr" => [
                        [
                            "title" => "Gilets de sauvetage",
                            "image" => "chalecos.webp",
                            "url" => "/fr/nautique/gilets_de_sauvetage"
                        ],
                        [
                            "title" => "Vêtements marins",
                            "image" => "ropa.webp",
                            "url" => "/fr/nautique/vetements_marins"
                        ],
                        [
                            "title" => "Chaussures bateau",
                            "image" => "calzado.webp",
                            "url" => "/fr/nautique/chaussures_bateau"
                        ],
                        [
                            "title" => "Ancrage",
                            "image" => "fondeo.webp",
                            "url" => "/fr/nautique/ancrage"
                        ],
                        [
                            "title" => "Équipements pour le pont",
                            "image" => "equipo-cubierta.webp",
                            "url" => "/fr/nautique/equipements_pour_le_pont"
                        ],
                        [
                            "title" => "Confort",
                            "image" => "confort.webp",
                            "url" => "/fr/nautique/confort"
                        ],

                    ],
                    "de" => [
                        [
                            "title" => "Rettungswesten",
                            "image" => "chalecos.webp",
                            "url" => "/de/nautik/rettungswesten"
                        ],
                        [
                            "title" => "Nautische Kleidung",
                            "image" => "ropa.webp",
                            "url" => "/de/nautik/nautische_kleidung"
                        ],
                        [
                            "title" => "Nautische Schuhe",
                            "image" => "calzado.webp",
                            "url" => "/de/nautik/nautische_schuhe"
                        ],
                        [
                            "title" => "Verankerung",
                            "image" => "fondeo.webp",
                            "url" => "/de/nautik/verankerung"
                        ],
                        [
                            "title" => "Deckausrüstung",
                            "image" => "equipo-cubierta.webp",
                            "url" => "/de/nautik/deckausrustung"
                        ],
                        [
                            "title" => "Komfort",
                            "image" => "confort.webp",
                            "url" => "/de/nautik/komfort"
                        ],

                    ],
                    "it" => [
                        [
                            "title" => "Gilet",
                            "image" => "chalecos.webp",
                            "url" => "/it/nautica/gilet"
                        ],
                        [
                            "title" => "Abbigliamento nautico",
                            "image" => "ropa.webp",
                            "url" => "/it/nautica/abbigliamento_nautico"
                        ],
                        [
                            "title" => "Calzature nautiche",
                            "image" => "calzado.webp",
                            "url" => "/it/nautica/calzature_nautiche"
                        ],
                        [
                            "title" => "Ancoraggio",
                            "image" => "fondeo.webp",
                            "url" => "/it/nautica/ancoraggio"
                        ],
                        [
                            "title" => "Attrezzatura da coperta",
                            "image" => "equipo-cubierta.webp",
                            "url" => "/it/nautica/attrezzatura_da_coperta"
                        ],
                        [
                            "title" => "Comfort",
                            "image" => "confort.webp",
                            "url" => "/it/nautica/comfort"
                        ],

                    ],
                ],
            ];
        }
        if ($deporte == "esqui") {
            $data = [
                "botones" => [
                    "es" => [
                        "texto" => "VER TODO BLACK FRIDAY ESQUÍ",
                        "url" => "/esqui",
                    ],
                    "en" => [
                        "texto" => "SEE ALL BLACK FRIDAY SKI",
                        "url" => "/en/skiing",
                    ],
                    "pt" => [
                        "texto" => "VER TUDO BLACK FRIDAY ESQUI",
                        "url" => "/pt/esqui",
                    ],
                    "fr" => [
                        "texto" => "VOIR LE BLACK FRIDAY SKI",
                        "url" => "/fr/ski",
                    ],
                    "de" => [
                        "texto" => "SEHEN BLACK FRIDAY SKI",
                        "url" => "/de/ski",
                    ],
                    "it" => [
                        "texto" => "VEDI BLACK FRIDAY SCI",
                        "url" => "/it/sci",
                    ],

                ],
                "deporte" => "esqui",
                "h1" => [
                    "es" => "Black Friday de ESQUÍ en Álvarez",
                    "en" => "SKI Black Friday at Álvarez",
                    "pt" => "Black Friday de ESQUI na Álvarez",
                    "fr" => "Black Friday SKI chez Álvarez",
                    "de" => "SKI Black Friday bei Álvarez",
                    "it" => "Black Friday di SCI da Álvarez",

                ],
                "after" => [
                    "es" => "Estamos preparando el próximo Black Friday; mientras tanto, aquí tienes una selección de nuestros mejores productos:",
                    "en" => "We are preparing the next Black Friday; in the meantime, here’s a selection of our best inventaries:",
                    "pt" => "Estamos a preparar o próximo Black Friday; entretanto, aqui tem uma seleção dos nossos melhores produtos:",
                    "fr" => "Nous préparons le prochain Black Friday ; en attendant, voici une sélection de nos meilleurs produits :",
                    "de" => "Wir bereiten den nächsten Black Friday vor; in der Zwischenzeit findest du hier eine Auswahl unserer besten Produkte:",
                    "it" => "Stiamo preparando il prossimo Black Friday; nel frattempo, ecco una selezione dei nostri migliori prodotti:",

                ],
                "texts" => [
                    "es" => "Desde el 21 de noviembre hasta el 01 de diciembre llega el <strong>Black Friday 2025 a la sección de ESQUI de Álvarez.</strong>
                            Disfruta de una amplia selección de productos para esquiar, a precios increíblemente rebajados. Aprovecha los descuentos Black Friday en esquís y fijaciones, botas de esquí, guantes y máscaras para esquiar, bastones de nieve… Así como todo tipo de complementos necesarios para disfrutar al máximo en la montaña, al mejor precio.
                            Primeras marcas, CON LOS PRECIOS MÁS BAJOS DEL AÑO: <a href='/m/atomic'>Atomic</a>, <a href='/m/salomon'>Salomon</a>, <a href='/m/nordica'>Nordica</a>, <a href='/m/volkl'>Volkl</a>, <a href='/m/8000'>+8000</a>, <a href='/m/trangoworld'>TrangoWorld</a>, <a href='/m/arcteryx'>ArcTeryx</a>, <a href='/m/descente'>Descente</a>…
                            No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de esqui en Álvarez.
                            Ampliamos el periodo de devoluciones hasta el 31 de enero de 2026.</strong>",
                    "pt" => "De 21 de novembro a 2 de dezembro, a <strong>Black Friday 2025 chega à secção ESQUI da Álvarez</strong>.
                            Desfrute de uma vasta seleção de produtos para esquiar, a preços incrivelmente reduzidos. Aproveite os descontos da Black Friday em esquis e fixações, botas de esqui, luvas e máscaras de esqui, bastões de neve... Assim como todo o tipo de acessórios necessários para tirar o máximo partido da montanha, ao melhor preço.
                            Marcas de topo, AOS PREÇOS MAIS BAIXOS DO ANO: <a href='/pt/m/atomic'>Atomic</a>, <a href='/pt/m/salomon'>Salomon</a>, <a href='/pt/m/nordica'>Nordica</a>, <a href='/pt/m/volkl'>Volkl</a>, <a href='/pt/m/8000'>+8000</a>, <a href='/pt/m/trangoworld'>TrangoWorld</a>, <a href='/pt/m/arcteryx'>ArcTeryx</a>, <a href='/pt/m/descente'>Descente</a>…
                            Não perca esta oportunidade de se <strong>adiantar às suas compras de Natal e obter os melhores preços com as ofertas de esqui da Black Friday na Álvarez.
                            Prolongamos o período de devoluções até 31 de janeiro de 2026.</strong>",
                    "fr" => "Du 21 novembre au 1er décembre, le <strong>Black Friday 2025 arrive dans la section SKI d'Álvarez.</strong>
                            Profitez d'une large sélection de produits de ski à des prix incroyablement réduits. Profitez des réductions du Black Friday sur les skis et les fixations, les chaussures de ski, les gants et les masques de ski, les bâtons de neige... Ainsi que sur tous les accessoires nécessaires pour profiter au maximum de la montagne, au meilleur prix.
                            Les meilleures marques, AUX PRIX LES PLUS BAS DE L'ANNÉE : <a href='/m/atomic'>Atomic</a>, <a href='/m/salomon'>Salomon</a>, <a href='/m/nordica'>Nordica</a>, <a href='/m/volkl'>Volkl</a>, <a href='/m/8000'>+8000</a>, <a href='/m/trangoworld'>TrangoWorld</a>, <a href='/m/arcteryx'>ArcTeryx</a>, <a href='/m/descente'>Descente</a>…
                            Ne manquez pas cette occasion de <strong>prendre de l'avance sur vos achats de Noël et d'obtenir les meilleurs prix grâce aux offres Black Friday sur le ski chez Álvarez.
                            Nous prolongeons la période de retour jusqu'au 31 janvier 2026.</strong>",
                    "de" => "Vom 21. November bis zum 1. Dezember findet der <strong>Black Friday 2025 in der SKI-Abteilung von Álvarez statt. </strong>
                            Genießen Sie eine große Auswahl an Ski-Produkten zu unglaublich reduzierten Preisen. Profitieren Sie von den Black Friday-Rabatten auf Skier und Bindungen, Skischuhe, Handschuhe und Skibrillen, Skistöcke ... sowie alle Arten von Zubehör, das Sie benötigen, um die Berge in vollen Zügen zu genießen, zum besten Preis.
                            Top-Marken zu den niedrigsten Preisen des Jahres: <a href='/m/atomic'>Atomic</a>, <a href='/m/salomon'>Salomon</a>, <a href='/m/nordica'>Nordica</a>, <a href='/m/volkl'>Volkl</a>, <a href='/m/8000'>+8000</a>, <a href='/m/trangoworld'>TrangoWorld</a>, <a href='/m/arcteryx'>ArcTeryx</a>, <a href='/m/descente'>Descente</a>…
                            <strong> Verpassen Sie nicht diese Gelegenheit, Ihre Weihnachtseinkäufe vorzuziehen und die besten Preise mit den Black Friday-Angeboten für Skiausrüstung bei Álvarez zu erhalten.
                            Wir verlängern die Rückgabefrist bis zum 31. Januar 2026. </strong>",
                    "it" => "Dal 21 novembre al 1° dicembre arriva il <strong>Black Friday 2025 nella sezione SCI di Álvarez. </strong>
                            Approfitta di un'ampia selezione di prodotti per lo sci a prezzi incredibilmente scontati. Approfitta degli sconti del Black Friday su sci e attacchi, scarponi da sci, guanti e maschere da sci, bastoncini da neve... E su tutti i tipi di accessori necessari per goderti al massimo la montagna, al miglior prezzo.
                            Le migliori marche, AI PREZZI PIÙ BASSI DELL'ANNO: <a href='/m/atomic'>Atomic</a>, <a href='/m/salomon'>Salomon</a>, <a href='/m/nordica'>Nordica</a>, <a href='/m/volkl'>Volkl</a>, <a href='/m/8000'>+8000</a>, <a href='/m/trangoworld'>TrangoWorld</a>, <a href='/m/arcteryx'>ArcTeryx</a>, <a href='/m/descente'>Descente</a>…
                            Non perdere questa occasione per <strong>anticipare i tuoi acquisti natalizi e ottenere i prezzi migliori con le offerte del Black Friday sugli sci da Álvarez.
                            Abbiamo esteso il periodo di restituzione fino al 31 gennaio 2026.</strong>",
                    "en" => "From 21 November to 1 December, <strong>Black Friday 2025 arrives at Álvarez's SKI section. </strong>
                            Enjoy a wide selection of skiing inventaries at incredibly discounted prices. Take advantage of Black Friday discounts on skis and bindings, ski boots, gloves and ski goggles, ski poles... As well as all kinds of accessories you need to enjoy the mountains to the fullest, at the best price.
                            Top brands, WITH THE LOWEST PRICES OF THE YEAR: <a href='/m/atomic'>Atomic</a>, <a href='/m/salomon'>Salomon</a>, <a href='/m/nordica'>Nordica</a>, <a href='/m/volkl'>Volkl</a>, <a href='/m/8000'>+8000</a>, <a href='/m/trangoworld'>TrangoWorld</a>, <a href='/m/arcteryx'>ArcTeryx</a>, <a href='/m/descente'>Descente</a>…
                            Don't miss this opportunity to <strong>get your Christmas shopping done early and get the best prices with Álvarez's Black Friday ski offers.
                            We are extending the returns period until 31 January 2026. </strong>",
                ],
                "texts_after" => [
                    "es" => "Cada Black Friday te traemos la más amplia selección de productos de esquí a precios increíblemente rebajados.
                            Disfruta de una amplia selección de productos para esquiar, a precios irresistibles. Aprovecha los descuentos del Black Friday en esquís y fijaciones, botas de esquí, guantes, máscaras, bastones de nieve… Así como todo tipo de complementos necesarios para disfrutar al máximo en la montaña, al mejor precio.
                            Permanece atento al próximo Black Friday. ¡No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de esquí en Álvarez.</strong>",

                    "en" => "Every Black Friday we bring you the widest selection of ski inventaries at unbeatable prices.
                            Enjoy a great range of ski gear at incredible discounts. Take advantage of Black Friday deals on skis and bindings, ski boots, gloves, goggles, and snow poles — as well as all the accessories you need to make the most of the mountain at the best prices.
                            Stay tuned for the next Black Friday! Don’t miss this chance to <strong>get ahead on your Christmas shopping and grab the best prices with Álvarez’s Black Friday ski offers.</strong>",

                    "pt" => "Em cada Black Friday trazemos-lhe a mais ampla seleção de produtos de esqui a preços incrivelmente baixos.
                            Desfrute de uma grande seleção de artigos para esquiar, a preços imperdíveis. Aproveite os descontos da Black Friday em esquis e fixações, botas de esqui, luvas, máscaras e bastões de neve… Bem como todo o tipo de acessórios necessários para aproveitar ao máximo a montanha, ao melhor preço.
                            Fique atento à próxima Black Friday! Não perca a oportunidade de <strong>adiantar as suas compras de Natal e conseguir os melhores preços com as ofertas de esqui da Black Friday na Álvarez.</strong>",

                    "fr" => "Chaque Black Friday, nous vous proposons la plus large sélection de produits de ski à des prix incroyablement réduits.
                            Profitez d’un vaste choix d’articles pour le ski à des tarifs exceptionnels. Bénéficiez des réductions du Black Friday sur les skis et fixations, les chaussures de ski, les gants, les masques, les bâtons de neige… ainsi que sur tous les accessoires indispensables pour profiter pleinement de la montagne, au meilleur prix.
                            Restez attentif au prochain Black Friday ! Ne manquez pas cette occasion de <strong>prendre de l’avance sur vos achats de Noël et d’obtenir les meilleurs prix avec les offres de ski du Black Friday chez Álvarez.</strong>",

                    "de" => "An jedem Black Friday bieten wir dir die größte Auswahl an Ski-Produkten zu unglaublich reduzierten Preisen.
                            Genieße eine große Auswahl an Skiausrüstung zu fantastischen Preisen. Nutze die Black-Friday-Rabatte auf Skier und Bindungen, Skischuhe, Handschuhe, Skibrillen und Schneestöcke – sowie auf alle Accessoires, die du brauchst, um die Berge in vollen Zügen zu genießen.
                            Bleib dran für den nächsten Black Friday! Verpasse nicht die Gelegenheit, <strong>deine Weihnachtseinkäufe frühzeitig zu erledigen und die besten Preise mit den Black-Friday-Angeboten für Ski bei Álvarez zu sichern.</strong>",

                    "it" => "Ogni Black Friday ti offriamo la più ampia selezione di prodotti da sci a prezzi incredibilmente scontati.
                            Goditi un’ampia scelta di articoli per sciare, a prezzi imperdibili. Approfitta degli sconti del Black Friday su sci e attacchi, scarponi da sci, guanti, maschere, bastoncini da neve… oltre a tutti gli accessori necessari per goderti la montagna al meglio, al miglior prezzo.
                            Rimani aggiornato sul prossimo Black Friday! Non perdere l’occasione di <strong>anticipare i tuoi acquisti di Natale e ottenere i migliori prezzi con le offerte Black Friday di sci da Álvarez.</strong>",

                ],
                "imagenes" => [
                    "es" => [
                        [
                            "title" => "ESQUÍS+FIJACIONES",
                            "image" => "esquis.webp",
                            "url" => "/esqui/esquis_fijaciones"
                        ],
                        [
                            "title" => "BOTAS DE ESQUÍ",
                            "image" => "botas.webp",
                            "url" => "/esqui/botas_de_esqui"
                        ],
                        [
                            "title" => "CASCOS DE ESQUÍ",
                            "image" => "casco.webp",
                            "url" => "/esqui/cascos_esqui"
                        ],
                        [
                            "title" => "BASTONES DE ESQUÍ",
                            "image" => "palos.webp",
                            "url" => "/esqui/bastones_de_esqui"
                        ],
                        [
                            "title" => "GAFAS DE ESQUÍ",
                            "image" => "gafas.webp",
                            "url" => "/esqui/gafas_y_mascaras_de_esqui"
                        ],
                        [
                            "title" => "ROPA ESQUÍ",
                            "image" => "ropa.webp",
                            "url" => "/esqui/ropa_hombre_esqui"
                        ],

                    ],
                    "en" => [
                        [
                            "title" => "Skis + Fixings",
                            "image" => "esquis.webp",
                            "url" => "/en/skiing/skis_fixings"
                        ],
                        [
                            "title" => "Ski boots",
                            "image" => "botas.webp",
                            "url" => "/en/skiing/ski_boots"
                        ],
                        [
                            "title" => "Ski Helmets",
                            "image" => "casco.webp",
                            "url" => "/en/skiing/ski_helmets"
                        ],
                        [
                            "title" => "Ski poles",
                            "image" => "palos.webp",
                            "url" => "/en/skiing/ski_poles"
                        ],
                        [
                            "title" => "Ski goggles and masks",
                            "image" => "gafas.webp",
                            "url" => "/en/skiing/ski_goggles_and_masks"
                        ],
                        [
                            "title" => "Ski Men's Clothing",
                            "image" => "ropa.webp",
                            "url" => "/en/skiing/ski_mens_clothing"
                        ],

                    ],
                    "pt" => [
                        [
                            "title" => "ESQUÍS+FIXAÇÕES",
                            "image" => "esquis.webp",
                            "url" => "/pt/esqui/esquis_fixacoes"
                        ],
                        [
                            "title" => "BOTAS ESQUI",
                            "image" => "botas.webp",
                            "url" => "/pt/esqui/botas_de_esqui"
                        ],
                        [
                            "title" => "CAPACETES DE ESQUI",
                            "image" => "casco.webp",
                            "url" => "/pt/esqui/capacetes_de_esqui"
                        ],
                        [
                            "title" => "BASTÕES DE ESQUI",
                            "image" => "palos.webp",
                            "url" => "/pt/esqui/bastoes_de_esqui"
                        ],
                        [
                            "title" => "MÁSCARAS",
                            "image" => "gafas.webp",
                            "url" => "/pt/esqui/mascaras_e_oculos_de_esqui"
                        ],
                        [
                            "title" => "ROUPA",
                            "image" => "ropa.webp",
                            "url" => "/pt/esqui/roupa_homem_esqui"
                        ],

                    ],
                    "fr" => [
                        [
                            "title" => "Skis & Fixations",
                            "image" => "esquis.webp",
                            "url" => "/fr/ski/skis_fixations"
                        ],
                        [
                            "title" => "Bottes de ski",
                            "image" => "botas.webp",
                            "url" => "/fr/ski/bottes_de_ski"
                        ],
                        [
                            "title" => "Casques de ski",
                            "image" => "casco.webp",
                            "url" => "/fr/ski/casques_de_ski"
                        ],
                        [
                            "title" => "Bâtons de ski",
                            "image" => "palos.webp",
                            "url" => "/fr/ski/batons_de_ski"
                        ],
                        [
                            "title" => "Masques et lunettes",
                            "image" => "gafas.webp",
                            "url" => "/fr/ski/lunettes_et_masques_de_ski"
                        ],
                        [
                            "title" => "Vêtements de ski",
                            "image" => "ropa.webp",
                            "url" => "/fr/ski/vetements_de_ski_pour_homme"
                        ],

                    ],
                    "de" => [
                        [
                            "title" => "Ski & Bindungen",
                            "image" => "esquis.webp",
                            "url" => "/de/ski/ski_bindungen"
                        ],
                        [
                            "title" => "Skischuhe",
                            "image" => "botas.webp",
                            "url" => "/de/ski/skischuhe"
                        ],
                        [
                            "title" => "Skihelme",
                            "image" => "casco.webp",
                            "url" => "/de/ski/skihelme"
                        ],
                        [
                            "title" => "Skistöcke",
                            "image" => "palos.webp",
                            "url" => "/de/ski/skistocke"
                        ],
                        [
                            "title" => "Skibrille und Masken",
                            "image" => "gafas.webp",
                            "url" => "/de/ski/skibrille_und_masken"
                        ],
                        [
                            "title" => "Skibekleidung",
                            "image" => "ropa.webp",
                            "url" => "/de/ski/ski_herrenbekleidung"
                        ],

                    ],
                    "it" => [
                        [
                            "title" => "Sci e Attacchi",
                            "image" => "esquis.webp",
                            "url" => "/it/sci/sci_e_attacchi"
                        ],
                        [
                            "title" => "Scarponi da sci",
                            "image" => "botas.webp",
                            "url" => "/it/sci/scarponi_da_sci"
                        ],
                        [
                            "title" => "Caschi da sci",
                            "image" => "casco.webp",
                            "url" => "/it/sci/caschi_da_sci"
                        ],
                        [
                            "title" => "Bastoncini da sci",
                            "image" => "palos.webp",
                            "url" => "/it/sci/bastoncini_da_sci"
                        ],
                        [
                            "title" => "Maschere",
                            "image" => "gafas.webp",
                            "url" => "/it/sci/maschere_e_occhiali_da_sci"
                        ],
                        [
                            "title" => "Abbigliamento",
                            "image" => "ropa.webp",
                            "url" => "/it/sci/abbigliamento_da_sci_per_uomo"
                        ],

                    ],

                ],
            ];
        }
        if ($deporte == "padel") {
            $data = [
                "botones" => [
                    "es" => [
                        "texto" => "VER TODO BLACK FRIDAY PÁDEL",
                        "url" => "/padel",
                    ],
                    "en" => [
                        "texto" => "SEE ALL BLACK FRIDAY PADEL",
                        "url" => "/en/padel",
                    ],
                    "pt" => [
                        "texto" => "VER TUDO BLACK FRIDAY PÁDEL",
                        "url" => "/pt/padel",
                    ],
                    "fr" => [
                        "texto" => "VOIR LE BLACK FRIDAY PADEL",
                        "url" => "/fr/padel",
                    ],
                    "de" => [
                        "texto" => "SEHEN BLACK FRIDAY PADEL",
                        "url" => "/de/padel",
                    ],
                    "it" => [
                        "texto" => "VEDI BLACK FRIDAY PADEL",
                        "url" => "/it/padel",
                    ],
                ],
                "deporte" => "padel",
                "h1" => [
                    "es" => "Black Friday de PÁDEL en Álvarez",
                    "en" => "PADEL Black Friday at Álvarez",
                    "pt" => "Black Friday de PÁDEL na Álvarez",
                    "fr" => "Black Friday PÁDEL chez Álvarez",
                    "de" => "PADEL Black Friday bei Álvarez",
                    "it" => "Black Friday di PÁDEL da Álvarez",

                ],
                "after" => [
                    "es" => "Estamos preparando el próximo Black Friday; mientras tanto, aquí tienes una selección de nuestros mejores productos:",
                    "en" => "We are preparing the next Black Friday; in the meantime, here’s a selection of our best inventaries:",
                    "pt" => "Estamos a preparar o próximo Black Friday; entretanto, aqui tem uma seleção dos nossos melhores produtos:",
                    "fr" => "Nous préparons le prochain Black Friday ; en attendant, voici une sélection de nos meilleurs produits :",
                    "de" => "Wir bereiten den nächsten Black Friday vor; in der Zwischenzeit findest du hier eine Auswahl unserer besten Produkte:",
                    "it" => "Stiamo preparando il prossimo Black Friday; nel frattempo, ecco una selezione dei nostri migliori prodotti:",

                ],
                "texts" => [
                    "es" => "Desde el 21 de noviembre hasta el 01 de diciembre llega el <strong>Black Friday 2025 a la sección de PADEL de Álvarez.</strong>
                            Disfruta de una amplia selección de productos para jugar al pádel, a precios increíblemente rebajados. Aprovecha los descuentos Black Friday en palas, paleteros, zapatillas y  ropa de pádel, muñequeras… Así como todo tipo de complementos necesarios para disfrutar al máximo en la montaña, al mejor precio.
                            Primeras marcas, CON LOS PRECIOS MÁS BAJOS DEL AÑO: <a href='/m/bullpadel'>Bullpadel</a>, <a href='/m/varlion'>Varlion</a>, <a href='/m/drop_shot'>Drop Shot</a>, <a href='/m/head'>Head</a> …
                            No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de padel en Álvarez.
                            Ampliamos el periodo de devoluciones hasta el 31 de enero de 2026.</strong>",
                    "pt" => "De 21 de novembro a 2 de dezembro, a <strong>Black Friday 2025 chega à secção PADEL da Álvarez</strong>.
                            Desfrute de uma vasta seleção de produtos para jogar padel, a preços incrivelmente reduzidos. Aproveite os descontos da Black Friday em raquetes de padel, raquetes de padel,  sapatos de padel, roupas de padel, pulseiras de padel ... Além de todos os tipos de acessórios necessários para aproveitar ao máximo as montanhas, ao melhor preço.
                            Marcas de topo, COM OS PREÇOS MAIS BAIXOS DO ANO: <a href='/pt/m/bullpadel'>Bullpadel</a>, <a href='/pt/m/varlion'>Varlion</a>, <a href='/pt/m/drop_shot'>Drop Shot</a>, <a href='/pt/m/head'>Head</a> …
                            Não perca esta oportunidade de se <strong>adiantar às suas compras de Natal e obter os melhores preços com as ofertas de padel da Black Friday na Álvarez.
                            Prolongamos o período de devoluções até 31 de janeiro de 2026.</strong>",
                    "fr" => "Du 21 novembre au 1er décembre, le < strong>,Black Friday 2025 arrive dans la section SKI d'Álvarez. </strong>
                            Profitez d'une large sélection de produits de ski à des prix incroyablement réduits. Profitez des réductions du Black Friday sur les skis et les fixations, les chaussures de ski, les gants et les masques de ski, les bâtons de neige... Ainsi que sur tous les accessoires nécessaires pour profiter au maximum de la montagne, au meilleur prix.
                            Les meilleures marques, AUX PRIX LES PLUS BAS DE L'ANNÉE : <a href='/m/bullpadel'>Bullpadel</a>, <a href='/m/varlion'>Varlion</a>, <a href='/m/drop_shot'>Drop Shot</a>, <a href='/m/head'>Head</a> …
                            Ne manquez pas cette occasion de <strong>prendre de l'avance sur vos achats de Noël et d'obtenir les meilleurs prix grâce aux offres Black Friday sur le ski chez Álvarez.
                            Nous prolongeons la période de retour jusqu'au 31 janvier 2026.</strong>",
                    "de" => "Vom 21. November bis zum 1. Dezember findet der <strong>Black Friday 2025 in der SKI-Abteilung von Álvarez statt. </strong>
                            Genießen Sie eine große Auswahl an Ski-Produkten zu unglaublich reduzierten Preisen. Profitieren Sie von den Black Friday-Rabatten auf Skier und Bindungen, Skischuhe, Handschuhe und Skibrillen, Skistöcke ... sowie alle Arten von Zubehör, das Sie benötigen, um die Berge in vollen Zügen zu genießen, zum besten Preis.
                            Top-Marken zu den niedrigsten Preisen des Jahres: <a href='/m/bullpadel'>Bullpadel</a>, <a href='/m/varlion'>Varlion</a>, <a href='/m/drop_shot'>Drop Shot</a>, <a href='/m/head'>Head</a> …
                            <strong>Verpassen Sie nicht diese Gelegenheit, Ihre Weihnachtseinkäufe vorzuziehen und die besten Preise mit den Black Friday-Angeboten für Skiausrüstung bei Álvarez zu erhalten.
                            Wir verlängern die Rückgabefrist bis zum 31. Januar 2026.</strong>",
                    "it" => "Dal 21 novembre al 1° dicembre arriva il <strong>Black Friday 2025 nella sezione SCI di Álvarez.</strong>
                            Approfitta di un'ampia selezione di prodotti per lo sci a prezzi incredibilmente scontati. Approfitta degli sconti del Black Friday su sci e attacchi, scarponi da sci, guanti e maschere da sci, bastoncini da neve... E su tutti i tipi di accessori necessari per goderti al massimo la montagna, al miglior prezzo.
                            Le migliori marche, AI PREZZI PIÙ BASSI DELL'ANNO: <a href='/m/bullpadel'>Bullpadel</a>, <a href='/m/varlion'>Varlion</a>, <a href='/m/drop_shot'>Drop Shot</a>, <a href='/m/head'>Head</a> …
                            Non perdere questa occasione per <strong>anticipare i tuoi acquisti natalizi e ottenere i prezzi migliori con le offerte del Black Friday sugli sci da Álvarez.
                            Abbiamo esteso il periodo di restituzione fino al 31 gennaio 2026.</strong>",
                    "en" => "From 21 November to 1 December, <strong>Black Friday 2025 arrives at Álvarez's SKI section. </strong>
                            Enjoy a wide selection of skiing inventaries at incredibly discounted prices. Take advantage of Black Friday discounts on skis and bindings, ski boots, gloves and ski goggles, ski poles... As well as all kinds of accessories you need to enjoy the mountains to the fullest, at the best price.
                            Top brands, WITH THE LOWEST PRICES OF THE YEAR: <a href='/m/bullpadel'>Bullpadel</a>, <a href='/m/varlion'>Varlion</a>, <a href='/m/drop_shot'>Drop Shot</a>, <a href='/m/head'>Head</a> …
                            Don't miss this opportunity to <strong>get your Christmas shopping done early and get the best prices with Álvarez's Black Friday ski offers.
                            We are extending the returns period until 31 January 2026.</strong>",
                ],
                "texts_after" => [
                    "es" => "Cada Black Friday te traemos la más amplia selección de productos de pádel a precios increíblemente rebajados.
                            Disfruta de una amplia selección de productos para jugar al pádel, a precios increíbles. Aprovecha los descuentos del Black Friday en palas, paleteros, zapatillas, ropa de pádel, muñequeras…
                            Permanece atento al próximo Black Friday. ¡No dejes pasar esta oportunidad para <strong>adelantar tus compras navideñas y conseguir los mejores precios con las ofertas del Black Friday de pádel en Álvarez.</strong>",

                    "en" => "Every Black Friday we bring you the widest selection of padel inventaries at unbelievably low prices.
                            Enjoy a great range of padel gear at incredible discounts. Take advantage of Black Friday deals on rackets, bags, shoes, clothing, wristbands, and more.
                            Stay tuned for the next Black Friday! Don’t miss this chance to <strong>get ahead on your Christmas shopping and grab the best prices with Álvarez’s Black Friday padel offers.</strong>",

                    "pt" => "Em cada Black Friday trazemos-lhe a mais ampla seleção de produtos de padel a preços incrivelmente baixos.
                            Desfrute de uma grande seleção de artigos para jogar padel, a preços imperdíveis. Aproveite os descontos da Black Friday em raquetes, paleteiros, sapatilhas, roupas e pulseiras de padel…
                            Fique atento à próxima Black Friday! Não perca a oportunidade de <strong>adiantar as suas compras de Natal e conseguir os melhores preços com as ofertas de padel da Black Friday na Álvarez.</strong>",

                    "fr" => "Chaque Black Friday, nous vous proposons la plus large sélection de produits de padel à des prix incroyablement réduits.
                            Profitez d’un vaste choix d’articles pour jouer au padel, à prix exceptionnels. Bénéficiez des réductions du Black Friday sur les raquettes, sacs, chaussures, vêtements et bracelets de padel…
                            Restez attentif au prochain Black Friday ! Ne manquez pas cette occasion de <strong>prendre de l’avance sur vos achats de Noël et d’obtenir les meilleurs prix avec les offres de padel du Black Friday chez Álvarez.</strong>",

                    "de" => "An jedem Black Friday bieten wir dir die größte Auswahl an Padel-Produkten zu unglaublich reduzierten Preisen.
                            Genieße eine große Auswahl an Padel-Ausrüstung zu fantastischen Preisen. Nutze die Black-Friday-Rabatte auf Schläger, Taschen, Schuhe, Kleidung und Handgelenkbänder…
                            Bleib dran für den nächsten Black Friday! Verpasse nicht die Gelegenheit, <strong>deine Weihnachtseinkäufe frühzeitig zu erledigen und die besten Preise mit den Black-Friday-Angeboten für Padel bei Álvarez zu sichern.</strong>",

                    "it" => "Ogni Black Friday ti offriamo la più ampia selezione di prodotti da padel a prezzi incredibilmente scontati.
                            Goditi un’ampia scelta di articoli per giocare a padel a prezzi imperdibili. Approfitta degli sconti del Black Friday su racchette, borsoni, scarpe, abbigliamento e polsini da padel…
                            Rimani aggiornato sul prossimo Black Friday! Non perdere l’occasione di <strong>anticipare i tuoi acquisti di Natale e ottenere i migliori prezzi con le offerte Black Friday di padel da Álvarez.</strong>",
                ],
                "imagenes" => [
                    "es" => [
                        [
                            "title" => "PALAS DE PADEL",
                            "image" => "palas.webp",
                            "url" => "/padel/palas_de_padel"
                        ],
                        [
                            "title" => "PALETEROS",
                            "image" => "paletero.webp",
                            "url" => "/padel/paleteros"
                        ],
                        [
                            "title" => "PELOTAS",
                            "image" => "pelotas.webp",
                            "url" => "/padel/pelotas"
                        ],
                        [
                            "title" => "ROPA",
                            "image" => "ropa.webp",
                            "url" => "/padel/ropa"
                        ],
                        [
                            "title" => "ZAPATILLAS",
                            "image" => "zapatillas.webp",
                            "url" => "/padel/zapatillas_de_padel"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "en" => [
                        [
                            "title" => "Paddle shovels",
                            "image" => "palas.webp",
                            "url" => "/en/padel/paddle_shovels"
                        ],
                        [
                            "title" => "Padel bags",
                            "image" => "paletero.webp",
                            "url" => "/en/padel/paleteros"
                        ],
                        [
                            "title" => "Balls",
                            "image" => "pelotas.webp",
                            "url" => "/en/padel/balls"
                        ],
                        [
                            "title" => "Padel clothing",
                            "image" => "ropa.webp",
                            "url" => "/en/padel/clothing"
                        ],
                        [
                            "title" => "Paddle shoes",
                            "image" => "zapatillas.webp",
                            "url" => "/en/padel/paddle_shoes"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "pt" => [
                        [
                            "title" => "RAQUETES DE PADEL",
                            "image" => "palas.webp",
                            "url" => "/pt/padel/raquetes_de_padel"
                        ],
                        [
                            "title" => "SACOS DE RAQUETES",
                            "image" => "paletero.webp",
                            "url" => "/pt/padel/sacos_de_raquetes"
                        ],
                        [
                            "title" => "BOLAS",
                            "image" => "pelotas.webp",
                            "url" => "/pt/padel/bolas"
                        ],
                        [
                            "title" => "ROUPA",
                            "image" => "ropa.webp",
                            "url" => "/pt/padel/roupa"
                        ],
                        [
                            "title" => "SAPATILHAS DE PADEL",
                            "image" => "zapatillas.webp",
                            "url" => "/pt/padel/sapatilhas_de_padel"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],


                    ],
                    "fr" => [
                        [
                            "title" => "Raquettes de Padel",
                            "image" => "palas.webp",
                            "url" => "/fr/padel/raquettes_de_padel"
                        ],
                        [
                            "title" => "Sacs de Padel",
                            "image" => "paletero.webp",
                            "url" => "/fr/padel/sacs_de_padel"
                        ],
                        [
                            "title" => "Balles",
                            "image" => "pelotas.webp",
                            "url" => "/fr/padel/balles"
                        ],
                        [
                            "title" => "Vêtements",
                            "image" => "ropa.webp",
                            "url" => "/fr/padel/vetements"
                        ],
                        [
                            "title" => "Chaussures de Padel",
                            "image" => "zapatillas.webp",
                            "url" => "/fr/padel/chaussures_de_padel"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "de" => [
                        [
                            "title" => "Padelschläger",
                            "image" => "palas.webp",
                            "url" => "/de/padel/padelschlaeger"
                        ],
                        [
                            "title" => "Padeltaschen",
                            "image" => "paletero.webp",
                            "url" => "/de/padel/padeltaschen"
                        ],
                        [
                            "title" => "Bälle",
                            "image" => "pelotas.webp",
                            "url" => "/de/padel/balle"
                        ],
                        [
                            "title" => "Padelbekleidung",
                            "image" => "ropa.webp",
                            "url" => "/de/padel/kleidung"
                        ],
                        [
                            "title" => "Paddelschuhe",
                            "image" => "zapatillas.webp",
                            "url" => "/de/padel/paddelschuhe"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                    "it" => [
                        [
                            "title" => "Racchette da Padel",
                            "image" => "palas.webp",
                            "url" => "/it/padel/racchette_da_padel"
                        ],
                        [
                            "title" => "Porta Racchette",
                            "image" => "paletero.webp",
                            "url" => "/it/padel/borse_e_porta_racchette_padel"
                        ],
                        [
                            "title" => "Palloni",
                            "image" => "pelotas.webp",
                            "url" => "/it/padel/palloni"
                        ],
                        [
                            "title" => "Abbigliamento",
                            "image" => "ropa.webp",
                            "url" => "/it/padel/abbigliamento"
                        ],
                        [
                            "title" => "Scarpe sportive",
                            "image" => "zapatillas.webp",
                            "url" => "/it/padel/scarpe_sportive"
                        ],
                        [
                            "title" => "1",
                            "image" => "relleno-2025.webp",
                            "url" => ""
                        ],
                    ],
                ],
            ];
        }

        if ($deporte == "RebajasInv25") {
            $data = [
                "texts" => [
                    "es" => "
                            Disfruta de lo que más te gusta con nuestras REBAJAS ESPECIALES de ENERO en golf, caza, hípica, esquí, pesca, buceo, náutica, padel…
                            Pantalones, chaquetas, botas, zapatos… renueva tu equipación con grandes descuentos!!!
                            Para que puedas disfrutar al máximo de tu deporte favorito, ampliamos nuestras REBAJAS con cientos de productos a precios especiales, solo por tiempo limitado.
                            Las mejores marcas, al mejor precio: Adidas, Beretta, Shimano, TaylorMade, Chervó, Footjoy, Helly Hansen, Rapala, Salomon, Slam, Hart, Regatta, Polo Ralph Lauren, Kingsland, Horze, Zaldi, Bullpadel,… Aprovecha nuestras rebajas para equiparte con lo mejor de los mejores, al mejor precio.
                            Y recuerda, dispones de muchas más rebajas en nuestras tiendas:
                            • Madrid: C/ Capitán Haya nº60 (ahora C/ Poeta Joan Maragall, nº60)
                            • Madrid: C/ Diego de León nº56
                            • La Coruña: Polígono de Pocomaco, C-13",
                    "pt" => "Desfrute do que mais gosta com a nossa VENDA ESPECIAL DE JANEIRO em golfe, caça, equitação, esqui, pesca, mergulho, vela, padel...
                            Calças, casacos, botas, sapatos... renove o seu equipamento com grandes descontos!!!!
                            Para que possa desfrutar ao máximo do seu desporto preferido, alargamos a nossa VENDA com centenas de produtos a preços especiais, por tempo limitado.
                            As melhores marcas ao melhor preço: Adidas, Beretta, Shimano, TaylorMade, Chervó, Footjoy, Helly Hansen, Rapala, Salomon, Slam, Hart, Regatta, Polo Ralph Lauren, Kingsland, Horze, Zaldi, Bullpadel,... Aproveite os nossos saldos para se equipar com o melhor do melhor, ao melhor preço.
                            ",
                    "fr" => "Profitez de ce que vous aimez le plus avec nos SOLDES DE JANVIER pour le golf, la chasse, l'équitation, le ski, la pêche, la plongée, la voile, le padel...
                            Pantalons, vestes, bottes, chaussures... renouvelez votre équipement avec de grandes réductions !!!!
                            Pour que vous puissiez profiter au maximum de votre sport favori, nous étendons nos SOLDES avec des centaines de produits à des prix spéciaux, pour une durée limitée.
                            Les meilleures marques au meilleur prix : Adidas, Beretta, Shimano, TaylorMade, Chervó, Footjoy, Helly Hansen, Rapala, Salomon, Slam, Hart, Regatta, Polo Ralph Lauren, Kingsland, Horze, Zaldi, Bullpadel,... Profitez de nos soldes pour vous équiper avec le meilleur du meilleur, au meilleur prix.
                            ",
                    "en" => "Enjoy what you like the most with our SPECIAL JANUARY SALE in golf, hunting, horse riding, skiing, fishing, diving, sailing, padel...
                            Pants, jackets, boots, shoes... renew your equipment with great discounts!!!!
                            So you can make the most of your favorite sport, we extend our SALE with hundreds of inventaries at special prices, only for a limited time.
                            The best brands at the best price: Adidas, Beretta, Shimano, TaylorMade, Chervó, Footjoy, Helly Hansen, Rapala, Salomon, Slam, Hart, Regatta, Polo Ralph Lauren, Kingsland, Horze, Zaldi, Bullpadel,... Take advantage of our sales to equip yourself with the best of the best, at the best price.
                            ",
                    "de" => "Genießen Sie das, was Ihnen am meisten Spaß macht, mit unserem JANUAR-SPEZIAL-SALE für Golf, Jagd, Reiten, Angeln, Tauchen...
                            Hosen, Jacken, Stiefel, Schuhe... erneuern Sie Ihre Ausrüstung mit tollen Rabatten!!!!
                            Damit Sie Ihren Lieblingssport in vollen Zügen genießen können, erweitern wir unseren SALE mit Hunderten von Produkten zu Sonderpreisen, nur für eine begrenzte Zeit.
                            Die besten Marken zum besten Preis: Adidas, Beretta, Shimano, TaylorMade, Chervó, Footjoy, Helly Hansen, Rapala, Salomon, Slam, Hart, Regatta, Polo Ralph Lauren, Kingsland, Horze, Zaldi, Bullpadel,... Nutzen Sie unseren Schlussverkauf, um sich mit dem Besten vom Besten zum besten Preis auszustatten.
                            ",
                    "pt" => "Godetevi ciò che vi piace di più con la nostra VENDITA SPECIALE DI GENNAIO in golf, caccia, equitazione, pesca, subacquea...
                            Pantaloni, giacche, stivali, scarpe... rinnovate il vostro equipaggiamento con grandi sconti!!!!
                            Per permettervi di praticare al meglio il vostro sport preferito, prolunghiamo la nostra VENDITA con centinaia di prodotti a prezzi speciali, solo per un periodo limitato.
                            Le migliori marche al miglior prezzo: Adidas, Beretta, Shimano, TaylorMade, Chervó, Footjoy, Helly Hansen, Rapala, Salomon, Slam, Hart, Regatta, Polo Ralph Lauren, Kingsland, Horze, Zaldi, Bullpadel,... Approfittate dei nostri saldi per dotarvi del meglio del meglio, al miglior prezzo.
                            "
                ],
                "titles" => [
                    "es" => "REBAJAS",
                    "pt" => "SALDOS",
                    "fr" => "SOLDES",
                    "en" => "SALES",
                    "de" => "RABATTE",
                    "it" => "SALDI",
                ],
                "titlesprint" => [
                    "es" => "Rebajas Álvarez invierno 2025",
                    "pt" => "SALDOS JANEIRO Álvarez 2025",
                    "fr" => "Ventes d'hiver 2025 d'Álvarez",
                    "en" => "Álvarez winter 2025 sales",
                    "de" => "Álvarez Winterschlussverkauf 2025",
                    "it" => "Álvarez saldi invernali 2025",
                ],
                "descriptions" => [
                    "es" => [
                        "caza" => "CAZA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "HÍPICA",
                        "buceo" => "BUCEO",
                        "nautica" => "NAUTICA",
                        "esqui" => "ESQUÍ",
                        "padel" => "PADEL",
                    ],
                    "pt" => [
                        "caza" => "CAÇA",
                        "golf" => "GOLFE",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAÇAO",
                        "buceo" => "MERGULHO",
                        "nautica" => "VELA",
                        "esqui" => "ESQUI",
                        "padel" => "PADEL",
                    ],
                    "fr" => [
                        "caza" => "CHASSE",
                        "golf" => "GOLF",
                        "pesca" => "PÊCHE",
                        "hipica" => "ÈQUITATION",
                        "buceo" => "PLONGÈE",
                        "nautica" => "NAUTIQUE",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "en" => [
                        "caza" => "HUNTING",
                        "golf" => "GOLF",
                        "pesca" => "FISHING",
                        "hipica" => "RIDING",
                        "buceo" => "DIVING",
                        "nautica" => "BOATING",
                        "esqui" => "SKIING",
                        "padel" => "PADEL",
                    ],
                    "de" => [
                        "caza" => "JAGD",
                        "golf" => "GOLF",
                        "pesca" => "ANGELN",
                        "hipica" => "REITEN",
                        "buceo" => "TAUCHEN",
                        "nautica" => "NAUTIK",
                        "esqui" => "SKI",
                        "padel" => "PADEL",
                    ],
                    "it" => [
                        "caza" => "CACCIA",
                        "golf" => "GOLF",
                        "pesca" => "PESCA",
                        "hipica" => "EQUITAZIONE",
                        "buceo" => "SUBACQUEA",
                        "nautica" => "NAUTICA",
                        "esqui" => "SCI",
                        "padel" => "PADEL",
                    ],
                ],
                'urls' => [
                    "es" => [
                        "caza" => "/caza",
                        "golf" => "/golf",
                        "pesca" => "/pesca",
                        "hipica" => "/hipica",
                        "buceo" => "/buceo",
                        "nautica" => "/nautica",
                        "esqui" => "/esqui",
                        "padel" => "/padel",
                    ],
                    "pt" => [
                        "caza" => "/pt/caca",
                        "golf" => "/pt/golfe",
                        "pesca" => "/pt/pesca",
                        "hipica" => "/pt/equitacao",
                        "buceo" => "/pt/mergulho",
                        "nautica" => "/pt/vela",
                        "esqui" => "/pt/esqui",
                        "padel" => "/pt/padel",
                    ],
                    "fr" => [
                        "caza" => "/fr/chasse",
                        "golf" => "/fr/golf",
                        "pesca" => "/fr/peche",
                        "hipica" => "/fr/equitation",
                        "buceo" => "/fr/plongee",
                        "nautica" => "/fr/nautique",
                        "esqui" => "/fr/ski",
                        "padel" => "/fr/padel",
                    ],
                    "en" => [
                        "caza" => "/en/hunting",
                        "golf" => "/en/golf",
                        "pesca" => "/en/fishing",
                        "hipica" => "/en/horse_riding",
                        "buceo" => "/en/diving",
                        "nautica" => "/en/boating",
                        "esqui" => "/en/skiing",
                        "padel" => "/en/padel",
                    ],
                    "de" => [
                        "caza" => "/de/jagd",
                        "golf" => "/de/golf",
                        "pesca" => "/de/angeln",
                        "hipica" => "/de/reiten",
                        "buceo" => "/de/tauchen",
                        "nautica" => "/de/nautik",
                        "esqui" => "/de/ski",
                        "padel" => "/de/padel",
                    ],
                    "it" => [
                        "caza" => "/it/caccia",
                        "golf" => "/it/golf",
                        "pesca" => "/it/pesca",
                        "hipica" => "/it/equitazione",
                        "buceo" => "/it/subacquea",
                        "nautica" => "/it/nautica",
                        "esqui" => "/it/sci",
                        "padel" => "/it/padel",
                    ],
                ]
            ];
        }

        return $data;
    }
}
