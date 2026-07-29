<?php
if (!defined('_PS_VERSION_')) { exit; }

class Alsernetrelacionados extends Module
{
    public function __construct()
    {
        $this->name = 'alsernetrelacionados';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Productos alternativos');
        $this->description = $this->l('Recomienda productos alternativos a partir de una referencia y filtros autocompletados.');
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() && $this->installTab();
    }

    public function uninstall()
    {
        return $this->uninstallTab() && parent::uninstall();
    }

    protected function installTab()
    {
        // Usar AdminParentCatalog si existe; si no, AdminCatalog
        $id_parent = (int) Tab::getIdFromClassName('AdminParentCatalog');
        if (!$id_parent) {
            $id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAlsernetRelacionados';
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Productos alternativos';
        }
        $tab->id_parent = $id_parent ?: 0;
        $tab->module = $this->name;
        return (bool) $tab->add();
    }

    protected function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminAlsernetRelacionados');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return (bool) $tab->delete();
        }
        return true;
    }
}
