<?php
class EventsController extends Module
{
    public $module;

    public function __construct(){
        $this->bootstrap = true;
        $this->module =  Module::getInstanceByName("alserneteventmanager");
        parent::__construct();
    }
    public function isActive($eventId)
    {
        $event = Db::getInstance()->getRow('
        SELECT * FROM '._DB_PREFIX_.'alsernet_event_manager
        WHERE id_event = '.(int)$eventId
        );

        if (!$event) {
            return false;
        }

        if ($event['available'] != 1) {
            return false;
        }

        $currentDate = date('Y-m-d H:i:s');

        if ($event['start_at'] > $currentDate || $event['end_at'] < $currentDate) {
            return false;
        }

        return true;
    }

    public function getAlls(){

        $currentDate = date('Y-m-d H:i:s');

        $sql = '
        SELECT em.* FROM '._DB_PREFIX_.'alsernet_event_manager em
        WHERE em.available = 1
        AND em.start_at <= "'.pSQL($currentDate).'"
        AND em.end_at >= "'.pSQL($currentDate).'"';

        $events = Db::getInstance()->executeS($sql);
        $categories = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'alsernet_event_manager_categories');
        $languages = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'alsernet_event_manager_lang');

        $response = [];

        foreach ($events as $event) {

            $eventCategories = [];
            foreach ($categories as $category) {
                if ($category['id_event'] == $event['id_event']) {
                    $categoryId = $category['id_category'];
                    $categoryObject = new Category($categoryId, $this->context->language->id);
                    $eventCategories[] = [
                        'id_category' => $categoryObject->id,
                        'name' => $categoryObject->name,
                    ];
                }
            }

            $eventLanguages = [];
            foreach ($languages as $language) {
                if ($language['id_event'] == $event['id_event']) {

                    switch ($language['id_lang']) {
                        case 1: $id_country = 6; break;
                        case 2: $id_country = 17; break;
                        case 3: $id_country = 8; break;
                        case 4: $id_country = 15; break;
                        case 5: $id_country = 1; break;
                        case 6: $id_country = 10; break;
                        default: continue 2;
                    }

                    $country = new Country($id_country, (int)$language['id_lang']);
                    $iso_code = ($id_country == 17) ?'en' : $country->iso_code;
                    $eventLanguages[] = [
                        'id_country' => $country->id,
                        'name' => $country->name,
                        'iso_code' => $iso_code,
                        'buttom_all' => $language['buttom_all'],
                        'buttom_one' => $language['buttom_one'],
                        'filter' => $language['filter'],
                        'url_special' => $language['url_special'],
                        'title_special' => $language['title_special'],

                    ];
                }
            }

            $response[] = [
                'id_event' => $event['id_event'],
                'start_at' => $event['start_at'],
                'end_at' => $event['end_at'],
                'event_title' => $event['title'],
                'filter_tag' => $event['filter_tag'],
                'management_tag' => $event['management_tag'],
                'hover_buttom' => $event['hover_buttom'],
                'color_buttom' => $event['color_buttom'],
                'amazing' => $event['amazing'],
                'category' => $eventCategories,
                'languages' => $eventLanguages,
            ];
        }

        return array(
             'status' => 'success',
             'message' => 'success',
             'data' => $response,
        );

    }

    public function l($string, $specific = false, $locale = null){

        return $this->getModuleTranslation(
            $this->module,
            $string,
            ($specific) ? $specific : $this->name,
            null,
            false,
            $locale
        );
    }


    public  function getModuleTranslation(
        $module,
        $originalString,
        $source,
        $sprintf = null,
        $js = false,
        $locale = null,
        $fallback = true,
        $escape = true
    ) {
        global $_MODULES, $_MODULE, $_LANGADM;

        static $langCache = [];
        static $name = null;

        static $translationsMerged = [];

        $name = $module->name;

        if (null !== $locale) {
            $iso = Language::getIsoByLocale($locale);
        }

        if (empty($iso)) {
            $iso = Context::getContext()->language->iso_code;
        }

        if (!isset($translationsMerged[$name][$iso])) {
            $filesByPriority = [
                _PS_MODULE_DIR_ . $name . '/translations/' . $iso . '.php',
                _PS_MODULE_DIR_ . $name . '/' . $iso . '.php',
                _PS_THEME_DIR_ . 'modules/' . $name . '/translations/' . $iso . '.php',
                _PS_THEME_DIR_ . 'modules/' . $name . '/' . $iso . '.php',
            ];
            foreach ($filesByPriority as $file) {
                if (file_exists($file)) {
                    include_once $file;
                    $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
                }
            }
            $translationsMerged[$name][$iso] = true;
        }


        $string = preg_replace("/\\\*'/", "\'", $originalString);
        $key = md5($string);

        $cacheKey = $name . '|' . $string . '|' . $source . '|' . (int) $js . '|' . $iso;
        if (isset($langCache[$cacheKey])) {
            $ret = $langCache[$cacheKey];
        } else {
            $currentKey = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
            $defaultKey = strtolower('<{' . $name . '}prestashop>' . $source) . '_' . $key;

            if ('controller' == substr($source, -10, 10)) {
                $file = substr($source, 0, -10);
                $currentKeyFile = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
                $defaultKeyFile = strtolower('<{' . $name . '}prestashop>' . $file) . '_' . $key;
            }

            if (isset($currentKeyFile) && !empty($_MODULES[$currentKeyFile])) {
                $ret = stripslashes($_MODULES[$currentKeyFile]);
            } elseif (isset($defaultKeyFile) && !empty($_MODULES[$defaultKeyFile])) {
                $ret = stripslashes($_MODULES[$defaultKeyFile]);
            } elseif (!empty($_MODULES[$currentKey])) {
                $ret = stripslashes($_MODULES[$currentKey]);
            } elseif (!empty($_MODULES[$defaultKey])) {
                $ret = stripslashes($_MODULES[$defaultKey]);
            } elseif (!empty($_LANGADM)) {
                $ret = stripslashes(Translate::getGenericAdminTranslation($string, $key, $_LANGADM));
            } else {
                $ret = stripslashes($string);
            }

            if (
                $sprintf !== null &&
                (!is_array($sprintf) || !empty($sprintf)) &&
                !(count($sprintf) === 1 && isset($sprintf['legacy']))
            ) {
                $ret = Translate::checkAndReplaceArgs($ret, $sprintf);
            }

            if ($js) {
                $ret = addslashes($ret);
            } elseif ($escape) {
                $ret = htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
            }

            if ($sprintf === null) {
                $langCache[$cacheKey] = $ret;
            }
        }

        if (!is_array($sprintf) && null !== $sprintf) {
            $sprintf_for_trans = [$sprintf];
        } elseif (null === $sprintf) {
            $sprintf_for_trans = [];
        } else {
            $sprintf_for_trans = $sprintf;
        }

        if ($ret === $originalString && $fallback) {
            $ret = Context::getContext()->getTranslator()->trans($originalString, $sprintf_for_trans, null, $locale);
        }

        return $ret;
    }


}



