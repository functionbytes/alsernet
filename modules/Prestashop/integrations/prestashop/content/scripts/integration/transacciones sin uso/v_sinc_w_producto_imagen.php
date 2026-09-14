<?php

class v_sinc_w_producto_imagenClass
{
    public function Procesar_v_sinc_w_producto_imagen($data, $fila, $tipo)
    {
        auxiliares::sendmail('ENTRO EN v_sinc_w_producto_imagen');
        $modelo = 0;
        $idorigen = $data['id'];
        $producto = $data['id_producto'];
        $filename = $data['path_imagen'];
        $posicion = $data['orden'];

        $idprodattrps = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen='.$producto.' and id_product_attribute in (select id_product_attribute from aalv_product_attribute)');

        $idprodps = '';
        if ($idprodattrps != '') {
            $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idprodattrps);
        }

        $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$modelo);

        if ($idprodps != '') {
            if ($tipo <= 2) {

                // ver si existe la imagen en la tabla image_import
                $existe = ''.Db::getInstance()->getValue('SELECT id_image FROM aalv_image_import WHERE id_product='.$idprodps." and filename='".$filename."'");

                if ($existe != '') {

                    $image = new Image($existe);
                    $image->position = (int) $posicion;
                    $image->update();

                    // añadirla a la combinación
                    Db::getInstance()->execute('REPLACE INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES ('.$idprodattrps.','.$image->id.')');
                } else {

                    if (download($filename)) {

                        $image = new Image;
                        $image->id_product = $idprodps;
                        $image->position = (int) $posicion;

                        if (($image->validateFields(false, true)) === true &&
                            ($image->validateFieldsLang(false, true)) === true && $image->add()
                        ) {

                            if (! copyImg($idprodps, $image->id, __DIR__.'/backups/'.$filename, 'inventaries', true)) {
                                $image->delete();
                                // echo "pasa....1";
                            } else {
                                if (! file_exists(_PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.'.$image->image_format)) {
                                    $image->delete();
                                    // echo "pasa....2 "._PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format;
                                }
                            }
                            // echo "llega imagen";
                            unlink(__DIR__.'/backups/'.$filename);

                            Db::getInstance()->Execute('INSERT INTO aalv_image_import(id_image, id_product, filename, id_origen, modelo, producto, posicion) VALUES ('.$image->id.','.$idprodps.",'".$filename."',".$idorigen.','.$modelo.','.$producto.','.(int) $posicion.')');

                            // añadirla a la combinación
                            Db::getInstance()->execute('REPLACE INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES ('.$idprodattrps.','.$image->id.')');
                        }
                    } else {
                        // foto no existe en el ftp

                    }
                }
            }
        } else {
            if ($tipo == 3) {

                // recuperar del origen

                $idimage = '' & Db::getInstance()->getValue('SELECT id_image FROM aalv_image_import WHERE id_origen='.$fila);

                if ($idimage != '') {
                    $image = new Image($idimage);
                    $image->delete();
                }
            }
        }

        return 1;
    }
}
