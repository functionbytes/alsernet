<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AlsernetMarcasCategorias extends Module
{
    public function __construct()
    {
        $this->name = 'alsernetmarcascategorias';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Marcas a categorías');
        $this->description = $this->l('Asociar marcas a categorías y definir qué marcas se convierten en categorías.');
    }

    public function install()
    {
        return parent::install() &&
            $this->installDB() &&
            $this->installTab();
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->uninstallDB() &&
            $this->uninstallTab();
    }

    protected function installDB()
    {
        $sql = [];
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "alsernet_brand_category` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_manufacturer` INT(11) NOT NULL,
            `id_category` INT(11) NOT NULL
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "alsernet_brand_as_category` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_manufacturer` INT(11) NOT NULL,
            `id_category` INT(11) NOT NULL
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    protected function uninstallDB()
    {
        $sql = [];
        $sql[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "alsernet_brand_category`;";
        $sql[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "alsernet_brand_as_category`;";

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    protected function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminAlsernetMarcasCategorias';
        $tab->module = $this->name;
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentModulesSf');
        $tab->name = [];
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Marcas y categorías');
        }
        return $tab->add();
    }

    protected function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminAlsernetMarcasCategorias');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }
}
