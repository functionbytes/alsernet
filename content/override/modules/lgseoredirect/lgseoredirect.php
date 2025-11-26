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

if (!defined('_PS_VERSION_')) {
    exit;
}




class LGSEORedirectOverride extends LGSEORedirect
{

    public function checkRedirection()
    {
        if (Module::isEnabled($this->name)) {

            $uri_var = $_SERVER['REQUEST_URI'];
            if ((!strpos($uri_var, 'module')) &&
               (!strpos($uri_var, 'panel')) &&
                (!strpos($uri_var, 'pedido')) &&
                (!strpos($uri_var, 'order')) &&
                (!strpos($uri_var, 'commande')) &&
                (!strpos($uri_var, 'encomenda')) &&
                (!strpos($uri_var, 'bestellung')) &&
                (!strpos($uri_var, 'carrito')) &&
                (!strpos($uri_var, 'cart')) &&
                (!strpos($uri_var, 'panier')) &&
                (!strpos($uri_var, 'carrinho')) &&
                (!strpos($uri_var, 'warenkorb')) &&
                (!strpos($uri_var, 'jpg')) &&
                (!strpos($uri_var, 'webp'))
            ) {

                //PrestaShopLogger::addLog("Entra:".$uri_var);
                $context = Context::getContext();
                if ($context->language->is_rtl) {
                    $uri_var = rawurldecode($uri_var);
                }
                $shop_id = $context->shop->id;
                $baseuri = Tools::rtrimString($context->shop->getBaseURI(), '/');
                $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'lgseoredirect ' .
                    'WHERE (CONCAT("' . $baseuri . '", url_old) = "' . pSQL($uri_var) . '" ' .
                    'OR CONCAT("' . $baseuri . '", url_old) LIKE "' . pSQL($uri_var) . '#%") ' .
                    'AND id_shop = "' . (int)$shop_id . '" ' .
                    'ORDER BY id DESC';
                $redirect = Db::getInstance()->getRow($sql);
                if ($redirect &&
                    $uri_var == preg_replace('/(#.*)/', '', $baseuri . $redirect['url_old'])
                    && $shop_id == $redirect['id_shop']
                ) {
                    if ($redirect['redirect_type'] == 301) {
                        $header = 'HTTP/1.1 301 Moved Permanently';
                    }
                    if ($redirect['redirect_type'] == 302) {
                        $header = 'HTTP/1.1 302 Moved Temporarily';
                    }
                    if ($redirect['redirect_type'] == 303) {
                        $header = 'HTTP/1.1 303 See Other';
                    }
                    Tools::redirect($redirect['url_new'], __PS_BASE_URI__, null, $header);
                }
            }
        }
    }


}
