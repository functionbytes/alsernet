<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
class auxiliares
{
    public static function peticionget($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }

    public static function procesarcombinaciones($idproduct)
    {

        // ver si tiene combinaciones

        $productattributes = Db::getInstance()->ExecuteS('SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product='.$idproduct);

        if ($productattributes) {
            // echo "procesarcombinaciones => ".$productattributes."\n";

            $idproductatributeminimo = 0;
            $preciominimo = 999999;
            $numcambios = 0;

            $arprecios = [];

            foreach ($productattributes as $productattributeitem) {

                $noestaborrada = ''.Db::getInstance()->getValue('SELECT id_tot_switch_attribute_disabled FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute='.$productattributeitem['id_product_attribute']);

                if ($noestaborrada == '') {

                    // ver si tiene stock
                    $stock = StockAvailable::getQuantityAvailableByProduct($idproduct, $productattributeitem['id_product_attribute']);

                    if ($stock > 0) {
                        // coger precio
                        $specific_price = '';
                        $miprecio = Product::priceCalculation(1, $idproduct, $productattributeitem['id_product_attribute'], 0, 0, '', 0, 0, 1, true, 6, 0, false, false, $specific_price, false, 0, true, 0, 0, 0);

                        if (is_array($specific_price) && array_key_exists('reduction', $specific_price)) {
                            $miprecio = $miprecio - $specific_price['reduction'];
                        }

                        $miprecio = round($miprecio, 6);
                        $miprecio = ((int) ($miprecio * 100)) / 100;

                        if (! in_array($miprecio, $arprecios)) {
                            $arprecios[] = $miprecio;
                        }

                        if ($miprecio < $preciominimo) {
                            $preciominimo = $miprecio;
                            $idproductatributeminimo = $productattributeitem['id_product_attribute'];
                            $numcambios = $numcambios + 1;
                        }
                    }
                }
            }

            if ($idproductatributeminimo != 0) {
                // hacer $idproductatributeminimo la combinacion Por defecto

                $product = new Product($idproduct);
                $product->deleteDefaultAttributes();
                $product->setDefaultAttribute($idproductatributeminimo);
                $product->update();
                Product::updateDefaultAttribute($idproduct);
            }

            if (count($arprecios) > 1) {
                // atributo desde
                $idfeaturedesde = Feature::addFeatureImport('Poner desde');
                $idfeaturedesdevalue = self::crearFeatureValue($idfeaturedesde, '1', 0);
                if ($idfeaturedesdevalue != 0) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=9 and id_product='.$idproduct);
                    $product = new Product($idproduct);
                    $product->addFeatureProductImport($idproduct, $idfeaturedesde, $idfeaturedesdevalue);
                }
            } else {
                Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=9 and id_product='.$idproduct);
            }
        }

