<?php
class CecaOverride extends Ceca
{
    public function hookDisplayCustomerAccount($params)
    {
        if (isset($params['cookie']) && isset($params['cookie']->id_customer) && (int)$params['cookie']->id_customer > 0) {
            if ($this->getCustomerCardsList($params['cookie']->id_customer)) {
                $this->context->smarty->assign(array(
                    'myaccount_ctrl' => $this->context->link->getModuleLink('ceca', 'cards', array(), true),
                    'credit_card_icon' => _MODULE_DIR_.$this->name.'/views/img/credit-card-icon.png'
                ));
                if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                    //return $this->context->smarty->fetch($this->local_path.'views/templates/hook/my-account17.tpl');
                    return $this->display(__FILE__, 'my-account17.tpl');
                } elseif (version_compare(_PS_VERSION_, '1.6', '>=')) {
                    //return $this->context->smarty->fetch($this->local_path.'views/templates/hook/my-account.tpl');
                    return $this->display(__FILE__, 'my-account.tpl');
                } else {
                    //return $this->context->smarty->fetch($this->local_path.'views/templates/hook/my-account15.tpl');
                    return $this->display(__FILE__, 'my-account15.tpl');
                }
            }
        }
        return '';
    }
}