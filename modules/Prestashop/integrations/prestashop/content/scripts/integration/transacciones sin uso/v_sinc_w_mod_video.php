<?php

class v_sinc_w_mod_videoClass
{
    public function Procesar_v_sinc_w_mod_video($data, $fila, $tipo)
    {
        auxiliares::sendmail('ENTRO EN v_sinc_w_mod_video');

        if ((! $data) && ($tipo <= 2)) {
            // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }

        $idioma = $data['idioma'];
        $codidioma = getidioma($idioma);

        $idvideo = str_replace('https://www.youtube.com/embed/', '', $data['contenido']);
        $idvideo = str_replace('https://youtu.be/', '', $idvideo);
        $idvideo = trim($idvideo);

        if ($tipo <= 2) {

            $idvideops = ''.Db::getInstance()->getValue('select a.id_productvideo from aalv_video_import a inner join aalv_product_videos b on a.id_productvideo=b.id_productvideo and b.id_lang='.$codidioma.' where id_origen='.$data['id']);
            if ($idvideops != '') {
                // ya existe, ver si el modelo coincide con el producto del video
                $prodps = ''.Db::getInstance()->getValue('select id_product from aalv_product_videos where id_productvideo='.$idvideops);
                $prodfrommodel = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$data['id_modelo']);

                if ($prodps == $prodfrommodel) {
                    // hacer update del video
                    Db::getInstance()->Execute("UPDATE aalv_product_videos SET id_video='".$idvideo."', video_url='".$data['contenido']."', position=".$data['orden'].' WHERE id_productvideo='.$idvideops);
                } else {

                    // discrepancia
                }
            } else {
                // creacion

                $idproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$data['id_modelo']);

                if ($idproduct != '') {

                    Db::getInstance()->Execute('INSERT INTO aalv_product_videos(id_product, id_video, title, provider, video_url, position, id_lang, id_shop) VALUES ('.$idproduct.",'".$idvideo."','','youtube','".$data['contenido']."',".$data['orden'].','.$codidioma.',1)');
                    $idproductvideo = (int) Db::getInstance()->Insert_ID();
                    if ($idproductvideo != 0) {
                        Db::getInstance()->Execute('INSERT INTO aalv_video_import(id_productvideo, id_origen) VALUES ('.$idproductvideo.','.$data['id'].')');
                    }
                }
            }
        } else {

            // borrado
            $idvideops = Db::getInstance()->ExecuteS('select id_productvideo from aalv_video_import where id_origen='.$fila);
            foreach ($idvideops as $row) {
                Db::getInstance()->Execute('delete from aalv_product_videos where id_productvideo='.$row['id_productvideo']);
            }
            Db::getInstance()->Execute('delete from aalv_video_import where id_origen='.$fila);
        }

        return 1;
    }
}
