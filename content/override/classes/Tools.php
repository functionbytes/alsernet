<?php
class Tools extends ToolsCore
{
    /*Añadida Addis para permitir subida múltiple de archivos*/
    public static function fileMultiAttachment($numItem, $input = 'fileUpload', $return_content = true)
    {
        $file_attachment = null;
        if (isset($_FILES[$input]['name'][$numItem]) && !empty($_FILES[$input]['name'][$numItem]) && !empty($_FILES[$input]['tmp_name'][$numItem])) {
            $file_attachment['rename'] = uniqid() . Tools::strtolower(substr($_FILES[$input]['name'][$numItem], -5));
            if ($return_content) {
                $file_attachment['content'] = file_get_contents($_FILES[$input]['tmp_name'][$numItem]);
            }
            $file_attachment['tmp_name'] = $_FILES[$input]['tmp_name'][$numItem];
            $file_attachment['name'] = $_FILES[$input]['name'][$numItem];
            $file_attachment['mime'] = $_FILES[$input]['type'][$numItem];
            $file_attachment['error'] = $_FILES[$input]['error'][$numItem];
            $file_attachment['size'] = $_FILES[$input]['size'][$numItem];
        }

        return $file_attachment;
    }
    
    /*
    * module: currencyformat
    * date: 2022-02-07 09:16:22
    * version: 1.1.6
    */
    // public static function displayPrice($price, $currency = null, $no_utf8 = false, Context $context = null)
    // {
    //     if (!Module::isEnabled('currencyformat')) {
    //         return parent::displayPrice($price, $currency, $no_utf8, $context);
    //     }
    //     if ($context == null) {
    //         $context = Context::getContext();
    //     }
    //     if (isset($context->controller) && in_array($context->controller->controller_type, array('admin'))) {
    //         return parent::displayPrice($price, $currency, $no_utf8, $context);
    //     }
    //     if (!is_numeric($price)) {
    //         return $price;
    //     }
    //     include_once(_PS_MODULE_DIR_.'currencyformat/classes/CurrencyformatConfiguration.php');
    //     $config = new CurrencyformatConfiguration();
    //     $priceFormatted = $config->getPriceFormatted($price, $currency);
    //     if ($priceFormatted) {
    //         return $priceFormatted;
    //     }
    //     return parent::displayPrice($price, $currency, $no_utf8, $context);
    // }

    /*
    * module: removeiso
    * date: 2022-03-29 10:33:39
    * version: 1.4.3
    */



    /*
    * module: removeiso
    * date: 2022-05-03 15:50:52
    * version: 1.4.3
    */
    public static function setCookieLanguage($cookie = null)
    {
        if (defined('_PS_ADMIN_DIR_')) {
            return parent::setCookieLanguage($cookie = null);
        }
        if (!$cookie) {
            $cookie = Context::getContext()->cookie;
        }
        if (!Configuration::get('PS_DETECT_LANG')) {
            unset($cookie->detect_language);
        }

        if (!Tools::getValue('isolang') && !Tools::getValue('id_lang') && (!$cookie->id_lang || isset($cookie->detect_language)) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $array = explode(',', Tools::strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
            $string = $array[0];
            if (Validate::isLanguageCode($string)) {
                $lang = Language::getLanguageByIETFCode($string);
                if (Validate::isLoadedObject($lang) && $lang->active && $lang->isAssociatedToShop()) {
                    Context::getContext()->language = $lang;
                    Context::getContext()->cookie->id_lang = (int)$lang->id;
                    $cookie->id_lang = (int)$lang->id;
                }
            }
            $iso = Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT'));
        } else {
            if (Tools::getValue('isolang', 'false') != 'false' && Tools::getValue('id_lang', 'false') != 'false') {
                $languag_defined = 1;
                $lang = new Language(Tools::getValue('id_lang', 'false'));
                if (Validate::isLoadedObject($lang) && $lang->active && $lang->isAssociatedToShop()) {
                    Context::getContext()->language = $lang;
                    Context::getContext()->cookie->id_lang = (int)$lang->id;
                    $cookie->id_lang = (int)$lang->id;
                }
                $iso = Language::getIsoById((int)$cookie->id_lang);
            }
            if (!isset($languag_defined)) {
                $lang = new Language(Configuration::get('PS_LANG_DEFAULT'));
                if (Validate::isLoadedObject($lang) && $lang->active && $lang->isAssociatedToShop()) {
                    Context::getContext()->language = $lang;
                    Context::getContext()->cookie->id_lang = (int)$lang->id;
                    $cookie->id_lang = (int)$lang->id;
                }
                $iso = Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT'));
            }
        }
        @include_once(_PS_THEME_DIR_ . 'lang/' . $iso . '.php');
        return $iso;
    }
}