<?php
if (!defined('_PS_VERSION_')) { exit; }

class Alsernetattributelang extends Module
{
    public function __construct()
    {
        $this->name = 'alsernetattributelang';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Alsernet Attribute Lang');
        $this->description = $this->l('Editar traducciones de grupos y valores de atributos por producto.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
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
        $id_parent = (int)Tab::getIdFromClassName('IMPROVE');
        if (!$id_parent) {
            // Fallback: ponerlo bajo el menú principal
            $id_parent = 0;
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAlsernetAttributeLang';
        $tab->name = [];
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Attribute Lang';
        }
        $tab->id_parent = $id_parent; // Menú: Mejoras
        $tab->module = $this->name;
        return (bool)$tab->add();
    }

    protected function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminAlsernetAttributeLang');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return (bool)$tab->delete();
        }
        return true;
    }

    public function getContent()
    {
        $link = $this->context->link->getAdminLink('AdminAlsernetAttributeLang');
        $html = '<div class="panel"><h3>'.$this->displayName.'</h3>';
        $html .= '<p>'.$this->l('Abrir herramienta de traducción de atributos por producto.').'</p>';
        $html .= '<a class="btn btn-primary" href="'.htmlspecialchars($link).'">'.$this->l('Abrir').'</a>';
        $html .= '</div>';
        return $html;
    }
}
