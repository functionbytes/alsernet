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

class ManufacturerController extends ManufacturerControllerCore
{

    public function getAlternativeLangsUrl()
    {
        $alternativeLangs = parent::getAlternativeLangsUrl();

            $languages = Language::getLanguages(true, $this->context->shop->id);
            foreach ($languages as $lang) {
                $alternativeLangs[$lang['language_code']] = $this->context->link->getManufacturerLink($this->manufacturer,null,$lang['id_lang']);
            }

        return $alternativeLangs;
    }

	public function init()
    {
        $id_manufacturer = (int)Tools::getValue('id_manufacturer');

        if ($id_manufacturer) {
            $this->manufacturer = new Manufacturer($id_manufacturer, $this->context->language->id);

            if (!Validate::isLoadedObject($this->manufacturer) || !$this->manufacturer->active || !$this->manufacturer->isAssociatedToShop()) {
                $this->redirect_after = '404';
                $this->redirect();
            } else {
                $this->canonicalRedirection();
            }
        }

        parent::init();
    }
	private function getKeyExistanceManuf($request)
	{
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT `id_manufacturer`
				FROM '._DB_PREFIX_.'manufacturer
				WHERE `name` LIKE "'.pSQL($request).'"');
	}

	protected function assignManufacturer()
    {
    	parent::assignManufacturer();

        $manufacturer_description_extra = '';
        if (Module::isEnabled('marcasdeportes')) {
        	require_once _PS_MODULE_DIR_ . 'marcasdeportes/classes/MarcasdeportesModel.php';

        	$texto_superior = MarcasdeportesModel::getTextoSuperiorByIdManufacturer($this->manufacturer->id);
        	if ($texto_superior) {
        		$manufacturer_description_extra .= $texto_superior;
        	}

        	$texto_inferior = MarcasdeportesModel::getTextoInferiorByIdManufacturer($this->manufacturer->id);
        	if ($texto_inferior) {
        		$manufacturer_description_extra .= $texto_inferior;
        	}
        }

        $titulo_filtrado = '';
        if(Tools::getValue('categorias')){
        	$titulo_filtrado = Tools::getValue('categorias').' '.$this->manufacturer->name;
        }


		$this->context->smarty->assign(array(
            'canonical_url' => $this->context->link->getManufacturerLink($this->manufacturer),
        	'titulo_filtrado' => $titulo_filtrado,
        ));
        $this->context->smarty->assign([
            'manufacturer_description_extra' => $manufacturer_description_extra,
        ]);
    }
}
