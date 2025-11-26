<?php
/**
 *  Please read the terms of the CLUF license attached to this module(cf "licences" folder)
 *
 * @author    Línea Gráfica E.C.E. S.L.
 * @copyright Lineagrafica.es - Línea Gráfica E.C.E. S.L. all rights reserved.
 * @license   https://www.lineagrafica.es/licenses/license_en.pdf
 *            https://www.lineagrafica.es/licenses/license_es.pdf
 *            https://www.lineagrafica.es/licenses/license_fr.pdf
 */

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;

require_once(
    _PS_MODULE_DIR_ .
    DIRECTORY_SEPARATOR .
    'lgcomments' .
    DIRECTORY_SEPARATOR .
    'classes' .
    DIRECTORY_SEPARATOR .
    'LGUtils.php'
);


/**
 * Class LGMailer
 *
 * Clase para gestionar el envio de emails
 * Todo: Tratar de encapsular el envío de emails en esta clase
 */
class LGMailer
{
    public $default_language;
    public $subjects_lang;
    public $email_cron;
    public $email_alerts;
    public $sendtwice;
    public $daysafter;
    public $dias_desde              = 0;
    public $dias_hasta              = 0;
    public $theme_template_path;
    public $theme_template_path_en;
    public $module_template_path;
    public $module_template_path_en;
    private $module;
    // private $max_days_changed = 3; // 20230731 - Num. máx de días entre que se realizó el pedido y se cambió al estado deseado

    public function __construct()
    {
        $this->module           = ModuleCore::getInstanceByName('lgcomments');
        $this->dias_desde       = (int)Configuration::get('PS_LGCOMMENTS_DIAS');
        $this->dias_hasta       = (int)Configuration::get('PS_LGCOMMENTS_DIAS2');
        $this->default_language = ConfigurationCore::get('PS_LANG_DEFAULT');
        $this->email_cron       = Configuration::get('PS_LGCOMMENTS_EMAIL_CRON');
        $this->email_alerts     = Configuration::get('PS_LGCOMMENTS_EMAIL_ALERTS');
        $this->sendtwice        = (int)Configuration::get('PS_LGCOMMENTS_EMAIL_TWICE');
        $this->daysafter        = (int)Configuration::get('PS_LGCOMMENTS_DAYS_AFTER');
        $this->subjects_lang    = $this->getSubjects();
        $this->theme_template_path = _PS_THEME_DIR_
            . 'modules' . DIRECTORY_SEPARATOR
            . 'lgcomments' . DIRECTORY_SEPARATOR
            . 'mails' . DIRECTORY_SEPARATOR;
        $this->theme_template_path_en = _PS_THEME_DIR_
            . 'modules' . DIRECTORY_SEPARATOR
            . 'lgcomments' . DIRECTORY_SEPARATOR
            . 'mails' . DIRECTORY_SEPARATOR
            . 'en' . DIRECTORY_SEPARATOR;
        $this->module_template_path = _PS_MODULE_DIR_
            . 'lgcomments' . DIRECTORY_SEPARATOR
            . 'mails' . DIRECTORY_SEPARATOR;
        $this->module_template_path_en = _PS_MODULE_DIR_
            . 'lgcomments' . DIRECTORY_SEPARATOR
            . 'mails' . DIRECTORY_SEPARATOR
            . 'en' . DIRECTORY_SEPARATOR;
    }

