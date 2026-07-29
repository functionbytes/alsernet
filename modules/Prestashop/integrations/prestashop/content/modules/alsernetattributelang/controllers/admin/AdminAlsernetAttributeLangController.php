<?php

class AdminAlsernetAttributeLangController extends ModuleAdminController
{
    /** @var int Lengua de referencia fija (izquierda). */
    protected $ref_lang_id = 1; // según tu requerimiento

    public function __construct()
    {
        $this->bootstrap = true;

        // Inicializa contexto y translator ANTES de traducir nada
        parent::__construct();

        $this->meta_title = $this->trans(
            'Attribute Lang por Producto',
            [],
            'modules.Alsernetattributelang.Admin'
        );
    }

    public function initContent()
    {
        parent::initContent();

        $errors = [];
        $success = [];
        $id_product = (int)Tools::getValue('id_product');

        if (Tools::isSubmit('submitSaveTranslations') && $this->isTokenValid()) {
            try {
                $this->processSaveTranslations();
                $success[] = $this->l('Traducciones guardadas correctamente.');
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $groups = [];
        $attributes = [];
        $languages = Language::getLanguages(false);

        if (Tools::isSubmit('submitLoad') && $this->isTokenValid()) {
            if ($id_product <= 0 || !(new Product($id_product))->id) {
                $errors[] = $this->l('Producto no válido.');
            } else {
                list($groups, $attributes) = $this->loadProductAttributes($id_product, $languages);
                if (empty($attributes) && empty($groups)) {
                    $errors[] = $this->l('No se encontraron atributos para este producto.');
                }
            }
        }

        $this->context->smarty->assign([
            'link'           => $this->context->link,
            'currentIndex'   => self::$currentIndex,
            'token'          => $this->token,
            'errors'         => $errors,
            'success'        => $success,
            'id_product'     => $id_product,
            'languages'      => $languages,
            'ref_lang_id'    => $this->ref_lang_id,
            'groups'         => $groups,     // [id_group => ['names'=>[id_lang=>name], 'public_names'=>[id_lang=>public_name]]]
            'attributes'     => $attributes, // [id_attribute => ['id_group'=>..., 'names'=>[id_lang=>name]]]
        ]);

        $this->setTemplate('attributes.tpl');
    }

    protected function isTokenValid()
    {
        return Tools::getAdminToken($this->controller_name . (int)Tab::getIdFromClassName($this->controller_name) . (int)$this->context->employee->id) === Tools::getValue('token');
    }

    /**
     * Carga grupos y valores de atributos utilizados por el producto.
     */
    protected function loadProductAttributes($id_product, $languages)
    {
        $id_shop = (int)$this->context->shop->id;
        $id_langs = array_map(function ($l) {
            return (int)$l['id_lang'];
        }, $languages);

        // 1) Obtener todos los id_attribute usados en combinaciones del producto
        $sql = '
            SELECT DISTINCT pac.id_attribute, a.id_attribute_group
            FROM ' . _DB_PREFIX_ . 'product_attribute pa
            INNER JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
            INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute
            WHERE pa.id_product = ' . (int)$id_product;
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$rows) {
            return [[], []];
        }

        $attributeIds = array_map(function ($r) {
            return (int)$r['id_attribute'];
        }, $rows);
        $groupIds = array_unique(array_map(function ($r) {
            return (int)$r['id_attribute_group'];
        }, $rows));

        // 2) Cargar nombres de grupos por idioma (name y public_name)
        $groups = [];
        if ($groupIds) {
            $sqlg = '
                SELECT ag.id_attribute_group, agl.id_lang, agl.name, agl.public_name
                FROM ' . _DB_PREFIX_ . 'attribute_group ag
                INNER JOIN ' . _DB_PREFIX_ . 'attribute_group_lang agl
                    ON (agl.id_attribute_group = ag.id_attribute_group)
                WHERE ag.id_attribute_group IN (' . implode(',', array_map('intval', $groupIds)) . ')';
            $glang = Db::getInstance()->executeS($sqlg);

            foreach ($groupIds as $gid) {
                $groups[$gid] = [
                    'names' => [],
                    'public_names' => [],
                ];
            }
            foreach ($glang as $g) {
                $gid = (int)$g['id_attribute_group'];
                $groups[$gid]['names'][(int)$g['id_lang']] = $g['name'];
                $groups[$gid]['public_names'][(int)$g['id_lang']] = $g['public_name'];
            }
            // Asegurar claves vacías para todos los idiomas
            foreach ($groups as $gid => &$gdata) {
                foreach ($id_langs as $id_lang) {
                    if (!isset($gdata['names'][$id_lang])) $gdata['names'][$id_lang] = '';
                    if (!isset($gdata['public_names'][$id_lang])) $gdata['public_names'][$id_lang] = '';
                }
            }
        }


        // 3) Cargar nombres de atributos (valores) por idioma
        $attributes = [];
        if ($attributeIds) {
            $sqla = '
                SELECT a.id_attribute, a.id_attribute_group, al.id_lang, al.name
                FROM ' . _DB_PREFIX_ . 'attribute a
                LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al
                    ON (al.id_attribute = a.id_attribute )
                WHERE a.id_attribute IN (' . implode(',', array_map('intval', $attributeIds)) . ')';
            $alang = Db::getInstance()->executeS($sqla);

            foreach ($attributeIds as $aid) {
                $attributes[$aid] = ['id_group' => null, 'names' => []];
            }
            // Asegurar id_group
            foreach ($rows as $r) {
                $attributes[(int)$r['id_attribute']]['id_group'] = (int)$r['id_attribute_group'];
            }
            foreach ($alang as $a) {
                $aid = (int)$a['id_attribute'];
                $attributes[$aid]['names'][(int)$a['id_lang']] = $a['name'];
            }
            // Asegurar claves vacías por idioma
            foreach ($attributes as $aid => &$adata) {
                foreach ($id_langs as $id_lang) {
                    if (!isset($adata['names'][$id_lang])) $adata['names'][$id_lang] = '';
                }
            }
        }

        return [$groups, $attributes];
    }

    /**
     * Guarda traducciones recibidas del formulario:
     * - group[ID_GROUP][ID_LANG] => name
     * - group_public[ID_GROUP][ID_LANG] => public_name (opcional)
     * - attr[ID_ATTRIBUTE][ID_LANG] => name
     */
    protected function processSaveTranslations()
    {

        $groupNames = (array)Tools::getValue('group', []);
        $groupPublic = (array)Tools::getValue('group_public', []);
        $attrNames  = (array)Tools::getValue('attr', []);

        // Guardar grupos
        foreach ($groupNames as $id_group => $byLang) {
            foreach ($byLang as $id_lang => $name) {
                $name = trim((string)$name);
                $public = isset($groupPublic[$id_group][$id_lang]) ? trim((string)$groupPublic[$id_group][$id_lang]) : $name;

                // REPLACE mantiene única (id_attribute_group, id_lang, id_shop)
                Db::getInstance()->execute(
                    'REPLACE INTO ' . _DB_PREFIX_ . 'attribute_group_lang (id_attribute_group, id_lang, name, public_name)
                     VALUES (' . (int)$id_group . ', ' . (int)$id_lang . ', "' . pSQL($name) . '", "' . pSQL($public) . '")'
                );
            }
        }

        // Guardar atributos (valores)
        foreach ($attrNames as $id_attribute => $byLang) {
            foreach ($byLang as $id_lang => $name) {
                $name = trim((string)$name);
                Db::getInstance()->execute(
                    'REPLACE INTO ' . _DB_PREFIX_ . 'attribute_lang (id_attribute, id_lang, name)
                     VALUES (' . (int)$id_attribute . ', ' . (int)$id_lang . ', "' . pSQL($name) . '")'
                );
            }
        }
    }
}
