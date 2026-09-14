<?php

class v_sinc_w_ayudasClass
{
    public function Procesar_v_sinc_w_ayudas($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            $idayuda = $data['id'];
            $idayudaps = ''.Db::getInstance()->getValue('SELECT id FROM aalv_ayudas WHERE idorigen='.$idayuda);
            if ($idayudaps != '') {

                $descripcion = base64_decode($data['texto']);
                $descripcion = utf8_encode($descripcion);

                $tactivo = $data['activo'];
                $activo = '1';
                if ($tactivo == 'no') {
                    $activo = '0';
                }

                Db::getInstance()->Execute("UPDATE aalv_ayudas SET titulo='".$data['titulo']."',texto='".$descripcion."',enlace='".$data['enlace']."',activo=".$activo.' WHERE id='.$idayudaps);
            } else {

                $descripcion = base64_decode($data['texto']);
                $descripcion = utf8_encode($descripcion);

                $tactivo = $data['activo'];
                $activo = '1';
                if ($tactivo == 'no') {
                    $activo = '0';
                }

                Db::getInstance()->Execute("INSERT INTO aalv_ayudas(titulo, texto, enlace, activo, idorigen) VALUES ('".$data['titulo']."','".$descripcion."','".$data['enlace']."',".$activo.','.$idayuda.')');
            }
        } else {
            // borraddo
            Db::getInstance()->Execute('DELETE FROM aalv_ayudas WHERE idorigen='.$fila);
        }

        return 1;
    }
}
