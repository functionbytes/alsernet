<?php

require_once(dirname(__FILE__).'/../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../init.php');
include_once(dirname(__FILE__).'/front/CategoryController.php');
include_once(dirname(__FILE__).'/front/ProductController.php');

class Routes extends Module
{
    public function routes()
    {

        $response = $this->handleAction();

        $this->sendJsonResponse($response);

    }

    private function handleAction()
    {
        $type = Tools::getValue('type');
        $category = Tools::getValue('category');
        $product = Tools::getValue('product');
        $iso = Tools::getValue('iso');

        $controllerCategory = new CategoryController();
        $controllerProduct = new ProductController();
        $response = null;

        switch ($type) {
            case 'news':
                $response = $controllerCategory->news($category,$type);
                break;
            case 'sales':
                $response = $controllerCategory->sales($category,$type);
                break;
            case 'analytics':
                $response = $controllerCategory->analytics($category,$type);
                break;
            case 'detail':
                $response = $controllerProduct->delete();
                break;
            case 'attributes':
                $response = $controllerProduct->update();
                break;
            case 'view':
                $response = $controllerProduct->view();
                break;
            case 'viewproduct':
                $response = $controllerProduct->viewProduct();
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
