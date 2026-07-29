<?php

class AdminAlsernetEventManagerController extends ModuleAdminController
{
    public function __construct()
    {
        // Definir las propiedades del controlador
        $this->table = 'alsernet_event_manager';
        $this->className = 'AlsernetEventManager';
        $this->identifier = 'id_event';
        $this->lang = true;
        $this->name = 'alsernet_event_manager';  // Asegúrate de que este sea el nombre de tu módulo

        parent::__construct();
    }

    public function renderList()
    {
        // Definir los campos de la lista
        $this->fields_list = [
            'id_event' => [
                'title' => $this->l('Event ID'),
                'align' => 'center',
                'type' => 'text',
            ],
            'title' => [
                'title' => $this->l('Event Title'),
                'align' => 'left',
                'type' => 'text',
            ],
            'start_date' => [
                'title' => $this->l('Start Date'),
                'align' => 'center',
                'type' => 'datetime',
            ],
            'end_date' => [
                'title' => $this->l('End Date'),
                'align' => 'center',
                'type' => 'datetime',
            ],
            'available' => [
                'title' => $this->l('Available'),
                'align' => 'center',
                'type' => 'bool',
            ],
        ];

        // Agregar las acciones de fila
        $this->addRowAction('edit');  // Acción de editar
        $this->addRowAction('delete'); // Acción de eliminar

        // URL para agregar un nuevo evento
        $this->context->smarty->assign('add_event_url', $this->context->link->getAdminLink('AdminModules').'&configure='.$this->module->name.'&controller=addEvent');

        // Generar la vista de la lista
        return parent::renderList();
    }

    public function renderForm($event = null)
    {
        // Si no se pasa un evento, inicializamos los valores como vacíos

        if ($event) {
            $default_values = [
                'EVENT_TITLE' => $event['title'],
                'EVENT_START_DATE' => $event['start_date'],
                'EVENT_END_DATE' => $event['end_date'],
                'EVENT_FILTER_TAG' => $event['filter_tag'],
                'EVENT_MANAGEMENT_TAG' => $event['management_tag'],
                'EVENT_COLOR_BUTTON' => $event['color_buttom'],
                'EVENT_COLOR' => $event['color'],
                'EVENT_AVAILABLE' => $event['available'],
                'EVENT_CATEGORIES' => $this->getCategories($event['id_event']),
                'EVENT_LANGS' => $this->getLanguages($event['id_event']),  // Cargar los idiomas asociados
            ];
        }

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Create/Edit Event'),
                    'icon' => 'fa-duotone icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Título del Evento'),
                        'name' => 'EVENT_TITLE',
                        'required' => true,
                        'value' => isset($default_values['EVENT_TITLE']) ? $default_values['EVENT_TITLE'] : '',
                    ],
                    [
                        'type' => 'datetime',
                        'label' => $this->l('Fecha de Inicio'),
                        'name' => 'EVENT_START_DATE',
                        'required' => true,
                        'value' => isset($default_values['EVENT_START_DATE']) ? $default_values['EVENT_START_DATE'] : '',
                    ],
                    [
                        'type' => 'datetime',
                        'label' => $this->l('Fecha de Fin'),
                        'name' => 'EVENT_END_DATE',
                        'required' => true,
                        'value' => isset($default_values['EVENT_END_DATE']) ? $default_values['EVENT_END_DATE'] : '',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Etiqueta de Filtro'),
                        'name' => 'EVENT_FILTER_TAG',
                        'required' => false,
                        'value' => isset($default_values['EVENT_FILTER_TAG']) ? $default_values['EVENT_FILTER_TAG'] : '',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Etiqueta de Gestión'),
                        'name' => 'EVENT_MANAGEMENT_TAG',
                        'required' => false,
                        'value' => isset($default_values['EVENT_MANAGEMENT_TAG']) ? $default_values['EVENT_MANAGEMENT_TAG'] : '',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Color del Botón'),
                        'name' => 'EVENT_COLOR_BUTTON',
                        'required' => false,
                        'value' => isset($default_values['EVENT_COLOR_BUTTON']) ? $default_values['EVENT_COLOR_BUTTON'] : '#ffffff',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Color del Evento'),
                        'name' => 'EVENT_COLOR',
                        'required' => false,
                        'value' => isset($default_values['EVENT_COLOR']) ? $default_values['EVENT_COLOR'] : '#000000',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Evento Disponible'),
                        'name' => 'EVENT_AVAILABLE',
                        'required' => false,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Sí'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                        'value' => isset($default_values['EVENT_AVAILABLE']) ? $default_values['EVENT_AVAILABLE'] : 1,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Idiomas'),
                        'name' => 'EVENT_LANGS[]',  // Make sure it's an array
                        'multiple' => true,
                        'required' => true,
                        'options' => [
                            'query' => $this->getLanguages(),  // Obtener los idiomas activos
                            'id' => 'id_lang',
                            'name' => 'name',
                            'value' => isset($default_values['EVENT_LANGS']) ? $default_values['EVENT_LANGS'] : [],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Categorías'),
                        'name' => 'EVENT_CATEGORIES[]',  // Make sure it's an array
                        'multiple' => true,
                        'required' => false,
                        'options' => [
                            'query' => $this->getCategories(),  // Obtener todas las categorías disponibles
                            'id' => 'id_category',
                            'name' => 'name',
                            'value' => isset($default_values['EVENT_CATEGORIES']) ? $default_values['EVENT_CATEGORIES'] : [],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save Event'),
                ],
            ],
        ];

        $helper = new HelperForm;
        $helper->module = $this;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->fields_value = $event; // Directamente asignamos el evento
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper->generateForm([$form]);
    }

    public function getLanguages()
    {
        $languages = Language::getLanguages(false);  // Obtener idiomas activos
        $language_options = [];

        foreach ($languages as $language) {
            $language_options[] = [
                'id_lang' => $language['id_lang'],
                'name' => $language['name'],
            ];
        }

        return $language_options;
    }

    public function getCategoriess()
    {
        $categories = Category::getCategories($this->context->language->id, true, false);
        $category_options = [];

        foreach ($categories as $category) {
            $category_options[] = [
                'id' => $category['id_category'],
                'name' => $category['name'],
            ];
        }

        return $category_options;
    }
}
