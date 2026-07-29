<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AlsernetProductVideo extends Module
{
    public function __construct()
    {
        $this->name = 'alsernetproductvideo';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Alsernet - Productos Videos');
        $this->description = $this->l('Administrador para agregar videos a los productos');

        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() && $this->addAdminTab();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->removeAdminTab();
    }

    private function addAdminTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminProductFilm';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Productos Videos';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog'); // Asignar a AdminCatalog
        $tab->module = $this->name;
        $tab->icon = 'videocam'; // Puedes cambiar el icono a otro de Material Icons

        return $tab->add();
    }

    private function removeAdminTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminProductFilm');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }
}
