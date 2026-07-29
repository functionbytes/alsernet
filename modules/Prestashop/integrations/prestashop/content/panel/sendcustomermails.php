<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
include _PS_ADMIN_DIR_.'/../init.php';

class askForOpinions
{
    const DAYS_FROM = 7;

    const DAYS_TO = 15;

    private $aPriority = ['100004097', '100004102', '9878'];

    private $seguimientoLineasPedidoDAO = null;

    private $productoDAO = null;

    private $modeloDAO = null;

    private $logTransportistaEnvioDAO = null;

    private $aOrders = [];

    public function testenvio()
    {

        $lang_id = 1;

        // Envio de correo
        $sSubject = 'Ahora que tienes el pedido en tus manos…';

        switch ($lang_id) {
            case 1: // ES
                $sSubject = 'Ahora que tienes el pedido en tus manos…';
                break;
            case 2: // EN
                $sSubject = 'Now that you have the order in your hands…';
                break;
            case 3: // FR
                $sSubject = 'Maintenant que vous avez la commande entre vos mains…';
                break;
            case 4: // PT
                $sSubject = 'Agora que você tem o pedido em suas mãos…';
                break;
            case 5: // DE
                $sSubject = 'Nun, da Sie die Bestellung in Ihren Händen halten …';
                break;
            default:
        }

        $oModel = new Product(48768);

        $img = Product::getCover($oModel->id);
        $image_type = 'home_default';
        $link = new Link;
        $imagensrc = 'https://'.$link->getImageLink(isset($oModel->link_rewrite[$lang_id]) ? $oModel->link_rewrite[$lang_id] : $oModel->name[$lang_id], (int) $img['id_image'], $image_type);
        $url = $link->getProductLink($oModel);

        $data = [
            '{nombre_cliente}' => 'Pedro',
            '{nombre_producto}' => $oModel->name[1],
            '{imagen_producto}' => $imagensrc,
            '{link_producto}' => $url,
        ];

        echo 'llega1'._PS_MAIL_DIR_;
        Mail::Send(
            $lang_id,
            'order_opinion',
            $sSubject,
            $data,
            'desarrollo@addis.es',
            Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            [],
            null,
            _PS_MAIL_DIR_,
            false,
            1
        );

        echo 'llega2';

    }

    public function run()
    {

        echo 'ENVIO DE MAILS DE PETICION DE OPINIONES<br/>';

        // dump($this->getUserGestion("123445","GarcÍa Perez"));
        // dump($this->getUserGestion("02255577J","Haro Flores"));

        echo 'Buscando los pedidos <br/>';
        $this->findOrders();
        echo count($this->aOrders).' pedidos<br/>';

        // Envia los correos
        echo 'Enviando los correos necesarios<br/>';
        $this->sendAllMails();
        // $this->printNewLines();
        // $this->printProcessFinished();

    }

    private function replaceAccents($str)
    {
        $a = ['À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ'];
        $b = ['A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o'];

        return str_replace($a, $b, $str);
    }

    private function GetClienteMultiplesParametrosWeb($sDNI, $sApellido)
    {

        $url = 'http://127.0.0.1:58002/api-gestion/cliente/?dni='.$sDNI.'&apellidos='.rawurlencode(strtoupper($this->replaceAccents($sApellido)));

        // $url = "http://127.0.0.1:58002/api-gestion/cliente/?idclienteweb=384943";
        // $url = "http://127.0.0.1:58002/api-gestion/cliente/?idclienteweb=209836";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);

        dump($content);

        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        return $array;

    }

    private function getUserGestion($sDNI, $sApellido)
    {
        $oReturn = null;

        $aUser = $this->GetClienteMultiplesParametrosWeb($sDNI, $sApellido);
        $oReturn = (object) [
            'email' => (! empty($aUser['email'])) ? strtolower($aUser['email']) : '',
            'nombre' => (! empty($aUser['nombre'])) ? $aUser['nombre'] : '',
            'apellidos' => (! empty($aUser['apellidos'])) ? $aUser['apellidos'] : '',
            'idGestion' => (! empty($aUser['idcliente'])) ? $aUser['idcliente'] : null,
            'idWeb' => (! empty($aUser['codigo_internet'])) ? $aUser['codigo_internet'] : null,
            'lopdDate' => (! empty($aUser['faceptacion_lopd'])) ? $aUser['faceptacion_lopd'] : null,
            'lopdNoInfo' => (isset($aUser['no_informacion_comercial_lopd'])) ? $aUser['no_informacion_comercial_lopd'] : null,
            'lopdNoTerceros' => (isset($aUser['no_datos_a_terceros_lopd'])) ? $aUser['no_datos_a_terceros_lopd'] : null,
            'lopdLegitimo' => (isset($aUser['tiene_interes_legitimo_lopd'])) ? $aUser['tiene_interes_legitimo_lopd'] : null,
            'albaranes' => (! empty($aUser['cantidad_albaranes'])) ? $aUser['cantidad_albaranes'] : 0,
        ];

        return $oReturn;

    }

