<?php

class v_sinc_w_modelo_imagenClass
{
    public function Procesar_v_sinc_w_modelo_imagen($data, $fila, $tipo)
    {
        auxiliares::sendmail('ENTRO EN v_sinc_w_modelo_imagen');

        $modelo = $data['id_modelo'];
        $idorigen = $data['id'];
        $producto = 0;
        $filename = $data['path_imagen'];
        $posicion = $data['orden'];

        $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$modelo);

        if ($idprodps != '') {
            if ($tipo <= 2) {

                // ver si existe la imagen en la tabla image_import
                $existe = ''.Db::getInstance()->getValue('SELECT id_image FROM aalv_image_import WHERE id_product='.$idprodps." and filename='".$filename."'");

                if ($existe != '') {

                    $image = new Image($existe);
                    $image->position = (int) $posicion;
                    $image->update();
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
                        }
                    } else {
                        // foto no existe en el ftp

                    }
                }
            }
        } else {
            if ($tipo == 3) {

                // recuperar del origen

                $idimage = ''.Db::getInstance()->getValue('SELECT id_image FROM aalv_image_import WHERE id_origen='.$fila);

                if ($idimage != '') {
                    $image = new Image($idimage);
                    $image->delete();
                }
            }
        }

        return 1;
    }
}
