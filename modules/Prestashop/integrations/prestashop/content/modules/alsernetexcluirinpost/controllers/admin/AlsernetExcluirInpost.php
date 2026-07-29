<?php
class AlsernetExcluirInpostController extends ModuleAdminController
{
    public function __construct()
    {
        $this->name = 'AlsernetExcluirInpost';
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

        /* Lista de los Tipos de Productos
        */
        $product_type_selected = Db::getInstance()->executeS('  select
                                                                    lang.id_feature_value,
                                                                    lang.value
                                                                from
                                                                    aalv_feature_value val
                                                                    inner join aalv_feature_value_lang lang on lang.id_feature_value = val.id_feature_value
                                                                    left join aalv_alsernet_exclude_product_inpost type on type.id_feature_value = val.id_feature_value
                                                                where
                                                                    lang.id_lang = 1
                                                                    and val.id_feature = 5
                                                                    and type.id_feature_value is null
                                                                order by lang.value asc');

        /* Lista de los Tipo de productos que se seleccionaron
        */
        $product_type_unselected = Db::getInstance()->executeS('select
                                                                    lang.id_feature_value,
                                                                    lang.value
                                                                from
                                                                    aalv_alsernet_exclude_product_inpost type
                                                                    inner join aalv_feature_value_lang lang on lang.id_feature_value = type.id_feature_value
                                                                where
                                                                    lang.id_lang = 1
                                                                order by lang.value asc');

        // Array de Familias ya seleccionadas y no seleccionadas
        $selectLista = [
            'productTypeSelected' => [],
            'productTypeUnselected' => []
        ];

        foreach ($product_type_selected as $lista_selected) {
            $selectLista['productTypeSelected'][$lista_selected['id_feature_value']] = $lista_selected['value'];
        }

        foreach ($product_type_unselected as $lista_unselected) {
            $selectLista['productTypeUnselected'][$lista_unselected['id_feature_value']] = $lista_unselected['value'];
        }

        return $selectLista;
    }

}