    private function getOrdersToSend($iDaysFrom, $iDaysTo)
    {

        $query = '
            SELECT DISTINCT lt.*, p.id_order AS id_pedido, (select c1.email from aalv_customer c1 where c1.id_customer=p.id_customer) as email, (select c2.firstname from aalv_address c2 where c2.id_address=p.id_address_invoice) AS nombre_cliente, (select c3.lastname from aalv_address c3 where c3.id_address=p.id_address_invoice) AS apellido1,(select c4.vat_number from aalv_address c4 where c4.id_address=p.id_address_invoice) AS dni, sp.id_cliente_gestion, p.id_lang 
                FROM log_transportista AS lt
                LEFT JOIN log_transportista_envio AS lte on (lt.numero_pedido = lte.numero_pedido)
                INNER JOIN seguimiento_pedidos AS sp on (lt.numero_pedido = sp.referencia_transportista)
                INNER JOIN aalv_orders AS p on (p.id_order = sp.id_internet)
                where lt.fecha < date_sub(now(), interval '.$iDaysFrom.' day) and
                lt.fecha >= date_sub(now(), interval '.$iDaysTo.' day) and lte.fecha_envio is null';

        return Db::getInstance()->ExecuteS($query);
    }

    private function findOrders()
    {
        $this->aOrders = $this->getOrdersToSend(self::DAYS_FROM, self::DAYS_TO);
    }

    private function isOkLOPD($sDNI, $sApellido)
    {
        $bReturn = false;

        $oUser = $this->getUserGestion($sDNI, $sApellido);

        if (! empty($oUser->lopdDate)) {
            if ($oUser->lopdNoInfo == 0) {
                $bReturn = true; // Correcto
            }
        } elseif ($oUser->lopdLegitimo == 1) {
            $bReturn = true; // Correcto
        } elseif (! empty($oUser->albaranes)) {
            $bReturn = true; // Correcto
        }

        return $bReturn;
    }

    private function getPedidoByReferenciaTransportista($referenciatrans)
    {
        // $query = "SELECT * FROM seguimiento_pedidos WHERE referencia_transportista = ".$referenciatrans." LIMIT 1";
        $query = 'SELECT * FROM seguimiento_pedidos WHERE referencia_transportista = '.$referenciatrans;

        return Db::getInstance()->getRow($query);
    }

    private function getPedidosPosteriores($idclientegestion, $fechapedido, $idgestion)
    {

        $sQuery = 'SELECT * FROM seguimiento_pedidos WHERE id_cliente_gestion = '.$idclientegestion." AND fecha_pedido >= '".$fechapedido."' AND id_gestion != ".$idgestion;

        return Db::getInstance()->ExecuteS($query);

    }

    private static function orderModelToSend($a, $b)
    {
        if ($a->posicion_prioridad == $b->posicion_prioridad) {
            if ($a->precio_unitario == $b->precio_unitario) {
                return 0;
            } else {
                return $a->precio_unitario > $b->precio_unitario ? -1 : 1;
            }
        } else {
            return $a->posicion_prioridad > $b->posicion_prioridad ? -1 : 1;
        }
    }

    private function getLineasPedidoBySerieAndIdGestion($serie, $id_gestion)
    {

        $sQuery = 'SELECT * FROM seguimiento_lineas_pedido where id_pedido_gestion='.$id_gestion.' and serie='.$serie;

        return Db::getInstance()->ExecuteS($query);

    }

    private function getModeloPorReferencia($referencia)
    {

        $sQuery = "SELECT id_modelo FROM aalv_product_import WHERE id_product in (SELECT id_product FROM aalv_product_attribute where reference='".$referencia."' union select id_product from aalv_product where reference='".$referencia."')";

        return Db::getInstance()->getValue($query);

    }

    private function getModelToAsk($oOrderDataSend)
    {
        $oReturn = null;
        if (empty($oOrderDataSend)) {
            return $oReturn;
        }

        $aOrderLines = $this->getLineasPedidoBySerieAndIdGestion($oOrderDataSend->serie, $oOrderDataSend->id_gestion);

        $aModels = [];
        if (! empty($aOrderLines)) {
            foreach ($aOrderLines as $oLine) {
                if (empty($oLine->unidades)) {
                    continue;
                }

                // $aProducto = $this->productoDAO->selectProductoByReferencia($oLine->referencia);
                // if (empty($aProducto)) { continue; }
                // $oModelo = $this->modeloDAO->get($aProducto[0]->id_modelo);

                // buscar modelo por referencia

                $idModelo = $this->getModeloPorReferencia($oLine->referencia);

                // if ($this->modeloDAO->isActivoMarketing($oModelo->id) ) {
                $aModels[] = (object) [
                    'referencia' => $oLine->referencia,
                    'id' => $idModelo,
                    'nombre' => $oLine->producto,
                    'unidades' => $oLine->unidades,
                    'precio_unitario' => $oLine->subtotal / $oLine->unidades,
                    'subtotal' => $oLine->subtotal,
                    'posicion_prioridad' => array_search($idModelo, $this->aPriority),
                ];
                // }
            }

            if (! empty($aModels)) {
                usort($aModels, ['askForOpinions', 'orderModelToSend']);

                // $oApiConnect = new utils_ApiConnect();
                // $oReturn = $oApiConnect->connect(URL_API_PUB."catalog/models/".$aModels[0]->id, false);
                // if (!empty($oReturn->also_purchased)) { unset($oReturn->also_purchased); }

                // aqui devolver producto

                $idproductps = ''.Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute where reference='".$aModels[0]->referencia."' union select id_product from aalv_product where reference='".$aModels[0]->referencia."'");

                if ($idproductps != '') {
                    $oReturn = new Product((int) $idproductps);
                }

            }
        }

        return $oReturn;
    }

