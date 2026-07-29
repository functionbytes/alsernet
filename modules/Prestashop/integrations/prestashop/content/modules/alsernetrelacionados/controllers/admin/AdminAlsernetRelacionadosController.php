<?php
if (!defined('_PS_VERSION_')) exit;

require_once _PS_MODULE_DIR_ . 'alsernetrelacionados/classes/AlsernetRelatedSearch.php';

class AdminAlsernetRelacionadosController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Alsernet Relacionados');

        $this->addJS(_MODULE_DIR_ . 'alsernetrelacionados/views/js/admin.js');
    }

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'ajax_url'   => $this->context->link->getAdminLink('AdminAlsernetRelacionados'),
            'token'      => $this->token,
            'id_lang'    => (int) $this->context->language->id,
            'id_shop'    => (int) $this->context->shop->id,
            'languages'  => Language::getLanguages(false),
        ]);

        $this->setTemplate('relacionados.tpl');
    }

    /** AJAX: Cargar filtros desde referencia (en idioma elegido) */
    public function displayAjaxLoadFilters()
    {
        $ref = Tools::getValue('reference');
        $filter_id_lang = (int) Tools::getValue('filter_id_lang', (int)$this->context->language->id);

        $found = AlsernetRelatedSearch::getProductByReferenceDetailed($ref);
        $id = (int) $found['id_product'];
        $ipa = (int) $found['id_product_attribute'];

        if (!$id) {
            die(json_encode(['ok' => false, 'message' => $this->l('Referencia no encontrada.')]));
        }

        $id_shop = (int)$this->context->shop->id;

        $data = AlsernetRelatedSearch::getBaseDataFromProduct($id, $filter_id_lang, $id_shop, $ipa);
        if (!$data) {
            die(json_encode(['ok' => false, 'message' => $this->l('No se pudo obtener datos del producto.')]));
        }

        // Precios vacíos (usuario define)
        $price_from = '';
        $price_to   = '';

        // ⚠️ Cargar marcas SOLO aquí (una vez), en base a la búsqueda por categoría/stock/exclusión
        //    sin filtrar por marca (para que salgan todas las posibles válidas).
        $initialFilters = [
            'id_category'     => (int)$data['id_category'],
            'id_brand'        => 0,                    // compat
            'name_like'       => '',                   // puedes poner $data['name'] si quieres restringir
            'price_from'      => '',                   // rango vacío
            'price_to'        => '',
            'exclude_id'      => (int)$id,
            'filter_id_lang'  => (int)$filter_id_lang,
            'id_brand_list'   => [],                   // sin filtro de marcas
        ];
        $initialSearch = AlsernetRelatedSearch::search(
            $initialFilters,
            (int)$filter_id_lang,
            (int)$id_shop,
            1,
            9999 // no hay paginado efectivo en tu search, pero dejamos valor alto
        );
        $brand_options = isset($initialSearch['brand_options']) ? $initialSearch['brand_options'] : [];

        die(json_encode([
            'ok' => true,
            'payload' => [
                'id_product'           => $id,
                'id_product_attribute' => $ipa,
                'name'                 => $data['name'],
                'id_category'          => $data['id_category'],
                'category'             => $data['category'],
                'id_brand'             => $data['id_brand'],
                'brand'                => $data['brand'],
                'attributes'           => $data['attributes'], // mostrar bajo el precio
                'price'                => $data['price'],
                'price_from'           => $price_from,
                'price_to'             => $price_to,
                'image'                => $data['image'],
                'brand_options'        => $brand_options, // ✅ marcas SOLO cargadas aquí
            ]
        ]));
    }


    /** AJAX: Buscar productos por filtros (en idioma elegido) */
    public function displayAjaxSearchRelated()
    {
        $filter_id_lang = (int) Tools::getValue('filter_id_lang', (int)$this->context->language->id);
        $id_shop = (int)$this->context->shop->id;

        $exclude_id = (int)Tools::getValue('exclude_id');

        // Lista de marcas (multi)
        $id_brand_list = Tools::getValue('id_brand_list');
        if (!is_array($id_brand_list)) $id_brand_list = [];

        $filters = [
            'id_category'     => (int) Tools::getValue('id_category'),
            'id_brand'        => (int) Tools::getValue('id_brand'), // compatibilidad; no se usa
            'name_like'       => trim((string) Tools::getValue('name_like')),
            'price_from'      => Tools::getValue('price_from') !== '' ? (float) Tools::getValue('price_from') : '',
            'price_to'        => Tools::getValue('price_to') !== '' ? (float) Tools::getValue('price_to') : '',
            'exclude_id'      => $exclude_id,
            'filter_id_lang'  => $filter_id_lang,
            'id_brand_list'   => array_map('intval', $id_brand_list),
        ];

        $page = max(1, (int) Tools::getValue('page', 1));
        $page_size = min(100, max(1, (int) Tools::getValue('page_size', 20)));

        $res = AlsernetRelatedSearch::search(
            $filters,
            $filter_id_lang,
            $id_shop,
            $page,
            $page_size
        );

        die(json_encode(['ok' => true, 'payload' => $res]));
    }
}
