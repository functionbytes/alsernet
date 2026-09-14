<?php

class v_sinc_w_valores_navClass
{
    public function Procesar_v_sinc_w_valores_nav($data, $fila, $tipo)
    {
        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            $idorigen = $data['id'];
            $idcatsps = Db::getInstance()->ExecuteS('SELECT id_cat FROM aalv_category_import WHERE id_origen='.$idorigen);
            $nombre = $data['nombre'];
            $codidioma = 1;

            foreach ($idcatsps as $cat) {
                $category = new Category((int) $cat['id_cat']);
                $category->name[$codidioma] = $nombre;
                $category->link_rewrite[$codidioma] = str_replace('-', '_', auxiliares::safeName($category->name[$codidioma]));
                $category->update();
            }

            $existe = ''.Db::getInstance()->getValue('select id_origen from aalv_valores_nav_import where id_origen='.$data['id']);
            if ($existe == '') {
                Db::getInstance()->execute('INSERT INTO aalv_valores_nav_import(id_origen, nombre) VALUES ('.$data['id'].",'".$data['nombre']."')");
            } else {
                Db::getInstance()->execute("UPDATE aalv_valores_nav_import SET nombre='".$data['nombre']."' WHERE id_origen=".$existe);
            }

        }

        return 1;
    }
}