    /**
     * Obtiene el listado de ordenes a mandar, el parametro extra_info es para la llamada en el backoffice
     *
     * @param bool $add_extra_info
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public function getOrders($add_extra_info = false)
    {
        $query = new DbQuery();
        $query->select('o.`id_order`');
        $query->select('o.`id_customer`');
        $query->select('o.`id_cart`');
        $query->select('o.`id_lang`');
        $query->select('o.`id_shop`');
        $query->select('lo.`id_order` as loidorder'); // Testigo de que hemnos procesado esta orden si no será null
        $query->select('lo.`date_email`');
        $query->select('lo.`sent`');
        $query->select('lo.`date_email2`');
        $query->select('lo.`voted`');
        $query->select('lo.`hash`');
        $query->select('c.`firstname`');
        $query->select('c.`lastname`');
        $query->select('c.`email`');

        if ($add_extra_info) {
            $query->select('osl.name as statusname');
            $query->select('o.`date_add`');
            $query->select('o.`reference`');
            $query->select('os.`color`');
            $query->select('c.`newsletter`');
            $query->select('c.`optin`');
            $query->select('CONCAT(c.firstname, " ", (SUBSTRING(c.lastname,1,1)), ".") as customer');
        }

        $query->from('orders', 'o');
        $query->innerJoin('lgcomments_status', 'ek', 'o.`current_state` = ek.`id_order_status`');

        // 20230731 - Miramos el día en que se cambió a ese estado
        // $query->innerJoin('order_history', 'oh', 'o.`current_state` = oh.`id_order_state` AND o.`id_order` = oh.`id_order`');

        if ($add_extra_info) {
            $query->innerJoin('order_state_lang', 'osl', 'o.`current_state` = osl.`id_order_state`');
            $query->innerJoin('order_state', 'os', 'osl.`id_order_state` = os.`id_order_state`');
        }
        $query->leftJoin('lgcomments_orders', 'lo', 'o.`id_order` = lo.`id_order`');


        if (version_compare(_PS_VERSION_, '1.6.1.1', '>=')) {
            $query->rightJoin('customer_group', 'cg', 'o.`id_customer` = cg.`id_customer`');
        } else {
            $query->join(
                'RIGHT JOIN `'._DB_PREFIX_.bqSQL('customer_group').'` `'.pSQL('cg')
                .'` ON o.`id_customer` = cg.`id_customer`'
            );
        }

        if (version_compare(_PS_VERSION_, '1.6.1.1', '>=')) {
            $query->rightJoin('customer', 'c', 'o.`id_customer` = c.`id_customer`');
        } else {
            $query->join(
                'RIGHT JOIN `'._DB_PREFIX_.bqSQL('customer').'` `'.pSQL('c')
                .'` ON o.`id_customer` = c.`id_customer`'
            );
        }
        $query->innerJoin('lgcomments_customergroups', 'lcg', 'cg.`id_group` = lcg.`id_customer_group`');

        if ($add_extra_info) {
            $query->innerJoin('group_lang', 'gl', 'cg.`id_group` = gl.`id_group`');
        }

        if (version_compare(_PS_VERSION_, '1.6.1.1', '>=')) {
            $query->rightJoin('lgcomments_multistore', 'lm', 'o.`id_shop` = lm.`id_shop`');
        } else {
            $query->join(
                'RIGHT JOIN `'._DB_PREFIX_.bqSQL('lgcomments_multistore').'` `'.pSQL('lm').'` ' .
                'ON o.`id_shop` = lm.`id_shop`'
            );
        }

        $query->where(
            'o.`date_add` <= DATE_SUB(CONCAT(CURDATE(), " 23:59:59"), INTERVAL ' . (int)$this->dias_desde . ' DAY)'
        );

	// 20230731 - No puede diferir más de {$max_days_changed} días entre que se realizó el pedido y se cambió a dicho estado
        // $query->where('DATEDIFF(oh.`date_add`, o.`date_add`) <= ' . (int)$this->max_days_changed);

        if ($this->dias_hasta > 1) {
            $query->where(
                'o.`date_add` >= DATE_SUB(CONCAT(CURDATE(), " 00:00:00"), INTERVAL ' . (int)$this->dias_hasta . ' DAY)'
            );
        }
        if (Configuration::get('PS_LGCOMMENTS_BOXES') == 2) {
            $query->where('c.`newsletter` = 1');
        } elseif (Configuration::get('PS_LGCOMMENTS_BOXES') == 3) {
            $query->where('c.`optin` = 1');
        } elseif (Configuration::get('PS_LGCOMMENTS_BOXES') == 4) {
            $query->where('c.`newsletter` = 1 AND c.`optin` = 1');
        }

        if ($add_extra_info) {
            $context = Context::getContext();
            $query->where('osl.id_lang = ' . (int)$context->language->id);
            $query->where('gl.id_lang = ' . (int)$context->language->id);
        }

        $query->orderBy('o.`id_order` DESC');
        return Db::getInstance()->executeS($query);
    }

    /**
     * Obtiene todos los asuntos en los lenguajes disponibles
     *
     * @return array
     */
    public function getSubjects()
    {
        $langs            = Language::getLanguages();
        $subjects_lang    = array();
        foreach ($langs as $lang) {
            $subjects_lang[$lang['id_lang']] = Configuration::get('PS_LGCOMMENTS_SUBJECT' . $lang['iso_code']);
        }
        return $subjects_lang;
    }

    public function getSubject($id_lang)
    {
        if (isset($this->subjects_lang[$id_lang])) {
            $subject = $this->subjects_lang[$id_lang];
        } else {
            $subject = $this->subjects_lang[$this->default_language];
        }

        return $subject;
    }

    public function needSendFirstTime($order)
    {
        return !isset($order['loidorder'])
            || (isset($order['loidorder']) && (int)$order['sent'] == 0);
    }

