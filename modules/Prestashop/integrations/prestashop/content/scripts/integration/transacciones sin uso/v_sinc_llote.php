<?php

class v_sinc_lloteClass
{
    public function Procesar_v_sinc_llote($data, $fila, $tipo)
    {
        try {
            // Iniciar la transacción manualmente
            Db::getInstance()->execute('START TRANSACTION');

            if ($tipo <= 2) {

                if (! $data) {
                    // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                    return 1;
                }

                $bundlesection = ''.Db::getInstance()->getValue('select bundle_section from aalv_llote_import where idllote='.$data['idllote']);

                $idbundleproduct = ''.Db::getInstance()->getValue('SELECT bundle_product FROM aalv_lote_import WHERE idlote='.$data['idlote']);

                if (''.$bundlesection == '') {
                    // crear la bundle section

                    Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section(min_quantity, choose_quantity, quantity_wise_discount, active, is_required, state, date_add, date_upd) VALUES (1,0,0,'.$data['estado'].',1,'.$data['estado'].',now(),now())');
                    $bundlesection = Db::getInstance()->Insert_ID();

                    Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.",1,1,'".$data['descripcion']."')");
                    Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.",1,2,'".$data['descripcion']."')");
                    Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.",1,3,'".$data['descripcion']."')");
                    Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.",1,4,'".$data['descripcion']."')");
                    Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.",1,5,'".$data['descripcion']."')");

                    Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_shop(id_wk_bundle_section, id_shop, date_add, date_upd) VALUES ('.$bundlesection.',1,now(),now())');

                    Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_map(id_wk_bundle_product, id_wk_bundle_section) VALUES ('.$idbundleproduct.','.$bundlesection.')');

                    Db::getInstance()->Execute('INSERT INTO aalv_llote_import(idllote, bundle_section) VALUES ('.$data['idllote'].','.$bundlesection.')');
                } else {

                    Db::getInstance()->Execute('UPDATE aalv_wk_bundle_section SET active='.$data['estado'].', state='.$data['estado'].', date_upd=now() where id_wk_bundle_section='.$bundlesection);

                    Db::getInstance()->Execute('REPLACE INTO aalv_wk_bundle_section_map(id_wk_bundle_product, id_wk_bundle_section) VALUES ('.$idbundleproduct.','.$bundlesection.')');

                    Db::getInstance()->Execute("UPDATE aalv_wk_bundle_section_lang set section_name='".$data['descripcion']."' where id_wk_bundle_section=".$bundlesection.' and id_lang=1');
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