        Db::getInstance()->Execute("INSERT INTO aalv_alsernet_cache_producto values (NULL, $idproduct)");
        // self::peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=" . $idproduct);
    }

    public static function sendmail($mensaje)
    {
        $dest = [];
        $dest[] = 'alvarez@alsernet.es';

        $data = ['{message}' => $mensaje];
        Mail::Send(1,
            'integracion',
            'Integracion',
            $data,
            $dest,
            Configuration::get('PS_SHOP_NAME'),
            'desarrollotest@a-alvarez.com',
            'desarrollotest',
            [],
            null,
            _PS_MAIL_DIR_,
            false,
            1
        );
    }

    public static function Cambiarproducto($idprodps, $idProductAttribute, $idprodpsanterior, $idpscombanterior)
    {

        // tarifa_cabecera
        Db::getInstance()->Execute('UPDATE aalv_tarifa_cabecera_import SET id_product='.$idprodps.',id_attribute='.$idProductAttribute.' WHERE id_product='.$idprodpsanterior.' and id_attribute='.$idpscombanterior);
        // specific
        Db::getInstance()->Execute('UPDATE aalv_specific_price SET id_product='.$idprodps.',id_product_attribute='.$idProductAttribute.' WHERE id_product='.$idprodpsanterior.' and id_product_attribute='.$idpscombanterior);
        // repositorio
        Db::getInstance()->Execute('UPDATE aalv_repositorio_stock SET id_product='.$idprodps.',id_product_attribute='.$idProductAttribute.' WHERE id_product='.$idprodpsanterior.' and id_product_attribute='.$idpscombanterior);
        // stock
        Db::getInstance()->Execute('UPDATE aalv_stock_available SET id_product='.$idprodps.',id_product_attribute='.$idProductAttribute.' WHERE id_product='.$idprodpsanterior.' and id_product_attribute='.$idpscombanterior);

        // precios especificos
        Db::getInstance()->Execute('UPDATE aalv_specific_price SET id_product='.$idprodps.',id_product_attribute='.$idProductAttribute.' WHERE id_product='.$idprodpsanterior.' and id_product_attribute='.$idpscombanterior);

        if ($idpscombanterior != '0') {
            Db::getInstance()->Execute('DELETE FROM aalv_combinaciones_import WHERE id_product_attribute='.$idpscombanterior);
            // Db::getInstance()->Execute("DELETE FROM aalv_product_attribute WHERE id_product_attribute=" . $idpscombanterior);
            $combination_obj = new Combination($idpscombanterior);
            $combination_obj->delete();
        }

        Db::getInstance()->Execute('DELETE FROM aalv_combinacionunica_import WHERE id_product='.$idprodpsanterior);
        self::procesarcombinaciones($idprodpsanterior);
    }

    public static function validarFeature($id_feature, $id_product)
    {
        $buscar_feature = Db::getInstance()->executeS('SELECT * FROM aalv_feature_product WHERE id_product = '.$id_product.' AND id_feature = '.$id_feature);
        if ($buscar_feature) {
            return true;
        } else {
            return false;
        }
    }

    public static function ProcesarTipoProducto($data, $idprodps)
    {

        $tipoproducto = self::gettipoproducto($idprodps);

        if ($tipoproducto == 0) {

            if ($data['es_arma'] == 1) {
                Db::getInstance()->Execute('INSERT INTO aalv_feature_product(id_feature, id_product, id_feature_value) VALUES (5,'.$idprodps.',10100)');

                return;
            }
            if ($data['es_arma_fogueo'] == 1) {
                Db::getInstance()->Execute('INSERT INTO aalv_feature_product(id_feature, id_product, id_feature_value) VALUES (5,'.$idprodps.',10102)');

                return;
            }
            if ($data['es_cartucho'] == 1) {
                Db::getInstance()->Execute('INSERT INTO aalv_feature_product(id_feature, id_product, id_feature_value) VALUES (5,'.$idprodps.',10101)');

                return;
            }
        }

        if ($tipoproducto == 10100) { // es arma ya el producto

            if ($data['es_arma'] == 0) {

                // ver si todos tienen es_arma a cero, y si es así, borrar el tipo
                if (self::consultartotal($idprodps, 'es_arma') == 0) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=5 and id_product='.$idprodps.' and id_feature_value=10100');
                    self::ProcesarTipoProducto($data, $idprodps);
                }
            }
        }

        if ($tipoproducto == 10101) { // es cartucho

            if ($data['es_cartucho'] == 0) {
                if (self::consultartotal($idprodps, 'es_cartucho') == 0) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=5 and id_product='.$idprodps.' and id_feature_value=10101');
                    self::ProcesarTipoProducto($data, $idprodps);
                }
            }
        }

        if ($tipoproducto == 10102) { // es arma fogueo

            if ($data['es_arma_fogueo'] == 0) {
                if (self::consultartotal($idprodps, 'es_arma_fogueo') == 0) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=5 and id_product='.$idprodps.' and id_feature_value=10102');
                    self::ProcesarTipoProducto($data, $idprodps);
                }
            }
        }
    }

    public static function gettipoproducto($idprodpresta)
    {

        if (''.$idprodpresta == '') {
            return 0;
        } else {

            $id_feature_value = ''.Db::getInstance()->getValue('SELECT id_feature_value FROM aalv_feature_product WHERE id_feature=5 and id_product='.$idprodpresta);

            if ($id_feature_value == '') {
                return 0;
            } else {
                return (int) $id_feature_value;
            }
        }
    }

    public static function consultartotal($idprodps, $campo)
    {

        $total = Db::getInstance()->getValue('SELECT ifnull(sum('.$campo.'),0) FROM aalv_combinaciones_import where id_product_attribute in (SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product='.$idprodps.')');
        $totalp = ''.Db::getInstance()->getValue('SELECT '.$campo.' FROM aalv_combinacionunica_import WHERE id_product='.$idprodps);
        if ($totalp != '') {
            $total = $total + (int) $totalp;
        }

        return $total;
    }

    public static function getIdIva($iva = 21)
    {
        return Db::getInstance()->getValue("SELECT id_tax_rules_group FROM aalv_tax_rules_group atrg WHERE active = 1 AND deleted = 0 AND `name` LIKE '%".$iva."%'");
    }

    public static function getPaisPrestashop($data)
    {
        if ($data['codigo_iso_pais']) {
            return Db::getInstance()->getValue("SELECT id_country FROM aalv_country WHERE iso_code = '".$data['codigo_iso_pais']."'");
        } else {
            switch ($data['idregpais']) {
                case 2: // Portugal
                    return 15;

                case 1: // España
                default:
                    return 0; // Todos los países
            }
        }
    }

    public static function crearFeatureValue($idFeature, $value, $custom)
    {

        $idFeatureValue = Db::getInstance()->getValue('
                    SELECT fv.`id_feature_value`
                    FROM '._DB_PREFIX_.'feature_value fv
                    LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.`id_feature_value` = fv.`id_feature_value` AND fvl.`id_lang` = 1)
                    WHERE `value` = \''.pSQL($value).'\'
                    AND fv.`id_feature` = '.(int) $idFeature.'
                    AND fv.`custom` = '.$custom.'
                    GROUP BY fv.`id_feature_value`');
        if ($idFeatureValue) {
            return (int) $idFeatureValue;
        } else {
            self::sendmail('Entra en crearFeatureValue() HORA: '.date('Y-m-d H:i:s'));
            $feature_value = new FeatureValue;
            $feature_value->id_feature = (int) $idFeature;
            $feature_value->custom = $custom;
            $feature_value->value = array_fill_keys(Language::getIDs(false), $value);

            $feature_value->add();

            return (int) $feature_value->id;
        }
    }

    public static function getidioma($idioma)
    {
        $codidioma = 1;

        if ($idioma == 'en') {
            $codidioma = 2;
        }

        if ($idioma == 'fr') {
            $codidioma = 3;
        }

        if ($idioma == 'pt') {
            $codidioma = 4;
        }

        if ($idioma == 'de') {
            $codidioma = 5;
        }

        if ($idioma == 'it') {
            $codidioma = 5;
        }

        return $codidioma;
    }

    public static function esportestandard($porte)
    {

        $portestandard = [];

        $portestandard[] = 'A';
        $portestandard[] = 'CM1';
        $portestandard[] = 'B';
        $portestandard[] = 'AA';
        $portestandard[] = 'ALEMANIA STANDARD';
        $portestandard[] = 'ALEMANIA  EXPRESS';
        $portestandard[] = 'AUSTRIA STANDARD';
        $portestandard[] = 'AUSTRIA EXPRESS';
        $portestandard[] = 'BELGICA STANDARD';
        $portestandard[] = 'BELGICA EXPRESS';
        $portestandard[] = 'CHIPRE STANDARD';
        $portestandard[] = 'CHIPRE  EXPRESS';
        $portestandard[] = 'DINAMARCA STANDARD';
        $portestandard[] = 'DINAMARCA  EXPRESS';
        $portestandard[] = 'ESLOVAQUIA STANDARD';
        $portestandard[] = 'ESLOVAQUIA  EXPRESS';
        $portestandard[] = 'ESLOVENIA STANDARD';
        $portestandard[] = 'ESLOVENIA  EXPRESS';
        $portestandard[] = 'ESTONIA STANDARD';
        $portestandard[] = 'ESTONIA  EXPRESS';
        $portestandard[] = 'FINLANDIA STANDARD';
        $portestandard[] = 'FINLANDIA  EXPRESS';
        $portestandard[] = 'FRANCIA STANDARD';
        $portestandard[] = 'FRANCIA EXPRESS';
        $portestandard[] = 'GRECIA STANDARD';
        $portestandard[] = 'GRECIA  EXPRESS';
        $portestandard[] = 'HUNGRIA STANDARD';
        $portestandard[] = 'HUNGRIA  EXPRESS';
        $portestandard[] = 'IRLANDA STANDARD';
        $portestandard[] = 'IRLANDA  EXPRESS';
        $portestandard[] = 'ITALIA STANDARD';
        $portestandard[] = 'ITALIA EXPRESS';
        $portestandard[] = 'LETONIA STANDARD';
        $portestandard[] = 'LETONIA  EXPRESS';
        $portestandard[] = 'LITUANIA STANDARD';
        $portestandard[] = 'LITUANIA  EXPRESS';
        $portestandard[] = 'LUXEMBURGO STANDARD';
        $portestandard[] = 'LUXEMBURGO  EXPRESS';
        $portestandard[] = 'MALTA STANDARD';
        $portestandard[] = 'MALTA  EXPRESS';
        $portestandard[] = 'HOLANDA STANDARD';
        $portestandard[] = 'HOLANDA  EXPRESS';
        $portestandard[] = 'POLONIA STANDARD';
        $portestandard[] = 'POLONIA  EXPRESS';
        $portestandard[] = 'REINO UNIDO STANDARD';
        $portestandard[] = 'REINO UNIDO  EXPRESS';
        $portestandard[] = 'REP. CHECA STANDARD';
        $portestandard[] = 'REP. CHECA  EXPRESS';
        $portestandard[] = 'SUECIA STANDARD';
        $portestandard[] = 'SUECIA  EXPRESS';
        $portestandard[] = 'GUERNSEY STANDARD';
        $portestandard[] = 'JERSEY STANDARD';
        $portestandard[] = 'SUIZA STANDARD';
        $portestandard[] = 'SUIZA EXPRESS';
        $portestandard[] = 'RUMANIA STANDARD';
        $portestandard[] = 'RUMANIA EXPRESS';
        $portestandard[] = 'NORUEGA STANDARD';
        $portestandard[] = 'NORUEGA EXPRESS';
        $portestandard[] = 'BULGARIA STANDARD';
        $portestandard[] = 'BULGARIA  EXPRESS';
        $portestandard[] = 'TURQUIA STANDARD';
        $portestandard[] = 'TURQUIA EXPRESS';
        $portestandard[] = 'RUSIA EXPRESS';
        $portestandard[] = 'LIECHTESTEIN EXPRESS';
        $portestandard[] = 'MONACO EXPRESS';
        $portestandard[] = 'ISLANDIA EXPRESS';
        $portestandard[] = 'CROACIA EXPRESS';
        $portestandard[] = 'ALBANIA EXPRESS';
        $portestandard[] = 'BOSNIA EXPRESS';
        $portestandard[] = 'MONTENEGRO EXPRESS';
        $portestandard[] = 'MACEDONIA EXPRESS';
        $portestandard[] = 'SERVIA EXPRESS';
        $portestandard[] = 'BIELORRUSIA EXPRESS';
        $portestandard[] = 'MOLDAVIA EXPRESS';
        $portestandard[] = 'UCRANIA EXPRESS';
        $portestandard[] = 'ARMENIA EXPRESS';
        $portestandard[] = 'GEORGIA EXPRESS';
        $portestandard[] = 'COSTA RICA EXPRESS';
        $portestandard[] = 'EL SALVADOR EXPRESS';
        $portestandard[] = 'GUATEMALA EXPRESS';
        $portestandard[] = 'HONDURAS EXPRESS';
        $portestandard[] = 'NICARAGUA EXPRESS';
        $portestandard[] = 'PANAMA EXPRESS';
        $portestandard[] = 'BRASIL EXPRESS';
        $portestandard[] = 'ARGENTINA EXPRESS';
        $portestandard[] = 'PERU EXPRESS';
        $portestandard[] = 'COLOMBIA EXPRESS';
        $portestandard[] = 'BOLIVIA EXPRESS';
        $portestandard[] = 'VENEZUELA EXPRESS';
        $portestandard[] = 'CHILE EXPRESS';
        $portestandard[] = 'PARAGUAY EXPRESS';
        $portestandard[] = 'ECUADOR EXPRESS';
        $portestandard[] = 'URUGUAY EXPRESS';
        $portestandard[] = 'MEXICO EXPRESS';
        $portestandard[] = 'ESTADOS UNIDOS EXPRESS';
        $portestandard[] = 'CANADA EXPRESS';
        $portestandard[] = 'REP. DOMINICANA EXPRESS';
        $portestandard[] = 'CUBA EXPRESS';
        $portestandard[] = 'PUERTO RICO EXPRESS';
        $portestandard[] = 'JAMAICA EXPRESS';
        $portestandard[] = 'CORCEGA STANDARD';
        $portestandard[] = 'IRLANDA DEL NORTE STANDARD';
        $portestandard[] = 'HONG KONG';
        $portestandard[] = 'INDONESIA EXPRESS';
        $portestandard[] = 'JAPON EXPRESS';
        $portestandard[] = 'SINGAPUR EXPRESS';
        $portestandard[] = 'TAIWAN EXPRESS';
        $portestandard[] = 'ARABIA SAUDI EXPRESS';
        $portestandard[] = 'ARGELIA EXPRESS';
        $portestandard[] = 'EGIPTO EXPRESS';
        $portestandard[] = 'EMIRATOS ARABES UNIDOS EXPRESS';
        $portestandard[] = 'CAMERUN EXPRESS';
        $portestandard[] = 'COSTA DE MARFIL EXPRESS';
        $portestandard[] = 'KUWAIT EXPRESS';
        $portestandard[] = 'MARRUECOS EXPRESS';
        $portestandard[] = 'QATAR EXPRESS';
        $portestandard[] = 'SURAFRICA EXPRESS';
        $portestandard[] = 'TUNEZ EXPRESS';
        $portestandard[] = 'ANGOLA EXPRESS';
        $portestandard[] = 'AUSTRALIA EXPRESS';
        $portestandard[] = 'CHINA EXPRESS';
        $portestandard[] = 'FILIPINAS EXPRESS';
        $portestandard[] = 'GUINEA EXPRESS';
        $portestandard[] = 'INDIA EXPRESS';
        $portestandard[] = 'ISRAEL EXPRESS';
        $portestandard[] = 'JORDANIA EXPRESS';
        $portestandard[] = 'KENIA EXPRESS';
        $portestandard[] = 'LIBANO EXPRESS';
        $portestandard[] = 'MADAGASCAR EXPRESS';
        $portestandard[] = 'MAURITANIA EXPRESS';
        $portestandard[] = 'NUEVA ZELANDA EXPRESS';
        $portestandard[] = 'SENEGAL EXPRESS';
        $portestandard[] = 'UGANDA EXPRESS';
        $portestandard[] = 'VIETNAM EXPRESS';
        $portestandard[] = 'ZAMBIA EXPRESS';
        $portestandard[] = 'ZIMBAWE EXPRESS';

        return in_array($porte, $portestandard);
    }

    public static function getpaisps($idorigen)
    {

        $idreturn = 0;

        if ($idorigen == 53) {
            $idreturn = 40;
        }
        if ($idorigen == 125) {
            $idreturn = 215;
        }
        if ($idorigen == 155) {
            $idreturn = 229;
        }
        if ($idorigen == 94) {
            $idreturn = 43;
        }
        if ($idorigen == 93) {
            $idreturn = 42;
        }
        if ($idorigen == 56) {
            $idreturn = 228;
        }
        if ($idorigen == 65) {
            $idreturn = 45;
        }
        if ($idorigen == 133) {
            $idreturn = 41;
        }
        if ($idorigen == 77) {
            $idreturn = 44;
        }
        if ($idorigen == 13) {
            $idreturn = 2;
        }
        if ($idorigen == 135) {
            $idreturn = 24;
        }
        if ($idorigen == 95) {
            $idreturn = 46;
        }
        if ($idorigen == 66) {
            $idreturn = 47;
        }
        if ($idorigen == 57) {
            $idreturn = 231;
        }
        if ($idorigen == 98) {
            $idreturn = 51;
        }
        if ($idorigen == 162) {
            $idreturn = 50;
        }
        if ($idorigen == 14) {
            $idreturn = 3;
        }
        if ($idorigen == 168) {
            $idreturn = 60;
        }
        if ($idorigen == 42) {
            $idreturn = 233;
        }
        if ($idorigen == 163) {
            $idreturn = 49;
        }
        if ($idorigen == 169) {
            $idreturn = 62;
        }
        if ($idorigen == 164) {
            $idreturn = 54;
        }
        if ($idorigen == 96) {
            $idreturn = 55;
        }
        if ($idorigen == 167) {
            $idreturn = 59;
        }
        if ($idorigen == 80) {
            $idreturn = 34;
        }
        if ($idorigen == 76) {
            $idreturn = 58;
        }
        if ($idorigen == 97) {
            $idreturn = 48;
        }
        if ($idorigen == 170) {
            $idreturn = 56;
        }
        if ($idorigen == 166) {
            $idreturn = 57;
        }
        if ($idorigen == 62) {
            $idreturn = 52;
        }
        if ($idorigen == 69) {
            $idreturn = 53;
        }
        if ($idorigen == 91) {
            $idreturn = 4;
        }
        if ($idorigen == 215) {
            $idreturn = 71;
        }
        if ($idorigen == 213) {
            $idreturn = 66;
        }
        if ($idorigen == 214) {
            $idreturn = 72;
        }
        if ($idorigen == 39) {
            $idreturn = 19;
        }
        if ($idorigen == 127) {
            $idreturn = 32;
        }
        if ($idorigen == 82) {
            $idreturn = 68;
        }
        if ($idorigen == 126) {
            $idreturn = 64;
        }
        if ($idorigen == 136) {
            $idreturn = 5;
        }
        if ($idorigen == 79) {
            $idreturn = 69;
        }
        if ($idorigen == 70) {
            $idreturn = 73;
        }
        if ($idorigen == 110) {
            $idreturn = 75;
        }
        if ($idorigen == 156) {
            $idreturn = 65;
        }
        if ($idorigen == 15) {
            $idreturn = 76;
        }
        if ($idorigen == 34) {
            $idreturn = 16;
        }
        if ($idorigen == 12) {
            $idreturn = 1;
        }
        if ($idorigen == 236) {
            $idreturn = 77;
        }
        if ($idorigen == 16) {
            $idreturn = 20;
        }
        if ($idorigen == 99) {
            $idreturn = 78;
        }
        if ($idorigen == 92) {
            $idreturn = 79;
        }
        if ($idorigen == 123) {
            $idreturn = 38;
        }
        if ($idorigen == 84) {
            $idreturn = 81;
        }
        if ($idorigen == 19) {
            $idreturn = 86;
        }
        if ($idorigen == 124) {
            $idreturn = 82;
        }
        if ($idorigen == 177) {
            $idreturn = 85;
        }
        if ($idorigen == 1) {
            $idreturn = 6;
        }
        if ($idorigen == 10) {
            $idreturn = 244;
        }
        if ($idorigen == 9) {
            $idreturn = 243;
        }
        if ($idorigen == 8) {
            $idreturn = 242;
        }
        if ($idorigen == 178) {
            $idreturn = 87;
        }
        if ($idorigen == 20) {
            $idreturn = 7;
        }
        if ($idorigen == 179) {
            $idreturn = 90;
        }
        if ($idorigen == 203) {
            $idreturn = 145;
        }
        if ($idorigen == 21) {
            $idreturn = 8;
        }
        if ($idorigen == 180) {
            $idreturn = 91;
        }
        if ($idorigen == 33) {
            $idreturn = 17;
        }
        if ($idorigen == 183) {
            $idreturn = 95;
        }
        if ($idorigen == 67) {
            $idreturn = 93;
        }
        if ($idorigen == 88) {
            $idreturn = 238;
        }
        if ($idorigen == 37) {
            $idreturn = 101;
        }
        if ($idorigen == 182) {
            $idreturn = 94;
        }
        if ($idorigen == 112) {
            $idreturn = 97;
        }
        if ($idorigen == 181) {
            $idreturn = 92;
        }
        if ($idorigen == 138) {
            $idreturn = 102;
        }
        if ($idorigen == 101) {
            $idreturn = 98;
        }
        if ($idorigen == 184) {
            $idreturn = 84;
        }
        if ($idorigen == 22) {
            $idreturn = 9;
        }
        if ($idorigen == 72) {
            $idreturn = 100;
        }
        if ($idorigen == 185) {
            $idreturn = 103;
        }
        if ($idorigen == 85) {
            $idreturn = 104;
        }
        if ($idorigen == 117) {
            $idreturn = 22;
        }
        if ($idorigen == 73) {
            $idreturn = 107;
        }
        if ($idorigen == 58) {
            $idreturn = 74;
        }
        if ($idorigen == 113) {
            $idreturn = 105;
        }
        if ($idorigen == 186) {
            $idreturn = 142;
        }
        if ($idorigen == 118) {
            $idreturn = 110;
        }
        if ($idorigen == 24) {
            $idreturn = 26;
        }
        if ($idorigen == 140) {
            $idreturn = 29;
        }
        if ($idorigen == 139) {
            $idreturn = 109;
        }
        if ($idorigen == 187) {
            $idreturn = 112;
        }
        if ($idorigen == 188) {
            $idreturn = 111;
        }
        if ($idorigen == 49) {
            $idreturn = 108;
        }
        if ($idorigen == 25) {
            $idreturn = 10;
        }
        if ($idorigen == 38) {
            $idreturn = 115;
        }
        if ($idorigen == 114) {
            $idreturn = 114;
        }
        if ($idorigen == 141) {
            $idreturn = 116;
        }
        if ($idorigen == 119) {
            $idreturn = 11;
        }
        if ($idorigen == 142) {
            $idreturn = 118;
        }
        if ($idorigen == 192) {
            $idreturn = 122;
        }
        if ($idorigen == 171) {
            $idreturn = 63;
        }
        if ($idorigen == 193) {
            $idreturn = 119;
        }
        if ($idorigen == 174) {
            $idreturn = 70;
        }
        if ($idorigen == 106) {
            $idreturn = 178;
        }
        if ($idorigen == 175) {
            $idreturn = 120;
        }
        if ($idorigen == 176) {
            $idreturn = 28;
        }
        if ($idorigen == 128) {
            $idreturn = 121;
        }
        if ($idorigen == 100) {
            $idreturn = 234;
        }
        if ($idorigen == 191) {
            $idreturn = 117;
        }
        if ($idorigen == 194) {
            $idreturn = 123;
        }
        if ($idorigen == 143) {
            $idreturn = 125;
        }
        if ($idorigen == 107) {
            $idreturn = 179;
        }
        if ($idorigen == 45) {
            $idreturn = 129;
        }
        if ($idorigen == 223) {
            $idreturn = 195;
        }
        if ($idorigen == 196) {
            $idreturn = 127;
        }
        if ($idorigen == 195) {
            $idreturn = 126;
        }
        if ($idorigen == 27) {
            $idreturn = 130;
        }
        if ($idorigen == 28) {
            $idreturn = 12;
        }
        if ($idorigen == 26) {
            $idreturn = 124;
        }
        if ($idorigen == 197) {
            $idreturn = 128;
        }
        if ($idorigen == 129) {
            $idreturn = 151;
        }
        if ($idorigen == 48) {
            $idreturn = 147;
        }
        if ($idorigen == 63) {
            $idreturn = 146;
        }
        if ($idorigen == 59) {
            $idreturn = 149;
        }
        if ($idorigen == 144) {
            $idreturn = 133;
        }
        if ($idorigen == 189) {
            $idreturn = 139;
        }
        if ($idorigen == 60) {
            $idreturn = 132;
        }
        if ($idorigen == 201) {
            $idreturn = 137;
        }
        if ($idorigen == 165) {
            $idreturn = 61;
        }
        if ($idorigen == 204) {
            $idreturn = 148;
        }
        if ($idorigen == 105) {
            $idreturn = 140;
        }
        if ($idorigen == 145) {
            $idreturn = 141;
        }
        if ($idorigen == 29) {
            $idreturn = 138;
        }
        if ($idorigen == 202) {
            $idreturn = 35;
        }
        if ($idorigen == 200) {
            $idreturn = 136;
        }
        if ($idorigen == 199) {
            $idreturn = 134;
        }
        if ($idorigen == 89) {
            $idreturn = 144;
        }
        if ($idorigen == 198) {
            $idreturn = 135;
        }
        if ($idorigen == 157) {
            $idreturn = 152;
        }
        if ($idorigen == 205) {
            $idreturn = 153;
        }
        if ($idorigen == 208) {
            $idreturn = 158;
        }
        if ($idorigen == 158) {
            $idreturn = 31;
        }
        if ($idorigen == 74) {
            $idreturn = 157;
        }
        if ($idorigen == 30) {
            $idreturn = 13;
        }
        if ($idorigen == 159) {
            $idreturn = 23;
        }
        if ($idorigen == 207) {
            $idreturn = 155;
        }
        if ($idorigen == 206) {
            $idreturn = 154;
        }
        if ($idorigen == 146) {
            $idreturn = 27;
        }
        if ($idorigen == 209) {
            $idreturn = 162;
        }
        if ($idorigen == 75) {
            $idreturn = 166;
        }
        if ($idorigen == 78) {
            $idreturn = 169;
        }
        if ($idorigen == 212) {
            $idreturn = 167;
        }
        if ($idorigen == 137) {
            $idreturn = 170;
        }
        if ($idorigen == 210) {
            $idreturn = 163;
        }
        if ($idorigen == 31) {
            $idreturn = 14;
        }
        if ($idorigen == 160) {
            $idreturn = 181;
        }
        if ($idorigen == 111) {
            $idreturn = 172;
        }
        if ($idorigen == 11) {
            $idreturn = 15;
        }
        if ($idorigen == 36) {
            $idreturn = 245;
        }
        if ($idorigen == 211) {
            $idreturn = 164;
        }
        if ($idorigen == 83) {
            $idreturn = 168;
        }
        if ($idorigen == 172) {
            $idreturn = 173;
        }
        if ($idorigen == 40) {
            $idreturn = 36;
        }
        if ($idorigen == 61) {
            $idreturn = 188;
        }
        if ($idorigen == 44) {
            $idreturn = 175;
        }
        if ($idorigen == 216) {
            $idreturn = 176;
        }
        if ($idorigen == 122) {
            $idreturn = 186;
        }
        if ($idorigen == 190) {
            $idreturn = 192;
        }
        if ($idorigen == 219) {
            $idreturn = 189;
        }
        if ($idorigen == 225) {
            $idreturn = 196;
        }
        if ($idorigen == 35) {
            $idreturn = 18;
        }
        if ($idorigen == 120) {
            $idreturn = 25;
        }
        if ($idorigen == 18) {
            $idreturn = 191;
        }
        if ($idorigen == 17) {
            $idreturn = 37;
        }
        if ($idorigen == 220) {
            $idreturn = 190;
        }
        if ($idorigen == 46) {
            $idreturn = 184;
        }
        if ($idorigen == 147) {
            $idreturn = 187;
        }
        if ($idorigen == 222) {
            $idreturn = 193;
        }
        if ($idorigen == 87) {
            $idreturn = 197;
        }
        if ($idorigen == 218) {
            $idreturn = 185;
        }
        if ($idorigen == 71) {
            $idreturn = 83;
        }
        if ($idorigen == 221) {
            $idreturn = 200;
        }
        if ($idorigen == 224) {
            $idreturn = 199;
        }
        if ($idorigen == 173) {
            $idreturn = 67;
        }
        if ($idorigen == 230) {
            $idreturn = 33;
        }
        if ($idorigen == 153) {
            $idreturn = 204;
        }
        if ($idorigen == 228) {
            $idreturn = 202;
        }
        if ($idorigen == 229) {
            $idreturn = 80;
        }
        if ($idorigen == 231) {
            $idreturn = 210;
        }
        if ($idorigen == 132) {
            $idreturn = 208;
        }
        if ($idorigen == 154) {
            $idreturn = 206;
        }
        if ($idorigen == 43) {
            $idreturn = 209;
        }
        if ($idorigen == 109) {
            $idreturn = 207;
        }
        if ($idorigen == 232) {
            $idreturn = 212;
        }
        if ($idorigen == 121) {
            $idreturn = 201;
        }
        if ($idorigen == 227) {
            $idreturn = 203;
        }
        if ($idorigen == 64) {
            $idreturn = 214;
        }
        if ($idorigen == 148) {
            $idreturn = 213;
        }
        if ($idorigen == 90) {
            $idreturn = 21;
        }
        if ($idorigen == 86) {
            $idreturn = 216;
        }
        if ($idorigen == 233) {
            $idreturn = 217;
        }
        if ($idorigen == 47) {
            $idreturn = 106;
        }
        if ($idorigen == 108) {
            $idreturn = 182;
        }
        if ($idorigen == 81) {
            $idreturn = 219;
        }
        if ($idorigen == 104) {
            $idreturn = 221;
        }
        if ($idorigen == 102) {
            $idreturn = 222;
        }
        if ($idorigen == 149) {
            $idreturn = 220;
        }
        if ($idorigen == 234) {
            $idreturn = 218;
        }
        if ($idorigen == 217) {
            $idreturn = 183;
        }
        if ($idorigen == 235) {
            $idreturn = 225;
        }
        if ($idorigen == 131) {
            $idreturn = 30;
        }
        if ($idorigen == 150) {
            $idreturn = 226;
        }
        if ($idorigen == 151) {
            $idreturn = 227;
        }

        return $idreturn;
    }

    public static function safeName($sString)
    {
        $sReturn = $sString;

        $sReturn = strtr($sReturn, "()!$'?: ,&+-/.", '');

        $a = ['', '', '', '', '', '', '', '¥', 'µ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'];
        $b = ['S', 'O', 'Z', 's', 'o', 'z', 'Y', 'Y', 'u', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'];
        $sReturn = str_replace($a, $b, $sReturn);
        $sReturn = trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($sReturn))));

        $sReturn = str_replace(' ', '_', $sReturn);

        return $sReturn;
    }

    public static function getCategorypath($id_category)
    {
        $category_path = '';

        if ((int) $id_category != (int) Configuration::get('PS_HOME_CATEGORY') && (int) $id_category != 0) {
            $sql = 'SELECT c.`id_category`, c.`id_parent`, cl.`name`, c.`active`
                        FROM `'._DB_PREFIX_.'category` c
                        INNER JOIN `'._DB_PREFIX_.'category_shop` cs ON cs.`id_category`=c.`id_category` AND cs.`id_shop`='.Context::getContext()->shop->id.'
                        INNER JOIN `'._DB_PREFIX_.'category_lang` cl ON cl.`id_category`=c.`id_category` AND cl.`id_shop`='.Context::getContext()->shop->id.' AND cl.`id_lang`='.Context::getContext()->language->id.'
                        WHERE c.`id_category`='.(int) $id_category;
            $category = Db::getInstance()->getRow($sql);
            if ($category) {
                if ((int) $category['id_parent'] != (int) Configuration::get('PS_HOME_CATEGORY') && (int) $category['id_parent'] != 0 && (int) $category['active'] == 1) {
                    $category_path .= self::getCategorypath($category['id_parent']);
                }

                $category_path .= '('.$category['id_category'].') '.$category['name'].' / ';
            }
        }

        return $category_path;
    }

    // ///////////////////////////////////////// ALSERNET /////////////////////////////////////////////////////
    public static function TodoFeature($id_product, $data)
    {
        for ($i = 0; $i < 3; $i++) {
            $buscar_feature = Db::getInstance()->ExecuteS('select id_feature_value, id_feature from aalv_feature_product where id_feature in (11,12,13,14) and id_product = '.$id_product);
            if (count($buscar_feature) != 0) {
                foreach ($buscar_feature as $keyy => $valuee) {
                    $buscar = Db::getInstance()->ExecuteS('select value from aalv_feature_value_lang where id_feature_value = '.$valuee['id_feature_value']);
                    if (count($buscar) == 0) {
                        // echo "Le falta 5 idioma el id_product: ".$id_product."\n";
                        self::InsertFeature_value_V2($valuee['id_feature_value'], $valuee['id_feature']);
                        self::InsertFeature_value_lang($valuee['id_feature_value'], $data[$valuee['id_feature']]);
                    }/*elseif(count($buscar) != 5){
                        echo "Le falta 1 idioma el id_feature_value: ".$valuee['id_feature_value']."\n";
                    }*/
                }
                if (count($buscar_feature) != 4) {
                    // echo count($buscar_feature)." - FALTAN FEATURE EN ID_PRODUCTO: ".$id_product."\n";
                    self::BuscarAgregar($id_product, $data);
                }
            } else {
                // echo "Revisar este id_producto: ".$id_product."\n";
                self::BuscarAgregar($id_product, $data);
            }
        }
        foreach ($data as $key => $value) {
            self::AsignarTipoProducto($value, $id_product);
        }
    }

    public static function InsertFeature_value_V2($id_feature_value, $id_feature)
    {
        Db::getInstance()->Execute('insert into aalv_feature_value (id_feature_value,id_feature,custom) VALUE ('.$id_feature_value.','.$id_feature.',1)');
    }

    public static function InsertFeature_value_lang($id_feature_value, $value)
    {
        $ssql = 'INSERT INTO aalv_feature_value_lang (id_feature_value,id_lang,value) VALUES ';
        for ($i = 1; $i < 6; $i++) {
            $ssql .= '('.$id_feature_value.','.$i.','.$value.'),';
        }
        // Obtener la longitud del string
        $longitud = strlen($ssql);

        // Obtener el último parámetro
        $ultimoParametro = substr($ssql, $longitud - 1);

        if ($ultimoParametro == ',') {
            $ssql = substr($ssql, 0, -1);
        }
        $ssql .= ';';
        Db::getInstance()->execute($ssql);
    }

    public static function BuscarAgregar($id_product, $vall)
    {
        $buscar_feat = Db::getInstance()->ExecuteS('SELECT
                                                        fl.id_feature,
                                                        fl.name AS feature_name,
                                                        fvl.value AS feature_value
                                                    FROM
                                                        aalv_feature_lang fl
                                                        LEFT JOIN aalv_feature_product fp ON fp.id_feature = fl.id_feature AND fp.id_product = '.$id_product.'
                                                        LEFT JOIN aalv_feature_value_lang fvl ON fp.id_feature_value = fvl.id_feature_value AND fvl.id_lang = 1
                                                    WHERE
                                                        fp.id_feature IS NULL
                                                        AND fl.id_lang = 1
                                                    order by fl.id_feature asc');
        foreach ($buscar_feat as $keyy => $valuue) {
            switch ($valuue['id_feature']) {
                // Categoria
                case '11':
                    $idnewsp = self::InsertFeature_value($valuue['id_feature']);
                    self::InsertFeature_product($valuue['id_feature'], $id_product, $idnewsp);
                    self::InsertFeature_value_lang($idnewsp, $vall[$valuue['id_feature']]);
                    self::validarInpost($vall[$valuue['id_feature']], $id_product);
                    break;

                    // Familia
                case '12':
                    $idnewsp = self::InsertFeature_value($valuue['id_feature']);
                    self::InsertFeature_product($valuue['id_feature'], $id_product, $idnewsp);
                    self::InsertFeature_value_lang($idnewsp, $vall[$valuue['id_feature']]);
                    self::validarInpost($vall[$valuue['id_feature']], $id_product);
                    break;

                    // Subfamilia
                case '13':
                    $idnewsp = self::InsertFeature_value($valuue['id_feature']);
                    self::InsertFeature_product($valuue['id_feature'], $id_product, $idnewsp);
                    self::InsertFeature_value_lang($idnewsp, $vall[$valuue['id_feature']]);
                    self::validarInpost($vall[$valuue['id_feature']], $id_product);
                    break;

                    // Grupo
                case '14':
                    $idnewsp = self::InsertFeature_value($valuue['id_feature']);
                    self::InsertFeature_product($valuue['id_feature'], $id_product, $idnewsp);
                    self::InsertFeature_value_lang($idnewsp, $vall[$valuue['id_feature']]);
                    self::validarInpost($vall[$valuue['id_feature']], $id_product);
                    break;
            }
        }
    }

    public static function InsertFeature_value($id_feature)
    {
        Db::getInstance()->Execute('insert into aalv_feature_value (id_feature,custom) VALUE ('.$id_feature.',1)');

        return Db::getInstance()->Insert_ID();
    }

    public static function InsertFeature_product($id_feature, $id_product, $id_feature_value)
    {
        Db::getInstance()->Execute('insert into aalv_feature_product
                                    (id_feature,id_product,id_feature_value)
                                    VALUE
                                    ('.$id_feature.','.$id_product.','.$id_feature_value.')');
    }

    public static function validarInpost($id, $id_product)
    {
        $datos = Db::getInstance()->ExecuteS("SELECT id_feature_value, c_f_sf_g_asignado FROM aalv_alsernet_exclude_product_type WHERE c_f_sf_g_asignado LIKE '%".$id."%'");
        foreach ($datos as $key => $value) {
            $existe = ''.Db::getInstance()->getValue('SELECT * FROM aalv_alsernet_exclude_product_inpost WHERE id_feature_value = '.$value['id_feature_value']);
            if ($existe != '') {
                $exe = explode(',', $value['c_f_sf_g_asignado']);
                foreach ($exe as $k => $val) {
                    if ($val == $id) {
                        $weight = Db::getInstance()->getValue('SELECT `weight` FROM aalv_product WHERE id_product = '.$id_product);
                        if ($weight != 30) {
                            Db::getInstance()->execute('UPDATE aalv_product SET `weight` = 30 WHERE id_product = '.$id_product);
                            // echo "\n >>>>> ACA \n";
                        }
                    }
                }
            } // else{
            //     $weight = Db::getInstance()->getValue("SELECT `weight` FROM aalv_product WHERE id_product = ".$id_product);
            //     if($weight == 30){
            //         Db::getInstance()->execute("UPDATE aalv_product SET `weight` = 0 WHERE id_product = ".$id_product);
            //     }
            // }
        }
    }

    public static function AsignarTipoProducto($c_f_sf_g_asignado, $id_product)
    {
        $buscar = Db::getInstance()->ExecuteS("select id_feature_value from aalv_alsernet_exclude_product_type where FIND_IN_SET('".$c_f_sf_g_asignado."', c_f_sf_g_asignado) > 0 ");
        if ($buscar) {
            $buscar_asignado = Db::getInstance()->ExecuteS('select * from aalv_feature_product where id_product = '.$id_product.' and id_feature_value = '.$buscar[0]['id_feature_value']);
            if (! $buscar_asignado) {
                Db::getInstance()->execute('insert into aalv_feature_product
                                        (id_feature,id_product,id_feature_value)
                                        value
                                        (5,'.$id_product.','.$buscar[0]['id_feature_value'].');');
            }
        }
    }
}