    /**
     * Recibe un array procedente del metodo getOrders, o sea una fila
     *
     * @param $lgcommentorder
     * @return bool
     */
    public function needSendAgain($order)
    {
        // Todo: esto comapra strictamente 10 días desde la hora exacta, es mejor hacerlo quitar diez días
        // Todo: y que comience a las 0:00:00 de ese día
        $days_after = (int)Configuration::get('PS_LGCOMMENTS_DAYS_AFTER');
        if (empty($days_after)) {
            return false;
        }
        $dateAfter = new DateTime();
        $dateEmail = DateTime::createFromFormat('Y-m-d H:i:s', $order['date_email']);
        $dateEmail->add(new DateInterval('P'.(int)Configuration::get('PS_LGCOMMENTS_DAYS_AFTER').'D'));

        if ($this->sendtwice
            && 1 > (int)$order['voted']
            && 2 > (int)$order['sent']
            && (int)$order['sent'] > 0
            && $dateEmail <= $dateAfter
        ) {
            return true;
        }

        return false;
    }

    public function getTemplateVars($order)
    {
        $protocol_link = (Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode()) ?
            'https://' :
            'http://';
        $useSSL = (
            (
                isset($this->ssl)
                && $this->ssl
                && Configuration::get('PS_SSL_ENABLED')
            )
            || Tools::usingSecureMode()
        ) ? true : false;
        $protocol_content = ($useSSL) ? 'https://' : 'http://';
        $link             = new Link($protocol_link, $protocol_content);

        $preproducts    = $this->getCartProducts((int)$order['id_cart']);
        $products_array = array();
        $products       = '';
        foreach ($preproducts as $preproduct) {
            $id_product           = (int)$preproduct['id_product'];
            $id_product_attribute = (int)$preproduct['id_product_attribute'];
            $product_name         = Product::getProductName($id_product, $id_product_attribute);
            $image                = Image::getCover($id_product);
            $product              = new Product($id_product, $id_product_attribute, $order['id_lang']);
            $imagePath            = $this->getImageLink(
                $protocol_content,
                $product->link_rewrite,
                $image['id_image'],
                LGUtils::getFormattedName('cart')
            );
            $products_array[] = array(
                'product_name' => $product_name,
                'image_path'   => $imagePath,
            );
        }

        $module   = Module::getInstanceByName('lgcomments');
        $products = $module->getMailTemplateProducts($products_array);

        $module_link = $link->getModuleLink(
            'lgcomments',
            'account',
            array('id_order' => $order['id_order'], 'lghash' => $order['hash']),
            null,
            $order['id_lang'],
            $order['id_shop'],
            false
        );
        $mail_lang = $this->getTemplateLang($order['id_lang']);
        $shop = new Shop((int)$order['id_shop'], $mail_lang);
        $template_vars = array(
            '{firstname}'       => $order['firstname'],
            '{lastname}'        => $order['lastname'],
            '{storename}'       => $shop->name,
            '{email}'           => $order['email'],
            '{id_order}'        => $order['id_order'],
            '{link}'            => $module_link,
            '{product_details}' => $products
        );

        return $template_vars;
    }

    // Todo: Usar la clase del Core y eliminar este método
    private function getCartProducts($id_cart)
    {
        $sql = 'SELECT `id_product`, `id_product_attribute`, `quantity`
                FROM `' . _DB_PREFIX_ . 'cart_product`
                WHERE `id_cart` = ' . (int)$id_cart;

        return Db::getInstance()->executeS($sql);
    }

    private function generateHash()
    {
        $number_range = array('min' => 48, 'max' => 57);
        $upper_range  = array('min' => 65, 'max' => 90);
        $lower_range  = array('min' => 97, 'max' => 122);
        $hash         = '';

        for ($i = 0; $i < 59; $i++) {
            switch (rand(0, 2)) {
                case 0:
                    $hash .= chr(rand($number_range['min'], $number_range['max']));
                    break;
                case 1:
                    $hash .= chr(rand($upper_range['min'], $upper_range['max']));
                    break;
                case 2:
                    $hash .= chr(rand($lower_range['min'], $lower_range['max']));
                    break;
            }
        }
        return $hash;
    }

    public function getHash($order)
    {
        if (!isset($order['hash'])) {
            $hash = $this->generateHash();
        } else {
            $hash = $order['hash'];
        }
        return $hash;
    }

