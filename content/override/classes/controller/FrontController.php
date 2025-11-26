<?php

class FrontController extends FrontControllerCore
{
    public function getIdDeporteByName($name_deporte)
    {
        return Db::getInstance()->getValue("SELECT `id_category` FROM " . _DB_PREFIX_ . "category_lang WHERE (name = '" . $name_deporte . "' || name = '" . strtoupper($name_deporte) . "' || link_rewrite = '" . $name_deporte . "' || link_rewrite = '" . str_replace(' ', '-', $name_deporte) . "') AND id_lang = " . $this->context->language->id);
    }
    protected function smartyOutputContent($content)
    {
        $this->context->cookie->write();
        $html = '';
        $theme = $this->context->shop->theme->getName();
        if (is_array($content)) {
            foreach ($content as $tpl) {
                $html .= $this->context->smarty->fetch($tpl, null, $theme . '/templates/' . $this->getLayout());
            }
        } else {
            $html = $this->context->smarty->fetch($content, null, $theme . '/templates/' . $this->getLayout());
        }
        Hook::exec('actionOutputHTMLBefore', ['html' => &$html]);
        echo trim($html);
    }
    /**
     * Sanitize / Clean params of an URL
     *
     * @param string $url URL to clean
     *
     * @return string cleaned URL
     */
    protected function sanitizeUrl(string $url): string
    {
        $params = [];
        $url_details = parse_url($url);

        if (!empty($url_details['query'])) {
            parse_str($url_details['query'], $query);
            $params = $this->sanitizeQueryOutput($query);
        }

        $excluded_key = ['isolang', 'id_lang', 'controller', 'fc', 'id_product', 'id_category', 'id_manufacturer', 'id_supplier', 'id_cms'];
        $excluded_key = array_merge($excluded_key, $this->redirectionExtraExcludedKeys);
        foreach ($_GET as $key => $value) {
            if (in_array($key, $excluded_key)
                || !Validate::isUrl($key)
                || !$this->validateInputAsUrl($value)
            ) {
                continue;
            }

            $params[Tools::safeOutput($key)] = is_array($value) ? array_walk_recursive($value, 'Tools::safeOutput') : Tools::safeOutput($value);
        }

        $str_params = http_build_query($params, '', '&');
        $sanitizedUrl = preg_replace('/^([^?]*)?.*$/', '$1', $url) . (!empty($str_params) ? '?' . $str_params : '');

        if (isset($params['deporte'])) {
            $_POST['deporte'] = $params['deporte'];
        }

        return $sanitizedUrl;
    }


    /*
    * module: pagecache
    * date: 2024-05-21 08:49:56
    * version: 9.3.2
    */
    protected function displayAjax()
    {
        if (!Tools::getIsset('page_cache_dynamics_mods')) {
            if (is_callable('parent::displayAjax')) {
                return parent::displayAjax();
            } else {
                return;
            }
        }
        $this->initHeader();
        $this->assignGeneralPurposeVariables();
        require_once _PS_MODULE_DIR_ . 'pagecache/pagecache.php';
        $result = PageCache::execDynamicHooks($this);
        if (Tools::version_compare(_PS_VERSION_, '1.6', '>')) {
            $this->context->smarty->assign(array(
                'js_def' => PageCache::getJsDef($this),
            ));
            $result['js'] = $this->context->smarty->fetch(_PS_ALL_THEMES_DIR_ . 'javascript.tpl');
        }
        $this->context->cookie->write();
        header('Content-Type: text/html');
        header('Cache-Control: no-cache');
        header('X-Robots-Tag: noindex');
        $json = json_encode($result);
        if ($json === false) {
            header("HTTP/1.1 500 Internal Server Error");
            die(json_last_error_msg());
        }
        die($json);
    }
    /*
    * module: pagecache
    * date: 2024-05-21 08:49:56
    * version: 9.3.2
    */
    public function isRestrictedCountry()
    {
        return $this->restrictedCountry;
    }
    /*
    * module: pagecache
    * date: 2024-05-21 08:49:56
    * version: 9.3.2
    */
    public function geolocationManagementPublic($default_country)
    {
        $ret = $this->geolocationManagement($default_country);
        if (!$ret) {
            return $default_country;
        }
        return $ret;
    }

    ////////////////////// LOCALIZACION ADDIS ////////////////////////

