<?php

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;


if (!defined('_PS_VERSION_')) {
    exit;
}

class Alserneteventmanager extends Module implements WidgetInterface
{

    public function __construct()
    {
        $this->name = 'alserneteventmanager';
        $this->author = 'Alsernet';
        $this->version = '1.0.0';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Alsernet - Gestión de eventos ');
        $this->description = $this->l('Módulo para la gestión de eventos');

    }

    public function install()
    {
        //  Ensure parent install method is called first, followed by custom installation steps
        if (parent::install() && $this->registerHook('header')) {

            $tab = new Tab();
            $tab->class_name = 'AdminEvents'; // Tab controller name
            $tab->module = $this->name; // Module name
            $tab->id_parent = (int)Tab::getIdFromClassName('DEFAULT'); // Set parent tab (default is the Dashboard)
            $tab->icon = 'settings_applications'; // Tab icon
            // Set the tab name for each language
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $tab->name[$lang['id_lang']] = 'Alsernet - Gestión de eventos'; // Tab name
            }
            $tab->save();
            return true; // Return true if everything installs successfully
        }

        return false; // If any step fails, return false
    }


    public function uninstall()
    {
        // if (!parent::uninstall() || !$this->uninstallDB()) {

        $id_tab = (int)Tab::getIdFromClassName("AdminEvents");
        if ($id_tab) {
            $tabModel = new Tab($id_tab);
            $tabModel->delete();
        }

        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    private function installDB()
    {
        $sql1 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alsernet_event_manager` (
                `id_event` INT(11) NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `start_at` DATETIME NOT NULL,
                `end_at` DATETIME NOT NULL,
                `filter_tag` VARCHAR(255) NOT NULL,
                `management_tag` VARCHAR(255) NOT NULL,
                `color_buttom` VARCHAR(900) NOT NULL,
                `hover_buttom` VARCHAR(900) NOT NULL,
                `available` TINYINT(1) NOT NULL DEFAULT 1,
                `banners` VARCHAR(255) NOT NULL,
                `cms` VARCHAR(255) NOT NULL,
                `amazing` VARCHAR(255) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id_event`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


        $sql3 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alsernet_event_manager_categories` (
                `id_event` INT(11) NOT NULL,
                `id_category` INT(11) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id_event`, `id_category`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql4 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alsernet_event_manager_lang` (
                `id_event` INT(11) NOT NULL,
                `id_lang` INT(11) NOT NULL,
                `title` VARCHAR(900) NOT NULL,
                `special` TINYINT NOT NULL,
                `url_special` VARCHAR(900) NOT NULL,
                `title_special` VARCHAR(900) NOT NULL,
                `buttom_all` VARCHAR(900) NOT NULL,
                `buttom_one` VARCHAR(900) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id_event`, `id_lang`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql1) && Db::getInstance()->execute($sql3) && Db::getInstance()->execute($sql4);
    }

    private function uninstallDB()
    {
        // Definir las sentencias SQL paalv_alsernet_event_managerara eliminar las tablas
        $sql1 = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'alsernet_event_manager`;';
        $sql2 = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'alsernet_event_manager_lang`;';
        $sql3 = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'alsernet_event_manager_categories`;';
        $sql4 = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'alsernet_event_manager_lang`;';

        // Ejecutar las consultas para eliminar las tablas
        return Db::getInstance()->execute($sql1) &&
            Db::getInstance()->execute($sql2) &&
            Db::getInstance()->execute($sql3) &&
            Db::getInstance()->execute($sql4);
    }


    public function flagProducts($event)
    {

        dump('flagProducts');

        $tag = $event["management_tag"];
        $feature = (int)$event["featured"];
        $amazing = (int)$event["amazing"];

        $query = "
            SELECT DISTINCT id_product
            FROM (
                SELECT id_product
                FROM aalv_combinacionunica_import
                WHERE etiqueta LIKE '%" . pSQL($tag) . "%' AND id_product != 0
                UNION
                SELECT apa.id_product
                FROM aalv_combinaciones_import aci
                INNER JOIN aalv_product_attribute apa
                    ON apa.id_product_attribute = aci.id_product_attribute
                WHERE aci.etiqueta LIKE '%" . pSQL($tag) . "%' AND apa.id_product != 0
            ) AS etiqueta_products
        ";

        $productIds = array_column(DB::getInstance()->executeS($query), 'id_product');

        dump(count($productIds), 'productIds');

        $existingFeatureQuery = "SELECT DISTINCT id_product FROM aalv_feature_product WHERE id_feature = $feature AND id_feature_value = $amazing";
        $existingFeatureIds = array_column(DB::getInstance()->executeS($existingFeatureQuery), 'id_product');
        dump(count($existingFeatureIds), 'existingFeatureIds');

        $productsToAdd = array_diff($productIds, $existingFeatureIds);
        $productsToRemove = array_diff($existingFeatureIds, $productIds);

        dump(count($productsToAdd), count($productsToRemove));

        if (!empty($productsToAdd)) {
            $this->addFeaturesFromProducts($productsToAdd, $feature, $amazing);
        }

        if (!empty($productsToRemove)) {
            $this->removeFeaturesFromProducts($productsToRemove, $feature, $amazing);
        }

        return true;
    }

    function removeFeatureFromProduct($id_product, $feature_id, $feature_value_id)
    {
        return 'DELETE FROM `' . _DB_PREFIX_ . 'feature_product`
            WHERE `id_product` = ' . (int)$id_product . '
            AND `id_feature` = ' . (int)$feature_id . '
            AND `id_feature_value` = ' . (int)$feature_value_id;
    }

    function productHasFeature($id_product, $feature_id, $feature_value_id)
    {
        $sql = 'SELECT * FROM `aalv_feature_product`
            WHERE `id_product` = ' . (int)$id_product . '
            AND `id_feature` = ' . (int)$feature_id . '
            AND `id_feature_value` = ' . (int)$feature_value_id;
        $result = Db::getInstance()->getRow($sql);
        return !empty($result);
    }

    public function renderWidget($hookName, array $configuration)
    {
        // TODO: Implement renderWidget() method.
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        // TODO: Implement getWidgetVariables() method.
    }


    public function getActiveEvents()
    {

        $currentDate = date('Y-m-d H:i:s');

        $sql = '
        SELECT em.* FROM ' . _DB_PREFIX_ . 'alsernet_event_manager em
        WHERE em.available = 1
        AND em.start_at <= "' . pSQL($currentDate) . '"
        AND em.end_at >= "' . pSQL($currentDate) . '"
        ';

        $events = Db::getInstance()->executeS($sql);

        $response = [];

        foreach ($events as $event) {
            $response[] = [
                'id_event' => $event['id_event'],
                'iva' => $event['iva'],
                'event_title' => strtolower(str_replace(' ', '-', $event['title'])),
                'start_at' => $event['start_at'],
                'end_at' => $event['end_at'],
                'management' => $event['management_tag'],
                'featured' => $event['featured'],
                'amazing' => $event['amazing'],
                'iva_max_amount' => $event['iva_max_amount'],
            ];
        }

        return $response;
    }


    public function hookHeader($params)
    {

        $this->context->controller->addCSS($this->_path . 'views/css/front/events.css', 'all');
        $this->context->controller->addJS($this->_path . 'views/js/front/events.js');

    }


    public function processEventStatus()
    {
        $currentDate = date('Y-m-d H:i:s');

        $sql = ' SELECT em.*  FROM ' . _DB_PREFIX_ . 'alsernet_event_manager em';
        $events = Db::getInstance()->executeS($sql);

        foreach ($events as $event) {

            dump($event);

            if ($event['end_at'] < $currentDate && $event['completed'] == 0) {
                dump($event, 'validateEventEnd');
                $this->processInactiveEvent($event);
                $this->logEventAction($event['id_event'], 'Evento deshabilitado debido a fecha de finalización.');
                return true;
            }

            if (!$this->validateEventData($event)) {
                dump('validateEventData');
                $this->logEventAction($event['id_event'], 'Datos del evento incompletos. No procesado.');
                continue;
            }

            dump('initEvent');
            $startAt = $event['start_at'];
            $startDateTime = new DateTime($startAt);
            $startDateTime->modify('-2 minutes');
            $timeBeforeEvent = $startDateTime->format('Y-m-d H:i:s');
            $isEventWithin10Minutes = $currentDate >= $timeBeforeEvent && $currentDate <= $startAt;

            $this->processActiveEvent($event);

            //if ($currentDate >= $startAt && !$event['available']) {

            //dump('contentEventAvailable');

            /// $sqlEvent = 'UPDATE `' . _DB_PREFIX_ . 'alsernet_event_manager` SET `available`= 1  WHERE `id_event`=' . (int) $event['id_event'];
            //$sqlEventFlag = 'UPDATE `' . _DB_PREFIX_ . 'flags` SET `activo`= 1  WHERE etiqueta = "' . pSQL(strtoupper($event['title'])) . '"';

            // if (Db::getInstance()->execute($sqlEvent) && Db::getInstance()->execute($sqlEventFlag)) {
            //     return true;
            // }

            // $this->logEventAction($event['id_event'], 'Evento habilitado debido a fecha de inicio o posterior.');
            //}

            //if ($isEventWithin10Minutes && !$event['processed']) {
            // dump('isEventWithin10Minutes');
            // $this->processActiveEvent($event);
            // } elseif ($currentDate > $startAt && !$event['available'] && $event['processed']) {
            // dump('finishEventProcessingAvailable');
            //  $sqlEvent = 'UPDATE `' . _DB_PREFIX_ . 'alsernet_event_manager` SET `available`= 1  WHERE `id_event`=' . (int) $event['id_event'];
            //  if (Db::getInstance()->execute($sqlEvent)) {
            //       return true;
            //   }

            // } elseif ($currentDate > $startAt && !$event['processed']) {
            //   dump('processInactiveEvent');
            //     //$this->processInactiveEvent($event);
            // }

        }
    }

    private function restoreBannerStates($eventId)
    {
        // Recuperar los estados guardados
        $sql = 'SELECT id_banner, previous_status FROM ' . _DB_PREFIX_ . 'event_banner_status
            WHERE id_event = ' . (int)$eventId;
        $banners = Db::getInstance()->executeS($sql);

        // Restaurar el estado original de los banners
        foreach ($banners as $banner) {
            Db::getInstance()->update(
                'banners',
                ['active' => (int)$banner['previous_status']],
                'id_banner = ' . (int)$banner['id_banner']
            );
        }

        // Eliminar registros de la tabla auxiliar
        Db::getInstance()->delete('event_banner_status', 'id_event = ' . (int)$eventId);
    }

    private function validateEventData($event)
    {
        if (empty($event['start_at']) || empty($event['end_at'])) {
            return false;
        }
        return true;
    }


    private function processActiveEvent($event)
    {
        dump('processActiveEvent');
        $this->flagProducts($event);
        $this->processGenerateFlagEvent($event);
        //$this->reIndexProducts();

        if ($event['unique_banners']) {
            $this->enableUniqueBanners($event);
        } else {
            dump('processActiveEvent false');
            $this->enableNotUniqueBanners($event);
        }

        $sqlEvent = 'UPDATE `' . _DB_PREFIX_ . 'alsernet_event_manager` SET `processed`= 1  WHERE `id_event`=' . (int)$event['id_event'];

        if (Db::getInstance()->execute($sqlEvent)) {
            return true;
        }

    }

    private function processInactiveEvent($event)
    {
        $this->unflagProducts($event);
        $this->processDisabledFlagEvent($event);
        //$this->reIndexProducts();

        if ($event['unique_banners']) {
            $this->restoreUniqueBanners($event);
        } else {
            $this->restoreNotUniqueBanners($event);
        }

        $sqlEvent = 'UPDATE `' . _DB_PREFIX_ . 'alsernet_event_manager`   SET `processed` = 0, `available` = 0, `completed` = 1  WHERE `id_event` = ' . (int)$event['id_event'];

        if (Db::getInstance()->execute($sqlEvent)) {
            return true;
        }


    }

    private function processGenerateFlagEvent($event)
    {

        $id_event = (int)$event["id_event"];

        $languages = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'alsernet_event_manager_lang WHERE id_event = ' . $id_event);

        if (!empty($languages)) {

            $color_flag = $event["color_flag"];

            if (!empty($color_flag)) {

                $color_flag = $event["color_flag"];

                $styles = explode(';', $color_flag);
                $color = '';
                $background = '';

                foreach ($styles as $style) {
                    $style = trim($style);
                    if (strpos($style, 'color:') === 0 && strpos($style, 'background-color:') === false) {
                        $color = str_replace('color:', '', $style);
                    } elseif (strpos($style, 'background-color:') === 0) {
                        $background = str_replace('background-color:', '', $style);
                    }
                }

                $color = trim($color);
                $background = trim($background);

            } else {
                $color = NULL;
                $background = NULL;
            }

            $insertFlag = [
                'etiqueta' => strtoupper($event['management_tag']) ?? '',
                'color_texto' => $color,
                'color_fondo' => $background,
                'activo' => 0,
                'priority' => $event['priority_flag'] ?? 0,
            ];

            if (!Db::getInstance()->insert('flags', $insertFlag)) {
                return false;
            }

            $id_flag = Db::getInstance()->Insert_ID();

            $rows = [];

            foreach ($languages as $language) {
                $rows[] = [
                    'id' => (int)$id_flag,
                    'id_lang' => (int)$language["id_lang"],
                    'etiqueta_front' => strtoupper($language["title"]),
                ];

            }

            if (!Db::getInstance()->insert('flags_lang', $rows)) {
                return false;
            }

        }

    }

    private function processDisabledFlagEvent($event)
    {
        $id_event = (int)$event["id_event"];
        $languages = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'alsernet_event_manager_lang WHERE id_event = ' . $id_event);
        dump("processDisabledFlagEvent");
        if (!empty($languages)) {

            $existingFlagQuery = "
            SELECT id
            FROM " . _DB_PREFIX_ . "flags
            WHERE etiqueta = '" . pSQL(strtoupper($event['title'])) . "'";

            $existingFlag = Db::getInstance()->getRow($existingFlagQuery);

            if ($existingFlag) {

                $id_flag = (int)$existingFlag['id'];

                $deleteFlagLangQuery = "
                    DELETE FROM " . _DB_PREFIX_ . "flags_lang
                    WHERE id = " . $id_flag;

                Db::getInstance()->execute($deleteFlagLangQuery);

                $deleteFlagQuery = "
                    DELETE FROM " . _DB_PREFIX_ . "flags
                    WHERE id = " . $id_flag;

                Db::getInstance()->execute($deleteFlagQuery);

            }

            return true;
        }

        return false;
    }

    public function reIndexProducts()
    {
        dump('reIndexProducts');
        // die();
        // $module = Module::getInstanceByName('amazzingfilter');
        //if ($module) {

        //   $time = pSQL(Tools::getValue('time', microtime(true)));

        //    $products_per_request = 100;
        //   $id_shop = 1;

        //    dump($products_per_request);
        ///    $params = array(
        //        'id_shop' => $id_shop,
        //        'total_indexed' => 0,
        //        'time' => $time,
        //        'products_per_request' => $products_per_request,
        //    );

        //    $module->reIndexProducts($time, $products_per_request, array($id_shop));
        //   $indexation_data = $module->getIndexationProcessData($time, true);
        //    if (empty($indexation_data[$id_shop]['missing'])) {
        //        return true;
        //   }


        //   dump('amazzingfilter 3');
        // }


    }

    private function enableUniqueBanners($event)
    {
        dump('enableUniqueBanners');

        // Obtener los banners activos desde la base de datos
        $sql = 'SELECT b.*
            FROM `' . _DB_PREFIX_ . 'banner` b
            WHERE b.`active` = 1
            ORDER BY b.`id` ASC';

        $bannersData = Db::getInstance()->executeS($sql);
        $banners_backups = []; // Almacenar los banners activos en esta variable
        $today = date('Y-m-d');

        if ($bannersData) {
            foreach ($bannersData as $banner) {
                $start = $banner['date_start'];
                $end = $banner['date_end'] === '0000-00-00' ? '9999-12-31' : $banner['date_end'];
                $isActive = $banner['active'] === '1';

                // Guardar los banners activos en el backup si están dentro del rango válido
                if ($isActive && $start <= $today && $end >= $today) {
                    $banners_backups[] = $banner['id'];
                }

                // Activar/desactivar banners según si están en el array `$event['banners']`
                if (in_array($banner['id'], $event['banners'])) {
                    if (!$isActive) {
                        $sql = 'UPDATE `' . _DB_PREFIX_ . 'banner` SET `active` = 1 WHERE `id` = ' . (int)$banner['id'];
                        Db::getInstance()->execute($sql);
                    }
                } else {
                    if ($isActive) {
                        $sql = 'UPDATE `' . _DB_PREFIX_ . 'banner` SET `active` = 0 WHERE `id` = ' . (int)$banner['id'];
                        Db::getInstance()->execute($sql);
                    }
                }
            }
        }

        // Actualizar el campo `banners_backups` en el evento
        $sqlEvent = 'UPDATE `' . _DB_PREFIX_ . 'alsernet_event_manager`
                 SET `banners_backups` = "' . pSQL(implode(',', $banners_backups)) . '"
                 WHERE `id_event` = ' . (int)$event['id_event'];

        if (!Db::getInstance()->execute($sqlEvent)) {
            return false;
        }

        // Activar específicamente los banners en `$event['banners']`
        if (!empty($event['banners'])) {
            $sqlBackups = 'UPDATE `' . _DB_PREFIX_ . 'banner`
                       SET `active` = 1
                       WHERE `id` IN (' . $event['banners'] . ')';
            Db::getInstance()->execute($sqlBackups);
        }

        return true;
    }


    private function enableNotUniqueBanners($event)
    {

        if (!empty($event['banners'])) {
            $sqlBackups = 'UPDATE `' . _DB_PREFIX_ . 'banner`
            SET `active` = 1
            WHERE `id` IN (' . pSQL($event['banners']) . ')';
        }


        $sqlEvent = 'UPDATE `' . _DB_PREFIX_ . 'alsernet_event_manager` SET `banners_backups` = "" WHERE `id_event` = ' . (int)$event['id_event'];

        if (Db::getInstance()->execute($sqlEvent) && ($event['banners'] == "" || Db::getInstance()->execute($sqlBackups))) {
            return true;
        }

    }


    private function restoreUniqueBanners($event)
    {

        dump('restoreUniqueBanners');

        if ($event['banners_backups'] != '') {
            $sqlBackups = 'UPDATE `' . _DB_PREFIX_ . 'banner`
            SET `active` = 1
            WHERE `id` IN (' . pSQL($event['banners_backups']) . ')';

            Db::getInstance()->execute($sqlBackups);
        }

        if ($event['banners'] != '') {
            $sqlBanners = 'UPDATE `' . _DB_PREFIX_ . 'banner`
                SET `active` = 0
                WHERE `id` IN (' . pSQL($event['banners']) . ')';

            Db::getInstance()->execute($sqlBanners);
        }

        return true;

    }

    private function restoreNotUniqueBanners($event)
    {
        dump('restoreNotUniqueBanners');

        if (!empty($event['banners'])) {
            $sqlBanners = 'UPDATE `' . _DB_PREFIX_ . 'banner`
            SET `active` = 0
            WHERE `id` IN (' . pSQL($event['banners']) . ')';

            if (Db::getInstance()->execute($sqlBanners)) {
                return true;
            }
        }



    }

    private function unflagProducts($event)
    {

        $tag = $event["management_tag"];
        $feature = (int)$event["featured"];
        $amazing = (int)$event["amazing"];
        dump("unflagProducts");
        $query = "
            SELECT DISTINCT id_product
            FROM (
                SELECT id_product
                FROM aalv_combinacionunica_import
                WHERE etiqueta LIKE '%" . pSQL($tag) . "%' AND id_product != 0
                UNION
                SELECT apa.id_product
                FROM aalv_combinaciones_import aci
                INNER JOIN aalv_product_attribute apa
                    ON apa.id_product_attribute = aci.id_product_attribute
                WHERE aci.etiqueta LIKE '%" . pSQL($tag) . "%' AND apa.id_product != 0
            ) AS etiqueta_products
        ";

        $productIds = array_column(DB::getInstance()->executeS($query), 'id_product');

        if (count($productIds) > 0) {
            $this->removeFeaturesFromProducts($productIds, $feature, $amazing);
        }

        return true;
    }

    function removeFeaturesFromProducts($productIds, $feature_id, $feature_value_id)
    {

        if (empty($productIds) || !$feature_id || !$feature_value_id) {
            return false;
        }

        $productIds = array_map('intval', $productIds);
        $productIdsList = implode(',', $productIds);

        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'feature_product`
            WHERE `id_product` IN (' . $productIdsList . ')
            AND `id_feature` = ' . (int)$feature_id . '
            AND `id_feature_value` = ' . (int)$feature_value_id;

        return Db::getInstance()->execute($sql);
    }

    function addFeaturesFromProducts($productIds, $id_feature, $id_feature_value, $cust = 0)
    {
        if (empty($productIds) || !$id_feature || (!$id_feature_value && !$cust)) {
            return false;
        }

        $productIds = array_map('intval', $productIds);
        $productIdsList = implode(',', $productIds);

        if ($cust) {
            $row = [
                'id_feature' => (int)$id_feature,
                'custom' => 1,
            ];
            if (Db::getInstance()->insert('feature_value', $row)) {
                $id_feature_value = Db::getInstance()->Insert_ID();
            } else {
                return false;
            }
        }

        $existingQuery = "
            SELECT id_product
            FROM `" . _DB_PREFIX_ . "feature_product`
            WHERE `id_feature` = " . (int)$id_feature . "
            AND `id_feature_value` = " . (int)$id_feature_value . "
            AND `id_product` IN ($productIdsList)
        ";

        $existingProducts = Db::getInstance()->executeS($existingQuery);
        $existingProductIds = array_column($existingProducts, 'id_product');

        $productsToAdd = array_diff($productIds, $existingProductIds);

        if (!empty($productsToAdd)) {

            $rows = [];
            foreach ($productsToAdd as $idProduct) {
                $rows[] = [
                    'id_feature' => (int)$id_feature,
                    'id_product' => (int)$idProduct,
                    'id_feature_value' => (int)$id_feature_value,
                ];
            }

            if (!Db::getInstance()->insert('feature_product', $rows)) {
                return false;
            }

        }

        SpecificPriceRule::applyAllRules($productIds);

        return true;

    }

    private function logEventAction($eventId, $message)
    {
        PrestaShopLogger::addLog("Evento ID: $eventId - $message", 3);
    }

    private function notifyAdmin($message)
    {
        $adminEmail = Configuration::get('PS_SHOP_EMAIL');
        Mail::Send(
            (int)Configuration::get('PS_LANG_DEFAULT'),
            'event_notification',
            'Notificación de evento',
            ['{message}' => $message],
            $adminEmail,
            null,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . $this->name . '/mails/'
        );
    }

    function checkUrlAndReIndex($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        dump('https:', $httpCode, $ch);

        curl_close($ch);

        if ($httpCode === 200) {
            return true;
        }

        return false;
    }

}

