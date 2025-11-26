<?php


require_once(_PS_MODULE_DIR_.'psgdpr/classes/GDPRConsent.php');

class GdprController extends FrontController
{
    public $php_self = 'gdpr';

    public function initContent()
    {
        parent::initContent();

        if ($this->context->customer->isLogged()) {

            $this->display_column_right = false;
            $this->display_column_left = false;
            $context = Context::getContext();

            if (empty($context->customer->id)) {
                Tools::redirect('index.php');
            }

            $params = [
                'psgdpr_token' => sha1($context->customer->secure_key),
            ];

            $this->context->smarty->assign([
                'psgdpr_contactUrl' => $this->context->link->getPageLink('contact', true, $this->context->language->id),
                'psgdpr_front_controller' => Context::getContext()->link->getModuleLink('psgdpr', 'gdpr', $params, true),
                'psgdpr_csv_controller' => Context::getContext()->link->getModuleLink('psgdpr', 'ExportDataToCsv', $params, true),
                'psgdpr_pdf_controller' => Context::getContext()->link->getModuleLink('psgdpr', 'ExportDataToPdf', $params, true),
                'psgdpr_id_customer' => Context::getContext()->customer->id,
            ]);

            $this->setTemplate('customer/gdpr.tpl');

        }else {
            Tools::redirectLink($this->context->link->getPageLink('authentication', true));
        }

    }

}
