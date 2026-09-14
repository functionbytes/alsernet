<?php

class v_sinc_tag_temporalClass
{
    public function Procesar_v_sinc_tag_temporal($data, $fila, $tipo)
    {
        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }
            $idarticulo = $data['idarticulo'];
            $buscar = Db::getInstance()->getValue('SELECT es_segunda_mano FROM aalv_combinacionunica_import WHERE id_articulo='.$data['idarticulo']);

            if ($buscar == 1) {
                Db::getInstance()->Execute("UPDATE aalv_combinacionunica_import SET etiqueta='".pSQL('SEGUNDA MANO')."' WHERE id_articulo=".$data['idarticulo']);
            } else {
                Db::getInstance()->Execute("UPDATE aalv_combinacionunica_import SET etiqueta='".$data['etiqueta']."' WHERE id_articulo=".$idarticulo);
            }
            Db::getInstance()->Execute("UPDATE aalv_combinaciones_import SET etiqueta='".$data['etiqueta']."' WHERE id_articulo=".$idarticulo);

            return 1;
        } else {
            throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] El tipo es 3 ');

            return 1;
        }
    }
}
