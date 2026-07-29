<?php

class v_sinc_w_valores_prodClass // [REVISAR]
{
    public function Procesar_v_sinc_w_valores_prod($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            if ($data) {
                $idorigen = $data['id'];

                $idattributeps = ''.Db::getInstance()->getValue('SELECT id_attribute FROM aalv_attribute_import WHERE id_origen='.$idorigen);

                if ($idattributeps != '') {

                    $attribute = new Attribute((int) $idattributeps);
                    $attribute->name[1] = $data['nombre'];
                    $attribute->update();
                } else {
                    $idcaracteristica = $data['id_caracteristica'];
                    $idattributegroupps = ''.Db::getInstance()->getValue('SELECT id_attribute_group FROM aalv_attribute_group_import WHERE id_origen='.$idcaracteristica);

                    if ($idattributegroupps != '') {

                        if ($data['nombre'] == '') {
                            $data['nombre'] = '-';
                        }

                        $attribute = new Attribute;
                        $attribute->id_attribute_group = $idattributegroupps;
                        $attribute->name[1] = $data['nombre'];
                        $attribute->name[2] = $data['nombre'];
                        $attribute->name[3] = $data['nombre'];
                        $attribute->name[4] = $data['nombre'];
                        $attribute->name[5] = $data['nombre'];
                        $attribute->add();

                        Db::getInstance()->Execute('INSERT INTO aalv_attribute_import(id_attribute, id_origen, grupo) VALUES ('.$attribute->id.','.$idorigen.",'')");
                    } else {
                        throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] revisar porque no existe el id_caracteristica '.$idcaracteristica);

                        return 1;
                    }
                }
            }
        }

        return 1;
    }
}
