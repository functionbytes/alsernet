<?php

require_once(dirname(__FILE__).'/../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../init.php');
include_once(dirname(__FILE__).'/front/CustomerController.php');
include_once(dirname(__FILE__).'/front/AddressController.php');
include_once(dirname(__FILE__).'/front/WishlistController.php');

class Routes extends Module
{
    public function routes()
    {

        $response = $this->handleAction();

        $this->sendJsonResponse($response);
    }

    private function handleAction()
    {

        $modalitie = Tools::getValue('modalitie');
        $action = Tools::getValue('action');
        $iso = Tools::getValue('iso');
        $response = null;

        switch ($modalitie) {
            case 'wishlist':

                $controller = new WishlistController();
                $response = null;

                switch ($action) {
                    case 'add':
                        $response = $controller->add();
                        break;
                    case 'remove':
                        $response = $controller->remove();
                        break;
                    case 'delete':
                        $response = $controller->delete();
                        break;
                    case 'get':
                        //$response = $controller->get();
                        break;
                    case 'view':
                        //$response = $controller->view();
                        break;
                    case 'modal':
                        //$response = $controller->modal();
                        break;
                    case 'cart':
                        $response = $controller->cart();
                        break;
                    case 'count':
                        $response = $controller->count();
                        break;

                    default:
                        $response = array(
                            'status' => 'error',
                            'message' => 'Invalid action',
                        );
                        break;
                }

                return $response;


                break;
            case 'address':

                $controllerAddress = new AddressController();
                $response = null;


                switch ($action) {
                    case 'addaddress':
                        $response = $controllerAddress->addaddress();
                        break;
                    case 'editaddress':
                        $response = $controllerAddress->editaddress();
                        break;
                    case 'deleteaddress':
                        $response = $controllerAddress->deleteaddress();
                        break;
                    case 'getaddress':
                        $response = $controllerAddress->getaddress();
                        break;
                    case 'getaddaddressfields':
                        $response = $controllerAddress->getaddaddressfields();
                        break;
                    case 'getaddressfields':
                        $response = $controllerAddress->getaddressfields();
                        break;
                    case 'getstates':
                        $response = $controllerAddress->getstates();
                        break;
                    case 'getaddresses':
                        $response = $controllerAddress->getaddresses();
                        break;
                    default:
                        $response = array(
                            'status' => 'error',
                            'message' => 'Invalid action',
                        );
                        break;
                }

                return $response;


                break;
            case 'customer':

                $controllerCustomer = new CustomerController();
                $response = null;

                switch ($action) {
                    case 'information':

                        $response = $controllerCustomer->updateinformation();
                        break;

                    default:
                        $response = array(
                            'status' => 'error',
                            'message' => 'Invalid action',
                        );
                        break;
                }

                return $response;


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