    private function sendMail($oOrder, $oModel)
    {

        $lang_id = (int) $oOrder->id_lang;

        // Envio de correo
        $sSubject = 'Ahora que tienes el pedido en tus manos…';

        switch ($lang_id) {
            case 1: // ES
                $sSubject = 'Ahora que tienes el pedido en tus manos…';
                break;
            case 2: // EN
                $sSubject = 'Now that you have the order in your hands…';
                break;
            case 3: // FR
                $sSubject = 'Maintenant que vous avez la commande entre vos mains…';
                break;
            case 4: // PT
                $sSubject = 'Agora que você tem o pedido em suas mãos…';
                break;
            case 5: // DE
                $sSubject = 'Nun, da Sie die Bestellung in Ihren Händen halten …';
                break;
            default:
        }

        $img = Product::getCover($oModel->id);
        $image_type = 'home_default';
        $link = new Link;
        $imagensrc = 'https://'.$link->getImageLink(isset($oModel->link_rewrite[$lang_id]) ? $oModel->link_rewrite[$lang_id] : $oModel->name[$lang_id], (int) $img['id_image'], $image_type);
        $url = $link->getProductLink($oModel);

        $data = [
            '{nombre_cliente}' => $oOrder->nombre_cliente,
            '{nombre_producto}' => $oModel->name[1],
            '{imagen_producto}' => $imagensrc,
            '{link_producto}' => $url,

        ];

        /*Mail::Send(
                    1,
                    'order_opinion',
                    $sSubject,
                    $data,
                    $oOrder->email,
                    $oOrder->nombre_cliente,
                    null,
                    null,
                    [],
                    null,
                    _PS_MAIL_DIR_,
                    false,
                    1
                );*/
        Mail::Send(
            $lang_id,
            'order_opinion',
            $sSubject,
            $data,
            $oOrder->email,
            $oOrder->nombre_cliente,
            null,
            null,
            [],
            null,
            _PS_MAIL_DIR_,
            false,
            1
        );

        // coger plantilla

        /*
        $sContent = utils_CustomerMails::templateOpinions($oOrder, $oModel);





        if (!$this->isDebug()) {

            //enviar correo
            utils_CustomerMails::sendMail([[$oOrder->email, $oOrder->nombre_cliente]], $sSubject, $sContent);


        }
        */

        // Guardar el Log en la Base de datos
        $idmodelo = Db::getInstance()->getValue('SELECT id_modelo FROM aalv_product_import WHERE id_product='.$oModel->id);
        $query = "INSERT INTO log_transportista_envio(numero_pedido, fecha_envio, id_modelo, fecha_opinion, log_transportista_id) VALUES ('".$oOrder->numero_pedido."','".date('Y-m-d H:i:s')."',".$idmodelo.',null,'.$oOrder->id.')';

        Db::getInstance()->Execute($query);

    }

    private function sendAllMails()
    {
        if (empty($this->aOrders)) {
            return;
        }

        foreach ($this->aOrders as $oOrder) {
            echo 'Email para '.$oOrder['email'].'<br/> ';
            if ($this->isOkLOPD($oOrder['dni'], $oOrder['apellido1'])) {
                $oOrderDataSend = $this->getPedidoByReferenciaTransportista($oOrder['numero_pedido']);
                $aOrdersPosteriores = $this->getPedidosPosteriores($oOrder['id_cliente_gestion'], $oOrder['fecha_pedido'], $oOrderDataSend['id_gestion']);
                if (! empty($aOrdersPosteriores)) {
                    continue;
                }

                $oModelo = $this->getModelToAsk($oOrderDataSend);

                if (! empty($oModelo)) {
                    sleep(1); // Se duerme un segundo para que no salgan todos los correos juntos

                    // en omodelo tenemos el producto Prestashop

                    $this->sendMail($oOrder, $oModelo);

                } else {
                    echo 'Ya no hay modelo del que opinar<br/>';
                }
            } else {
                echo 'LOPD no aceptada<br/>';
            }
        }
    }
}

$oAskForOpinions = new askForOpinions;
$oAskForOpinions->run();
// $oAskForOpinions->testenvio();
