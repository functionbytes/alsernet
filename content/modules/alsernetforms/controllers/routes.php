<?php

require_once(dirname(__FILE__) . '/../../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../../init.php');
include_once(dirname(__FILE__) . '/front/FormController.php');
include_once(dirname(__FILE__) . '/front/SubscribersController.php');
include_once(dirname(__FILE__) . '/front/NewslettersController.php');

class Routes extends Module
{
    public function routes()
    {


        $modalitie = Tools::getValue('modalitie');
        $iso = trim(Tools::getValue('iso'));

        if (isset($modalitie) && $modalitie == 'notrecaptcha') {
            $response = $this->handleAction();

            $this->sendJsonResponse($response);
        } else {

            $secret = "6LcRY40nAAAAAFJjZX46U5wcWquOwY_g7MDeBUly";
            $response = Tools::getValue('g-recaptcha-response');

            if (!empty($response)) {
                $url = 'https://www.google.com/recaptcha/api/siteverify';
                $data = array(
                    'secret' => $secret,
                    'response' => $response
                );

                // Configuración de cURL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verifica el certificado SSL

                $verify = curl_exec($ch);

                if ($verify === false) {
                    // Manejo de error en cURL
                    $response = array(
                        'status' => 'error',
                        'message' => $this->l('Error: Unable to validate reCAPTCHA.', $iso) . curl_error($ch),
                    );
                } else {
                    $captcha_success = json_decode($verify);

                    if (isset($captcha_success) && $captcha_success->success) {
                        $response = $this->handleAction();
                    } else {
                        $response = array(
                            'status' => 'warning',
                            'message' => $this->l('Error: Invalid reCAPTCHA access.', 'formscontroller', $iso),
                        );
                    }
                }

                curl_close($ch);
            } else {
                $response = array(
                    'status' => 'warning',
                    'message' => $this->l('Error: No reCAPTCHA response received.', 'formscontroller', $iso),
                );
            }

            $this->sendJsonResponse($response);

        }


    }

    private function handleAction()
    {

        $action = Tools::getValue('action');
        $controller = new FormController();
        $controllerSubscribers = new SubscribersController();
        $controllerNewsletters = new NewslettersController();

        $response = null;

        switch ($action) {
            case 'fitting':
                $response = $controller->fitting();
                break;
            case 'demonday':
                //$response = $controller->demonday();
                break;
            case 'demondayorder':
                $response = $controller->demondayorder();
                break;
            case 'demondayvalidation':
                $response = $controller->demondayvalidation();
                break;
            case 'exchangesandreturns':
                $response = $controller->exchangesandreturns();
                break;

            case 'newslettersubscribe':
                $response = $controllerNewsletters->newslettersubscribe();
                break;

            case 'newsletterdischargersnone':
                $response = $controllerNewsletters->newsletterdischargersnone();
                break;

            case 'newsletterdischargersparties':
                $response = $controllerNewsletters->newsletterdischargersparties();
                break;

            case 'newsletterdischargerssports':
                $response = $controllerNewsletters->newsletterdischargerssports();
                break;
            case 'synchronizationnewsletter':
                //$response = $controllerSubscribers->synchronizationnewsletter();
                break;
            case 'customizeyourexperience':
                $response = $controllerSubscribers->customizeyourexperience();
                break;
            case 'giftvoucher':
                $response = $controllerSubscribers->giftvoucher();
                break;
            case 'wecallyouus':
                $response = $controller->wecallyouus();
                break;

            case 'contact':
                $response = $controller->contact();
                break;

            case 'compromise':
                $response = $controller->compromise();
                break;

            default:
                $response = array(
                    'status' => 'error',
                    'message' => 'Invalid action',
                );
                break;
        }

        return $response;
    }

    private function sendJsonResponse($response)
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        echo json_encode($response);
        exit; // Asegúrate de salir después de enviar la respuesta
    }
}

$routes = new Routes();
$routes->routes();
