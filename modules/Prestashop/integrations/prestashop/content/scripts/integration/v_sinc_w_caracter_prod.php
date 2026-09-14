<?php

class v_sinc_w_caracter_prodClass
{
    public function Procesar_v_sinc_w_caracter_prod($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            $idorigen = $data['id'];
            $idattrgroupps = ''.Db::getInstance()->getValue('SELECT id_attribute_group FROM aalv_attribute_group_import WHERE id_origen='.$idorigen);
            $nombre = $data['nombre'];

            if ($idattrgroupps == '') {

                $attributegroup = new AttributeGroup;
                $attributegroup->name[1] = $nombre;
                $attributegroup->public_name[1] = substr($nombre, 0, 64);
                $attributegroup->group_type = 'radio';

                $attributegroup->add();
                $id_attribute_group = $attributegroup->id;

                Db::getInstance()->Execute('INSERT INTO aalv_attribute_group_import(id_attribute_group, id_origen, in_google_shopping) VALUES ('.$id_attribute_group.','.$idorigen.",'')");
            } else {

                $attributegroup = new AttributeGroup((int) $idattrgroupps);
                $attributegroup->name[1] = $nombre;
                $attributegroup->public_name[1] = substr($nombre, 0, 64);
                $attributegroup->update();
            }

            return 1;
        } else {

            $idorigen = $data['id'];
            $idattrgroupps = ''.Db::getInstance()->getValue('SELECT id_attribute_group FROM aalv_attribute_group_import WHERE id_origen='.$idorigen);
            if ($idattrgroupps != '') {
                $attributegroup = new AttributeGroup((int) $idattrgroupps);
                $attributegroup->delete();
                Db::getInstance()->Execute('delete from aalv_attribute_group_import where id_attribute_group='.$idattrgroupps);
            }

            return 1;
        }
    }
}
