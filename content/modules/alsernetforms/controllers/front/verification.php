
<?php

include_once(dirname(__FILE__) . '/SubscribersController.php');


class AlsernetformsVerificationModuleFrontController extends ModuleFrontController
{
    private $message = '';
    private $verification = '';

    public function postProcess()
    {

        $token = Tools::getValue('token');

        if (strlen($token) < 100) {

            $this->verification =  $this->verificationEmail($token);

            if ($this->verification){
                $this->message = $this->confirmEmail(Tools::getValue('token'));
                $this->context->smarty->assign('verification', true);
                $this->context->smarty->assign('message', $this->message);
            }else{
                $this->context->smarty->assign('message', $this->message);
            }

        } else {

            $tokenParts = explode('?token=', $token);
            $tokenClean = $tokenParts[0];
            $tokens = $tokenClean ;

            $data = [
                'action' => 'checkat',
                'token' => $tokens,
            ];

            $apiManager = new ApiManager();
            $response = $apiManager->sendRequest('POST', 'api/subscribers', $data, 'subscribers');

            if (isset($response['response']['status']) && $response['response']['status'] === 'success') {

                $this->context->smarty->assign('verification', true);
                $this->context->smarty->assign('message', $this->message);
            } else {
                $this->context->smarty->assign('message', $this->message);
                $this->context->smarty->assign('verification', false);
            }

        }




    }

    public function initContent()
    {
        parent::initContent();

        $this->setTemplate('module:alsernetforms/views/templates/front/verification/verification.tpl');

    }

    public function confirmEmail($token)
    {

        $email = $this->getEmailByToken($token);

        if (is_null($email)) {
            return $this->trans('This email is already registered and/or invalid.', [], 'Modules.Emailsubscription.Shop');
        }else{
            $sql = "UPDATE `"._DB_PREFIX_."susc_newsletter` set `lopd` = 1 where `id_susc_newsletter` = '".$email["id_susc_newsletter"]."'";
            Db::getInstance()->execute($sql);
            $susc_newsletter_data = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."susc_newsletter where `id_susc_newsletter` = '".$email["id_susc_newsletter"]."'")[0];
            $id_lang_gestion = AlvarezERP::getIdiomaGestion($susc_newsletter_data['id_lang']);
            $existe_cliente_erp = AlvarezERP::recuperarclienteerpAlsernet($susc_newsletter_data['email']);
            if (!$existe_cliente_erp) {
                $response = AlvarezERP::guardardatosclienteerp(null,
                                                    $susc_newsletter_data['nombre'],
                                                    $susc_newsletter_data['apellidos'],
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
                                                    str_replace(" ", "T", $susc_newsletter_data['fecha_nac']),
                                                    str_replace(" ", "T", $susc_newsletter_data['fecha']),
                                                    $susc_newsletter_data['cliente_no_info_comercial'],
                                                    $susc_newsletter_data['cliente_no_datos_a_terceros'],
                                                    $susc_newsletter_data['ids_alta_baja'],
                                                    null,
                                                    0);
                $res = AlvarezERP::savelopd($email["email"], str_replace(' ', 'T', date('Y-m-d H:i:s')), 0,0);
                return $this->trans('Thank you for subscribing to our newsletter.', [], 'Modules.Emailsubscription.Shop');
            }


        }

    }

    public function confirmEmails($token)
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

            $newsletterController = new NewslettersController();
            $newsletter = $newsletterController->verificationExisEmail($susc_newsletter_data['email']);

            $data = [
                'user' => $newsletter['id_susc_newsletter'],
                'email' => $newsletter['email'],
                'id_lang' => $newsletter['id_lang'],
                'check' => 1,
            ];

            $newsletterController->updateNewsletterData($data);

            return $this->trans('This email is already registered and/or invalid.', [], 'Modules.Emailsubscription.Shop');

        }

    }


    protected function getEmailByToken($token)
    {
        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'susc_newsletter`
                WHERE MD5(CONCAT( `email` , \'' . pSQL(Configuration::get('NW_SALT')) . '\')) = \'' . pSQL($token) . '\'
                AND `lopd` = 0 order by `id_susc_newsletter` desc limit 1';

        $result = Db::getInstance()->ExecuteS($sql);
        return $result[0];
    }

    public function verificationEmails($token)
    {
        $token = pSQL($token);
        $salt = pSQL(Configuration::get('NW_SALT'));


        $sql = 'SELECT *
            FROM `' . _DB_PREFIX_ . 'alsernet_forms_newsletter`
            WHERE MD5(CONCAT(`email`, \'' . $salt . '\')) = \'' . $token . '\'
            AND `lopd` = 0
            ORDER BY `id_susc_newsletter` DESC
            LIMIT 1';

        try {
            $result = Db::getInstance()->ExecuteS($sql);
            if (!empty($result)) {
                return true;
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    public function verificationEmail($token)
    {
        $token = pSQL($token);
        $salt = pSQL(Configuration::get('NW_SALT'));

        $sql = 'SELECT *
            FROM `' . _DB_PREFIX_ . 'susc_newsletter`
            WHERE MD5(CONCAT(`email`, \'' . $salt . '\')) = \'' . $token . '\'
            AND `lopd` = 0
            ORDER BY `id_susc_newsletter` DESC
            LIMIT 1';

        try {
            $result = Db::getInstance()->ExecuteS($sql);
            if (!empty($result)) {
                return true;
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }


}
