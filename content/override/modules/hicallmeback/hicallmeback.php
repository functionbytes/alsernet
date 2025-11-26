<?php

require_once dirname(__FILE__).'/classes/CustomAdminForms.php';
require_once dirname(__FILE__).'/classes/Hicallmebackagente.php';

class HICallMeBackOverride extends HICallMeBack
{
    public $customAdminForms;

    public function __construct()
    {
        parent::__construct();
        $this->customAdminForms = new CustomAdminForms($this);
    }

    private function createDB()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'hicallmebackagente` (
            `id_hicallmebackagente` int NOT NULL AUTO_INCREMENT ,
            `name` VARCHAR( 100 ) NOT NULL,
            `email` VARCHAR( 100 ) NOT NULL,
            `active` TINYINT NOT NULL,
            `date_add` DATE NOT NULL,
            `date_upd` DATE NOT NULL,
                PRIMARY KEY ( `id_hicallmebackagente` )
           ) ENGINE = '._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        if (Db::getInstance()->Execute(trim($sql))) {
            return parent::createDB();
        } else {
            return false;
        }
    }

    private function proceedDb()
    {
        $tables = array(
            'hicallmebackagente',
        );
        foreach ($tables as $table) {
            DB::getInstance()->Execute('DROP TABLE IF EXISTS '._DB_PREFIX_.pSQL($table));
        }

        parent::proceedDb();
    }

    public function renderMenuTabs()
    {
        $tabs = array(
            'requests' => array(
                'title' => $this->l('Requests'),
                'icon' => 'icon-phone'
            ),
            'design_settings' => array(
                'title' => $this->l('Design Settings'),
                'icon' => 'icon-paint-brush'
            ),
            'general_settings' => array(
                'title' => $this->l('General settings'),
                'icon' => 'icon-cog'
            ),
            'agents' => array(
                'title' => $this->l('Agentes'),
                'icon' => 'icon-user'
            ),
            'export' => array(
                'title' => $this->l('Export'),
                'icon' => 'icon-download'
            ),
            'gdpr' => array(
                'title' => $this->l('GDPR'),
                'icon' => 'icon-user'
            ),
            'version' => array(
                'title' => $this->l('Version'),
                'icon' => 'icon-info'
            )
        );
        $more_module = $this->hiPrestaClass->getModuleContent('A_CMB');
        if ($more_module) {
            $tabs['more_module'] = array(
                'title' => $this->l('More Modules'),
                'icon' => 'icon-plus-square'
            );
        }
        $this->context->smarty->assign(
            array(
                'psv' => $this->psv,
                'tabs' => $tabs,
                'module_version' => $this->version,
                'module_url' => $this->hiPrestaClass->getModuleUrl(),
                'module_tab_key' => $this->name,
                'active_tab' => Tools::getValue($this->name),
            )
        );
        return $this->display(__FILE__, 'views/templates/admin/menu_tabs.tpl');
    }

    public function renderAdminStructure($form)
    {
        $content = $form;

        switch (Tools::getValue($this->name)) {
            case 'agents':
                if ((Tools::getIsset('addhicallmebackagente')) || (Tools::getIsset('updatehicallmebackagente') && Tools::getvalue('id_hicallmebackagente'))) {
                    $content = $this->customAdminForms->renderAgentForm();
                } else {
                    $content = $this->customAdminForms->renderAgentsList();
                }
                break;
            default:
        }

        return parent::renderAdminStructure($content);
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit_agentForm')) {
            $this->postProcess();
        }

        if (Tools::getIsset('deletehicallmebackagente') && Tools::getvalue('id_hicallmebackagente')) {
            $agente = New Hicallmebackagente((int) Tools::getValue('id_hicallmebackagente'));
            if (Validate::isLoadedObject($agente)) {
                if ($agente->delete()) {
                    $this->success[] = $this->l('Agente eliminado');
                } else {
                    $this->errors[] = $this->l('No se ha podido eliminar agente');
                }
            }
        }

        if (Tools::getIsset('statushicallmebackagente') && Tools::getvalue('id_hicallmebackagente')) {
            $agente = New Hicallmebackagente((int) Tools::getValue('id_hicallmebackagente'));
            if (Validate::isLoadedObject($agente)) {
                $agente->active = $agente->active ? 0 : 1;
                $agente->save();
            }
        }

        return parent::getContent();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submit_agentForm')) {
            if (Tools::getValue('name') == '') {
                $this->errors[] = $this->l('Nombre obligatorio');
            }

            if (Tools::getValue('email') == '') {
                $this->errors[] = $this->l('Email obligatorio');
            } else {
                if (!Validate::isEmail(Tools::getValue('email'))) {
                    $this->errors[] = $this->l('Email no válido');
                }
            }

            if (empty($this->errors)) {
                if (Tools::getIsset('updatehicallmebackagente') && Tools::getvalue('id_hicallmebackagente')) {
                    $agente = New Hicallmebackagente((int) Tools::getValue('id_hicallmebackagente'));
                    if (!Validate::isLoadedObject($agente)) {
                        $this->errors[] = $this->l('Error al guardar los datos del agente');
                        parent::postProcess();
                        return;
                    }
                } else {
                    $agente = New Hicallmebackagente();
                }

                $agente->name = Tools::getValue('name');
                $agente->email = Tools::getValue('email');
                $agente->active = (bool) Tools::getValue('active');

                if ($agente->save()) {
                    $this->success[] = $this->l('Agente guardado correctamente');
                } else {
                    $this->errors[] = $this->l('Error al guardar los datos del agente');
                }
            }
        }

        parent::postProcess();
    }

    public function hasEmailInNotificationList($email) {
        $email_address = trim(Configuration::get('HI_CMB_EMAIL_ADDRESS'));
        $emails = explode(',', $email_address);
        foreach ($emails as $email_item) {
            if (trim(strtolower($email_item)) == trim(strtolower($email))) {
                return true;
            }
        }

        return false;
    }

    public function changeEmailNotificationList($email, $mode, $email_old = '') {
        $email_address = trim(Configuration::get('HI_CMB_EMAIL_ADDRESS'));

        switch ($mode) {
            case 0: // insert
                if ($email_address) {
                    $email_address .= ','.$email;
                } else {
                    $email_address = $email;
                }

                break;

            case 1: // delete
                $emails = explode(',', $email_address);
                foreach ($emails as $key => $email_item) {
                    if (trim(strtolower($email_item)) == trim(strtolower($email))) {
                        unset($emails[$key]);
                    }
                }

                $email_address = implode(',', $emails);

                break;

            case 2: // update
                $emails = explode(',', $email_address);
                foreach ($emails as $key => $email_item) {
                    if (trim(strtolower($email_item)) == trim(strtolower($email_old))) {
                        $emails[$key] = $email;
                    }
                }

                $email_address = implode(',', $emails);

                break;
        }

        $this->email_address = $email_address;
        Configuration::updateValue('HI_CMB_EMAIL_ADDRESS', $this->email_address);
    }

    /* JLP - 15/01/2023 - lo comento porque con el CDN el JS se cachea sin la parte del modulo y no funciona en cualquier parte que no sea la home */
    /*public function hookDisplayHeader()
    {
        if ($this->context->controller->php_self != 'index') {
            return parent::hookDisplayHeader();
        } else {
            return '';
        }
    }*/

    public function returnHookContent($hook)
    {
        if ($this->context->controller->php_self != 'index') {
            if (trim(Configuration::get('HI_CMB_EMAIL_ADDRESS')) != '') {
                return parent::returnHookContent($hook);
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    public function getAgentsList()
    {
        $sql = '
            SELECT
                cmba.`id_hicallmebackagente`,
                cmba.`name`,
                cmba.`email`,
                cmba.`active`
            FROM `'._DB_PREFIX_.'hicallmebackagente` cmba';

        if (Tools::getValue('submitAgentsFilter')) {
            $this->context->cookie->{$this->name.'FilterAgent_id'} = Tools::getValue('filter_id');
            $this->context->cookie->{$this->name.'FilterAgent_name'} = Tools::getValue('filter_name');
            $this->context->cookie->{$this->name.'FilterAgent_email'} = Tools::getValue('filter_email');
            $this->context->cookie->{$this->name.'FilterAgent_active'} = (int)Tools::getValue('filter_active');
            $this->context->cookie->write();
        }

        if ($this->context->cookie->{$this->name.'FilterAgent_id'}) {
            $sql .= ' AND cmba.`id_hicallmebackagente` = '.(int) $this->context->cookie->{$this->name.'FilterAgent_id'};
        }
        if ($this->context->cookie->{$this->name.'FilterAgent_name'}) {
            $sql .= ' AND cmba.`name` = \''.pSQL($this->context->cookie->{$this->name.'FilterAgent_name'}).'\'';
        }
        if ($this->context->cookie->{$this->name.'FilterAgent_email'}) {
            $sql .= ' AND cmba.`email` = \''.pSQL($this->context->cookie->{$this->name.'FilterAgent_email'}).'\'';
        }
        if ($this->context->cookie->{$this->name.'FilterAgent_active'}) {
            $sql .= ' AND cmba.`active` = '.(int) $this->context->cookie->{$this->name.'FilterAgent_active'};
        }

        $sql .= ' ORDER BY `id_hicallmebackagente` DESC';

        return Db::getInstance()->ExecuteS($sql);
    }

    public function sendEmailNotification($firstname, $lastname, $email, $phone, $start_time, $end_time, $message = '')
    {
        return Mail::Send(
            Configuration::get('PS_LANG_DEFAULT'),
            'callmeback',
            Mail::l('Call Back Request'),
            array(
                '{first_name}' => $firstname,
                '{last_name}' => $lastname,
                '{phone}' => $phone,
                '{start_time}' => $start_time,
                '{end_time}' => $end_time,
                '{message}' => $message,
            ),
            $email,
            $firstname.' '.$lastname,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_.$this->name.'/mails/'
        );
    }

    
}
