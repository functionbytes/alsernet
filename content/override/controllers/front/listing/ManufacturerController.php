<?php

class ManufacturerController extends ManufacturerControllerCore
{
    public function initContent()
    {
        if (Configuration::get('PS_DISPLAY_MANUFACTURERS')) {
            parent::initContent();

            if (Validate::isLoadedObject($this->manufacturer) && $this->manufacturer->active && $this->manufacturer->isAssociatedToShop()) {
                $this->assignManufacturer();
                $this->label = $this->trans(
                    'List of products by brand %brand_name%',
                    [
                        '%brand_name%' => $this->manufacturer->name,
                    ],
                    'Shop.Theme.Catalog'
                );
                $this->doProductSearch(
                    'catalog/listing/category/list', [
                    'entity' => 'manufacturer',
                    'id' => $this->manufacturer->id,
                    'name' => $this->manufacturer->name,
                ]);
            } else {
                $this->assignAll();
                $this->label = $this->trans(
                    'List of all brands',
                    [],
                    'Shop.Theme.Catalog'
                );
                $this->setTemplate('catalog/manufacturers', ['entity' => 'manufacturers']);
            }
        } else {
            $this->redirect_after = '404';
            $this->redirect();
        }
    }
}
