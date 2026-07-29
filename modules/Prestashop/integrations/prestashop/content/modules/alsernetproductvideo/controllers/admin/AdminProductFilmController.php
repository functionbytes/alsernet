<?php

class AdminProductFilmController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'product_film'; // Nombre de la tabla
        $this->identifier = 'id_productvideo'; // Columna primaria correcta
        $this->lang = false; // Indica que no es una tabla traducible

        $this->bootstrap = true;

        // Configuración de la lista de campos
        $this->fields_list = [
            'id_productvideo' => ['title' => 'ID Video', 'width' => 50, 'type' => 'int'],
            'id_product' => ['title' => 'Product ID', 'width' => 50, 'type' => 'int'],
            'title' => ['title' => 'Title', 'type' => 'text'],
            'provider' => ['title' => 'Provider', 'type' => 'text'],
            'url' => ['title' => 'URL', 'type' => 'text'],
            'available' => ['title' => 'Available', 'type' => 'bool', 'align' => 'center', 'active' => 'status'],
        ];

        // Agregar acciones de fila
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        parent::__construct();
    }

    /**
     * Modificar la consulta SQL de la lista
     */
    public function renderList()
    {
        // Agregar cláusulas SQL personalizadas
        $this->_select = 'MAX(id_productvideo) as id_productvideo, id_product, title, provider, url, available';
        $this->_where = 'AND a.id_lang = 1';
        $this->_group = 'GROUP BY id_product';
        $this->_orderBy = 'id_productvideo';
        $this->_orderWay = 'DESC';

        return parent::renderList();
    }

    /**
     * Mostrar el formulario de edición y creación
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('Product Video', [], 'modules.AlsernetProductVideo.Admin'),
                'icon' => 'fa-duotone icon-film', // Cambiar icono si es necesario
            ],
            'input' => [
                // Campos existentes
                [
                    'type' => 'text',
                    'label' => 'Product ID',
                    'desc' => 'Debe ser el ID del producto de PrestaShop',
                    'placeholder' => 'Ejemplo => 65987',
                    'name' => 'id_product',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => 'URL',
                    'desc' => 'Debe ingresar la URL completa',
                    'placeholder' => 'Ejemplo => https://youtu.be/9MZVO5r2msM',
                    'name' => 'url',
                    'lang' => true, // Habilitar traducción
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => 'Video ID',
                    'desc' => 'Debe ser el código del video',
                    'placeholder' => 'Ejemplo => 9MZVO5r2msM',
                    'name' => 'id_video',
                    'lang' => true, // Habilitar traducción
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => 'Título',
                    'desc' => 'Se puede agregar un título a los videos',
                    'name' => 'title',
                    'lang' => true, // Habilitar traducción
                    'required' => false,
                ],
                [
                    'type' => 'select',
                    'label' => 'Proveedor',
                    'desc' => 'El proveedor de los videos.',
                    'name' => 'provider',
                    'required' => true,
                    'options' => [
                        'query' => [
                            [
                                'id' => 'youtube',
                                'name' => 'YouTube',
                            ],
                        ],
                        'id' => 'id',  // Usamos 'id' como valor del option
                        'name' => 'name',  // Usamos 'name' como el texto visible del option
                    ],
                    'default_value' => 'youtube', // Establece 'youtube' como el valor Por defecto
                ],
                // [
                //     'type' => 'select',
                //     'label' => 'Position',
                //     'desc' => 'La posición de los videos en el producto, esto es de 1 al 10',
                //     'name' => 'position',
                //     'required' => true,
                //     'options' => [
                //         'query' =>
                //         array_map(function ($i) {
                //             return [
                //                 'id' => $i,
                //                 'name' => (string) $i,
                //             ];
                //         }, range(1, 10)), // Genera opciones del 1 al 10
                //         'id' => 'id',  // Usamos 'id' como valor del option
                //         'name' => 'name',  // Usamos 'name' como el texto visible del option
                //     ],
                //     'default_value' => 1, // Establece 1 como el valor Por defecto
                // ],
                [
                    'type' => 'switch',
                    'label' => 'Disponible',
                    'desc' => 'La visibilidad del video',
                    'name' => 'available',
                    'is_bool' => true,
                    'values' => [
                        ['value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                        ['value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                    ],
                    'required' => true,
                    'default_value' => 1, // Establece 1 (activo) como el valor Por defecto
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions'),
                'name' => 'submit_add_product_video',
            ],
        ];

        // Agregar botón para duplicar la información en otros idiomas
        $this->fields_form['buttons'] = [
            'duplicate' => [
                'type' => 'button',
                'title' => 'Duplicar la información',
                'name' => 'duplicate_languages',
                'icon' => 'fa-duotone process-icon-copy', // Icono de duplicar
                'class' => 'btn btn-default', // Clase para estilo
                'id' => 'duplicate_languages_btn', // Añadir un ID para el botón
            ],
        ];

        if (Tools::getValue('id_productvideo')) {
            // Si estamos editando un registro, cargamos los datos correspondientes
            $id_productvideo = (int) Tools::getValue('id_productvideo');

            // Cargar los datos del registro
            $productFilm = Db::getInstance()->executeS(
                'SELECT * FROM '._DB_PREFIX_.'product_film WHERE id_productvideo = '.$id_productvideo
            );

            if ($productFilm) {
                // Cargar datos en varios idiomas
                $productFilm = $productFilm[0]; // Asegurarnos de que sea un solo registro
                $this->fields_value['id_product'] = $productFilm['id_product'];
                $this->fields_value['available'] = $productFilm['available'];
                $this->fields_value['provider'] = $productFilm['provider'];
                // $this->fields_value['position'] = $productFilm['position'];

                // Cargar traducciones para cada idioma
                $languages = Language::getLanguages(false);
                foreach ($languages as $lang) {
                    $lang_id = (int) $lang['id_lang'];

                    // Para campos multilingües (url, id_video, title)
                    $this->fields_value['url'][$lang_id] = Db::getInstance()->getValue(
                        'SELECT url FROM '._DB_PREFIX_.'product_film WHERE id_product = '.$productFilm['id_product'].' AND id_lang = '.$lang_id
                    );
                    $this->fields_value['id_video'][$lang_id] = Db::getInstance()->getValue(
                        'SELECT id_video FROM '._DB_PREFIX_.'product_film WHERE id_product = '.$productFilm['id_product'].' AND id_lang = '.$lang_id
                    );
                    $this->fields_value['title'][$lang_id] = Db::getInstance()->getValue(
                        'SELECT title FROM '._DB_PREFIX_.'product_film WHERE id_product = '.$productFilm['id_product'].' AND id_lang = '.$lang_id
                    );
                }
            }

        }

        // Este es el bloque donde agregaremos el JavaScript
        $this->context->controller->addJS(_PS_MODULE_DIR_.'alsernetproductvideo/views/js/duplicate_languages.js');

        return parent::renderForm();
    }

    /**
     * Guardar el formulario y los valores traducidos
     */
    public function processSave()
    {
        // Datos que son unicos
        $id_product = (int) Tools::getValue('id_product');
        $provider = pSQL(Tools::getValue('provider'));
        $available = (int) Tools::getValue('available');
        $position = 1;

        // Obtener los idiomas disponibles
        $languages = Language::getLanguages(false);

        foreach ($languages as $language) {

            $id_lang = (int) $language['id_lang'];
            $id_video = Tools::getValue('id_video_'.$id_lang);
            $title = Tools::getValue('title_'.$id_lang);
            $url = Tools::getValue('url_'.$id_lang);

            // Validar si los datos están presentes
            if ($id_video || $title || $url) {
                // Primero, verificar si ya existe el registro para este producto y idioma
                $sql = 'SELECT id_productvideo FROM '._DB_PREFIX_.'product_film
                            WHERE id_product = '.$id_product.'
                            AND id_lang = '.$id_lang;

                $existing = Db::getInstance()->getValue($sql);

                $data = [
                    'id_product' => $id_product,
                    'id_video' => pSQL($id_video),
                    'title' => pSQL($title),
                    'provider' => $provider,
                    'url' => pSQL($url),
                    'position' => (int) $position,
                    'id_lang' => $id_lang,
                    'id_shop' => 1, // Usar 1 o el ID de la tienda actual
                    'available' => pSQL($available), // Sanear el estado de disponibilidad
                ];

                if ($existing) {
                    // Si existe, realizar un UPDATE
                    $where = 'id_productvideo = '.(int) $existing;
                    if (! Db::getInstance()->update('product_film', $data, $where)) {
                        dump('Error al actualizar');
                        exit();
                    }
                } else {
                    // Si no existe, realizar una inserción
                    if (! Db::getInstance()->insert('product_film', $data)) {
                        dump('Error al insertar');
                        exit();
                    }
                }
            }
        }

        // Realizar la solicitud GET para limpiar la caché
        $url = 'https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product='.$id_product;
        $response = file_get_contents($url);

        // Verificar si hubo una respuesta
        if ($response === false) {
            exit('Error al realizar la solicitud GET');
        }

        // Después de guardar los datos, redirigir al listado
        Tools::redirectAdmin(self::$currentIndex.'&conf=4&token='.$this->token);
    }

    public function processDelete()
    {
        // Obtener el ID del registro a eliminar
        $id_productvideo = (int) Tools::getValue('id_productvideo');

        if ($id_productvideo > 0) {
            $sql = 'SELECT id_product FROM '._DB_PREFIX_.'product_film
                            WHERE id_productvideo = '.$id_productvideo;

            $existing = Db::getInstance()->getValue($sql);

            // Eliminar el registro directamente desde la base de datos
            if (Db::getInstance()->delete('product_film', 'id_product = '.$existing)) {
                // Agregar mensaje de confirmación
                $this->confirmations[] = 'El registro se ha eliminado correctamente.';

                // Realizar la solicitud GET para limpiar la caché
                $url = 'https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product='.$existing;
                $response = file_get_contents($url);

                // Verificar si hubo una respuesta
                if ($response === false) {
                    exit('Error al realizar la solicitud GET');
                }
            } else {
                // Agregar mensaje de error si falla la eliminación
                $this->errors[] = 'Se ha producido un error al intentar eliminar el registro.';
            }
        } else {
            // Agregar mensaje de error si no se encuentra el ID
            $this->errors[] = 'No se ha encontrado ningún registro que borrar.';
        }
    }

    public function postProcess()
    {
        if (isset($_GET['statusproduct_film'])) {
            // Tenemos que ocultar Todo
            $dato = Db::getInstance()->getValue('SELECT available FROM '._DB_PREFIX_.'product_film WHERE id_productvideo = '.$_GET['id_productvideo']);
            if ($dato == 1) {
                // Desactivamos todos los idiomas
                Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'product_film SET available = 0 WHERE id_product = (SELECT id_product FROM '._DB_PREFIX_.'product_film WHERE id_productvideo = '.$_GET['id_productvideo'].')');
            } elseif ($dato == 0) {
                // Activamos todo los idiomas
                Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'product_film SET available = 1 WHERE id_product = (SELECT id_product FROM '._DB_PREFIX_.'product_film WHERE id_productvideo = '.$_GET['id_productvideo'].')');
            }
            Tools::redirectAdmin(self::$currentIndex.'&conf=4&token='.$this->token);
        }

        // Verificar si se está enviando el formulario
        if (Tools::isSubmit('submitAdd'.$this->table)) {
            // Llamar a processSave para manejar la lógica de guardar
            $this->processSave();
        }

        // Verificar si se envía una acción de eliminación
        if (Tools::isSubmit('delete'.$this->table)) {
            $this->processDelete();
        }

    }
}
