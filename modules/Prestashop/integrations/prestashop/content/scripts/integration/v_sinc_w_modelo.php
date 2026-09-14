<?php

class v_sinc_w_modeloClass
{
    public function Procesar_v_sinc_w_modelo($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            $idmodelo = $data['id'];
            $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);

            $nombre = trim(str_replace('#', '', strip_tags($data['nombre'])));
            $descripcion = base64_decode($data['descripcion']);
            $descripcion = utf8_encode($descripcion);

            $descripciondestacado = base64_decode($data['descripcion_destacado']);
            $descripciondestacado = utf8_encode($descripciondestacado);

            $sufixtitle = ' | Comprar online | Alvarez';

            $sufixdescription = [];
            $sufixdescription[1] = ' al mejor precio en Deportes Álvarez. Compra online tu equipo de caza en Álvarez y ✅ no te pierdas nuestras ofertas ✅';
            $sufixdescription[2] = " at the best price at Alvarez's Sports. Purchase online your hunting en Álvarez y ✅ don't miss our offers ✅";
            $sufixdescription[3] = ' au meilleur prix à Álvarez Sports. Achetez votre équipe chasse en ligne à Álvarez et ✅ ne manquez pas nos offres ✅';
            $sufixdescription[4] = ' ao melhor preço em Desportos Álvarez. Compre online o seu equipamento de caca em Álvarez e ✅ não perca as nossas ofertas ✅';
            $sufixdescription[5] = ' zum besten Preis in Álvarez Sports. Kaufen Sie Ihr Team von jagd online in Álvarez und ✅ verpassen Sie nicht unsere Angebote ✅';
            $sufixdescription[6] = ' al miglior prezzo su Deportes Álvarez. Acquista online il tuo materiale da caccia in Álvarez e ✅ non perdere le nostre offerte ✅.';

            if ($idprodps != '') {
                // update
                $product = new Product((int) $idprodps);

                $actualizarnombre = ($product->name[1] != $nombre);
                $actualizardescripcion = ($product->description[1] != $descripcion);

                // Rellenamos idiomas
                $sql = 'SELECT * FROM aalv_lang WHERE id_lang=1'; // Solo español
                $idiomas = Db::getInstance()->ExecuteS($sql);
                foreach ($idiomas as $idioma) {
                    $product->name[$idioma['id_lang']] = ($actualizarnombre) ? $nombre : $product->name[$idioma['id_lang']];
                    $product->link_rewrite[$idioma['id_lang']] = Tools::link_rewrite($product->name[$idioma['id_lang']]); // Parece que se quiere mantener el slug inicial. Sino, debería de usarse $nombre

                    $product->description[$idioma['id_lang']] = ($actualizardescripcion) ? $descripcion : $product->description[$idioma['id_lang']];

                    $product->meta_title[$idioma['id_lang']] = ($actualizarnombre) ? $nombre.$sufixtitle : $product->meta_title[$idioma['id_lang']];
                    $product->meta_description[$idioma['id_lang']] = ($actualizarnombre) ? $nombre.$sufixdescription[$idioma['id_lang']] : $product->meta_description[$idioma['id_lang']];
                }

                // crear marca si no existe
                $marcaExists = Manufacturer::manufacturerExists($data['id_marca']);
                if ($marcaExists) {
                } else {

                    if (''.$data['id_marca'] != '') {
                        $marca = new Manufacturer;
                        $marca->force_id = true;
                        $marca->id = $data['id_marca'];
                        $marca->name = $data['id_marca'];
                        $marca->active = true;
                        $marca->add();
                    }
                }

                if (''.$data['id_marca'] != '') {
                    $product->id_manufacturer = $data['id_marca'];
                }

                $product->id_tax_rules_group = auxiliares::getIdIva();

                $idfeatureestadosegundamano = Feature::addFeatureImport('Estado segunda mano');
                $idfeatureprecioconsultar = Feature::addFeatureImport('Precio a consultar');
                $idfeatureventatelefono = Feature::addFeatureImport('Venta teléfono');
                $idfeaturetextoproductosnovendibles = Feature::addFeatureImport('Texto productos no vendibles');

                $idfeatureestadosegundamanovalue = 0;
                $idfeatureprecioconsultarvalue = 0;
                $idfeatureventatelefonovalue = 0;
                $idfeaturetextoproductosnovendiblesvalue = 0;

                if (strip_tags($descripciondestacado) != '') {

                    $descripciondestacado = substr(strip_tags($descripciondestacado), 0, 4095);
                    if (strpos($descripciondestacado, '>')) {
                        $descripciondestacado = strip_tags('<'.$descripciondestacado);
                    }

                    $idfeatureestadosegundamanovalue = auxiliares::crearFeatureValue($idfeatureestadosegundamano, $descripciondestacado, 1);
                }
                if ($idfeatureestadosegundamanovalue != 0) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature='.$idfeatureestadosegundamano.' and id_product='.$product->id);
                    $product->addFeatureProductImport($product->id, $idfeatureestadosegundamano, $idfeatureestadosegundamanovalue);
                }

