<?php
class AlsernetArbolErpController extends ModuleAdminController
{
    public function __construct()
    {
        $this->name = 'AlsernetArbolErp';
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        // Incluimos el archivo HTML del panel
        $this->setTemplate('admin.tpl');

        // Incluimos el JS del admin
        $this->context->controller->addJS($this->module->getPathUri().'views/js/admin.js');

        // Obtener los datos que deseas mostrar en la plantilla
        $query = $this->listaDeExclusion();

        // Asignar los datos a la plantilla Smarty
        $this->context->smarty->assign('query', $query);

        parent::initContent();
    }

    public function listaDeExclusion()
    {
        // Array de Tipo de Productos
        $selectLista = [
            'featureValue' => []
        ];

        $product_type_selected = Db::getInstance()->executeS('  select
                                                                    lang.id_feature_value,
                                                                    lang.value
                                                                from
                                                                    aalv_feature_value val
                                                                    inner join aalv_feature_value_lang lang on lang.id_feature_value = val.id_feature_value
                                                                where
                                                                    lang.id_lang = 1
                                                                    and val.id_feature = 5
                                                                order by lang.value asc');

        foreach ($product_type_selected as $lista_selected) {
            $selectLista['featureValue'][$lista_selected['id_feature_value']] = $lista_selected['value'];
        }

        return $selectLista;
    }

}
