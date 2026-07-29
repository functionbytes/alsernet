<?php

if (! defined('_PS_VERSION_')) {
    exit;
}

class AlsernetTransportistas extends Module
{
    public function __construct()
    {
        $this->name = 'alsernettransportistas';
        $this->className = 'AlsernetTransportistas';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Alsernet - Transportistas');
        $this->description = $this->l('Se creó este administrador para administrar los trasportistas de los pedidos que se envían a gestión.');

    }

    /**
     * Instalación del módulo.
     */
    public function install()
    {
        return parent::install() &&
            $this->createConfigurationValues();
    }

    /**
     * Desinstalación del módulo.
     */
    public function uninstall()
    {
        return parent::uninstall() &&
            $this->deleteConfigurationValues();
    }

    /**
     * Crear valores iniciales en la tabla `configuration`.
     */
    private function createConfigurationValues()
    {
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR', '21', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_SEUR_EUROPA', '4', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA', '100000164', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA', '100000264', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL', '100000045', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA', '21', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID', '4', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR', '2,3,74,20,37,191,86,7,8,9,142,10,124,130,12,138,13,14,18', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID', '100000164', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR', '40,231,233,16,106,191,97,26,129,147,149,23,17,36,188,19', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO', '100000165', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_ES_INPOST', '100000283', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_ES_ARMA', '100000001', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_ES_CARTUCHO', '100000045', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO', '100000304', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO', '21', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_TIENDA_CORUNA', '10', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA', '100000245', false, null, null);
        Configuration::updateValue('ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON', '100000244', false, null, null);

