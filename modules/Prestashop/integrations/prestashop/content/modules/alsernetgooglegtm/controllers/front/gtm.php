<?php

require_once(dirname(__FILE__) . '/../../../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../../../init.php');
require_once(dirname(__FILE__) . '/../../alsernetgooglegtm.php');

class alsernetgooglegtmgtmModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        //*parent::initContent();


        $gtm = new Alsernetgooglegtm();

        //die();
        switch (Tools::getValue('method')) {

                case 'select':

                    $product = Tools::getValue('product');
                    $category = Tools::getValue('category');
                    $response  = $gtm->handleSelect($product,$category);
                break;

        }


        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        echo json_encode($response);
        exit; // Asegúrate de salir después de enviar la respuesta

    }
}
