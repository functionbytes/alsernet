<?php

class v_sinc_w_valores_prod_idiomaClass
{
    public function Procesar_v_sinc_w_valores_prod_idioma($data, $fila, $tipo)
    {
        auxiliares::sendmail('ENTRO EN v_sinc_w_valores_prod_idioma');
        if ($tipo <= 2) {

            if (! $data) {
                // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }

            if ($data) {
                return 1;
            }

            return 1;
        } else {
            return 1;
        }
    }
}
