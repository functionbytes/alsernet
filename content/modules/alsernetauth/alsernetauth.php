<?php

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Alsernetauth extends Module implements WidgetInterface
{
    public function __construct(){
        $this->name = 'alsernetauth';
        $this->author = 'Alsernet';
        $this->version = '1.0.0';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = "Alsernet - Autenticación ";
        $this->description = $this->getTranslator()->trans('Make your customers feel at home on your store, invite them to sign in!', [], 'Modules.Customersignin.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

    }

    public function install(){

        return parent::install() && $this->registerHook('displayAuthLogin')
         && $this->registerHook('displayBeforeBodyClosingTag')
         && $this->registerHook('displayAuthRegister')
         && $this->registerHook('displayAuthPasswordNew')
         && $this->registerHook('displayAuthPasswordInformation')
         && $this->registerHook('displayAuthPasswordEmail')
         && $this->registerHook('displayTop')
         && $this->registerHook('header');
    }

    public function getWidgetVariablesAuth($hookName, array $configuration){

        $logged = $this->context->customer->isLogged();
        $link = $this->context->link;

        return [
            'logged' => $logged,
            'links' => $logged ? $link->getPageLink('my-account', true) : $link->getPageLink('iniciar-sesion', true) ,
        ];
    }

    public function getWidgetVariables($hookName, array $configuration){
    }

    public function renderWidget($hookName, array $configuration){

        if ($hookName == 'displayNav2' && $configuration['action'] == 'auth') {
            $this->smarty->assign($this->getWidgetVariablesAuth($hookName, $configuration));
            return $this->fetch('module:alsernetauth/views/templates/hook/hook/signin.tpl');
        }elseif ($hookName == 'displayAuthLogin') {
            return $this->fetch('module:alsernetauth/views/templates/hook/pages/login.tpl');
        }elseif ($hookName == 'displayAuthRegister') {
            return $this->fetch('module:alsernetauth/views/templates/hook/pages/register.tpl');
        }elseif ($hookName == 'displayAuthPasswordEmail') {
            return $this->fetch('module:alsernetauth/views/templates/hook/pages/password-email.tpl');
        }elseif ($hookName == 'displayAuthPasswordInformation') {
            return $this->fetch('module:alsernetauth/views/templates/hook/pages/password-infos.tpl');
        }elseif ($hookName == 'displayAuthPasswordNew') {
            return $this->fetch('module:alsernetauth/views/templates/hook/pages/password-new.tpl');
        }

    }
    public function hookDisplayAuthLogin($params){
        return $this->renderWidget('displayAuthLogin', $params);
    }
    public function hookDisplayAuthRegister($params){
        return $this->renderWidget('displayAuthRegister', $params);
    }
    public function hookDisplayAuthPasswordEmail($params){
        return $this->renderWidget('displayAuthPasswordEmail', $params);
    }
    public function hookHeader($params){

        $this->context->controller->addCSS($this->_path.'views/css/front/style.css','all');
        $this->context->controller->addJS($this->_path.'views/js/front/scripts.js');

    }

}


