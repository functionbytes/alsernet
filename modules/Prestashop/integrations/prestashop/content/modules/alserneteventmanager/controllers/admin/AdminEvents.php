<?php

require_once _PS_MODULE_DIR_.'alserneteventmanager/classes/EventModel.php';

class AdminEventsController extends ModuleAdminController
{
    public function __construct()
    {

        parent::__construct();

        $this->bootstrap = true;
        $this->table = 'alsernet_event_manager';
        $this->identifier = 'id_event';
        $this->className = 'EventModel';
        $this->lang = false;
        $this->_defaultOrderBy = 'start_at';
        $this->_defaultOrderWay = 'ASC';

        $this->fields_list = [
            'title' => ['title' => 'Titulo'],
            'start_at' => ['title' => 'Fecha inicio'],
            'end_at' => ['title' => 'Fecha finalización'],
            'available' => ['title' => 'Estado', 'type' => 'bool'],
        ];

        $this->actions = ['edit', 'delete'];

        $this->bulk_actions = [
            'delete' => [
                'text' => 'Borrar seleccionados',
                'icon' => 'icon-trash',
                'confirm' => '¿Está seguro?',
            ],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => 'Administrador de eventos',
                'icon' => 'fa-duotone icon-list-ul',
            ],
            'input' => [
                ['name' => 'title', 'type' => 'text', 'label' => 'Titulo', 'required' => true, 'lang' => true],
                ['name' => 'filter', 'type' => 'text', 'label' => 'Filtro url', 'required' => true, 'lang' => true],
                ['name' => 'management_tag', 'type' => 'text', 'label' => 'Etiqueta gestion', 'required' => true, 'lang' => true],

                ['name' => 'cms', 'type' => 'int', 'label' => 'Cms', 'required' => true],
                ['name' => 'color_buttom', 'type' => 'text', 'label' => 'Color boton', 'required' => true],
                ['name' => 'hover_buttom', 'type' => 'text', 'label' => 'Hover boton', 'required' => true],

                ['name' => 'priority_flag', 'type' => 'text', 'label' => 'Prioridad etiqueta', 'required' => true],
                ['name' => 'color_flag', 'type' => 'text', 'label' => 'Color etiqueta', 'required' => true],

                ['name' => 'start_at', 'type' => 'text', 'label' => 'Fecha inicio'],
                ['name' => 'end_at', 'type' => 'text', 'label' => 'Fecha finalización'],
                ['name' => 'available', 'type' => 'switch', 'label' => 'Activo', 'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => 'Activo',
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => 'Inactivo',
                        ],
                    ], ],

            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions'),
            ],
        ];
    }
}