                if ((''.$data['precio_consultar_ficha']) == '') {
                    $preconsul = '0';
                } else {
                    $preconsul = $data['precio_consultar_ficha'];
                }

                $idfeatureprecioconsultarvalue = auxiliares::crearFeatureValue($idfeatureprecioconsultar, $preconsul, 0);
                if ($idfeatureprecioconsultarvalue != 0) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature='.$idfeatureprecioconsultar.' and id_product='.$product->id);
                    $product->addFeatureProductImport($product->id, $idfeatureprecioconsultar, $idfeatureprecioconsultarvalue);
                }

                if (''.$data['venta_telefono'] == '') {
                    $data['venta_telefono'] = 0;
                }

                $idfeatureventatelefonovalue = auxiliares::crearFeatureValue($idfeatureventatelefono, $data['venta_telefono'], 0);
                if ($idfeatureventatelefonovalue != 0) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature='.$idfeatureventatelefono.' and id_product='.$product->id);
                    $product->addFeatureProductImport($product->id, $idfeatureventatelefono, $idfeatureventatelefonovalue);
                }

                if ($data['texto_productos_no_vendibles'] == 'solo_tienda') {

                    $idfeaturetextoproductosnovendiblesvalue = auxiliares::crearFeatureValue($idfeaturetextoproductosnovendibles, $data['texto_productos_no_vendibles'], 0);
                    if ($idfeaturetextoproductosnovendiblesvalue != 0) {
                        Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature='.$idfeaturetextoproductosnovendibles.' and id_product='.$product->id);
                        $product->addFeatureProductImport($product->id, $idfeaturetextoproductosnovendibles, $idfeaturetextoproductosnovendiblesvalue);
                    }
                }

                if (($data['texto_productos_no_vendibles'] != '') || ($data['precio_consultar_ficha'] == 1) || ($data['venta_telefono'] == 1)) {
                    $product->available_for_order = false;
                    $product->show_price = false;
                    $product->update();
                } else {
                    $product->available_for_order = true;
                    $product->show_price = true;
                    $product->update();
                }

                auxiliares::procesarcombinaciones($product->id);

                $product->update();

                Db::getInstance()->execute('UPDATE aalv_product_lang SET translated = 0 WHERE id_product = '.$idprodps);
            } else {
                // crear
                $product = new Product;
                $product->name[1] = $nombre;
                $product->description[1] = $descripcion;
                $product->link_rewrite[1] = Tools::link_rewrite($product->name[1]);
                $product->meta_title[1] = $nombre.$sufixtitle;
                $product->meta_description[1] = $nombre.$sufixdescription[1];

                $product->name[2] = $nombre;
                $product->description[2] = $descripcion;
                $product->link_rewrite[2] = Tools::link_rewrite($product->name[2]);
                $product->meta_title[2] = $nombre.$sufixtitle;
                $product->meta_description[2] = $nombre.$sufixdescription[2];

                $product->name[3] = $nombre;
                $product->description[3] = $descripcion;
                $product->link_rewrite[3] = Tools::link_rewrite($product->name[3]);
                $product->meta_title[3] = $nombre.$sufixtitle;
                $product->meta_description[3] = $nombre.$sufixdescription[3];

                $product->name[4] = $nombre;
                $product->description[4] = $descripcion;
                $product->link_rewrite[4] = Tools::link_rewrite($product->name[4]);
                $product->meta_title[4] = $nombre.$sufixtitle;
                $product->meta_description[4] = $nombre.$sufixdescription[4];

                $product->name[5] = $nombre;
                $product->description[5] = $descripcion;
                $product->link_rewrite[5] = Tools::link_rewrite($product->name[5]);
                $product->meta_title[5] = $nombre.$sufixtitle;
                $product->meta_description[5] = $nombre.$sufixdescription[5];

                $product->name[6] = $nombre;
                $product->description[6] = $descripcion;
                $product->link_rewrite[6] = Tools::link_rewrite($product->name[6]);
                $product->meta_title[6] = $nombre.$sufixtitle;
                $product->meta_description[6] = $nombre.$sufixdescription[6];

                $product->id_tax_rules_group = auxiliares::getIdIva();

                $product->id_category_default = 104787;
                $product->id_manufacturer = $data['id_marca'];

                $product->add();
                $categories[] = 104787;
                if (! is_null($data['id_marca'])) {
                    $categoria_marcas = Db::getInstance()->ExecuteS('select id_category from aalv_alsernet_brand_as_category where id_manufacturer = '.$data['id_marca']);
                    if (count($categoria_marcas) != 0) {
                        $categories[] = $categoria_marcas[0]['id_category'];
                    }
                }
                $product->addToCategories($categories);
                $product->update();

                $sql = 'INSERT INTO aalv_product_import(id_product, id_modelo) VALUES ('.$product->id.','.$idmodelo.')';
                Db::getInstance()->Execute($sql);

                $idfeatureestadosegundamano = Feature::addFeatureImport('Estado segunda mano');
                $idfeatureprecioconsultar = Feature::addFeatureImport('Precio a consultar');
                $idfeatureventatelefono = ''.Feature::addFeatureImport('Venta teléfono');

                $idfeaturetextoproductosnovendibles = Feature::addFeatureImport('Texto productos no vendibles');

                $idfeatureestadosegundamanovalue = 0;
                $idfeatureprecioconsultarvalue = 0;
                $idfeatureventatelefonovalue = 0;
                $idfeaturetextoproductosnovendiblesvalue = 0;

                if (strip_tags($descripciondestacado) != '') {

                    $descripciondestacado = substr(strip_tags($descripciondestacado), 0, 4095);
                    if (strpos($descripciondestacado, '>')) {
                        $descripciondestacado = strip_tags('<'.$descripciondestacado);
                    }

                    $idfeatureestadosegundamanovalue = auxiliares::crearFeatureValue($idfeatureestadosegundamano, $descripciondestacado, 1);
                }
                if ($idfeatureestadosegundamanovalue != 0) {
                    $product->addFeatureProductImport($product->id, $idfeatureestadosegundamano, $idfeatureestadosegundamanovalue);
                }

                if ((''.$data['precio_consultar_ficha']) == '') {
                    $preconsul = '0';
                } else {
                    $preconsul = $data['precio_consultar_ficha'];
                }

                $idfeatureprecioconsultarvalue = auxiliares::crearFeatureValue($idfeatureprecioconsultar, $preconsul, 0);
                if ($idfeatureprecioconsultarvalue != 0) {
                    $product->addFeatureProductImport($product->id, $idfeatureprecioconsultar, $idfeatureprecioconsultarvalue);
                }

                if (''.$data['venta_telefono'] == '') {
                    $data['venta_telefono'] = 0;
                }

                $idfeatureventatelefonovalue = auxiliares::crearFeatureValue($idfeatureventatelefono, $data['venta_telefono'], 0);
                if ($idfeatureventatelefonovalue != 0) {
                    $product->addFeatureProductImport($product->id, $idfeatureventatelefono, $idfeatureventatelefonovalue);
                }

                if ($data['texto_productos_no_vendibles'] == 'solo_tienda') {

                    $idfeaturetextoproductosnovendiblesvalue = auxiliares::crearFeatureValue($idfeaturetextoproductosnovendibles, $data['texto_productos_no_vendibles'], 0);
                    if ($idfeaturetextoproductosnovendiblesvalue != 0) {
                        Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature='.$idfeaturetextoproductosnovendibles.' and id_product='.$product->id);
                        $product->addFeatureProductImport($product->id, $idfeaturetextoproductosnovendibles, $idfeaturetextoproductosnovendiblesvalue);
                    }
                }

                auxiliares::procesarcombinaciones($product->id);
            }
        } else {
            // borrado? mejor desactivar?
            $idmodelo = $fila;
            $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);
        }

        return 1;
    }
}
