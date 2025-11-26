<?php

require dirname(__FILE__) . '/../../traduccion/DeepL/vendor/autoload.php';
define("_DEF_authKey", "a7c95f82-c7ba-456a-83e6-46a40d46c56b"); // Replace with your key


class Traduccion {

    private $url;
    private $id_categoria;
    private $id_lang;
    private $id_attribute_group;
    private $idioma;
    private $paginacion;
    private $texto_filtro;
    private $texto_filtro_traduccion;
    private $estado_traduccion;
    private $campo_evaluacion;
    private $total_registros;
    private $tipo_traduccion;


    public function __construct() {
        $this->url = $_SERVER['PHP_SELF'].'?';
        $this->id_categoria = 0;
        $this->id_lang = 0;
        $this->id_attribute_group = 0;
        $this->idioma = [];
        $this->paginacion = 0;
        $this->texto_filtro = '';
        $this->texto_filtro_traduccion = '';
        $this->estado_traduccion = 0;
        $this->campo_evaluacion = 'name';
        $this->total_registros = 0;
        $this->tipo_traduccion = '';
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }

    public function getJavascript() {
        $javascript = "
        document.addEventListener('DOMContentLoaded', function() {
            var inputs = document.querySelectorAll('.traduccion');
            inputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    input.classList.add('changed_manual');
                    input.classList.remove('changed_deepl');
                    var checkbox_no_traducir = document.getElementById('no_'+input.id);
                    checkbox_no_traducir.checked = true;
                    var checkbox_deepl_traducir = document.getElementById('deepl_'+input.id);
                    checkbox_deepl_traducir.checked = false;
                    checkbox_deepl_traducir.disabled = true;
                });
            });
            var checks_no_traducir = document.querySelectorAll('.no_traducir');
            checks_no_traducir.forEach(function(check) {
                check.addEventListener('change', function() {
                    var checkbox_deepl_traducir = document.getElementById(check.id.replace('no','deepl'));
                    if (check.checked) {
                        checkbox_deepl_traducir.disabled = true;
                        checkbox_deepl_traducir.checked = false;
                    }else{
                        checkbox_deepl_traducir.disabled = false;
                    }
                });
            });
            var checks_deepl_traducir = document.querySelectorAll('.deepl_traducir');
            checks_deepl_traducir.forEach(function(check) {
                check.addEventListener('change', function() {
                    if (check.checked) {
                        $.ajax({
                            url: '".$this->url."',
                            type: 'POST',
                            data: {
                                traduccion: 'deepl',
                                id_product: check.dataset.id_product,
                                id_attribute_group: check.dataset.id_attribute_group,
                                id_attribute: check.dataset.id_attribute,
                                campo_evaluacion: check.dataset.campo_evaluacion,
                                id_lang: check.dataset.id_lang
                            },
                            dataType: 'json',
                            success: function(data) {
                                var input = document.getElementById(check.id.replace('deepl_',''));
                                input.value = data;
                                input.classList.add('changed_deepl');
                                input.classList.remove('changed_manual');

                            },
                            error: function(xhr, status, error) {
                                console.log('Error:', error);
                            }
                        });
                    }
                });
            });
        });
        ";

        return $javascript;
    }

    public function getCSS() {
        $css = '
            body {
                font-family: Arial, sans-serif;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            table, th, td {
                border: 1px solid black;
            }

            .filtro table, .filtro th, .filtro td {
                border: none;
            }

            .filtro table {
                width: 25%;
            }

            th, td {
                padding: 5px;
                text-align: left;
            }

            input.changed_manual {
                background-color: #d4edda;
            }

            input.changed_deepl {
                background-color: orange;
            }

            .productos {
                color: #336699;
                background-color: #f0f0f0;
                padding: 20px;
            }

            .categorias {
                color: #cc3300;
                background-color: #f9f9f9;
                padding: 20px;
            }

            .mensaje {
                color: red;
                position: absolute;
                right: 15px;
                top: 15px;
                -moz-animation: cssAnimation 0s ease-in 5s forwards;
                /* Firefox */
                -webkit-animation: cssAnimation 0s ease-in 5s forwards;
                /* Safari and Chrome */
                -o-animation: cssAnimation 0s ease-in 5s forwards;
                /* Opera */
                animation: cssAnimation 0s ease-in 5s forwards;
                -webkit-animation-fill-mode: forwards;
                animation-fill-mode: forwards;
            }
            @keyframes cssAnimation {
                to {
                    width:0;
                    height:0;
                    overflow:hidden;
                }
            }
            @-webkit-keyframes cssAnimation {
                to {
                    width:0;
                    height:0;
                    visibility:hidden;
                }
            }
        ';

        return $css;
    }

    private function getSQLWhereEstadoTraduccion() {
        if ($this->estado_traduccion == 1) { //Sin traducir
            return " AND es=".$this->idioma['iso_code'];
        }elseif($this->estado_traduccion == 2) { //Traducido
            return " AND es!=".$this->idioma['iso_code'];
        }

        return '';
    }

    private function getSQLWhereFiltroTexto() {
        if ($this->texto_filtro) {
            return " AND es like '%".$this->texto_filtro."%'";
        }

        return '';
    }

    private function getSQLWhereFiltroTextoTraduccion() {
        if ($this->texto_filtro_traduccion) {
            return " AND ".$this->idioma['iso_code']." like '%".$this->texto_filtro_traduccion."%'";
        }

        return '';
    }

    public function getFormularioCategorias() {


        $url = $this->url."accion=traducciones&id_lang=".$this->id_lang."&estado_traduccion=".$this->estado_traduccion."&tipo_traduccion=".$this->tipo_traduccion;

        $where_sin_traducir = $this->getSQLWhereEstadoTraduccion();

        $where_texto_filtro = $this->getSQLWhereFiltroTexto();

        $where_texto_filtro_traduccion = $this->getSQLWhereFiltroTextoTraduccion();

        $formulario = '<h2>TRADUCIR CARACTERÍSTICAS</h2>
        <form method="post" action="#">
        <input type="hidden" name="accion" value="traducciones" />
        <input type="hidden" name="id_lang" value="'.$this->id_lang.'" />
        <input type="hidden" name="estado_traduccion" value="'.$this->estado_traduccion.'" />
        <input type="hidden" name="paginacion" value="'.$this->paginacion.'" />
        <input type="hidden" name="tipo_traduccion" value="'.$this->tipo_traduccion.'" />
        <input type="hidden" name="texto" value="'.$this->texto_filtro.'" />
        <table>
            <thead>
                <tr>
                    <th>Producto de<br />Referencia</th>
                    <th>Texto Español</th>
                    <th>Traducción '.$this->idioma['name'].'</th>
                    <th>Traducción DeepL</th>
                </tr>
            </thead>
            <tbody>';

        if ($this->id_lang > 1) {
                $sql = "select count(*) as total_registros from (SELECT
                p.id_attribute_group, (select l1.name
                                    from aalv_attribute_group_lang l1
                                    where l1.id_attribute_group=p.id_attribute_group
                                        AND l1.id_lang=1) as es,
                                (select l2.name
                                    from aalv_attribute_group_lang l2
                                    where l2.id_attribute_group=p.id_attribute_group
                                        AND l2.id_lang=".$this->idioma['id_lang'].") as ".$this->idioma['iso_code']."
                FROM aalv_attribute_group p) as traducciones
                WHERE es!=''".$where_sin_traducir.$where_texto_filtro.$where_texto_filtro_traduccion."
                ORDER by id_attribute_group DESC";

            $total_registros = Db::getInstance()->ExecuteS($sql)[0]['total_registros'];

            $sql = "select traducciones.* from (SELECT
            p.id_attribute_group, (select l1.name
                                from aalv_attribute_group_lang l1
                                where l1.id_attribute_group=p.id_attribute_group
                                    AND l1.id_lang=1) as es,
                            (select l2.name
                                from aalv_attribute_group_lang l2
                                where l2.id_attribute_group=p.id_attribute_group
                                    AND l2.id_lang=".$this->idioma['id_lang'].") as ".$this->idioma['iso_code']."
            FROM aalv_attribute_group p) as traducciones
            WHERE es!=''".$where_sin_traducir.$where_texto_filtro.$where_texto_filtro_traduccion."
            ORDER by id_attribute_group DESC
            LIMIT 10 OFFSET ".$this->paginacion;

            $elementos = Db::getInstance()->ExecuteS($sql);
            foreach ($elementos as $elemento) {
                if ($_POST['accion']=="traducciones" && $_POST[$elemento['id_attribute_group']]) {
                    $texto = $_POST[$elemento['id_attribute_group']];
                    if ($elemento[$this->idioma['iso_code']] != $texto) {
                        $elemento[$this->idioma['iso_code']] = $texto;
                        Db::getInstance()->execute("UPDATE aalv_attribute_group_lang SET name = '" . pSQL($texto) . "' where id_lang = ".$this->id_lang." AND id_attribute_group = ".$elemento['id_attribute_group']);
                    }
                }

                $formulario .= '<tr>
                            <td style="width: 7%;">'.htmlspecialchars($this->getProductoReferencia($elemento['id_attribute_group'])).'</td>
                            <td style="width: 45%;">'.htmlspecialchars($elemento['es']).'</td>';
                $formulario .= '<td><input type="text" style="width: 97%;" class="traduccion" name="'.$elemento['id_attribute_group'].'" value="'.htmlspecialchars($elemento[$this->idioma['iso_code']]).'" id="traducir_'.$elemento['id_attribute_group'].'" required></td>';

                $formulario .= '
                            <td style="width: 1%; text-align: center;"><input type="checkbox" data-id_attribute_group="'.$elemento['id_attribute_group'].'" data-id_lang="'.$this->id_lang.'" data-campo_evaluacion="name" id="deepl_traducir_'.$elemento['id_attribute_group'].'" name="deepl_traducir_'.$elemento['id_attribute_group'].'"  class="deepl_traducir" /></td>
                        </tr>';
            }
            if ($_POST['accion']=="traducciones") {
                $formulario .= '<div class="mensaje">Actualizadas las características enviadas.</div>';
            }
        }
        $formulario .= '</tbody></table>';
        if ($this->paginacion !== null) {
            $siguiente_pagina = $this->paginacion + 10;
            $anterior_pagina = $this->paginacion > 0 ? $this->paginacion - 10 : 0;
            $url_siguiente= "&paginacion=".$siguiente_pagina;
            $url_anterior= "&paginacion=".$anterior_pagina;
        }

        $formulario .= '<div style="text-align: right; margin: 10px;"><a href="'.$url.$url_anterior.'"><</a> '.$this->paginacion.'/'.$total_registros.' <a href="'.$url.$url_siguiente.'">></a></div>';
        $formulario .= '<div style="width: 100%; text-align: center; padding: 20px;"><button type="submit">Enviar</button></div></form>';

        return $formulario;

    }

    public function getFormularioAtributos() {

        $url = $this->url."accion=traducciones&id_lang=".$this->id_lang."&id_attribute_group=".$this->id_attribute_group."&estado_traduccion=".$this->estado_traduccion."&tipo_traduccion=".$this->tipo_traduccion;

        $where_sin_traducir = $this->getSQLWhereEstadoTraduccion();

        $where_texto_filtro = $this->getSQLWhereFiltroTexto();

        $where_texto_filtro_traduccion = $this->getSQLWhereFiltroTextoTraduccion();

        if ($this->id_attribute_group) {
            $where_caracteristica = " AND id_attribute_group = ".$this->id_attribute_group;
        }

        $formulario = '<h2>TRADUCIR ATRIBUTOS</h2>
        <form method="post" action="#">
        <input type="hidden" name="accion" value="traducciones" />
        <input type="hidden" name="id_lang" value="'.$this->id_lang.'" />
        <input type="hidden" name="id_attribute_group" value="'.$this->id_attribute_group.'" />
        <input type="hidden" name="estado_traduccion" value="'.$this->estado_traduccion.'" />
        <input type="hidden" name="paginacion" value="'.$this->paginacion.'" />
        <input type="hidden" name="tipo_traduccion" value="'.$this->tipo_traduccion.'" />
        <input type="hidden" name="texto" value="'.$this->texto_filtro.'" />
        <table>
            <thead>
                <tr>
                    <th>Producto de<br />Referencia</th>
                    <th>Característica</th>
                    <th>Texto Español</th>
                    <th>Traducción '.$this->idioma['name'].'</th>
                    <th>Traducción DeepL</th>
                </tr>
            </thead>
            <tbody>';

        if ($this->id_lang > 1) {
                $sql = "select count(*) as total_registros from (SELECT
                p.id_attribute, p.id_attribute_group, (select l1.name
                                    from aalv_attribute_lang l1
                                    where l1.id_attribute=p.id_attribute
                                        AND l1.id_lang=1) as es,
                                (select l2.name
                                    from aalv_attribute_lang l2
                                    where l2.id_attribute=p.id_attribute
                                        AND l2.id_lang=".$this->idioma['id_lang'].") as ".$this->idioma['iso_code']."
                FROM aalv_attribute p) as traducciones
                WHERE es!=''".$where_sin_traducir.$where_texto_filtro.$where_texto_filtro_traduccion.$where_caracteristica."
                ORDER by id_attribute DESC";

            $total_registros = Db::getInstance()->ExecuteS($sql)[0]['total_registros'];

            $sql = "select traducciones.* from (SELECT
            p.id_attribute, p.id_attribute_group, (select l1.name
                                from aalv_attribute_lang l1
                                where l1.id_attribute=p.id_attribute
                                    AND l1.id_lang=1) as es,
                            (select l2.name
                                from aalv_attribute_lang l2
                                where l2.id_attribute=p.id_attribute
                                    AND l2.id_lang=".$this->idioma['id_lang'].") as ".$this->idioma['iso_code']."
            FROM aalv_attribute p) as traducciones
            WHERE es!=''".$where_sin_traducir.$where_texto_filtro.$where_texto_filtro_traduccion.$where_caracteristica."
            ORDER by id_attribute DESC
            LIMIT 10 OFFSET ".$this->paginacion;

            $elementos = Db::getInstance()->ExecuteS($sql);
            foreach ($elementos as $elemento) {
                if ($_POST['accion']=="traducciones" && $_POST[$elemento['id_attribute']]) {
                    $texto = $_POST[$elemento['id_attribute']];
                    if ($elemento[$this->idioma['iso_code']] != $texto) {
                        $elemento[$this->idioma['iso_code']] = $texto;
                        Db::getInstance()->execute("UPDATE aalv_attribute_lang SET name = '" . pSQL($texto) . "' where id_lang = ".$this->id_lang." AND id_attribute = ".$elemento['id_attribute']);
                    }
                }

                $caracteristica_nombre = Db::getInstance()->ExecuteS("SELECT name FROM aalv_attribute_group_lang WHERE id_lang=1 AND id_attribute_group=".$elemento['id_attribute_group'])[0];
                $formulario .= '<tr>
                            <td style="width: 7%;">'.htmlspecialchars($this->getProductoReferencia(null, $elemento['id_attribute'])).'</td>
                            <td style="width: 7%;">'.htmlspecialchars($caracteristica_nombre['name']).'</td>
                            <td style="width: 45%;">'.htmlspecialchars($elemento['es']).'</td>';
                $formulario .= '<td><input type="text" style="width: 97%;" class="traduccion" name="'.$elemento['id_attribute'].'" value="'.htmlspecialchars($elemento[$this->idioma['iso_code']]).'" id="traducir_'.$elemento['id_attribute'].'" required></td>';

                $formulario .= '
                            <td style="width: 1%; text-align: center;"><input type="checkbox" data-id_attribute="'.$elemento['id_attribute'].'" data-id_lang="'.$this->id_lang.'" data-campo_evaluacion="name" id="deepl_traducir_'.$elemento['id_attribute'].'" name="deepl_traducir_'.$elemento['id_attribute'].'"  class="deepl_traducir" /></td>
                        </tr>';
            }
            if ($_POST['accion']=="traducciones") {
                $formulario .= '<div class="mensaje">Actualizadas las características enviadas.</div>';
            }
        }
        $formulario .= '</tbody></table>';
        if ($this->paginacion !== null) {
            $siguiente_pagina = $this->paginacion + 10;
            $anterior_pagina = $this->paginacion > 0 ? $this->paginacion - 10 : 0;
            $url_siguiente= "&paginacion=".$siguiente_pagina;
            $url_anterior= "&paginacion=".$anterior_pagina;
        }

        $formulario .= '<div style="text-align: right; margin: 10px;"><a href="'.$url.$url_anterior.'"><</a> '.$this->paginacion.'/'.$total_registros.' <a href="'.$url.$url_siguiente.'">></a></div>';
        $formulario .= '<div style="width: 100%; text-align: center; padding: 20px;"><button type="submit">Enviar</button></div></form>';

        return $formulario;

    }

    public function getFormularioProductos() {

        $url = $this->url."accion=traducciones&id_lang=".$this->id_lang."&id_categoria=".$this->id_categoria."&estado_traduccion=".$this->estado_traduccion."&campo_evaluacion=".$this->campo_evaluacion."&tipo_traduccion=".$this->tipo_traduccion."&texto=".urlencode($this->texto_filtro)."&texto_traduccion=".urlencode($this->texto_filtro_traduccion);

        $where_sin_traducir = $this->getSQLWhereEstadoTraduccion();

        $where_texto_filtro = $this->getSQLWhereFiltroTexto();

        $where_texto_filtro_traduccion = $this->getSQLWhereFiltroTextoTraduccion();

        if ($this->id_categoria > 0) {
            $productos = $this->obtenerProductosPorCategoria($this->id_categoria);
            $productos = array_unique($productos);
            $productos_string = implode(',', $productos);
            $where_productos = " AND id_product IN (".$productos_string.")";
        }

        $formulario_productos = '<h2>TRADUCIR PRODUCTOS</h2>
        <form method="post" action="#">
        <input type="hidden" name="accion" value="traducciones" />
        <input type="hidden" name="id_lang" value="'.$this->id_lang.'" />
        <input type="hidden" name="id_categoria" value="'.$this->id_categoria.'" />
        <input type="hidden" name="estado_traduccion" value="'.$this->estado_traduccion.'" />
        <input type="hidden" name="campo_evaluacion" value="'.$this->campo_evaluacion.'" />
        <input type="hidden" name="paginacion" value="'.$this->paginacion.'" />
        <input type="hidden" name="tipo_traduccion" value="'.$this->tipo_traduccion.'" />
        <input type="hidden" name="texto" value="'.$this->texto_filtro.'" />
        <input type="hidden" name="texto_traduccion" value="'.$this->texto_filtro_traduccion.'" />
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Texto Español</th>
                    <th>Traducción '.$this->idioma['name'].'</th>
                    <th>Traducción Manual</th>
                    <th>Traducción DeepL</th>
                </tr>
            </thead>
            <tbody>';

        if ($this->id_lang > 1) {
            $sql = "select count(*) as total_registros from (SELECT
            p.id_product,   (select l1.".$this->campo_evaluacion."
                                from aalv_product_lang l1
                                where l1.id_product=p.id_product
                                    AND l1.id_lang=1) as es,
                            (select l2.".$this->campo_evaluacion."
                                from aalv_product_lang l2
                                where l2.id_product=p.id_product
                                    AND l2.id_lang=".$this->idioma['id_lang'].") as ".$this->idioma['iso_code']."
            FROM aalv_product p
            WHERE p.active = 1) as traducciones where es!=''".$where_sin_traducir.$where_texto_filtro.$where_texto_filtro_traduccion.$where_productos."
            ORDER by id_product DESC";

            $total_registros = Db::getInstance()->ExecuteS($sql)[0]['total_registros'];

            $sql = "select (SELECT id_modelo FROM aalv_product_import i WHERE i.id_product=traducciones.id_product) id_modelo, traducciones.* from (SELECT
            p.id_product,   (select l1.".$this->campo_evaluacion."
                                from aalv_product_lang l1
                                where l1.id_product=p.id_product
                                    AND l1.id_lang=1) as es,
                            (select l2.".$this->campo_evaluacion."
                                from aalv_product_lang l2
                                where l2.id_product=p.id_product
                                    AND l2.id_lang=".$this->idioma['id_lang'].") as ".$this->idioma['iso_code']."
            FROM aalv_product p
            WHERE p.active = 1) as traducciones where es!=''".$where_sin_traducir.$where_texto_filtro.$where_texto_filtro_traduccion.$where_productos."
            ORDER by id_product DESC
            LIMIT 10 OFFSET ".$this->paginacion;

            $productos = Db::getInstance()->ExecuteS($sql);
            foreach ($productos as $producto) {
                if ($_POST['accion']=="traducciones" && $_POST[$producto['id_product']]) {
                    $texto = $_POST[$producto['id_product']];
                    if ($producto[$this->idioma['iso_code']] != $texto) {
                        $producto[$this->idioma['iso_code']] = $texto;
                        Db::getInstance()->execute("UPDATE aalv_product_lang SET ".$this->campo_evaluacion." = '" . pSQL($texto) . "' where id_lang = ".$this->id_lang." AND id_product = ".$producto['id_product']);
                    }
                    if ($_POST['no_traducir_'.$producto['id_product']]) {
                        Db::getInstance()->execute("REPLACE aalv_alsernet_traducciones SET no_traducir=1, id_product = " . $producto['id_product']);
                    }else{
                        Db::getInstance()->execute("REPLACE aalv_alsernet_traducciones SET no_traducir=0, id_product = " . $producto['id_product']);
                    }
                }
                $control_traduccion = Db::getInstance()->ExecuteS("SELECT no_traducir FROM aalv_alsernet_traducciones WHERE id_product=".$producto['id_product'])[0];
                if ($control_traduccion['no_traducir'] == 1) {
                    $checked = " checked";
                    $disabled = " disabled";
                }else{
                    $checked = "";
                    $disabled = "";
                }
                $formulario_productos .= '<tr>
                            <td style="width: 7%;">'.htmlspecialchars($producto['id_product']).'</td>
                            <td style="width: 45%;">'.htmlspecialchars($producto['es']).'</td>';
                if ($this->campo_evaluacion == "description") {
                    $formulario_productos .= '<td><textarea style="width: 97%;" class="traduccion" rows="10" id="traducir_'.$producto['id_product'].'" name="'.$producto['id_product'].'" required>'.htmlspecialchars($producto[$this->idioma['iso_code']]).'</textarea>';
                }else{
                    $formulario_productos .= '<td><input type="text" style="width: 97%;" class="traduccion" name="'.$producto['id_product'].'" value="'.htmlspecialchars($producto[$this->idioma['iso_code']]).'" id="traducir_'.$producto['id_product'].'" required></td>';
                }

                $formulario_productos .= '
                            <td style="width: 1%; text-align: center;"><input type="checkbox" '.$checked.' id="no_traducir_'.$producto['id_product'].'" name="no_traducir_'.$producto['id_product'].'" class="no_traducir" /></td>
                            <td style="width: 1%; text-align: center;"><input type="checkbox" '.$disabled.' data-id_product="'.$producto['id_product'].'" data-id_lang="'.$this->id_lang.'" data-campo_evaluacion="'.$this->campo_evaluacion.'" id="deepl_traducir_'.$producto['id_product'].'" name="deepl_traducir_'.$producto['id_product'].'"  class="deepl_traducir" /></td>
                        </tr>';
            }
            if ($_POST['accion']=="traducciones") {
                $formulario_productos .= '<div class="mensaje">Actualizados los productos enviados.</div>';
            }
        }
        $formulario_productos .= '</tbody></table>';
        if ($this->paginacion !== null) {
            $siguiente_pagina = $this->paginacion + 10;
            $anterior_pagina = $this->paginacion > 0 ? $this->paginacion - 10 : 0;
            $url_siguiente= "&paginacion=".$siguiente_pagina;
            $url_anterior= "&paginacion=".$anterior_pagina;
        }

        $formulario_productos .= '<div style="text-align: right; margin: 10px;"><a href="'.$url.$url_anterior.'"><</a> '.$this->paginacion.'/'.$total_registros.' <a href="'.$url.$url_siguiente.'">></a></div>';
        $formulario_productos .= '<div style="width: 100%; text-align: center; padding: 20px;"><button type="submit">Enviar</button></div></form>';

        return $formulario_productos;

    }

    public function getFormularioTextos() {

        if ($_POST['texto_origen'] && $_POST['idioma_origen'] && $_POST['idioma_destino']) {
            $texto_traduccion = $this->traducirTexto($_POST['texto_origen'], $_POST['idioma_origen'], $_POST['idioma_destino'], $_POST['contexto']);
        }else{
            $texto_traduccion = "";
        }

        $selector_idioma_origen = '<select name="idioma_origen">';
        $selector_idioma_destino = '<select name="idioma_destino">';
        foreach ($this->getIdiomas() as $i) {
            $selected = "";
            if ($i['id_lang'] == $_POST['idioma_origen']) {
                $selected = " selected";
            }
            $selector_idioma_origen .= '<option value="'.$i['id_lang'].'"'.$selected.'>'.$i['name'].'</option>';
            if ($i['id_lang'] == $_POST['idioma_destino']) {
                $selected = " selected";
            }
            $selector_idioma_destino .= '<option value="'.$i['id_lang'].'"'.$selected.'>'.$i['name'].'</option>';
        }
        $selector_idioma_origen .= '</select>';
        $selector_idioma_destino .= '</select>';


        $formulario_textos = '<h2>TRADUCIR TEXTOS</h2>
        <form method="post" action="#">
        <input type="hidden" name="tipo_traduccion" value="'.$this->tipo_traduccion.'" />
        <div><h4 style="margin: 2px;">Contexto del texto introducido:</h4>
        <!--<p style="font-size: smaller;">(En caso de introducir un contexto no se aplican las reglas del diccionario)</p>-->
        <textarea style="width: 50%; margin-bottom: 20px;" class="traduccion" rows="3" id="contexto" name="contexto">'.$_POST['contexto'].'</textarea>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Idioma origen: '.$selector_idioma_origen.'</th>
                    <th>Idioma destino: '.$selector_idioma_destino.'</th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td><textarea style="width: 97%;" class="traduccion" rows="30" id="texto_origen" name="texto_origen" required>'.$_POST['texto_origen'].'</textarea></td>
                <td><textarea style="width: 97%;" class="traduccion" rows="30" id="texto_destino" name="texto_destino" disabled>'.$texto_traduccion.'</textarea></td>
            </tr>
            </tbody>
        </table>
        <div style="width: 100%; text-align: center; padding: 20px;"><button type="submit">Traducir</button></div></form>';

        return $formulario_textos;

    }

    public function getIdiomas() {
        return Db::getInstance()->ExecuteS("SELECT id_lang, name, iso_code FROM aalv_lang");
    }

    public function getCaracteristicas($id_lang = '') {
        if ($id_lang) {
            $where_lang = ' WHERE agl.id_lang='.$id_lang;
        }else{
            $where_lang = '';
        }

        return Db::getInstance()->ExecuteS("SELECT ag.id_attribute_group, agl.id_lang, agl.name
                                            FROM aalv_attribute_group ag
                                            LEFT JOIN aalv_attribute_group_lang as agl
                                                ON ag.id_attribute_group = agl.id_attribute_group".$where_lang." ORDER BY agl.name ASC");
    }

    public function traducirProducto($id_product, $id_lang, $campo_evaluacion) {

        $target_lang = $this->getIdiomaCode($id_lang);

        $translator = new \DeepL\Translator(_DEF_authKey);
        $glosaries = $translator->listGlossaries();
        $glosary_id = '';
        foreach ($glosaries as $glosario) {
            if($glosario->targetLang == $target_lang)
            {
                $glosary_id = $glosario->glossaryId;
                break;
            }
        }

        $query = "SELECT name, description FROM " . _DB_PREFIX_ . "product_lang WHERE id_lang=".$id_lang." AND id_product=".$id_product;
        $producto = Db::getInstance()->executeS($query)[0];
        $options = [
            'context' => strip_tags($producto['description']),
            'formality' => 'prefer_more',
            'tag_handling' => 'xml'
        ];

        if ($glosary_id != '') {
            $options['glossary'] =  $glosary_id;
        }

        $traduccion = $translator->translateText($producto[$campo_evaluacion], 'es', $target_lang, $options);

        return $traduccion->text;

    }

    public function traducirCaracteristica($id_attribute_group, $id_lang) {

        $target_lang = $this->getIdiomaCode($id_lang);

        $translator = new \DeepL\Translator(_DEF_authKey);
        $query = "SELECT name FROM " . _DB_PREFIX_ . "attribute_group_lang WHERE id_lang=".$id_lang." AND id_attribute_group=".$id_attribute_group;
        $caracteristica = Db::getInstance()->executeS($query)[0];
        $options = ['formality' => 'prefer_more'];

        $traduccion = $translator->translateText($caracteristica['name'], 'es', $target_lang, $options);

        return $traduccion->text;

    }

    public function traducirAtributo($id_attribute, $id_lang) {

        $target_lang = $this->getIdiomaCode($id_lang);

        $translator = new \DeepL\Translator(_DEF_authKey);
        $query = "SELECT name FROM " . _DB_PREFIX_ . "attribute_lang WHERE id_lang=".$id_lang." AND id_attribute=".$id_attribute;
        $atributo = Db::getInstance()->executeS($query)[0];
        $options = ['formality' => 'prefer_more'];

        $traduccion = $translator->translateText($atributo['name'], 'es', $target_lang, $options);

        return $traduccion->text;

    }

    public function traducirTexto($texto, $id_lang_origen, $id_lang_destino, $contexto = '') {

        $idioma_origen = $this->getIdiomaCode($id_lang_origen, 'iso_code');
        $idioma_destino = $this->getIdiomaCode($id_lang_destino);

        $translator = new \DeepL\Translator(_DEF_authKey);
        $glosaries = $translator->listGlossaries();
        $glosary_id = '';
        foreach ($glosaries as $glosario) {
            if($glosario->targetLang == $idioma_destino)
            {
                $glosary_id = $glosario->glossaryId;
                break;
            }
        }

        $options['formality'] = 'prefer_more';
        if ($contexto) {
            $options['context'] = $contexto;
        }
        if ($glosary_id != '') {
            $options['glossary'] = $glosary_id;
        }

        try {
            $traduccion = $translator->translateText($texto, $idioma_origen, $idioma_destino, $options);
        } catch(Exception $e) {
            dump($e->getMessage());
            // dump($texto, $idioma_origen, $idioma_destino, $options);
        }


        return $traduccion->text;

    }

    private function getIdiomaCode($id_lang, $tipo = '') {
        $query = "SELECT * FROM aalv_lang WHERE id_lang = ". $id_lang;
        $idioma = Db::getInstance()->executeS($query)[0];

        if ($tipo) {
            return $idioma[$tipo];
        }

        switch ($idioma['iso_code']) {
            case 'pt':
            case 'en':
              return $idioma['locale'];
            case 'es':
            case 'fr':
            case 'de':
            case 'it':
            default:
              return $idioma['iso_code'];
          }

    }

    private function obtenerProductosPorCategoria($ids_categoria = false) {
        $productos = [];
        if (is_array($ids_categoria)) {
            $categorias = implode(",",$ids_categoria);
        }elseif ($ids_categoria>0) {
            $categorias = $ids_categoria;
        }else{
            $categorias = '3,4,5,6,7,8,9,10,11';
        }


        // Obtener los productos de la categoría actual
        $query = "SELECT p.id_product FROM " . _DB_PREFIX_ . "category_product c LEFT JOIN " . _DB_PREFIX_ . "product p ON p.id_product=c.id_product WHERE p.active = 1 AND c.id_category IN (".$categorias.")";
        $result = Db::getInstance()->executeS($query);

        foreach ($result as $row) {
            $productos[] = $row['id_product'];
        }

        // Obtener las subcategorías de la categoría actual
        $query = "SELECT id_category FROM " . _DB_PREFIX_ . "category WHERE id_parent = $ids_categoria";
        $result = Db::getInstance()->executeS($query);

        foreach ($result as $row) {
            // Llamada recursiva para obtener los productos de la subcategoría
            $productos = array_merge($productos, $this->obtenerProductosPorCategoria($row['id_category']));
        }

        return $productos;
    }

    private function getProductoReferencia($id_attribute_group=null, $id_attribute=null) {

        $where_atributos = '';

        if ($id_attribute_group) {
            $where_atributos .= " AND aag.id_attribute_group=".$id_attribute_group;
        }
        if ($id_attribute) {
            $where_atributos .= " AND a.id_attribute=".$id_attribute;
        }
        $sql = "select
                                                pl.name producto,
                                                aagl.name caracteristica,
                                                al.name propiedad
                                            from
                                                aalv_product p
                                            left join aalv_product_lang pl on
                                                p.id_product = pl.id_product
                                            left join aalv_product_attribute pa on
                                                pa.id_product = p.id_product
                                            left join aalv_product_attribute_combination ac on
                                                pa.id_product_attribute = ac.id_product_attribute
                                            left join aalv_attribute a on
                                                a.id_attribute = ac.id_attribute
                                            left join aalv_attribute_lang al on
                                                a.id_attribute = al.id_attribute
                                            left join aalv_attribute_group aag on
                                                a.id_attribute_group = aag.id_attribute_group
                                            left join aalv_attribute_group_lang aagl on
                                                aagl.id_attribute_group = aag.id_attribute_group
                                            WHERE pl.id_lang=1
                                            AND al.id_lang = 1
                                            AND aagl.id_lang = 1
                                            ".$where_atributos."
                                            LIMIT 1";

        $data = Db::getInstance()->executeS($sql)[0];

        return $data['producto']." => ".$data['caracteristica']." => ".$data['propiedad'];
    }
}