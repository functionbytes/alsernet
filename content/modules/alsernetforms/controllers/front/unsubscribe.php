<?php

class AlsernetformsUnsubscribeModuleFrontController extends ModuleFrontController
{
    private $message = '';
    private $unsubscribe = '';

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $this->unsubscribe =  $this->verificationEmail(Tools::getValue('token'));

        if (!$this->unsubscribe){

            $this->context->smarty->assign('unsubscribe', false);
            $this->context->smarty->assign('message', $this->message);

        }else{
                $type = Tools::getValue('token');

                switch ($type):
                    case 'nano':
                        $this->message = $this->unsubscribeNano(Tools::getValue('token'));
                        break;
                    case 'parties':
                        $this->message = $this->unsubscribeParties(Tools::getValue('token'));
                        break;
                    case 'information':
                        $this->message = $this->unsubscribeInformation(Tools::getValue('token'));
                        break;
                    default:
                        $this->message = 'Invalid token provided';
                        break;
                endswitch;

                $this->context->smarty->assign('unsubscribe', true);
                $this->context->smarty->assign('type', $type);
                $this->context->smarty->assign('message', $this->message);

            }

    }

    public function initContent()
    {
        parent::initContent();

        $this->setTemplate('module:alsernetforms/views/templates/front/unsubscribe/unsubscribe.tpl');

    }

    public function verificationEmail($token)
    {
        $token = pSQL($token);
        $salt = pSQL(Configuration::get('NW_SALT'));

        $sql = 'SELECT *
            FROM `' . _DB_PREFIX_ . 'alsernet_forms_newsletter`
            WHERE MD5(CONCAT(`email`, \'' . $salt . '\')) = \'' . $token . '\'
            AND `lopd` = 1
            ORDER BY `id_susc_newsletter` DESC
            LIMIT 1';

        try {
            $result = Db::getInstance()->ExecuteS($sql);
            // Si se encuentra un resultado, retorna true
            if (!empty($result)) {
                return true;
            }
        } catch (Exception $e) {
            // Manejo de errores
            // Se podría registrar el error o retornar false
            return false;
        }

        return false;
    }


    public function unsubscribeNano($token)
    {
        $email = $this->getEmailByToken($token);

        if (is_null($email)) {
            return $this->trans('This email is already registered and/or invalid.', [], 'Modules.Emailsubscription.Shop');
        }else{

            $sql = "UPDATE `"._DB_PREFIX_."alsernet_forms_newsletter` 
            SET `lopd` = 1, 
                `check_at` = NOW() 
            WHERE `id_susc_newsletter` = '".$email["id_susc_newsletter"]."'";

            Db::getInstance()->execute($sql);

            $susc_newsletter_data = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."alsernet_forms_newsletter where `id_susc_newsletter` = '".$email["id_susc_newsletter"]."'")[0];

            $id_lang_gestion = AlvarezERP::getIdiomaGestion($susc_newsletter_data['id_lang']);

            $existe_cliente_erp = AlvarezERP::recuperarclienteerpAlsernet($susc_newsletter_data['email']);

            if (!$existe_cliente_erp) {
                $response = AlvarezERP::guardardatosclienteerp(null,
                    $susc_newsletter_data['firstname'],
                    $susc_newsletter_data['lastname'],
                    null,
                    $susc_newsletter_data['email'],
                    null,
                    null,
                    $id_lang_gestion,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    str_replace(" ", "T", $susc_newsletter_data['created_at']),
                    $susc_newsletter_data['none'],
                    $susc_newsletter_data['parties'],
                    $susc_newsletter_data['ids_sports'],
                    null,
                    0);

                Db::getInstance()->execute($sql);

            }else{
                return $this->trans('This email is already registered and/or invalid.', [], 'Modules.Emailsubscription.Shop');
            }

            $res = AlvarezERP::savelopd($email["email"], str_replace(' ', 'T', date('Y-m-d H:i:s')), 0,0);

            return $this->trans('Thank you for subscribing to our newsletter.', [], 'Modules.Emailsubscription.Shop');
        }

    }

    public function unsubscribeParties($token)
    {
        $email = $this->getEmailByToken($token);

        if (is_null($email)) {
            return $this->trans('This email is already registered and/or invalid.', [], 'Modules.Emailsubscription.Shop');
        }else{

            $sql = "UPDATE `"._DB_PREFIX_."alsernet_forms_newsletter` 
            SET `lopd` = 1, 
                `check_at` = NOW() 
            WHERE `id_susc_newsletter` = '".$email["id_susc_newsletter"]."'";

            Db::getInstance()->execute($sql);

            $susc_newsletter_data = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."alsernet_forms_newsletter where `id_susc_newsletter` = '".$email["id_susc_newsletter"]."'")[0];

            $id_lang_gestion = AlvarezERP::getIdiomaGestion($susc_newsletter_data['id_lang']);

            $existe_cliente_erp = AlvarezERP::recuperarclienteerpAlsernet($susc_newsletter_data['email']);

            if (!$existe_cliente_erp) {
                $response = AlvarezERP::guardardatosclienteerp(null,
                    $susc_newsletter_data['firstname'],
                    $susc_newsletter_data['lastname'],
                    null,
                    $susc_newsletter_data['email'],
                    null,
                    null,
                    $id_lang_gestion,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    str_replace(" ", "T", $susc_newsletter_data['created_at']),
                    $susc_newsletter_data['none'],
                    $susc_newsletter_data['parties'],
                    $susc_newsletter_data['ids_sports'],
                    null,
                    0);

                Db::getInstance()->execute($sql);

            }else{
                return $this->trans('This email is already registered and/or invalid.', [], 'Modules.Emailsubscription.Shop');
            }

            $res = AlvarezERP::savelopd($email["email"], str_replace(' ', 'T', date('Y-m-d H:i:s')), 0,0);

            return $this->trans('Thank you for subscribing to our newsletter.', [], 'Modules.Emailsubscription.Shop');
        }

    }

    public function unsubscribeInformation($token)
    {
        $email = $this->getEmailByToken($token);

        if (is_null($email)) {
            return $this->trans('This email is already registered and/or invalid.', [], 'Modules.Emailsubscription.Shop');
        }else{

            $sql = "UPDATE `"._DB_PREFIX_."alsernet_forms_newsletter` 
            SET `lopd` = 1, 
                `check_at` = NOW() 
            WHERE `id_susc_newsletter` = '".$email["id_susc_newsletter"]."'";

            Db::getInstance()->execute($sql);

            $susc_newsletter_data = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."alsernet_forms_newsletter where `id_susc_newsletter` = '".$email["id_susc_newsletter"]."'")[0];

            $id_lang_gestion = AlvarezERP::getIdiomaGestion($susc_newsletter_data['id_lang']);

            $existe_cliente_erp = AlvarezERP::recuperarclienteerpAlsernet($susc_newsletter_data['email']);

            if (!$existe_cliente_erp) {
                $response = AlvarezERP::guardardatosclienteerp(null,
                    $susc_newsletter_data['firstname'],
                    $susc_newsletter_data['lastname'],
                    null,
                    $susc_newsletter_data['email'],
                    null,
                    null,
                    $id_lang_gestion,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    str_replace(" ", "T", $susc_newsletter_data['created_at']),
                    $susc_newsletter_data['none'],
                    $susc_newsletter_data['parties'],
                    $susc_newsletter_data['ids_sports'],
                    null,
                    0);

                Db::getInstance()->execute($sql);

            }else{
                return $this->trans('This email is already registered and/or invalid.', [], 'Modules.Emailsubscription.Shop');
            }

            $res = AlvarezERP::savelopd($email["email"], str_replace(' ', 'T', date('Y-m-d H:i:s')), 0,0);

            return $this->trans('Thank you for subscribing to our newsletter.', [], 'Modules.Emailsubscription.Shop');
        }

    }




}