        return true;
    }

    /**
     * Eliminar valores de la tabla `configuration`.
     */
    private function deleteConfigurationValues()
    {
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_SEUR_EUROPA');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_ES_INPOST');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_ES_ARMA');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_ES_CARTUCHO');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_TIENDA_CORUNA');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA');
        Configuration::deleteByName('ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON');

        return true;
    }

    /**
     * Contenido de la página de configuración del módulo.
     */
    public function getContent()
    {
        $output = '';

        // Detectar si se está editando o enviando información de forma intencionada
        $formId = Tools::getValue('form_id');
        if (! empty($formId) && $formId === 'alsernet_transportistas') {
            // Guardar la configuración
            if ($this->saveConfiguration()) {
                $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
            } else {
                $output .= $this->displayError($this->l('Failed to update backups.'));
            }
        }

        return $output.$this->renderForm();
    }

    /**
     * Guardar los valores del formulario.
     */
    private function saveConfiguration()
    {
        $ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR = Tools::getValue('ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR');
        $ALSERNET_TRANSPORTISTA_SEUR_EUROPA = Tools::getValue('ALSERNET_TRANSPORTISTA_SEUR_EUROPA');
        $ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA = Tools::getValue('ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA');
        $ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA = Tools::getValue('ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA');
        $ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL = Tools::getValue('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL');
        $ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA = Tools::getValue('ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA');
        $ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID = Tools::getValue('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID');
        $ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR = Tools::getValue('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR');
        $ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID = Tools::getValue('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID');
        $ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR = Tools::getValue('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR');
        $ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO = Tools::getValue('ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO');
        $ALSERNET_TRANSPORTISTA_ES_INPOST = Tools::getValue('ALSERNET_TRANSPORTISTA_ES_INPOST');
        $ALSERNET_TRANSPORTISTA_ES_ARMA = Tools::getValue('ALSERNET_TRANSPORTISTA_ES_ARMA');
        $ALSERNET_TRANSPORTISTA_ES_CARTUCHO = Tools::getValue('ALSERNET_TRANSPORTISTA_ES_CARTUCHO');
        $ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO = Tools::getValue('ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO');
        $ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO = Tools::getValue('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO');
        $ALSERNET_TRANSPORTISTA_TIENDA_CORUNA = Tools::getValue('ALSERNET_TRANSPORTISTA_TIENDA_CORUNA');
        $ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA = Tools::getValue('ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA');
        $ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON = Tools::getValue('ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON');

        if (is_array($ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR)) {
            $ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR = implode(',', $ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR);
        }

        if (is_array($ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR)) {
            $ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR = implode(',', $ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR);
        }

        $ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR', $ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR, false, null, null);
        $ALSERNET_TRANSPORTISTA_SEUR_EUROPA_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_SEUR_EUROPA', $ALSERNET_TRANSPORTISTA_SEUR_EUROPA, false, null, null);
        $ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA', $ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA, false, null, null);
        $ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA', $ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA, false, null, null);
        $ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL', $ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL, false, null, null);
        $ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA', $ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA, false, null, null);
        $ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_SAVE_ID = Configuration::updateValue('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID', $ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID, false, null, null);
        $ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR', $ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR, false, null, null);
        $ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_SAVE_ID = Configuration::updateValue('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID', $ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID, false, null, null);
        $ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR', $ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR, false, null, null);
        $ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO', $ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO, false, null, null);
        $ALSERNET_TRANSPORTISTA_ES_INPOST_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_ES_INPOST', $ALSERNET_TRANSPORTISTA_ES_INPOST, false, null, null);
        $ALSERNET_TRANSPORTISTA_ES_ARMA_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_ES_ARMA', $ALSERNET_TRANSPORTISTA_ES_ARMA, false, null, null);
        $ALSERNET_TRANSPORTISTA_ES_CARTUCHO_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_ES_CARTUCHO', $ALSERNET_TRANSPORTISTA_ES_CARTUCHO, false, null, null);
        $ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO', $ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO, false, null, null);
        $ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO', $ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO, false, null, null);
        $ALSERNET_TRANSPORTISTA_TIENDA_CORUNA_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_TIENDA_CORUNA', $ALSERNET_TRANSPORTISTA_TIENDA_CORUNA, false, null, null);
        $ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA', $ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA, false, null, null);
        $ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON_SAVE = Configuration::updateValue('ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON', $ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON, false, null, null);

        return $ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR_SAVE &
                $ALSERNET_TRANSPORTISTA_SEUR_EUROPA_SAVE &
                $ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA_SAVE &
                $ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA_SAVE &
                $ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_SAVE &
                $ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA_SAVE &
                $ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_SAVE_ID &
                $ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_SAVE &
                $ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_SAVE_ID &
                $ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_SAVE &
                $ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO_SAVE &
                $ALSERNET_TRANSPORTISTA_ES_INPOST_SAVE &
                $ALSERNET_TRANSPORTISTA_ES_ARMA_SAVE &
                $ALSERNET_TRANSPORTISTA_ES_CARTUCHO_SAVE &
                $ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO_SAVE &
                $ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO_SAVE &
                $ALSERNET_TRANSPORTISTA_TIENDA_CORUNA_SAVE &
                $ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA_SAVE &
                $ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON_SAVE;
    }

    /**
     * Renderizar el formulario.
     */
    protected function renderForm()
    {

        // Obtener la lista de todos los países
        $countries = Country::getCountries($this->context->language->id);

        // Obtener los países seleccionados desde la configuración (como cadena separada por comas)
        $selectedCountries_SI_SEUR = Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR');
        $selectedCountriesArray_SI_SEUR = $selectedCountries_SI_SEUR ? explode(',', $selectedCountries_SI_SEUR) : [];

        // Dividir los países en seleccionados y no seleccionados
        $unselectedCountries_SI_SEUR = array_filter($countries, function ($country) use ($selectedCountriesArray_SI_SEUR) {
            return ! in_array((string) $country['id_country'], $selectedCountriesArray_SI_SEUR, true);
        });
        $selectedCountriesList_SI_SEUR = array_filter($countries, function ($country) use ($selectedCountriesArray_SI_SEUR) {
            return in_array((string) $country['id_country'], $selectedCountriesArray_SI_SEUR, true);
        });

        $selectedCountries_NO_SEUR = Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR');
        $selectedCountriesArray_NO_SEUR = $selectedCountries_NO_SEUR ? explode(',', $selectedCountries_NO_SEUR) : [];

        // Dividir los países en seleccionados y no seleccionados
        $unselectedCountries_NO_SEUR = array_filter($countries, function ($country) use ($selectedCountriesArray_NO_SEUR) {
            return ! in_array((string) $country['id_country'], $selectedCountriesArray_NO_SEUR, true);
        });
        $selectedCountriesList_NO_SEUR = array_filter($countries, function ($country) use ($selectedCountriesArray_NO_SEUR) {
            return in_array((string) $country['id_country'], $selectedCountriesArray_NO_SEUR, true);
        });

        // Generar campos para el formulario
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Alsernet Transportistas Settings'),
                    'icon' => 'fa-duotone icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'button',
                        'label' => '',
                        'name' => 'help_button',
                        'desc' => '
                            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#helpModal">
                                '.$this->trans('¿Cómo funciona?', [], 'Admin.Catalog.Help').'
                            </button>
                        ',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('España No Peninsular'),
                        'name' => 'ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR',
                        'desc' => $this->l('El ID de gestión del transportistas. No importa el método de pago.'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('SEUR Europa'),
                        'name' => 'ALSERNET_TRANSPORTISTA_SEUR_EUROPA',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Europa que no envía SEUR'),
                        'name' => 'ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('España Galicia'),
                        'name' => 'ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('PENÍNSULA Y PORTUGAL'),
                        'name' => 'ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Resto de España'),
                        'name' => 'ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Transportistas de Europa si SEUR'),
                        'name' => 'ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->l('Países de Europa si SEUR'),
                        'name' => 'ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR',
                        'html_content' => $this->renderCountrySelection($unselectedCountries_SI_SEUR, $selectedCountriesList_SI_SEUR, 'ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Transportistas de Europa no SEUR'),
                        'name' => 'ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->l('Países de Europa no SEUR'),
                        'name' => 'ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR',
                        'html_content' => $this->renderCountrySelection($unselectedCountries_NO_SEUR, $selectedCountriesList_NO_SEUR, 'ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('RESTO DEL MUNDO'),
                        'name' => 'ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('INPOST'),
                        'name' => 'ALSERNET_TRANSPORTISTA_ES_INPOST',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('ARMA'),
                        'name' => 'ALSERNET_TRANSPORTISTA_ES_ARMA',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('CARTUCHO'),
                        'name' => 'ALSERNET_TRANSPORTISTA_ES_CARTUCHO',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('TARJETAS REGALO'),
                        'name' => 'ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Sin Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('España y PORTUGAL con Reembolso'),
                        'name' => 'ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO',
                        'desc' => $this->l('El ID de gestión del transportistas. Metodo de pago Con Reembolso'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Recogidas en tienda Coruña'),
                        'name' => 'ALSERNET_TRANSPORTISTA_TIENDA_CORUNA',
                        'desc' => $this->l('El ID de gestión del transportistas.'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Recogidas en tienda Capitan Haya'),
                        'name' => 'ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA',
                        'desc' => $this->l('El ID de gestión del transportistas.'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Recogidas en tienda Diego de Leon'),
                        'name' => 'ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON',
                        'desc' => $this->l('El ID de gestión del transportistas.'),
                        'size' => 255,
                        'required' => false,
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'form_id',
                        'value' => 'alsernet_transportistas',
                    ],

                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm;
        $helper->submit_action = 'submitAlsernetTransportistas';
        $helper->fields_value = [
            'ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR' => Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR'),
            'ALSERNET_TRANSPORTISTA_SEUR_EUROPA' => Configuration::get('ALSERNET_TRANSPORTISTA_SEUR_EUROPA'),
            'ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA' => Configuration::get('ALSERNET_TRANSPORTISTA_SEUR_NO_EUROPA'),
            'ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA' => Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA'),
            'ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL' => Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL'),
            'ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA' => Configuration::get('ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA'),
            'ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID' => Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID'),
            'ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR[]' => json_decode(Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR'), true),
            'ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID' => Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID'),
            'ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR[]' => json_decode(Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR'), true),
            'ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO' => Configuration::get('ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO'),
            'ALSERNET_TRANSPORTISTA_ES_INPOST' => Configuration::get('ALSERNET_TRANSPORTISTA_ES_INPOST'),
            'ALSERNET_TRANSPORTISTA_ES_ARMA' => Configuration::get('ALSERNET_TRANSPORTISTA_ES_ARMA'),
            'ALSERNET_TRANSPORTISTA_ES_CARTUCHO' => Configuration::get('ALSERNET_TRANSPORTISTA_ES_CARTUCHO'),
            'ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO' => Configuration::get('ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO'),
            'ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO' => Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO'),
            'ALSERNET_TRANSPORTISTA_TIENDA_CORUNA' => Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_CORUNA'),
            'ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA' => Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA'),
            'ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON' => Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON'),
            'form_id' => 'alsernet_transportistas', // Prellenar el campo oculto
        ];

        $modal_content = $this->context->smarty->fetch(_PS_MODULE_DIR_.'alsernettransportistas/views/templates/admin/modal.tpl');

        return $modal_content.$helper->generateForm([$fields_form]);
    }

    private function renderCountrySelection($unselectedCountries, $selectedCountriesList, $id)
    {
        $html = '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<label>'.$this->l('Países disponibles').'</label>';
        $html .= '<select id="un'.$id.'" class="form-control" size="10" multiple>';
        foreach ($unselectedCountries as $country) {
            $html .= '<option value="'.(int) $country['id_country'].'">'.htmlspecialchars($country['name']).'</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="col-md-6">';
        $html .= '<label>'.$this->l('Países seleccionados').'</label>';
        $html .= '<select id="'.$id.'" name="'.$id.'[]" class="form-control" size="10" multiple>';
        foreach ($selectedCountriesList as $country) {
            $html .= '<option value="'.(int) $country['id_country'].'" selected>'.htmlspecialchars($country['name']).'</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        // Agregar botones para mover países entre las listas
        $html .= '<div class="col-md-12 text-center mt-2">';
        $html .= '<button type="button" id="add_country_'.$id.'" class="btn btn-default">'.$this->l('Add').'</button>';
        $html .= '<button type="button" id="remove_country_'.$id.'" class="btn btn-default">'.$this->l('Remove').'</button>';
        $html .= '</div>';
        $html .= '</div>';

        // Agregar script para mover países entre las listas
        $html .= '<script>
            document.getElementById("add_country_'.$id.'").addEventListener("click", function() {
                const from = document.getElementById("un'.$id.'");
                const to = document.getElementById("'.$id.'");
                moveSelectedOptions(from, to);
            });

            document.getElementById("remove_country_'.$id.'").addEventListener("click", function() {
                const from = document.getElementById("'.$id.'");
                const to = document.getElementById("un'.$id.'");
                moveSelectedOptions(from, to);
            });

            function moveSelectedOptions(from, to) {
                Array.from(from.selectedOptions).forEach(option => {
                    to.appendChild(option);
                });
            }
        </script>';

        return $html;
    }
}
