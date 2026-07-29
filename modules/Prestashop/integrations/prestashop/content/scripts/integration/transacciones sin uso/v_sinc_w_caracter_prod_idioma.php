<?php

class v_sinc_w_caracter_prod_idiomaClass
{
    public function Procesar_v_sinc_w_caracter_prod_idioma($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            $idorigen = $data['id_caracteristica'];
            $idattrgroupps = ''.Db::getInstance()->getValue('SELECT id_attribute_group FROM aalv_attribute_group_import WHERE id_origen='.$idorigen);
            $nombre = $data['nombre'];

            $idioma = $data['idioma'];
            $codidioma = auxiliares::getidioma($idioma);

            if ($idattrgroupps != '') {

                $attributegroup = new AttributeGroup((int) $idattrgroupps);
                $attributegroup->name[$codidioma] = $nombre;
                $attributegroup->public_name[$codidioma] = substr($nombre, 0, 64);
                $attributegroup->update();
            } else {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Se tiene que crear ');
            }

            return 1;
        } else {
            throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Revisar el tipo ');

            return 1;
        }
    }
}
