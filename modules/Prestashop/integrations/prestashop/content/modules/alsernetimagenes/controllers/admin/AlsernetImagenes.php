<?php
class AlsernetImagenesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->name = 'AlsernetImagenes';
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        // Incluimos el archivo HTML del panel
        $this->setTemplate('admin.tpl');

        // Incluimos el JS del admin
        $this->context->controller->addJS($this->module->getPathUri().'views/js/admin.js?v='.date("ymdhis"));

        parent::initContent();
    }

}
