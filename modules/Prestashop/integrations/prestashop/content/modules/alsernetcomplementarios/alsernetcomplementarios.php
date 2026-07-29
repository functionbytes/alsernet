<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AlsernetComplementarios extends Module
{
    public function __construct()
    {
        $this->name = 'alsernetcomplementarios';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Complementarios de productos');
        $this->description = $this->l('Permite agregar productos complementarios por producto, categoría o marca.');
    }

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->registerHook('actionProductUpdate')
            && $this->installTab();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDb()
            && $this->uninstallTab();
    }

    private function installDb()
    {
        $sql = file_get_contents(__DIR__ . '/sql/install.sql');
        // Reemplazar placeholder de prefijo
        $sql = str_replace(['PREFIX_', 'PREFIX'], _DB_PREFIX_, $sql);
        return Db::getInstance()->execute($sql);
    }

    private function uninstallDb()
    {
        $sql = file_get_contents(__DIR__ . '/sql/uninstall.sql');
        // Reemplazar placeholder de prefijo
        $sql = str_replace(['PREFIX_', 'PREFIX'], _DB_PREFIX_, $sql);
        return Db::getInstance()->execute($sql);
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAlsernetComplementarios';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Complementarios';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;
        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminAlsernetComplementarios');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminAlsernetComplementarios'));
        return ''; // no renderiza nada aquí
    }
}
