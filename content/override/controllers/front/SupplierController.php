<?php
/**
 * FMM PrettyURLs
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    FMM Modules
 * @copyright Copyright 2018 © Fmemodules All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @category  FMM Modules
 * @package   PrettyURLs
*/

class SupplierController extends SupplierControllerCore
{
	public function init()
	{
		$link_rewrite = Tools::safeOutput(urldecode(Tools::getValue('supplier_rewrite')));
		$sup_pattern = '/.*?([0-9]+)\_\_([_a-zA-Z0-9-\pL]*)/';
		preg_match($sup_pattern, $_SERVER['REQUEST_URI'], $sup_array);

		if (isset($sup_array[2]) && $sup_array[2] != '') {
			$link_rewrite = $sup_array[2];
		}
		$id_shop = $this->context->shop->id;

		if ($link_rewrite) {
			$supplier = Tools::strtolower(str_replace('-', '%', $link_rewrite));
			$sql = 'SELECT t1.id_supplier
					FROM '._DB_PREFIX_.'supplier t1
					LEFT JOIN '._DB_PREFIX_.'supplier_shop t2 ON (t1.id_supplier = t2.id_supplier)
					WHERE t1.name LIKE (\''.pSQL($supplier).'\') AND t2.id_shop = '.(int)$id_shop;
			$id_supplier = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
			if ($id_supplier != '') {
				$_POST['id_supplier'] = $id_supplier;
				$_GET['supplier_rewrite'] = '';
			}
		}

		if (preg_match('/\?/', $_SERVER['REQUEST_URI'])) {
			$req_uri_qmark = explode('?', $_SERVER['REQUEST_URI']);
			$req_uri_without_qmark = $req_uri_qmark[0];
			$req_uri_without_qmark = explode('/', $req_uri_without_qmark);
			$request = end($req_uri_without_qmark);
			$clearify_request = str_replace('-', ' ', $request);
			$supp_existance = (int)$this->getKeyExistanceSup($clearify_request);
			if ($supp_existance > 0) {
				$_POST['id_supplier'] = $supp_existance;
			}
		}
		//Fix for accented chars URLs
		$allow_accented_chars = (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
		if ($allow_accented_chars > 0) {
			$id_supplier = (int)Tools::getValue('id_supplier');
			if ($id_supplier <= 0 && isset($_GET['supplier_rewrite'])) {
				$id = (int)$this->getKeyExistanceSup($_GET['supplier_rewrite']);
				if ($id > 0) {
					$_POST['id_supplier'] = $id;
				}
			}
		}
		parent::init();
	}

	private function getKeyExistanceSup($request)
	{
		$sql = 'SELECT `id_supplier`
					FROM '._DB_PREFIX_.'supplier
					WHERE `name` LIKE "'.pSQL($request).'"';
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
	}
}