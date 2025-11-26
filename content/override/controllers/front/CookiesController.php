<?php



class CookiesController extends FrontController
{
    public $php_self = 'cookies';

    public function initContent()
    {
        parent::initContent();

        if ($this->context->customer->isLogged()) {
            $lgCookiesLaw = Module::getInstanceByName('lgcookieslaw');
            if ($lgCookiesLaw && method_exists($lgCookiesLaw, 'deleteCookies')) {
                $lgCookiesLaw->deleteCookies();
            }
            $this->setTemplate('customer/cookies.tpl');
        } else {
            Tools::redirectLink($this->context->link->getPageLink('authentication', true));
        }

    }


}
