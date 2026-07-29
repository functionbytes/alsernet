<?php

require_once(dirname(__FILE__).'/../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../init.php');
include_once(dirname(__FILE__).'/front/AuthController.php');

class Routes extends Module
{
    public function routes()
    {

        $secret = "6LcRY40nAAAAAFJjZX46U5wcWquOwY_g7MDeBUly";
        $response = Tools::getValue('g-recaptcha-response');

        // if (!empty($response)) {
        //     $url = 'https://www.google.com/recaptcha/api/siteverify';
        //     $data = array(
        //         'secret' => $secret,
        //         'response' => $response
        //     );

        //     // Configuración de cURL
        //     $ch = curl_init();
        //     curl_setopt($ch, CURLOPT_URL, $url);
        //     curl_setopt($ch, CURLOPT_POST, true);
        //     curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verifica el certificado SSL

        //     $verify = curl_exec($ch);

        //     if ($verify === false) {
        //         // Manejo de error en cURL
        //         $response = array(
        //             'status' => 'error',
        //             'message' => 'Error: Unable to validate reCAPTCHA. ' . curl_error($ch),
        //         );
        //     } else {
        //         $captcha_success = json_decode($verify);

        //         if (isset($captcha_success) && $captcha_success->success) {
                    $response = $this->handleAction();
        //         } else {
        //             $response = array(
        //                 'status' => 'warning',
        //                 'message' => 'Error: Invalid reCAPTCHA access',
        //             );
        //         }
        //     }

        //     curl_close($ch);
        // } else {
        //     $response = array(
        //         'status' => 'warning',
        //         'message' => 'Error: No reCAPTCHA response received',
        //     );
        // }

        $this->sendJsonResponse($response);
    }

    private function handleAction()
    {

        $action = Tools::getValue('action');
        $controller = new AuthController();
        $response = null;

        switch ($action) {
            case 'login':
                $response = $controller->login();
                break;
            case 'resetpassword':
                $response = $controller->resetpassword();
                break;
            case 'changepassword':
                $response = $controller->changepassword();
                break;
            case 'validateemail':
                $response = $controller->validateemail();
                break;
            case 'register':
                $response = $controller->register();
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