    /**
     * Intenta obtener la plantilla de email y si no usará la plantilla en inglés que si existe porque la hemos
     * subido esto implica que puede que se mande un correo en inglés pero con el idioma por defecto de la tienda
     * (OJO con esto ya que anteriormente se forzaba el inglés y puede que no existiera, y esto se ha cambiado a
     * mandar en el idioma por defecto)
     *
     * Todo: Mejorar esto ya que esto comprueba la existencia del directorio, pero puede ser que aun existiendo
     * Todo: no exista el archivo
     *
     * @param $order
     */
    public function getTemplatePath($id_lang = null)
    {
        if (is_null($id_lang) || (int)$id_lang == 0) {
            $id_lang = $this->default_language;
        }

        $template_lang           = Language::getIsoById($id_lang) . DIRECTORY_SEPARATOR;
        $theme_template_path     = $this->theme_template_path . $template_lang;
        $module_template_path    = $this->module_template_path . $template_lang;

        // Check if email template exists for current iso code. If not, use English template.
        if (is_dir($theme_template_path)) { // Probamos la plantilla del tema en el lenguaje del pedido
            $path = $theme_template_path;
        } elseif (is_dir($module_template_path)) { // Probamos la plantilla del modulo en el lenguaje del pedido
            $path = $this->module_template_path;
        } elseif (is_dir($this->theme_template_path_en)) { // Probamos la plantilla del tema en inglés
            $path = $this->theme_template_path_en;
        } elseif (is_dir($this->module_template_path_en)) { // Probamos la plantilla del módulo en inglés
            $path = $this->module_template_path_en;
        } else {
            // Todo: Mejorar esto con uso de excepciones
            $path = false;
            // Excepción, no hay plantillas
            // throw new Exception($this->module->l('There are not available mail templates.'), 100);
        }

        return $path;
    }

    /**
     * Intenta obtener el idioma del pedido, si no es posible retorna el idioma por defecto, esto se hace para que
     * en caso de que unpedido tenga un lenguaje eliminado el mensaje pueda enviarse usando otro idioma.
     *
     * @param $id_lang
     */
    public function getTemplateLang($id_lang = null)
    {
        if (is_null($id_lang) || (int)$id_lang == 0) {
            $id_lang = $this->default_language;
        }

        $lang = new Language($id_lang);
        if (Validate::isLoadedObject($lang)) {
            return $lang->id;
        } else {
            return $this->default_language;
        }
    }

    public function getImageLink($protocol_content, $name, $ids, $type = null)
    {
        $link = new Link;

        return $protocol_content.$link->getImageLink($name, $ids, $type);
    }

    protected static $cache_nb_media_servers = null;

    public static function getMediaServer($filename, $id_shop)
    {
        if (self::$cache_nb_media_servers === null
            && defined('_MEDIA_SERVER_1_')
            && defined('_MEDIA_SERVER_2_')
            && defined('_MEDIA_SERVER_3_')) {
            if (_MEDIA_SERVER_1_ == '') {
                self::$cache_nb_media_servers = 0;
            } elseif (_MEDIA_SERVER_2_ == '') {
                self::$cache_nb_media_servers = 1;
            } elseif (_MEDIA_SERVER_3_ == '') {
                self::$cache_nb_media_servers = 2;
            } else {
                self::$cache_nb_media_servers = 3;
            }
        }

        if ($filename
            && self::$cache_nb_media_servers
            && ($id_media_server = (abs(crc32($filename)) % self::$cache_nb_media_servers + 1))
        ) {
            return constant('_MEDIA_SERVER_' . $id_media_server . '_');
        }

        return Tools::usingSecureMode() ? self::getShopDomainSSL($id_shop) : self::getShopDomain($id_shop);
    }

    /**
     * getShopDomain returns domain name according to configuration and ignoring ssl.
     *
     * @param bool $http if true, return domain name with protocol
     * @param bool $entities if true, convert special chars to HTML entities
     *
     * @return string domain
     */
    public static function getShopDomain($id_shop, $http = false, $entities = false)
    {
        if (!$domain = ShopUrl::getMainShopDomain($id_shop)) {
            $domain = Tools::getHttpHost();
        }
        if ($entities) {
            $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
        }
        if ($http) {
            $domain = 'http://' . $domain;
        }

        return $domain;
    }

    /**
     * getShopDomainSSL returns domain name according to configuration and depending on ssl activation.
     *
     * @param bool $http if true, return domain name with protocol
     * @param bool $entities if true, convert special chars to HTML entities
     *
     * @return string domain
     */
    public static function getShopDomainSSL($id_shop, $http = false, $entities = false)
    {
        if (!$domain = ShopUrl::getMainShopDomainSSL($id_shop)) {
            $domain = Tools::getHttpHost();
        }
        if ($entities) {
            $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
        }
        if ($http) {
            $domain = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $domain;
        }

        return $domain;
    }
}
