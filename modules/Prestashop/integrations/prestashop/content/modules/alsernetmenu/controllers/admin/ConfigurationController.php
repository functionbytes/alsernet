<?php

namespace modules\alsernetmenu\controllers\admin;
use ModuleAdminController;class ConfigurationController extends ModuleAdminController
{

    // public function __construct()
    // {
    //     $this->name = 'alsernetshortcodes';
    //     $this->bootstrap = true;
    //     parent::__construct();
    // }

    // public function initContent()
    // {
    //     // Incluimos el archivo HTML del panel
    //     $this->setTemplate('admin.tpl');

    //     // Incluimos el JS del admin
    //     $this->context->controller->addJS($this->module->getPathUri().'views/js/admin.js');

    //     // Asignar los datos a la plantilla Smarty
    //     $this->context->smarty->assign('config', $this->getConfigFormValues());

    //     parent::initContent();
    // }

    // protected function getConfigFormValues()
    // {
    //     return array(
    //         'enable_recaptcha' => Configuration::get('enable_recaptcha', true),
    //         'public_key' => Configuration::get('public_key', true),
    //         'private_key' => Configuration::get('private_key', null),
    //    );
    // }

}