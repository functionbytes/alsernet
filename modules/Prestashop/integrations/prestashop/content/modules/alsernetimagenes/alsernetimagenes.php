<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Alsernetimagenes extends Module
{
    public function __construct()
    {
        $this->name = 'alsernetimagenes';
        $this->tab = 'administration';
        $this->version = '2.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->displayName = $this->l('Alsernet - Gestor de imágenes');
        $this->description = $this->l('Este módulo agiliza agregar imágenes a las referencias');
        $this->bootstrap = true;
        parent::__construct();
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('displayImagenesAlvarez') ||
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
            !$this->unregisterHook('displayImagenesAlvarez') ||
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
        $sql = "CREATE TABLE IF NOT EXISTS "._DB_PREFIX_."alsernet_imagenes (
                        id_alsernet_imagenes INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                        id_modelo INT(12) NOT NULL,
                        PRIMARY KEY (id_alsernet_imagenes)
                    ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;";

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        return true;

    }

    public function uninstallDb()
    {
        // Aquí puedes agregar la lógica para eliminar las tablas o realizar otras operaciones en la base de datos necesarias para desinstalar tu módulo.
        $sql = "DROP TABLE IF EXISTS "._DB_PREFIX_."alsernet_imagenes;";

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        return true;
    }

    public function registerModuleTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AlsernetImagenes';
        $tab->name = array();
        $tab->icon = 'local_shipping';
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Gestor de imágenes';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('DEFAULT');
        $tab->module = $this->name;

        return $tab->save();
    }

    public function unregisterModuleTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AlsernetImagenes');
        if ($id_tab) {
            $tab = new Tab($id_tab);

            return $tab->delete();
        }

        return true;
    }

}












