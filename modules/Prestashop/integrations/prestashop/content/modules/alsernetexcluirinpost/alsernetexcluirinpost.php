<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Alsernetexcluirinpost extends Module
{
    public function __construct()
    {
        $this->name = 'alsernetexcluirinpost';
        $this->tab = 'administration';
        $this->version = '2.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->displayName = $this->l('Alsernet - Excluir productos de InPost');
        $this->description = $this->l('Excluye los productos que tengan un "Tipo de Producto"');
        parent::__construct();
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('displayInpostExclusion') ||
            !$this->installDb() ||
            !$this->registerModuleTab()
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
            !$this->unregisterHook('displayInpostExclusion') ||
            !$this->uninstallDb() ||
            !$this->unregisterModuleTab()
        ) {
            return false;
        }

        return true;
    }

    public function installDb()
    {
        // Aquí puedes agregar la lógica para crear las tablas o realizar otras operaciones en la base de datos necesarias para tu módulo.
        $sql = "CREATE TABLE IF NOT EXISTS "._DB_PREFIX_."alsernet_exclude_product_inpost (
                        id_exclude_product_inpost INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                        id_feature_value INT(12) NOT NULL,
                        PRIMARY KEY (id_exclude_product_inpost)
                    ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;";

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        return true;

    }

    public function uninstallDb()
    {
        // Aquí puedes agregar la lógica para eliminar las tablas o realizar otras operaciones en la base de datos necesarias para desinstalar tu módulo.
        $sql = "DROP TABLE IF EXISTS "._DB_PREFIX_."alsernet_exclude_product_inpost;";

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }
        return true;
    }

    public function registerModuleTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AlsernetExcluirInpost';
        $tab->name = array();
        $tab->icon = 'local_shipping';
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Excluir INPOST';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('DEFAULT');
        $tab->module = $this->name;

        return $tab->save();
    }

    public function unregisterModuleTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AlsernetExcluirInpost');
        if ($id_tab) {
            $tab = new Tab($id_tab);

            return $tab->delete();
        }

        return true;
    }

}












