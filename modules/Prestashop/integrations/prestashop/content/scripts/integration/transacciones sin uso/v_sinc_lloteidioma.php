<?php

class v_sinc_lloteidiomaClass
{
    public function Procesar_v_sinc_lloteidioma($data, $fila, $tipo)
    {
        try {
            // Iniciar la transacción manualmente
            Db::getInstance()->execute('START TRANSACTION');
            if ($tipo <= 2) {

                if (! $data) {
                    // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                    return 1;
                }

                // {"idlloteidioma":26930,"idllote":100009087,"ididioma":7,"descripcion":"COLETE 1","idioma_descripcion":"Portugues"}

                $bundlesection = ''.Db::getInstance()->getValue('select bundle_section from aalv_llote_import where idllote='.$data['idllote']);

                if ($bundlesection != '') {

                    $idioma = 0;
                    $ididioma = $data['ididioma'];
                    if ($ididioma == 7) {
                        $idioma = 4;
                    }

                    if ($idioma != 0) {

                        Db::getInstance()->Execute('REPLACE INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.',1,'.$idioma.",'".$data['descripcion']."')");
                    }
                }
            } else {
            }

            // Confirmar la transacción manualmente
            Db::getInstance()->execute('COMMIT');

            return 1;
        } catch (Exception $e) {
            // Revertir la transacción manualmente en caso de error
            Db::getInstance()->execute('ROLLBACK');

            echo 'Error: '.$e->getMessage();
        }
    }
}
