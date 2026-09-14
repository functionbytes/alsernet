<?php

class v_sinc_w_valores_nav_idiomaClass
{
    public function Procesar_v_sinc_w_valores_nav_idioma($data, $fila, $tipo)
    {
        throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] revisar '.$fila);

        return 1;

        if ($tipo <= 2) {

            if (! $data) {
                // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }

            $idorigen = $data['id_valor'];

            $idcatsps = Db::getInstance()->ExecuteS('SELECT id_cat FROM aalv_category_import WHERE id_origen='.$idorigen);

            $nombre = $data['nombre'];
            $idioma = $data['idioma'];
            $codidioma = getidioma($idioma);

            foreach ($idcatsps as $cat) {
                $category = new Category((int) $cat['id_cat']);
                $category->name[$codidioma] = $nombre;
                $category->link_rewrite[$codidioma] = safeName($category->name[$codidioma]);
                $category->update();
            }

            return 1;
        } else {
            return 1;
        }
    }
}
