<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AlsernetFormCategories extends Module
{
    public function __construct()
    {
        $this->name = 'alsernetformcategories';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.7',
            'max' => '1.7.8',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Alsernet - Mejora ficha de producto sin categorías');
        $this->description = $this->l('Suprime el árbol de categorías y los atributos en los productos simples para mejorar el rendimiento en la ficha del producto.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }
        
}
