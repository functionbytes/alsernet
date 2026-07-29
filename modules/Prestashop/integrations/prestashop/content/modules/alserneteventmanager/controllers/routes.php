<?php

require_once(dirname(__FILE__).'/../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../init.php');
include_once(dirname(__FILE__).'/front/EventsController.php');

class Routes extends Module
{
    public function routes()
    {
        $response = $this->handleAction();
        $this->sendJsonResponse($response);
    }

    private function handleAction()
    {

        $action = Tools::getValue('action');
        $controller = new EventsController();

        switch ($action) {
            case 'get':
                $response = $controller->getAlls();
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