    public function init()
    {

        parent::init();

        if (isset($_GET['logout']) || isset($this->context->customer) && ($this->context->customer->logged && Customer::isBanned($this->context->customer->id))) {
            $this->context->customer->logout();
            Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);
        } elseif (isset($_GET['mylogout'])) {
            $this->context->customer->mylogout();
            Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);
        }

        try {

            $context = Context::getContext();

            $sports = $this->getSportsByIdsAndTranslate();

            $this->context->smarty->assign(array(
                'sports' => $sports,
                'iso_code' => $context->language->iso_code,
            ));

            if (isset($this->context->customer) && $this->context->customer->isLogged()) {
                $this->context->smarty->assign('customerauth', [
                    'firstname' => (string) $this->context->customer->firstname,
                    'lastname' => (string) $this->context->customer->lastname,
                    'email' => (string) $this->context->customer->email,
                ]);
            }else {
                $this->context->smarty->assign('customerauth', null);
            }


            // Verifica que el contexto y el cliente estén inicializados

                $id_country = 6; //default España
                if ($context->language->id == 1) $id_country = 6;
                if ($context->language->id == 2) $id_country = 17;
                if ($context->language->id == 3) $id_country = 8;
                if ($context->language->id == 4) $id_country = 15;
                if ($context->language->id == 5) $id_country = 1;
                if ($context->language->id == 6) $id_country = 10;

                $country = new Country($id_country, (int)$context->language->id);
                $context->country = $country;
                $context->cookie->iso_code_country = strtoupper($context->country->iso_code);


            // Obtén el módulo por su nombre (en este ejemplo, 'mimodulo')
            $module = Module::getInstanceByName('alserneteventmanager');

            if ($module && $module->active) {
                // Llama a la función pública del módulo (por ejemplo, 'miFuncion')

                $events = $module->getActiveEvents();

                if ($events) {
                    // Asignar los eventos a la plantilla
                    $this->context->smarty->assign('active_events', $events);

                    // Puedes generar clases específicas para cada evento
                    $eventClasses = [];
                    $eventClasses[] = 'event';
                    foreach ($events as $event) {
                        // Generamos una clase específica para cada evento, por ejemplo usando el id_event
                        $eventClasses[] = 'event-' . $event['event_title'];
                        // $module->flagProducts($event);
                    }

                    // Asignar las clases generadas
                    $this->context->smarty->assign('event_classes', implode(' ', $eventClasses));
                } else {
                    $this->context->smarty->assign('active_events', []);
                    $this->context->smarty->assign('event_classes', '');
                }

            } else {
                // Si el módulo no está cargado, manejar el error o lógica correspondiente
                // Por ejemplo, asignar un mensaje de error
                $this->context->smarty->assign('error', 'El módulo no se encuentra disponible.');
            }


        } catch (Exception $e) {
            $country = $context->country ?? null; // Usa el operador de fusión nula para evitar errores
        }

        if ($country) {
            $context->country = $country;
        }
        $this->context->smarty->assign('need_invoice_option', false);

    }

    public function getSportsByIdsAndTranslate() {

        $lang = $this->context->language->id;

        $sports_map = [
            1 => 'GOLF',
            5 => 'HUNTING',
            6 => 'FISHING',
            3 => 'HORSE',
            4 => 'DIVING',
            2 => 'BOATING',
            9 => 'SKIING',
            1395 => 'PADEL',
            10 => 'ADVENTURE',
        ];

        $sports_translation_map = [
            1 => [
                'GOLF' => 'GOLF',
                'HUNTING' => 'CAZA',
                'FISHING' => 'PESCA',
                'HORSE' => 'HÍPICA',
                'DIVING' => 'BUCEO',
                'BOATING' => 'NAUTICA',
                'SKIING' => 'ESQUÍ',
                'PADEL' => 'PÁDEL',
                'ADVENTURE' => 'AVENTURA',
            ],
            2 => [
                'GOLF' => 'GOLF',
                'HUNTING' => 'HUNTING',
                'FISHING' => 'FISHING',
                'HORSE' => 'HORSE RIDING',
                'DIVING' => 'DIVING',
                'BOATING' => 'BOATING',
                'SKIING' => 'SKIING',
                'PADEL' => 'PADEL',
                'ADVENTURE' => 'ADVENTURE',
            ],
            3 => [
                'GOLF' => 'GOLF',
                'HUNTING' => 'CHÂSSE',
                'FISHING' => 'PÊCHE',
                'HORSE' => 'ÉQUITATION',
                'DIVING' => 'PLONGÉE',
                'BOATING' => 'NAUTIQUE',
                'SKIING' => 'SKI',
                'PADEL' => 'PADEL',
                'ADVENTURE' => 'OUTDOOR',
            ],
            4 => [
                'GOLF' => 'GOLFE',
                'HUNTING' => 'CAÇA',
                'FISHING' => 'PESCA',
                'HORSE' => 'EQUITAÇÃO',
                'DIVING' => 'MERGULHO',
                'BOATING' => 'VELA',
                'SKIING' => 'ESQUI',
                'PADEL' => 'PADEL',
                'ADVENTURE' => 'AVENTURA',
            ],
            5 => [
                'GOLF' => 'GOLF',
                'HUNTING' => 'JAGD',
                'FISHING' => 'ANGELN',
                'HORSE' => 'REITEN',
                'DIVING' => 'TAUCHEN',
                'BOATING' => 'NAUTIK',
                'SKIING' => 'SKI',
                'PADEL' => 'PADEL',
                'ADVENTURE' => 'OUTDOOR',
            ],
            6 => [
                'GOLF' => 'GOLF',
                'HUNTING' => 'CACCIA',
                'FISHING' => 'PESCA',
                'HORSE' => 'EQUITAZIONE',
                'DIVING' => 'SUBACQUEA',
                'BOATING' => 'NAUTICA',
                'SKIING' => 'SCI',
                'PADEL' => 'PADEL',
                'ADVENTURE' => 'OUTDOOR',
            ],
        ];

        $ids = [1, 5, 6, 3, 4, 2,  9 , 1395, 10];

        $sports_in_language = array_map(function ($id) use ($sports_map, $sports_translation_map, $lang) {
            $sport_name = $sports_map[$id];
            return [
                'id' => $id,
                'name' => $sports_translation_map[$lang][$sport_name] ?? $sport_name,
            ];
        }, $ids);


        return $sports_in_language;
    }


}
