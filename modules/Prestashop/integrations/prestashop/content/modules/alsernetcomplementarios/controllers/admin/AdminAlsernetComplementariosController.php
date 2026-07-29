<?php

require_once _PS_MODULE_DIR_.'alsernetcomplementarios/classes/AlcComplementario.php';

class AdminAlsernetComplementariosController extends ModuleAdminController
{
    public function __construct()
    {
        // Config básica que NO usa $this->l()
        $this->bootstrap = true;
        $this->table = 'alsernet_complementarios';
        $this->className = 'AlcComplementario';
        $this->lang = false;

        parent::__construct();

        $this->identifier = 'id_complementario';
        // si quieres que el listado ordene por posición:
        // $this->_orderBy   = 'position';
        // $this->_orderWay  = 'ASC';
        // si prefieres seguir por ID:
        $this->_orderBy = 'id_complementario';
        $this->_orderWay = 'DESC';

        $this->fields_list = [
            'id_complementario' => ['title' => $this->l('ID'), 'class' => 'fixed-width-xs'],
            'title' => ['title' => $this->l('Título'), 'orderby' => true, 'search' => true],
            'complement_refs' => [
                'title' => $this->l('Complementarios'),
                'callback' => 'renderComplementsColumn',
                'remove_onclick' => true,
            ],
            'type' => [
                'title' => $this->l('Tipo'),
                'callback' => 'renderTypeBadge',
                'align' => 'center',
                'orderby' => true,
                'search' => true,
            ],
            // NUEVO: mostrar orden
            'position' => [
                'title' => $this->l('Orden'),
                'align' => 'center',
                'orderby' => true,
            ],
            'date_add' => ['title' => $this->l('Fecha'), 'align' => 'center'],
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Eliminar selección'),
                'confirm' => $this->l('¿Seguro que quieres eliminar los elementos seleccionados?'),
                'icon' => 'fa-duotone icon-trash',
            ],
        ];
    }

    public function renderList()
    {
        // Mensaje de éxito al volver del guardado
        $msg = Tools::getValue('alcmsg');
        if ($msg == '1') {
            $this->confirmations[] = $this->l('Registro creado correctamente.');
        } elseif ($msg == '2') {
            $this->confirmations[] = $this->l('Registro actualizado correctamente.');
        }

        $this->toolbar_title = $this->l('Listado de complementarios');

        return parent::renderList();
    }

    /* ==== Callbacks columnas del listado ==== */
    public function renderTypeBadge($value, $row)
    {
        $map = [
            'product' => ['txt' => $this->l('Producto'),  'cls' => 'label-primary'],
            'category' => ['txt' => $this->l('Categoría'), 'cls' => 'label-warning'],
            'brand' => ['txt' => $this->l('Marca'),     'cls' => 'label-info'],
            'label' => ['txt' => $this->l('Etiqueta'),  'cls' => 'label-success'], // ← NUEVO
        ];
        $m = isset($map[$value]) ? $map[$value] : ['txt' => $value, 'cls' => 'label-default'];

        return '<span class="label '.$m['cls'].'">'.$m['txt'].'</span>';
    }

    public function renderSourceColumn($json, $row)
    {
        $ids = json_decode($json, true) ?: [];
        $type = $row['type'];
        if (! $ids) {
            return '<span class="text-muted">'.$this->l('Vacío').'</span>';
        }

        if ($type === 'product') {
            $items = $this->namesForProducts($ids);
        } elseif ($type === 'category') {
            $items = $this->namesForCategories($ids);
        } else { // brand
            $items = $this->namesForManufacturers($ids);
        }

        return $this->chipList($items, count($ids));
    }

    public function renderComplementsColumn($value, $row)
    {
        // Si viene desde 'complement_refs' (CSV de refs)
        if (isset($row['complement_refs'])) {
            $refs = $this->splitRefsCsv($row['complement_refs']);
            if (! $refs) {
                return '<span class="text-muted">-</span>';
            }

            // Mostramos hasta 3 y "+n"
            return $this->chipList($refs, count($refs));
        }

        // Compatibilidad: si viniera 'complement_ids' (JSON de IDs)
        if (isset($row['complement_ids'])) {
            $ids = json_decode($row['complement_ids'], true) ?: [];
            if (! $ids) {
                return '<span class="text-muted">-</span>';
            }
            // Opcional: convertir IDs a "Nombre [REF]" si quieres
            $items = $this->namesForProducts($ids); // ya definida en tu controlador

            return $this->chipList($items, count($ids));
        }

        return '<span class="text-muted">-</span>';
    }

    protected function splitRefsCsv($csv, $etiqueta = false)
    {
        if ($csv === null) {
            return [];
        }

        $csv = trim((string) $csv);
        if ($csv === '') {
            return [];
        }

        if ($etiqueta) {
            // ✅ Solo separa por coma, preservando espacios dentro de la etiqueta
            $tokens = array_map('trim', explode(',', $csv));
        } else {
            // 🧩 Mantiene compatibilidad: espacios, coma, punto y coma, saltos, tabs, etc.
            $tokens = preg_split('/[\s,;\r\n\t]+/', $csv, -1, PREG_SPLIT_NO_EMPTY);
            $tokens = array_map('trim', $tokens);
        }

        // Filtramos vacíos y duplicados
        $tokens = array_values(array_unique(array_filter($tokens, function ($t) {
            return $t !== '';
        })));

        return $tokens;
    }

    public function renderExcludedColumn($json)
    {
        $ids = json_decode($json, true) ?: [];
        if (! $ids) {
            return '<span class="text-muted">-</span>';
        }
        $items = $this->namesForProducts($ids);

        return '<span class="label label-default">'.$this->l('Excluidos').': '.(int) count($ids).'</span> '.$this->chipList($items, count($ids), 2);
    }

    protected function chipList(array $items, $total, $max = 3)
    {
        $out = [];
        $count = 0;
        foreach ($items as $txt) {
            $out[] = '<span class="badge" style="margin-right:4px;">'.Tools::safeOutput(Tools::substr($txt, 0, 32)).'</span>';
            if (++$count >= $max) {
                break;
            }
        }
        if ($total > $max) {
            $out[] = '<span class="badge">+'.($total - $max).'</span>';
        }

        return implode(' ', $out);
    }

    protected function namesForProducts(array $ids)
    {
        if (! $ids) {
            return [];
        }
        $id_lang = (int) $this->context->language->id;
        $rows = Db::getInstance()->executeS('
            SELECT p.id_product, pl.name, p.reference
            FROM '._DB_PREFIX_.'product p
            INNER JOIN '._DB_PREFIX_.'product_lang pl
              ON (pl.id_product=p.id_product AND pl.id_lang='.(int) $id_lang.' AND pl.id_shop='.(int) $this->context->shop->id.')
            WHERE p.id_product IN ('.implode(',', array_map('intval', $ids)).')
        ');
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['id_product']] = trim($r['name'].' '.($r['reference'] ? ('['.$r['reference'].']') : ''));
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($map[$id])) {
                $ordered[] = $map[$id];
            }
        }

        return $ordered;
    }

    protected function namesForCategories(array $ids)
    {
        if (! $ids) {
            return [];
        }
        $id_lang = (int) $this->context->language->id;
        $rows = Db::getInstance()->executeS('
            SELECT c.id_category, cl.name
            FROM '._DB_PREFIX_.'category c
            INNER JOIN '._DB_PREFIX_.'category_lang cl
              ON (cl.id_category=c.id_category AND cl.id_lang='.(int) $id_lang.' AND cl.id_shop='.(int) $this->context->shop->id.')
            WHERE c.id_category IN ('.implode(',', array_map('intval', $ids)).')
        ');
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['id_category']] = $r['name'].' (#'.$r['id_category'].')';
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($map[$id])) {
                $ordered[] = $map[$id];
            }
        }

        return $ordered;
    }

    protected function namesForManufacturers(array $ids)
    {
        if (! $ids) {
            return [];
        }
        $rows = Db::getInstance()->executeS('
            SELECT id_manufacturer, name
            FROM '._DB_PREFIX_.'manufacturer
            WHERE id_manufacturer IN ('.implode(',', array_map('intval', $ids)).')
        ');
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['id_manufacturer']] = $r['name'].' (#'.$r['id_manufacturer'].')';
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($map[$id])) {
                $ordered[] = $map[$id];
            }
        }

        return $ordered;
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->registerAssets(); // <-- centralizamos aquí
    }

    public function initContent()
    {
        parent::initContent();
        // No armes contenido aquí si usas display=view; lo haremos en renderView()
    }

    /* =========================
       ========== AJAX =========
       ========================= */
    public function displayAjaxResolveProducts()
    {
        $list = Tools::getValue('list'); // refs
        $res = $this->resolveRefsToProducts($list);
        $data = $this->fetchProductsData($res['ids'], $res['refs']);
        exit(Tools::jsonEncode([
            'ids' => $res['ids'],
            'refs' => $res['refs'],
            'data' => $data,
        ]));
    }

    public function displayAjaxPreview()
    {
        $complements = Tools::getValue('complements'); // refs
        $res = $this->resolveRefsToProductsSemicolon($complements);
        $data = $this->fetchProductsData($res['ids'], $res['refs']);
        exit(Tools::jsonEncode([
            'ids' => $res['ids'],
            'refs' => $res['refs'],
            'data' => $data,
        ]));
    }

    public function displayAjaxCheckConflicts()
    {
        $type = Tools::getValue('type');
        $sources = Tools::getValue('sources');
        $complements = Tools::getValue('complements');

        $resolvedSources = $this->resolveSourceByType($type, $sources);
        $resolvedComplements = $this->resolveProductList($complements);

        $conflicts = $this->findConflicts($type, $resolvedSources, $resolvedComplements);

        // Duplicados por TYPE
        $dups = [];
        if ($type === 'product') {
            $srcRes = $this->resolveRefsToProducts($sources);
            $dups = $this->findDuplicatesForType('product', $srcRes['refs'], $srcRes['ids']);
        } elseif ($type === 'category') {
            // duplicados por COMPLEMENTOS (no por categorías fuente)
            $cmpRes = $this->resolveRefsToProductsSemicolon($complements);
            $dups = [];
            if (! empty($cmpRes['ids'])) {
                $whereAnyCmp = $this->jsonArrayContainsAny('complement_ids', $cmpRes['ids']);
                $sqlDup = '
                SELECT id_complementario, title
                FROM '._DB_PREFIX_.'alsernet_complementarios
                WHERE type = "category"
                AND '.$whereAnyCmp.'
                LIMIT 50';
                $dups = Db::getInstance()->executeS($sqlDup) ?: [];
            }
        } elseif ($type === 'brand') {
            $dups = $this->findDuplicatesForType('brand', [], $resolvedSources);
        } elseif ($type === 'label') { // ← NUEVO
            $labels = is_array($sources) ? $sources : $this->splitRefsCsv((string) $sources);
            $srcIds = $this->getProductsByLabels($labels, []); // resolvemos ids desde etiquetas
            $dups = $this->findDuplicatesForType('label', [], $srcIds);
        }

        foreach ($dups as $d) {
            $conflicts[] = [
                'id' => (int) $d['id_complementario'],
                'reason' => 'existing_rule_for_source',
                'title' => $d['title'],
                'type' => $type,
            ];
        }

        exit(Tools::jsonEncode(['conflicts' => $conflicts]));
    }

    public function displayAjaxSaveMapping()
    {
        $id = (int) Tools::getValue('id');
        $type = Tools::getValue('type');
        $title = Tools::getValue('title');
        $sources = Tools::getValue('sources');      // para label es texto de etiquetas
        $complements = Tools::getValue('complements');  // refs
        $excluded = Tools::getValue('excluded');     // refs (opc)
        $position = (int) Tools::getValue('position', 0); // NUEVO: orden numérico

        $errors = [];
        $excludeId = (int) $id;

        if (! in_array($type, ['product', 'category', 'brand', 'label'])) {
            $errors[] = $this->l('Tipo inválido.');
        }

        /* ===============================
       ========== TIPO LABEL ==========
       =============================== */
        if ($type === 'label') {
            // 1) Etiquetas de origen (texto) -> array
            $labels = is_array($sources) ? $sources : $this->splitRefsCsv((string) $sources, true);

            // 2) Complementarios (refs -> ids) y Excluidos (refs -> ids)
            $cmpRes = $this->resolveRefsToProductsSemicolon($complements);
            $excRes = $this->resolveRefsToProducts($excluded);

            // 3) Resolver productos desde etiquetas (filtrando excluidos)
            $sourceIds = $this->getProductsByLabels($labels, $excRes['ids']);

            // 4) Normalizar para guardar
            $source_refs = $this->normalizeRefString($labels, true);        // CSV etiquetas original
            $complement_refs = $this->normalizeRefString($cmpRes['refs']);      // CSV refs complementarias
            $source_ids_json = json_encode(array_values($sourceIds));
            $complement_ids_json = json_encode(array_values($cmpRes['ids']));
            $excluded_ids_json = json_encode(array_values($excRes['ids']));

            // 5) Guardar (crear/actualizar)
            if ($id) {
                $obj = new AlcComplementario($id);
                if (! Validate::isLoadedObject($obj)) {
                    exit(Tools::jsonEncode(['success' => false, 'errors' => [$this->l('Registro no encontrado.')]]));
                }
            } else {
                $obj = new AlcComplementario;
                $obj->type = 'label';
            }

            $obj->title = pSQL($title);
            $obj->source_refs = $source_refs;
            $obj->complement_refs = $complement_refs;
            $obj->source_ids = $source_ids_json;
            $obj->complement_ids = $complement_ids_json;
            $obj->excluded_products = $excluded_ids_json;
            $obj->position = $position; // NUEVO

            $ok = $id ? $obj->update() : $obj->add();
            if (! $ok) {
                exit(Tools::jsonEncode(['success' => false, 'errors' => [$this->l('No se pudo guardar el registro.')]]));
            }
            exit(Tools::jsonEncode(['success' => true, 'id' => (int) $obj->id]));
        }

        /* ===============================
       ==== TIPOS product/category/brand
       =============================== */

        if ($type === 'product') {
            $srcRes = $this->resolveRefsToProducts($sources);
            $cmpRes = $this->resolveRefsToProductsSemicolon($complements);

            if (empty($srcRes['refs'])) {
                $errors[] = $this->l('Debes ingresar al menos una referencia de producto origen.');
            }
            if (empty($cmpRes['refs'])) {
                $errors[] = $this->l('Debes ingresar al menos una referencia de producto complementario.');
            }

            $source_refs = $this->normalizeRefString($srcRes['refs']);
            $complement_refs = $this->normalizeRefString($cmpRes['refs']);
            $source_ids = json_encode($srcRes['ids']);
            $complement_ids = json_encode($cmpRes['ids']);
            $dups = $this->findDuplicatesForType('product', $srcRes['refs'], $srcRes['ids'], $excludeId);
        } else {
            // category / brand
            $resolvedSources = $this->resolveSourceByType($type, $sources);
            $cmpRes = $this->resolveRefsToProductsSemicolon($complements);

            // marcas (ids) opcionales solo para category
            $brandIds = [];
            if ($type === 'category') {
                $brandsIn = Tools::getValue('brands'); // array de ids
                if (! is_array($brandsIn)) {
                    $brandsIn = [];
                }
                $brandIds = array_values(array_unique(array_map('intval', $brandsIn)));
            }

            if (empty($resolvedSources)) {
                $errors[] = $this->l('Debes seleccionar al menos una fuente (categoría/marca).');
            }
            if (empty($cmpRes['refs'])) {
                $errors[] = $this->l('Debes ingresar al menos una referencia de producto complementario.');
            }

            $source_refs = ''; // no aplica
            $complement_refs = $this->normalizeRefString($cmpRes['refs']);
            $source_ids = json_encode($resolvedSources);
            $complement_ids = json_encode($cmpRes['ids']);
            $dups = $this->findDuplicatesForType($type, [], $resolvedSources, $excludeId);
        }

        $cmpIds = isset($cmpRes) ? $cmpRes['ids'] : [];
        if (! empty($cmpIds)) {
            $whereAnyCmp = $this->jsonArrayContainsAny('complement_ids', $cmpIds);
            $idEx = (int) $id;

            // ⚠️ SIN LIMIT 1 AQUÍ: Db::getRow ya lo añade
            $sqlDup = '
            SELECT id_complementario, title, complement_refs
            FROM '._DB_PREFIX_.'alsernet_complementarios
            WHERE type = "'.pSQL($type).'"
              AND '.$whereAnyCmp.'
              '.($idEx ? 'AND id_complementario <> '.$idEx : '');

            $dup = Db::getInstance()->getRow($sqlDup);
            if ($dup) {
                $errors[] = sprintf(
                    $this->l('Ya existe un registro (ID #%d, "%s") de tipo "%s" que contiene alguna de estas referencias de complementarios: %s'),
                    (int) $dup['id_complementario'],
                    $dup['title'],
                    $type,
                    $dup['complement_refs']
                );
            }
        }

        if (! empty($dups)) {
            foreach ($dups as $d) {
                $errors[] = sprintf(
                    $this->l('Ya existe un registro (ID %d, %s) con estas fuentes para el tipo "%s".'),
                    (int) $d['id_complementario'],
                    $d['title'],
                    $type
                );
            }
        }

        // Si hay errores, parar
        if (! empty($errors)) {
            exit(Tools::jsonEncode(['success' => false, 'errors' => $errors]));
        }

        // Exclusiones
        $resolvedExcluded = $this->resolveRefsToProducts($excluded);
        $excluded_ids_json = json_encode($resolvedExcluded['ids']);

        // Guardar
        if ($id) {
            $obj = new AlcComplementario($id);
            if (! Validate::isLoadedObject($obj)) {
                exit(Tools::jsonEncode(['success' => false, 'errors' => [$this->l('Registro no encontrado.')]]));
            }
        } else {
            $obj = new AlcComplementario;
            $obj->type = pSQL($type);
        }

        $obj->title = pSQL($title);
        $obj->source_refs = $source_refs;
        $obj->complement_refs = $complement_refs;
        $obj->source_ids = $source_ids;

        if ($type === 'category') {
            $obj->source_brand_ids = json_encode($brandIds);
        } else {
            $obj->source_brand_ids = null;
        }

        $obj->complement_ids = $complement_ids;
        $obj->excluded_products = $excluded_ids_json;
        $obj->position = $position; // NUEVO

        $ok = $id ? $obj->update() : $obj->add();
        if (! $ok) {
            exit(Tools::jsonEncode(['success' => false, 'errors' => [$this->l('No se pudo guardar el registro.')]]));
        }

        exit(Tools::jsonEncode(['success' => true, 'id' => (int) $obj->id]));
    }

    public function displayAjaxExcludeOnly()
    {
        $type = Tools::getValue('type');
        $sources = Tools::getValue('sources');
        $excluded = Tools::getValue('excluded');
        $position = (int) Tools::getValue('position', 0); // NUEVO

        $resolvedSources = $this->resolveSourceByType($type, $sources);
        $resolvedExcluded = $this->resolveProductList($excluded);

        $obj = new AlcComplementario;
        $obj->type = pSQL($type);
        $obj->source_ids = json_encode($resolvedSources);
        $obj->complement_ids = json_encode([]); // vacío
        $obj->excluded_products = json_encode($resolvedExcluded);
        $obj->position = $position;       // NUEVO
        $ok = $obj->add();

        exit(Tools::jsonEncode(['success' => (bool) $ok, 'id' => (int) $obj->id]));
    }

    /* ===== Helpers ===== */

    /**
     * Convierte una lista (string CSV o array) de IDs o referencias a IDs de producto.
     * - IDs numéricos: se validan contra ps_product.
     * - Referencias: se buscan en ps_product.reference y ps_product_attribute.reference (UNION).
     */
    protected function resolveProductList($list)
    {
        // 1) Normalizar tokens (admite comas, ;, saltos, tabs, espacios múltiples)
        if (is_array($list)) {
            $tokens = $list;
        } else {
            $tokens = preg_split('/[\s,;\r\n\t]+/', (string) $list, -1, PREG_SPLIT_NO_EMPTY);
        }
        $tokens = array_values(array_filter(array_map('trim', $tokens), function ($t) {
            return $t !== '';
        }));

        if (! $tokens) {
            return [];
        }

        // 2) Separar IDs y referencias
        $ids_direct = [];
        $refs = [];
        foreach ($tokens as $t) {
            if (ctype_digit($t)) {
                $ids_direct[] = (int) $t;
            } else {
                $refs[] = $t;
            }
        }

        $ids = [];

        // 3) Validar IDs directos que existan en product
        if ($ids_direct) {
            $rows = Db::getInstance()->executeS('
            SELECT id_product
            FROM '._DB_PREFIX_.'product
            WHERE id_product IN ('.implode(',', array_map('intval', $ids_direct)).')
        ');
            foreach ($rows as $r) {
                $ids[] = (int) $r['id_product'];
            }
        }

        // 4) Resolver referencias en bloque con UNION (product + product_attribute)
        if ($refs) {
            // Sanear y armar IN (...)
            $refs_sanit = array_map(function ($r) {
                return '"'.pSQL($r).'"';
            }, array_unique($refs));
            $in = implode(',', $refs_sanit);

            $rows = Db::getInstance()->executeS('
            (SELECT DISTINCT p.id_product
               FROM '._DB_PREFIX_.'product p
              WHERE p.reference IN ('.$in.'))
            UNION
            (SELECT DISTINCT pa.id_product
               FROM '._DB_PREFIX_.'product_attribute pa
              WHERE pa.reference IN ('.$in.'))
        ');

            foreach ($rows as $r) {
                $ids[] = (int) $r['id_product'];
            }
        }

        // 5) Unificar y devolver
        $ids = array_values(array_unique(array_filter($ids)));

        return $ids;
    }

    /**
     * Resuelve fuentes según tipo.
     *  - product: usa resolveProductList
     *  - category: devuelve IDs de categoría recibidos (checkboxes del tree)
     *  - brand: recibe array de id_manufacturer
     */
    protected function resolveSourceByType($type, $sources)
    {
        if ($type === 'product') {
            return $this->resolveProductList($sources);
        }
        if ($type === 'category') {
            // del árbol llega en _POST['categoryBox[]'] o en $sources
            $catIds = [];
            if (is_array($sources) && ! empty($sources)) {
                $catIds = array_map('intval', $sources);
            } elseif (Tools::getIsset('categoryBox')) {
                $catIds = array_map('intval', (array) Tools::getValue('categoryBox'));
            }

            return array_values(array_unique(array_filter($catIds)));
        }
        if ($type === 'brand') {
            if (! is_array($sources)) {
                $sources = preg_split('/[,\n;]/', (string) $sources, -1, PREG_SPLIT_NO_EMPTY);
            }

            return array_values(array_unique(array_map('intval', $sources)));
        }

        return [];
    }

    /**
     * Devuelve datos de productos (para previsualización) con imagen de portada.
     */
    protected function fetchProductsData(array $ids, array $refs = [])
    {
        if (! $ids) {
            return [];
        }

        $id_lang = (int) $this->context->language->id;
        $id_shop = (int) $this->context->shop->id;

        // 1) Traemos info base (sin precio aún)
        $rows = Db::getInstance()->executeS('
            SELECT
                p.id_product,
                p.reference,
                pl.name,
                pl.link_rewrite
            FROM '._DB_PREFIX_.'product p
            INNER JOIN '._DB_PREFIX_.'product_lang pl
                ON (pl.id_product = p.id_product
                AND pl.id_lang = '.$id_lang.'
                AND pl.id_shop = '.$id_shop.')
            WHERE p.id_product IN ('.implode(',', array_map('intval', $ids)).')
        ');

        if (! $rows) {
            return [];
        }

        foreach ($rows as $key => &$r) {
            $id_product = (int) $r['id_product'];
            $id_attribute = 0;

            // 2) Buscar si hay una combinación activa con cover/reference
            // Si la referencia pertenece a un product_attribute, la resolvemos aquí:

            if (count($refs) == 0) {
                $id_attribute = Db::getInstance()->executeS('
                SELECT id_product_attribute
                FROM '._DB_PREFIX_.'product_attribute
                WHERE id_product = '.$id_product.'
                ORDER BY default_on DESC, id_product_attribute ASC
                LIMIT 1
            ');
            } else {
                $id_attribute = Db::getInstance()->executeS('
                SELECT id_product_attribute
                FROM '._DB_PREFIX_.'product_attribute
                WHERE id_product = '.$id_product.' and reference = "'.$refs[$key].'"
                ORDER BY default_on DESC, id_product_attribute ASC
                LIMIT 1
            ');
            }

            // 3) Imagen
            $r['image_url'] = str_replace('maxi.preproduccion.', '', self::getCoverImageUrl($id_product, $id_lang, 'home_default'));

            // 4) Precio por país (usa la combinación si existe)
            $r['price'] = (float) self::getPriceByCountry($id_product, $id_attribute[0]['id_product_attribute'] ?: 0, 6);

            // 5) URL de edición
            $r['url'] = $this->context->link->getProductLink(
                (int) $id_product,
                $r['link_rewrite'],
                null,
                null,
                (int) $id_lang,
                (int) $id_shop,
                0,
                false
            );
        }

        return $rows;
    }

    /**
     * Busca conflictos: registros existentes donde se solapen fuentes/tipo o
     * donde complementarios ya estén asignados con reglas incompatibles.
     * Regresa array con detalle mínimo para alertar.
     */
    protected function findConflicts($type, array $sources, array $complements)
    {
        $conflicts = [];
        $all = Db::getInstance()->executeS('SELECT id_complementario, type, source_ids, complement_ids
        FROM '._DB_PREFIX_.'alsernet_complementarios');

        foreach ($all as $row) {
            $rowType = $row['type'];

            // Conflicto si MISMO tipo y se cruzan fuentes
            if ($rowType === $type && $type !== 'category') {
                $src_existing = json_decode($row['source_ids'], true) ?: [];
                if (count(array_intersect($src_existing, $sources)) > 0) {
                    $conflicts[] = [
                        'id' => (int) $row['id_complementario'],
                        'reason' => 'overlap_source_same_type',
                    ];
                }
            }

            // Conflicto si MISMO tipo y algunos complementarios ya existen en otro registro
            if ($rowType === $type) {
                $cmp_existing = json_decode($row['complement_ids'], true) ?: [];
                if (count(array_intersect($cmp_existing, $complements)) > 0) {
                    $conflicts[] = [
                        'id' => (int) $row['id_complementario'],
                        'reason' => 'overlap_complements_same_type',
                    ];
                }
            }
        }

        return $conflicts;
    }

    public function renderForm()
    {
        $this->registerAssets();

        $ajaxBase = $this->context->link->getAdminLink('AdminAlsernetComplementarios');
        $this->context->smarty->assign([
            'alc_ajax_base' => $ajaxBase,
            'alc_list_url' => $ajaxBase,
        ]);

        $id = (int) Tools::getValue('id_complementario');
        $obj = new AlcComplementario($id);
        if (! Validate::isLoadedObject($obj)) {
            return parent::renderForm();
        }

        $type = $obj->type;
        $sources = json_decode($obj->source_ids, true) ?: [];
        $excluded = json_decode($obj->excluded_products, true) ?: [];
        $position = (int) $obj->position;

        if ($type === 'label') {
            $this->context->smarty->assign([
                'alc_title' => $obj->title,
                'alc_labels_text' => $obj->source_refs,
                'alc_complements_text' => $obj->complement_refs,
                'alc_excluded_text' => $this->idsToRefsCsv($excluded),
                'alc_edit_id' => (int) $obj->id,
                'alc_position' => $position,          // NUEVO
            ]);

            return $this->context->smarty->fetch(
                _PS_MODULE_DIR_.$this->module->name.'/views/templates/admin/form_label.tpl'
            );
        }

        if ($type === 'product') {
            $this->context->smarty->assign([
                'alc_title' => $obj->title,
                'alc_sources_text' => $obj->source_refs,
                'alc_complements_text' => $obj->complement_refs,
                'alc_position' => $position,          // NUEVO
            ]);
            $tpl = 'form_product.tpl';
        } elseif ($type === 'category') {
            $combinedTreeHtml = $this->renderWhitelistedCategoryTree($sources);

            $mans = Manufacturer::getManufacturers(false, $this->context->language->id, true);
            $selectedBrands = json_decode($obj->source_brand_ids, true) ?: [];

            $this->context->smarty->assign([
                'alc_title' => $obj->title,
                'alc_category_tree' => $combinedTreeHtml,
                'alc_manufacturers' => $mans,
                'alc_selected_brands' => $selectedBrands,
                'alc_complements_text' => $obj->complement_refs,
                'alc_excluded_text' => $this->idsToRefsCsv($excluded),
                'alc_position' => $position,        // NUEVO
            ]);
            $tpl = 'form_category.tpl';
        } else { // brand
            $mans = Manufacturer::getManufacturers(false, $this->context->language->id, true);
            $this->context->smarty->assign([
                'alc_title' => $obj->title,
                'alc_manufacturers' => $mans,
                'alc_selected_brands' => $sources,
                'alc_complements_text' => $obj->complement_refs,
                'alc_excluded_text' => $this->idsToRefsCsv($excluded),
                'alc_position' => $position,         // NUEVO
            ]);
            $tpl = 'form_brand.tpl';
        }

        $this->context->smarty->assign([
            'alc_edit_id' => (int) $obj->id,
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_.$this->module->name.'/views/templates/admin/'.$tpl
        );
    }

    public function postProcess()
    {
        if (Tools::isSubmit('delete'.$this->table)) {
            $id = (int) Tools::getValue($this->identifier);
            $obj = new AlcComplementario($id);
            if ($obj->id && $obj->delete()) {
                Tools::redirectAdmin(self::$currentIndex.'&conf=1&token='.$this->token);
            } else {
                $this->errors[] = $this->l('No se pudo eliminar el registro.');

                // Importante: NO seguir a parent::postProcess() porque volvería a intentar borrar
                return;
            }
        }

        if (Tools::isSubmit('submitBulkdelete'.$this->table)) {
            $ids = Tools::getValue($this->table.'Box');
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    $o = new AlcComplementario((int) $id);
                    if ($o->id) {
                        $o->delete();
                    }
                }
                $this->confirmations[] = $this->l('Registros eliminados.');
            }
        }

        return parent::postProcess();
    }

    public function initPageHeaderToolbar()
    {
        // siempre llamar al padre
        parent::initPageHeaderToolbar();

        // Mostrar la barra de herramientas de cabecera
        $this->show_page_header_toolbar = true;
        $this->page_header_toolbar_title = $this->l('Complementarios');

        // Botón estándar "Agregar nuevo" (opcional)
        $this->toolbar_btn['new'] = [
            'href' => self::$currentIndex.'&addalsernet_complementarios&token='.$this->token,
            'desc' => $this->l('Agregar nuevo'),
            'icon' => 'fa-duotone process-icon-new',
        ];

        // Tus 3 botones de atajo
        $this->page_header_toolbar_btn['add_product'] = [
            'href' => $this->context->link->getAdminLink('AdminAlsernetComplementarios').'&addType=product',
            'desc' => $this->l('Por Producto'),
            'icon' => 'fa-duotone process-icon-new',
        ];
        $this->page_header_toolbar_btn['add_category'] = [
            'href' => $this->context->link->getAdminLink('AdminAlsernetComplementarios').'&addType=category',
            'desc' => $this->l('Por Categoría'),
            'icon' => 'fa-duotone process-icon-category',
        ];
        $this->page_header_toolbar_btn['add_brand'] = [
            'href' => $this->context->link->getAdminLink('AdminAlsernetComplementarios').'&addType=brand',
            'desc' => $this->l('Por Marca'),
            'icon' => 'fa-duotone process-icon-manufacturer',
        ];
        $this->page_header_toolbar_btn['add_label'] = [
            'href' => $this->context->link->getAdminLink('AdminAlsernetComplementarios').'&addType=label',
            'desc' => $this->l('Por Etiqueta'),
            'icon' => 'fa-duotone process-icon-tags', // o el que prefieras
        ];
    }

    public function initProcess()
    {
        parent::initProcess();

        if (Tools::getIsset('addType')) {
            // Forzar modo view para que NO se renderice la lista
            $this->display = 'view';
            // Opcional: ocultar barras de lista cuando estamos en view
            $this->show_toolbar = false;
            $this->show_page_header_toolbar = false;
        }
    }

    /**
     * Carga jQuery + JS/CSS del módulo usando URL pública.
     * La llamamos desde setMedia() y también desde renderView() por si setMedia no se ejecuta.
     */
    protected function registerAssets()
    {
        $this->addJquery();
        $base = $this->module->getPathUri();
        $ver = '?v='.urlencode($this->module->version);

        // Usa la RUTA REAL de tus assets:
        $this->context->controller->addJS($base.'views/templates/js/admin.js'.$ver);
        $this->context->controller->addCSS($base.'views/templates/css/admin.css'.$ver);
    }

    public function renderView()
    {
        $this->registerAssets();
        $type = Tools::getValue('addType');

        $ajaxBase = $this->context->link->getAdminLink('AdminAlsernetComplementarios');
        $this->context->smarty->assign([
            'alc_ajax_base' => $ajaxBase,
            'alc_list_url' => $ajaxBase,
        ]);

        // posición Por defecto 0 en altas nuevas
        $this->context->smarty->assign([
            'alc_position' => 0,
        ]);

        if (! in_array($type, ['product', 'category', 'brand', 'label'])) {
            $listUrl = $this->context->link->getAdminLink('AdminAlsernetComplementarios');
            $this->context->smarty->assign('alc_list_url', $listUrl);
        }

        switch ($type) {
            case 'product':
                $tpl = 'form_product.tpl';
                $this->context->smarty->assign([
                    'alc_title' => '',
                    'alc_sources_text' => '',
                    'alc_complements_text' => '',
                ]);
                break;

            case 'category':
                $tpl = 'form_category.tpl';
                $mans = Manufacturer::getManufacturers(false, $this->context->language->id, true);

                $this->context->smarty->assign([
                    'alc_category_tree' => $this->renderWhitelistedCategoryTree([]),
                    'alc_manufacturers' => $mans,
                    'alc_selected_brands' => [],
                    'alc_title' => '',
                    'alc_complements_text' => '',
                    'alc_excluded_text' => '',
                    'currentIndex' => self::$currentIndex,
                    'token' => $this->token,
                ]);
                break;

            case 'brand':
                $tpl = 'form_brand.tpl';
                $mans = Manufacturer::getManufacturers(false, $this->context->language->id, true);
                $this->context->smarty->assign([
                    'alc_manufacturers' => $mans,
                    'alc_title' => '',
                    'alc_complements_text' => '',
                    'alc_selected_brands' => [],
                    'alc_excluded_text' => '',
                ]);
                break;

            case 'label':
                $tpl = 'form_label.tpl';
                $this->context->smarty->assign([
                    'alc_title' => '',
                    'alc_labels_text' => '',
                    'alc_complements_text' => '',
                    'alc_excluded_text' => '',
                ]);
                break;
        }

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_.$this->module->name.'/views/templates/admin/'.$tpl
        );
    }

    public static function getCoverImageUrl($id_product, $id_shop)
    {
        $cover = Image::getCover($id_product);
        if (! is_array($cover) || empty($cover['id_image'])) {
            return '';
        }
        $image = new Image($cover['id_image']);
        $link = Context::getContext()->link;

        $url = $link->getImageLink(null, $image->getExistingImgPath(), 'large_default');
        if (! $url || strpos($url, 'default') !== false) {
            $url = $link->getImageLink(null, $image->getExistingImgPath(), 'home_default');
        }
        if (! $url) {
            $url = _PS_IMG_.'p/es-default-large_default.jpg';
        }

        return $url;
    }

    /**
     * Obtiene el precio final de un producto según el país.
     */
    public static function getPriceByCountry($id_product, $id_product_attribute = 0, $id_country = 6)
    {
        $id_product = (int) $id_product;
        $id_product_attribute = (int) $id_product_attribute;
        $id_country = (int) $id_country;

        $ctx = Context::getContext();
        $id_shop = (int) $ctx->shop->id;

        $country = new Country($id_country);
        if (! Validate::isLoadedObject($country)) {
            $id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        }

        $id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $id_group = (int) Configuration::get('PS_UNIDENTIFIED_GROUP');
        $id_customer = 0;

        $specific_price = null;
        $price = Product::priceCalculation(
            $id_shop,
            $id_product,
            $id_product_attribute,
            $id_country,
            0,
            '',
            $id_currency,
            $id_group,
            1,
            true,
            6,
            false,
            true,
            true,
            $specific_price,
            true,
            $id_customer,
            true,
            0,
            0,
            0
        );

        if (! $price || $price < 0) {
            $price = Product::getPriceStatic($id_product, true, $id_product_attribute);
        }

        return (float) $price;
    }

    /**
     * Normaliza una lista textual y la devuelve como CSV limpio (con espacios tras la coma).
     * - Por defecto admite separadores múltiples: comas, punto y coma, espacios, tabs y saltos de línea.
     * - Si $etiqueta = true, SOLO separa por coma (las etiquetas pueden llevar espacios).
     */
    protected function normalizeRefString($list, $etiqueta = false)
    {
        // 1) Obtener string base
        $str = is_array($list) ? implode(',', $list) : (string) $list;

        // 2) Tokenizar según modo
        if ($etiqueta) {
            // Solo coma como separador; no romper por espacios
            $tokens = array_map('trim', explode(',', $str));
        } else {
            // Separadores amplios (coma, ;, espacios, tabs, saltos)
            $tokens = preg_split('/[\s,;\r\n\t]+/', $str, -1, PREG_SPLIT_NO_EMPTY);
            $tokens = array_map('trim', $tokens);
        }

        // 3) Limpiar, deduplicar y reconstruir CSV con espacio tras coma
        $tokens = array_values(array_unique(array_filter($tokens, function ($t) {
            return $t !== '';
        })));

        return implode(', ', $tokens);
    }

    /**
     * Resuelve referencias (incluyendo numéricas) y/o IDs a IDs de producto.
     * Devuelve:
     *  [
     *    'ids'  => [1,2,3],                  // IDs de producto únicos
     *    'refs' => ['CAN203','320200', ...]  // tokens normalizados tal como llegaron (incluye numéricos)
     *  ]
     */
    protected function resolveRefsToProducts($list)
    {
        // 1) Tokenizar (coma, punto y coma, espacios, tabs, saltos de línea)
        if (is_array($list)) {
            $tokens = $list;
        } else {
            $tokens = preg_split('/[\s,;\r\n\t]+/', (string) $list, -1, PREG_SPLIT_NO_EMPTY);
        }
        $tokens = array_values(array_filter(array_map('trim', $tokens), function ($t) {
            return $t !== '';
        }));
        if (! $tokens) {
            return ['ids' => [], 'refs' => []];
        }

        // 2) Preparar buckets
        // refs_cand: TODOS los tokens (incluye numéricos) -> intentar como referencia (product + product_attribute)
        // ids_cand:  SOLO tokens numéricos -> intentar además como id_product
        $refs_cand = array_values(array_unique($tokens));
        $ids_cand = [];
        foreach ($refs_cand as $t) {
            if (ctype_digit($t)) {
                $ids_cand[] = (int) $t;
            }
        }

        $ids = [];

        // 3) Resolver por referencia (product.reference y product_attribute.reference)
        if ($refs_cand) {
            $in = implode(',', array_map(function ($r) {
                return '"'.pSQL($r).'"';
            }, $refs_cand));

            $rows = Db::getInstance()->executeS('
            (SELECT DISTINCT p.id_product
               FROM '._DB_PREFIX_.'product p
              WHERE p.reference IN ('.$in.'))
            UNION
            (SELECT DISTINCT pa.id_product
               FROM '._DB_PREFIX_.'product_attribute pa
              WHERE pa.reference IN ('.$in.'))
        ');

            foreach ($rows as $r) {
                $ids[] = (int) $r['id_product'];
            }
        }

        // 4) Resolver también como ID (solo para tokens numéricos)
        if ($ids_cand && count($ids) == 0) {
            $rows = Db::getInstance()->executeS('
            SELECT id_product
            FROM '._DB_PREFIX_.'product
            WHERE id_product IN ('.implode(',', array_map('intval', $ids_cand)).')
        ');
            foreach ($rows as $r) {
                $ids[] = (int) $r['id_product'];
            }
        }

        // 5) Unificar
        $ids = array_values(array_unique(array_filter($ids, 'intval')));
        $refs = array_values(array_unique($refs_cand)); // preserva numéricos también

        return ['ids' => $ids, 'refs' => $refs];
    }

    /**
     * Árbol único desde raíces 3..11 con carpetas plegables (cerradas al cargar).
     */
    protected function renderWhitelistedCategoryTree(array $selected = [])
    {
        $id_lang = (int) $this->context->language->id;
        $id_shop = (int) $this->context->shop->id;
        $allowedRoots = [3, 4, 5, 6, 7, 8, 9, 10, 11];

        $html = '<div id="alc-category-tree-wrap" class="panel">';
        $html .= '  <div class="panel-heading" style="display:flex;gap:8px;align-items:center;">';
        $html .= '    <button type="button" class="btn btn-default btn-sm" id="alc-expand-all"><i class="icon-plus-sign-alt"></i> '.$this->l('Expandir todo').'</button>';
        $html .= '    <button type="button" class="btn btn-default btn-sm" id="alc-collapse-all"><i class="icon-minus-sign-alt"></i> '.$this->l('Contraer todo').'</button>';
        $html .= '  </div>';
        $html .= '  <div class="panel-body">';
        $html .= '    <ul class="tree" id="alc-category-tree-root">';

        foreach ($allowedRoots as $rootId) {
            $root = new Category((int) $rootId, $id_lang, $id_shop);
            if (! Validate::isLoadedObject($root)) {
                continue;
            }
            $nested = Category::getNestedCategories(
                (int) $rootId,
                $id_lang,
                true,   // solo activas
                null,   // groups
                true    // respetar tienda
            );
            if (is_array($nested) && ! empty($nested)) {
                foreach ($nested as $node) {
                    $html .= $this->renderCategoryNodeRecursive($node, $selected);
                }
            }
        }

        $html .= '    </ul>';
        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Nodo recursivo con estructura BO:
     * - carpetas: <li class="tree-folder">, icono folder, UL hijos oculto
     * - items:    <li class="tree-item">, icono dot
     */
    protected function renderCategoryNodeRecursive(array $node, array $selected = [])
    {
        $id = (int) $node['id_category'];
        $name = isset($node['name']) ? $node['name'] : ('#'.$id);
        $isSelected = in_array($id, $selected);
        $inputId = 'categoryBox_'.$id;
        $hasChildren = ! empty($node['children']) && is_array($node['children']);

        $html = '';

        if ($hasChildren) {
            $html .= '<li class="tree-folder">';
            $html .= '  <span class="tree-folder-name">';
            $html .= '    <i class="icon-folder-close"></i>';
            $html .= '    <input type="checkbox" name="categoryBox[]" id="'.htmlspecialchars($inputId).'" value="'.$id.'" '.($isSelected ? 'checked="checked"' : '').' />';
            $html .= '    <label for="'.htmlspecialchars($inputId).'">'.htmlspecialchars($name).' ('.$id.')</label>';

            // ⬇️ NUEVO: selector de hijas/subhijas (no marca el padre)
            $html .= '    <label class="alc-children-toggle" title="'.$this->l('Seleccionar solo hijas y subhijas').'" style="margin-left:10px; font-weight:normal;">';
            $html .= '      <input type="checkbox" class="alc-children-only" data-parent-id="'.$id.'"> '.$this->l('Hijas');
            $html .= '    </label>';

            $html .= '  </span>';
            $html .= '  <ul class="tree" style="display:none;">';
            foreach ($node['children'] as $child) {
                $html .= $this->renderCategoryNodeRecursive($child, $selected);
            }
            $html .= '  </ul>';
            $html .= '</li>';
        } else {
            $html .= '<li class="tree-item">';
            $html .= '  <span class="tree-item-name">';
            $html .= '    <i class="tree-dot"></i>';
            $html .= '    <input type="checkbox" name="categoryBox[]" id="'.htmlspecialchars($inputId).'" value="'.$id.'" '.($isSelected ? 'checked="checked"' : '').' />';
            $html .= '    <label for="'.htmlspecialchars($inputId).'">'.htmlspecialchars($name).' ('.$id.')</label>';
            $html .= '  </span>';
            $html .= '</li>';
        }

        return $html;
    }

    /**
     * Devuelve IDs de productos que estén en (al menos) una de las categorías dadas
     * y que cumplan: activos, visibles (!= 'none') y con stock > 0.
     * Filtra excluidos (array de IDs de producto).
     */
    protected function getProductsByCategories(array $categoryIds, array $excludeProductIds = [])
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        if (! $categoryIds) {
            return [];
        }

        $idShop = (int) $this->context->shop->id;

        // Productos por categoría
        $sql = '
            SELECT DISTINCT p.id_product
            FROM '._DB_PREFIX_.'category_product cp
            INNER JOIN '._DB_PREFIX_.'product p
                ON (p.id_product = cp.id_product AND p.active = 1 AND p.visibility != "none")
            LEFT JOIN '._DB_PREFIX_.'stock_available sa
                ON (sa.id_product = p.id_product
                    AND sa.id_product_attribute = 0
                    AND (sa.id_shop = '.(int) $idShop.' OR sa.id_shop = 0))
            WHERE cp.id_category IN ('.implode(',', $categoryIds).')
            AND IFNULL(sa.quantity, 0) > 0
        ';

        $rows = Db::getInstance()->executeS($sql);
        if (! $rows) {
            return [];
        }

        $ids = array_map(function ($r) {
            return (int) $r['id_product'];
        }, $rows);
        if ($excludeProductIds) {
            $exclude = array_flip(array_map('intval', $excludeProductIds));
            $ids = array_values(array_filter($ids, function ($id) use ($exclude) {
                return ! isset($exclude[$id]);
            }));
        }

        return $ids;
    }

    /**
     * Previsualización para CATEGORÍA:
     * - sources: categorías seleccionadas (IDs)
     * - complements: referencias
     * - excluded: referencias (opcional)
     */
    public function displayAjaxPreviewCategory()
    {
        // Categorías (IDs)
        $sources = Tools::getValue('sources');
        if (! is_array($sources)) {
            $sources = [];
        }
        $sources = array_values(array_unique(array_map('intval', $sources)));

        // Marcas (IDs) — NUEVO
        $brands = Tools::getValue('brands');
        if (! is_array($brands)) {
            $brands = [];
        }
        $brands = array_values(array_unique(array_map('intval', $brands)));

        // Complementos (refs -> ids)
        $cmpRes = $this->resolveRefsToProductsSemicolon(Tools::getValue('complements'));
        $cmpIds = $cmpRes['ids'];

        // Excluidos (refs -> ids)
        $excRes = $this->resolveRefsToProducts(Tools::getValue('excluded'));
        $excIds = $excRes['ids'];

        // Origen: productos por categoría (+marca si corresponde)
        $originIds = $this->getProductsByCategoriesAndBrands($sources, $brands, $excIds);

        // Datos para tablas
        $originData = $this->fetchProductsData($originIds);
        $complementsData = $this->fetchProductsData($cmpIds);

        exit(Tools::jsonEncode([
            'sources_ids' => $originIds,
            'sources_data' => $originData,
            'complement_ids' => $cmpIds,
            'complements_data' => $complementsData,
            'excluded_matched' => $excIds,
        ]));
    }

    /**
     * Convierte una lista de id_product a referencias legibles (CSV).
     * Prioriza la referencia de la combinación Por defecto; si no hay, usa la referencia del producto.
     */
    protected function idsToRefsCsv(array $ids)
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (! $ids) {
            return '';
        }

        $refs = [];
        foreach ($ids as $id_product) {
            // 1) referencia de la combinación Por defecto, si existe
            $ref = Db::getInstance()->executeS('
                SELECT pa.reference
                FROM '._DB_PREFIX_.'product_attribute pa
                WHERE pa.id_product = '.(int) $id_product.' AND pa.default_on = 1
                LIMIT 1
            ');

            // 2) fallback a referencia del producto
            if (count($ref) == 0) {
                $ref = Db::getInstance()->executeS('
                    SELECT p.reference
                    FROM '._DB_PREFIX_.'product p
                    WHERE p.id_product = '.(int) $id_product.'
                    LIMIT 1
                ');
            }

            // 3) si sigue vacío, al menos devolvemos el id como marcador
            $refs[] = $ref ? $ref[0]['reference'] : ('#'.$id_product);
        }

        // CSV limpio
        $refs = array_values(array_unique(array_filter(array_map('trim', $refs), function ($t) {
            return $t !== '';
        })));

        return implode(', ', $refs);
    }

    /**
     * Devuelve IDs de productos por marca(s) que cumplan:
     * activos, visibles (!= 'none') y con stock > 0 en la tienda actual.
     */
    protected function getProductsByBrands(array $brandIds, array $excludeProductIds = [])
    {
        $brandIds = array_values(array_unique(array_map('intval', $brandIds)));
        if (! $brandIds) {
            return [];
        }

        $idShop = (int) $this->context->shop->id;

        $sql = '
        SELECT DISTINCT p.id_product
        FROM '._DB_PREFIX_.'product p
        LEFT JOIN '._DB_PREFIX_.'stock_available sa
            ON (sa.id_product = p.id_product
                AND sa.id_product_attribute = 0
                AND (sa.id_shop = '.(int) $idShop.' OR sa.id_shop = 0))
        WHERE p.id_manufacturer IN ('.implode(',', $brandIds).')
          AND p.active = 1
          AND p.visibility != "none"
          AND IFNULL(sa.quantity, 0) > 0
    ';

        $rows = Db::getInstance()->executeS($sql);
        if (! $rows) {
            return [];
        }

        $ids = array_map(function ($r) {
            return (int) $r['id_product'];
        }, $rows);
        if ($excludeProductIds) {
            $exclude = array_flip(array_map('intval', $excludeProductIds));
            $ids = array_values(array_filter($ids, function ($id) use ($exclude) {
                return ! isset($exclude[$id]);
            }));
        }

        return $ids;
    }

    /** AJAX: Previsualización para MARCA */
    public function displayAjaxPreviewBrand()
    {
        // Marcas seleccionadas
        $brands = Tools::getValue('sources');
        if (! is_array($brands)) {
            $brands = [];
        }
        $brands = array_values(array_unique(array_map('intval', $brands)));

        // Complementos (refs -> ids)
        $cmpRes = $this->resolveRefsToProductsSemicolon(Tools::getValue('complements'));

        $cmpIds = $cmpRes['ids'];

        // Excluidos (refs -> ids) para filtrar origen
        $excRes = $this->resolveRefsToProducts(Tools::getValue('excluded'));
        $excIds = $excRes['ids'];

        // Productos de origen por marca(s)
        $originIds = $this->getProductsByBrands($brands, $excIds);

        // Tablas
        $originData = $this->fetchProductsData($originIds);
        $complementsData = $this->fetchProductsData($cmpIds);

        exit(Tools::jsonEncode([
            'sources_ids' => $originIds,
            'sources_data' => $originData,
            'complement_ids' => $cmpIds,
            'complements_data' => $complementsData,
            'excluded_matched' => $excIds,
        ]));
    }

    /**
     * LIKE para JSON de enteros guardado como texto: ["12", "34", 56]
     * Coincide "123" sin falsos positivos (comillas obligatorias alrededor del número)
     */
    // protected function likeJsonInt($field, $int)
    // {
    //     $int = (int)$int;
    //     // buscamos "123" o ,123, de forma simple al estar almacenado como texto
    //     return $field . ' LIKE ' . '"%\"' . $int . '\"%"';
    // }

    // ✅ Nueva versión: usa el mismo truco de comas que jsonArrayContainsAny
    protected function likeJsonInt($field, $int)
    {
        $int = (int) $int;
        $wrapped = $this->sqlWrapJsonArray($field); // ",1696,1697,"

        return "$wrapped LIKE '%,{$int},%'";
    }

    /** REGEXP para machacar refs en source_refs/complement_refs normalizadas "A, B, C" */
    protected function regexpRefExact($field, $ref)
    {
        $ref = pSQL($ref);
        // Usamos [:space:] para soportar espacios/tab/nuevas líneas
        // NOTA: preg_quote para seguridad en el patrón
        $refQuoted = preg_quote($ref, '/');

        return $field." REGEXP '(^|[,[:space:]]+)".$refQuoted."([,[:space:]]+|$)'";
    }

    /**
     * Devuelve posibles duplicados por TYPE y fuentes:
     * - product: si alguna ref o id_product ya existe en source_refs/source_ids de un registro del mismo type.
     * - category / brand: si algún id de $sourceIds ya existe en source_ids de un registro del mismo type.
     * Excluye el registro $excludeId (para edición).
     *
     * Retorna array de filas: id_complementario, type, title
     */
    protected function findDuplicatesForType($type, array $sourceRefs, array $sourceIds, $excludeId = 0)
    {
        $type = pSQL($type);
        $excludeId = (int) $excludeId;

        $wheres = [];
        if ($type === 'product') {
            foreach (array_unique($sourceRefs) as $r) {
                if ($r === '') {
                    continue;
                }
                $wheres[] = $this->regexpRefExact('source_refs', $r);
            }
            foreach (array_unique(array_map('intval', $sourceIds)) as $id) {
                if ($id > 0) {
                    $wheres[] = $this->likeJsonInt('source_ids', $id);
                }
            }
        } elseif ($type === 'brand' || $type === 'label') {
            foreach (array_unique(array_map('intval', $sourceIds)) as $id) {
                if ($id > 0) {
                    $wheres[] = $this->likeJsonInt('source_ids', $id);
                }
            }
        } elseif ($type === 'category') {
            // ✅ En categoría no buscamos duplicado por fuentes.
            return [];
        }

        if (! $wheres) {
            return [];
        }

        $sql = '
        SELECT id_complementario, type, title
        FROM '._DB_PREFIX_.'alsernet_complementarios
        WHERE type = "'.$type.'"
          AND ('.implode(' OR ', $wheres).')
          '.($excludeId ? ' AND id_complementario != '.$excludeId : '').'
        ORDER BY id_complementario DESC
        LIMIT 50';

        return Db::getInstance()->executeS($sql) ?: [];
    }

    protected function sqlWrapJsonArray($col)
    {
        return "CONCAT(',', REPLACE(REPLACE($col,'[',''),']',''), ',')";
    }

    protected function jsonArrayContainsAny($col, array $ints)
    {
        $ints = array_values(array_unique(array_map('intval', $ints)));
        if (! $ints) {
            return '0';
        } // false
        $wrapped = $this->sqlWrapJsonArray($col);
        $ors = [];
        foreach ($ints as $n) {
            $ors[] = "$wrapped LIKE '%,{$n},%'";
        }

        return '('.implode(' OR ', $ors).')';
    }

    /**
     * Devuelve IDs de productos que coincidan con una o varias etiquetas.
     * Busca tanto en combinaciones como en producto “único”.
     * $labels: array de strings (etiquetas). Se hace LIKE por cada una (OR).
     * $excludeProductIds: ids a excluir del resultado.
     */
    protected function getProductsByLabels(array $labels, array $excludeProductIds = [])
    {
        $labels = array_values(array_unique(array_filter(array_map('trim', $labels), function ($s) {
            return $s !== '';
        })));
        if (! $labels) {
            return [];
        }

        // Escapar % y _ en LIKE, y preparar condiciones OR
        $likeParts = [];
        foreach ($labels as $lab) {
            $safe = pSQL(str_replace(['%', '_'], ['\%', '\_'], $lab));
            $likeParts[] = "aci.etiqueta LIKE '%".$safe."%'";
        }
        $whereLike = '('.implode(' OR ', $likeParts).')';

        $sql = '
        SELECT apa.id_product
        FROM '._DB_PREFIX_.'combinaciones_import aci
        LEFT JOIN '._DB_PREFIX_.'product_attribute apa
               ON apa.id_product_attribute = aci.id_product_attribute
        WHERE '.$whereLike.'
        UNION
        SELECT aci.id_product
        FROM '._DB_PREFIX_.'combinacionunica_import aci
        WHERE '.$whereLike;

        $rows = Db::getInstance()->executeS($sql);
        if (! $rows) {
            return [];
        }

        $ids = array_values(array_unique(array_map(function ($r) {
            return (int) $r['id_product'];
        }, $rows)));

        if ($excludeProductIds) {
            $exclude = array_flip(array_map('intval', $excludeProductIds));
            $ids = array_values(array_filter($ids, function ($id) use ($exclude) {
                return ! isset($exclude[$id]);
            }));
        }

        return $ids;
    }

    /** AJAX: Previsualización para ETIQUETA */
    public function displayAjaxPreviewLabel()
    {
        // Etiquetas ingresadas (texto CSV/espacios)
        $labelsTxt = (string) Tools::getValue('sources', '');

        $labels = $this->splitRefsCsv($labelsTxt, true); // reutilizamos splitRefsCsv() para tokenizar

        // Complementos (refs -> ids)

        $cmpRes = $this->resolveRefsToProductsSemicolon(Tools::getValue('complements'));

        $cmpIds = $cmpRes['ids'];

        // Excluidos (refs -> ids) para filtrar origen
        $excRes = $this->resolveRefsToProducts(Tools::getValue('excluded'));
        $excIds = $excRes['ids'];

        // Productos de origen por etiqueta(s)
        $originIds = $this->getProductsByLabels($labels, $excIds);

        // Tablas
        $originData = $this->fetchProductsData($originIds);

        $complementsData = $this->fetchProductsData($cmpIds);

        exit(Tools::jsonEncode([
            'sources_ids' => $originIds,
            'sources_data' => $originData,
            'complement_ids' => $cmpIds,
            'complements_data' => $complementsData,
            'excluded_matched' => $excIds,
        ]));
    }

    // --- NUEVO: tokenizar solo por ; o saltos de línea, luego trim ---
    protected function tokenizeSemicolon($str)
    {
        $tokens = preg_split('/[;\r\n]+/', (string) $str, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_map('trim', $tokens);

        // quitar vacíos y duplicados
        return array_values(array_unique(array_filter($tokens, function ($t) {
            return $t !== '';
        })));
    }

    // --- NUEVO: igual a resolveRefsToProducts pero usando tokenizeSemicolon ---
    protected function resolveRefsToProductsSemicolon($list)
    {
        $tokens = is_array($list) ? $list : $this->tokenizeSemicolon($list);
        if (! $tokens) {
            return ['ids' => [], 'refs' => []];
        }

        $refs_cand = array_values(array_unique($tokens));
        $ids_cand = [];
        foreach ($refs_cand as $t) {
            if (ctype_digit($t)) {
                $ids_cand[] = (int) $t;
            }
        }

        $ids = [];

        // por referencia (product.reference + product_attribute.reference)
        if ($refs_cand) {
            $in = implode(',', array_map(function ($r) {
                return '"'.pSQL($r).'"';
            }, $refs_cand));
            $rows = Db::getInstance()->executeS('
            (SELECT DISTINCT p.id_product FROM '._DB_PREFIX_.'product p WHERE p.reference IN ('.$in.'))
            UNION
            (SELECT DISTINCT pa.id_product FROM '._DB_PREFIX_.'product_attribute pa WHERE pa.reference IN ('.$in.'))
        ');
            foreach ($rows as $r) {
                $ids[] = (int) $r['id_product'];
            }
        }

        // también aceptar IDs numéricos (si vinieran)
        if ($ids_cand) {
            $rows = Db::getInstance()->executeS('
            SELECT id_product FROM '._DB_PREFIX_.'product
            WHERE id_product IN ('.implode(',', array_map('intval', $ids_cand)).')
        ');
            foreach ($rows as $r) {
                $ids[] = (int) $r['id_product'];
            }
        }

        $ids = array_values(array_unique(array_filter($ids, 'intval')));
        $refs = array_values(array_unique($refs_cand));

        return ['ids' => $ids, 'refs' => $refs];
    }

    /**
     * Devuelve productos que estén en AL MENOS una de las categorías dadas
     * y pertenezcan a AL MENOS una de las marcas dadas (si $brandIds no está vacío),
     * activos, visibles (!='none') y con stock > 0. Filtra excluidos.
     */
    protected function getProductsByCategoriesAndBrands(array $categoryIds, array $brandIds = [], array $excludeProductIds = [])
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        $brandIds = array_values(array_unique(array_map('intval', $brandIds)));
        if (! $categoryIds) {
            return [];
        }

        $idShop = (int) $this->context->shop->id;

        $sql = '
        SELECT DISTINCT p.id_product
        FROM '._DB_PREFIX_.'category_product cp
        INNER JOIN '._DB_PREFIX_.'product p
            ON (p.id_product = cp.id_product AND p.active = 1 AND p.visibility != "none")
        LEFT JOIN '._DB_PREFIX_.'stock_available sa
            ON (sa.id_product = p.id_product
                AND sa.id_product_attribute = 0
                AND (sa.id_shop = '.(int) $idShop.' OR sa.id_shop = 0))
        WHERE cp.id_category IN ('.implode(',', $categoryIds).')
          AND IFNULL(sa.quantity, 0) > 0
          '.($brandIds ? ' AND p.id_manufacturer IN ('.implode(',', $brandIds).')' : '').'
    ';
        $rows = Db::getInstance()->executeS($sql) ?: [];
        $ids = array_map(function ($r) {
            return (int) $r['id_product'];
        }, $rows);

        if ($excludeProductIds) {
            $exclude = array_flip(array_map('intval', $excludeProductIds));
            $ids = array_values(array_filter($ids, function ($id) use ($exclude) {
                return ! isset($exclude[$id]);
            }));
        }

        return $ids;
    }
}
