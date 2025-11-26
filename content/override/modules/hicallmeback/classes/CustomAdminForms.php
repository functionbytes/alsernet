<?php
/**
* 2012 - 2020 HiPresta
*
* MODULE Call Me Back
*
* @author    HiPresta <support@hipresta.com>
* @copyright HiPresta 2020
* @license   AddonsPrestaShop license limitation
* @link      https://hipresta.com
*
* NOTICE OF LICENSE
*
* Don't use this module on several shops. The license provided by PrestaShop Addons
* for all its modules is valid only once for a single shop.
*/

require_once dirname(__FILE__).'/Hicallmebackagente.php';

class CustomAdminForms
{
    public $module;
    public $name;
    public $context;

    public $agent_table;
    public $agent_table_identifier;
    public $agent_action;

    public function __construct($module)
    {
        $this->module = $module;
        $this->name = $module->name;
        $this->context = Context::getContext();

        $this->agent_table = 'hicallmebackagente';
        $this->agent_table_identifier = 'id_hicallmebackagente';
        $this->agent_action = 'agents';
    }

    public function renderAgentsList()
    {
        $fields_list = array(
            'id_hicallmebackagente' => array(
                'title' => $this->l('ID'),
                'width' => 30,
                'filter_key' => 'id_hicallmebackagente'
            ),
            'name' => array(
                'title' => $this->l('Nombre'),
                'filter_key' => 'name'
            ),
            'email' => array(
                'title' => $this->l('Email'),
                'filter_key' => 'email'
            ),
            'active' => array(
                'title' => $this->l('Estado'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_type' => 'bool',
                'class' => 'fixed-width-sm',
                'filter_key' => 'active'
            ),
        );
        if (!Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            unset($fields_list['shop_name']);
        }
        $helper_list = new HelperList();
        $helper_list->module = $this->module;
        $helper_list->title = $this->l('Agentes');
        $helper_list->shopLinkType = '';
        $helper_list->no_link = true;
        $helper_list->show_toolbar = true;
        $helper_list->simple_header = false;
        $helper_list->identifier = $this->agent_table_identifier;
        $helper_list->table = $this->agent_table;
        $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&'.$this->name.'='.$this->agent_action;
        $helper_list->token = Tools::getAdminTokenLite('AdminModules');
        $helper_list->actions = array('edit', 'delete');
        $helper_list->bulk_actions = array(
            'delete' => array(
                'text'=>$this->l('Delete selected'),
                'confirm'=>$this->l('Delete selected items?')
            )
        );
        $helper_list->toolbar_btn['new'] = array(
                    'href' => $this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&tab_module='.$this->module->tab.'&module_name='.$this->module->name.$prefix.'&'.$this->name.'='.$this->agent_action.'&add'.$this->agent_table,
                    'desc' => $this->l('Add new')
        );

        if (isset($this->context->cookie->{$this->name.'FilterAgent_active'}) && $this->context->cookie->{$this->name.'FilterAgent_active'} != '') {
            $filter_active = $this->context->cookie->{$this->name.'FilterAgent_active'};
        } else {
            $filter_active = '';
        }
        $this->context->smarty->assign(
            array(
                'psv' => $this->module->psv,
                'call_status' => $filter_active,
            )
        );

        $this->_helperlist = $helper_list;
        $agents = $this->module->getAgentsList($fields_list);
        $helper_list->listTotal = count($agents);
        $page = ($page = Tools::getValue('submitAgentsFilter'.$helper_list->table)) ? $page : 1;
        $pagination = ($pagination = Tools::getValue($helper_list->table.'_pagination')) ? $pagination : 50;
        $agents = $this->module->hiPrestaClass->pagination($agents, $page, $pagination);
        return $helper_list->generateList($agents, $fields_list);
    }

    public function renderAgentForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' =>$this->l('Agente'),
                    'icon' => 'icon-user'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Nombre'),
                        'name' => 'name',
                        'id' => 'name',
                        'required' => true,
                        'class' => 't'
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Email'),
                        'name' => 'email',
                        'id' => 'email',
                        'required' => true,
                        'class' => 't'
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activo'),
                        'name' => 'active',
                        'class' => 't',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Activado')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Desactivado')
                            )
                        ),
                    )
                ),
                'submit' => array(
                    'title' =>  $this->l('Save'),
                    'name' => 'submit_agentForm',
                    'class' => 'btn btn-default pull-right',
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = true;
        $languages = Language::getLanguages(false);
        foreach ($languages as $key => $language) {
            $languages[$key]['is_default'] = (int)($language['id_lang'] == Configuration::get('PS_LANG_DEFAULT'));
        }
        $helper->languages = $languages;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $this->fields_form = array();
        $helper->module = $this;
        $helper->submit_action = 'submitAgentForm';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        if (Tools::getIsset('update'.$this->agent_table) && Tools::getvalue($this->agent_table_identifier)) {
            $helper->currentIndex = $this->context->link->getAdminLink('AdminModules',false).'&configure='.$this->name.'&tab_module='.$this->module->tab.'&module_name='.$this->name.'&'.$this->name.'='.$this->agent_action.'&update'.$this->agent_table.'&'.$this->agent_table_identifier.'='.(int) Tools::getvalue($this->agent_table_identifier);
        } else {
            $helper->currentIndex = $this->context->link->getAdminLink('AdminModules',false).'&configure='.$this->name.'&tab_module='.$this->module->tab.'&module_name='.$this->name.'&'.$this->name.'='.$this->agent_action.'&add'.$this->agent_table;
        }

        $helper->tpl_vars = array(
            'fields_value' => array(
                'name' => Tools::getValue('name'),
                'email' => Tools::getValue('email'),
                'active' => 1,
            ),
            'psv' => $this->module->psv,
        );

        if (Tools::getIsset('update'.$this->agent_table) && Tools::getvalue($this->agent_table_identifier)) {
            $agente = New Hicallmebackagente((int) Tools::getValue($this->agent_table_identifier));
            if (Validate::isLoadedObject($agente)) {
                $helper->tpl_vars['fields_value']['name'] = $agente->name;
                $helper->tpl_vars['fields_value']['email'] = $agente->email;
                $helper->tpl_vars['fields_value']['active'] = $agente->active;
            }
        }

        $helper->override_folder = '/';
        return $helper->generateForm(array($fields_form));
    }

    public function l($string)
    {
        return $this->module->l($string);
    }
}
