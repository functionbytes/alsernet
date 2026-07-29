<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
require _PS_ADMIN_DIR_.'/../config/config.panel.inc.php';
include dirname(__FILE__).'/init.php';
require dirname(__FILE__).'/../override/classes/Traduccion.php';

$traduccion = new Traduccion;

$javascript = $traduccion->getJavascript();
$css = $traduccion->getCSS();

if ($_REQUEST) {
    $traduccion->id_categoria = $_REQUEST['id_categoria'] ?: 0;
    $traduccion->id_lang = $_REQUEST['id_lang'] ?: 0;
    $traduccion->id_attribute_group = $_REQUEST['id_attribute_group'] ?: 0;
    $traduccion->texto_filtro = $_REQUEST['texto'];
    $traduccion->texto_filtro_traduccion = $_REQUEST['texto_traduccion'];
    $traduccion->estado_traduccion = $_REQUEST['estado_traduccion'];
    $traduccion->tipo_traduccion = $_REQUEST['tipo_traduccion'];
    switch ($_REQUEST['campo_evaluacion']) {
        case 'description':
            $traduccion->campo_evaluacion = 'description';
            break;
        case 'name':
        default:
            $traduccion->campo_evaluacion = 'name';
            break;
    }
}
if ($_POST['paginacion']) {
    $traduccion->paginacion = $_POST['paginacion'];
} elseif ($_GET['paginacion']) {
    $traduccion->paginacion = $_GET['paginacion'];
} else {
    $traduccion->paginacion = 0;
}

$idiomas = $traduccion->getIdiomas();
foreach ($idiomas as $i) {
    if ($i['id_lang'] == $traduccion->id_lang) {
        $traduccion->idioma = $i;
    }
}

// Retorno petición AJAX
if ($_POST) {
    if ($_POST['traduccion'] == 'deepl' && $_POST['campo_evaluacion']) {
        if ($_POST['id_product'] > 0) {
            echo json_encode($traduccion->traducirProducto($_POST['id_product'], $_POST['id_lang'], $_POST['campo_evaluacion']));
        } elseif ($_POST['id_attribute_group'] > 0) {
            echo json_encode($traduccion->traducirCaracteristica($_POST['id_attribute_group'], $_POST['id_lang']));
        } elseif ($_POST['id_attribute'] > 0) {
            echo json_encode($traduccion->traducirAtributo($_POST['id_attribute'], $_POST['id_lang']));
        }
        exit;
    }
}

$url = $_SERVER['PHP_SELF'].'?';
if ($traduccion->id_categoria !== null) {
    $url .= '&id_categoria='.$traduccion->id_categoria;
}
if ($traduccion->id_lang !== null) {
    $url .= '&id_lang='.$traduccion->id_lang;
}
if ($traduccion->estado_traduccion !== null) {
    $url .= '&estado_traduccion='.$traduccion->estado_traduccion;
}
if ($traduccion->campo_evaluacion !== null) {
    $url .= '&campo_evaluacion='.$traduccion->campo_evaluacion;
}
if ($traduccion->texto_filtro !== null) {
    $url .= '&texto='.$traduccion->texto_filtro;
}

$selector_tipo_traduccion = '<select name="tipo_traduccion">';
$selector_tipo_traduccion .= '<option value="texto">Traducir textos</option>';
$selector_tipo_traduccion .= '<option value="productos">Traducir productos</option>';
$selector_tipo_traduccion .= '<option value="caracteristicas">Traducir características</option>';
$selector_tipo_traduccion .= '<option value="atributos">Traducir atributos</option>';
$selector_tipo_traduccion .= '</select>';

$selected = '';
$selector_categoria = '<select name="id_categoria">';
if (! $traduccion->id_categoria) {
    $selected = ' selected';
}
$selector_categoria .= '<option value=""'.$selected.'></option>';
$selected = '';
if ($traduccion->id_categoria == 3) {
    $selected = ' selected';
}
$selector_categoria .= '<option value="3"'.$selected.'>Golf</option>';
$selected = '';
if ($traduccion->id_categoria == 4) {
    $selected = ' selected';
}
$selector_categoria .= '<option value="4"'.$selected.'>Caza</option>';
$selected = '';
if ($traduccion->id_categoria == 5) {
    $selected = ' selected';
}
$selector_categoria .= '<option value="5"'.$selected.'>Pesca</option>';
$selected = '';
if ($traduccion->id_categoria == 6) {
    $selected = ' selected';
}
$selector_categoria .= '<option value="6"'.$selected.'>Hípica</option>';
$selected = '';
if ($traduccion->id_categoria == 7) {
    $selected = ' selected';
}
$selector_categoria .= '<option value="7"'.$selected.'>Buceo</option>';
$selected = '';
if ($traduccion->id_categoria == 8) {
    $selected = ' selected';
}
$selector_categoria .= '<option value="8"'.$selected.'>Náutica</option>';
$selected = '';
if ($traduccion->id_categoria == 9) {
    $selected = ' selected';
}
$selector_categoria .= '<option value="9"'.$selected.'>Esquí</option>';
$selected = '';
if ($traduccion->id_categoria == 10) {
    $selected = ' selected';
}
$selector_categoria .= '<option value="10"'.$selected.'>Pádel</option>';
$selected = '';
if ($traduccion->id_categoria == 11) {
    $selected = ' selected';
}
$selector_categoria .= '<option value="11"'.$selected.'>Aventura</option>';
$selector_categoria .= '</select>';

$selector_idiomas = '<select name="id_lang">';
foreach ($idiomas as $i) {
    $selected = '';
    if ($i['id_lang'] == $traduccion->id_lang) {
        $selected = ' selected';
    }
    if ($i['id_lang'] == 1) {
        continue;
    }
    $selector_idiomas .= '<option value="'.$i['id_lang'].'"'.$selected.'>'.$i['name'].'</option>';
}
$selector_idiomas .= '</select>';

$selector_caracteristicas = '<select name="id_attribute_group">';
$selector_caracteristicas .= '<option value=""></option>';
foreach ($traduccion->getCaracteristicas(1) as $i) {
    $selected = '';
    if ($i['id_attribute_group'] == $traduccion->id_attribute_group) {
        $selected = ' selected';
    }
    if ($i['id_attribute_group'] == 1) {
        continue;
    }
    $selector_caracteristicas .= '<option value="'.$i['id_attribute_group'].'"'.$selected.'>'.$i['name'].'</option>';
}
$selector_caracteristicas .= '</select>';

$selected = '';
$selector_estado = '<select name="estado_traduccion">';
if ($traduccion->estado_traduccion == 1) {
    $selected = ' selected';
}
$selector_estado .= '<option value="1"'.$selected.'>Pendientes de traducción</option>';
$selected = '';
if ($traduccion->estado_traduccion == 2) {
    $selected = ' selected';
}
$selector_estado .= '<option value="2"'.$selected.'>Traducidos</option>';
$selector_estado .= '</select>';

$selected = '';
$selector_campo = '<select name="campo_evaluacion">';
if ($traduccion->campo_evaluacion == 'name') {
    $selected = ' selected';
}
$selector_campo .= '<option value="name"'.$selected.'>Nombre</option>';
$selected = '';
if ($traduccion->campo_evaluacion == 'description') {
    $selected = ' selected';
}
$selector_campo .= '<option value="description"'.$selected.'>Descripción</option>';
$selector_campo .= '</select>';

$texto_buscar = '<input type="text" value="'.$traduccion->texto_filtro.'" name="texto" />';
$texto_buscar_traduccion = '<input type="text" value="'.$traduccion->texto_filtro_traduccion.'" name="texto_traduccion" />';

switch ($traduccion->tipo_traduccion) {
    case 'texto':
        $formulario_traduccion = $traduccion->getFormularioTextos();
        break;
    case 'productos':
        $formulario_filtro = '
        <h2>FILTRO</h2>
        <form class="filtro" method="post" action="#">
        <input type="hidden" name="accion" value="filtro" />
        <input type="hidden" name="paginacion" value="0" />
        <input type="hidden" name="tipo_traduccion" value="'.$traduccion->tipo_traduccion.'" />
        <table>
        <tr><td>Categoría:</td><td>'.$selector_categoria.'</td>
        <tr><td>Idioma destino:</td><td>'.$selector_idiomas.'</td>
        <tr><td>Campo a evaluar:</td><td>'.$selector_campo.'</td>
        <tr><td>Texto a buscar (es):</td><td>'.$texto_buscar.'</td>';
        if ($traduccion->idioma) {
            $formulario_filtro .= '<tr><td>Texto a buscar ('.$traduccion->idioma['iso_code'].'):</td><td>'.$texto_buscar_traduccion.'</td>';
        }
        $formulario_filtro .= '
        <tr><td>Estado traducción:</td><td>'.$selector_estado.'</td>
        <tr><td colspan="2"><button type="submit">Aplicar filtro</button></td></tr></table></form>';

        $formulario_traduccion = $traduccion->getFormularioProductos();
        break;
    case 'caracteristicas':
        $formulario_filtro = '
        <h2>FILTRO</h2>
        <form class="filtro" method="post" action="#">
        <input type="hidden" name="accion" value="filtro" />
        <input type="hidden" name="paginacion" value="0" />
        <input type="hidden" name="tipo_traduccion" value="'.$traduccion->tipo_traduccion.'" />
        <table>
        <tr><td>Idioma destino:</td><td>'.$selector_idiomas.'</td>
        <tr><td>Texto a buscar (es):</td><td>'.$texto_buscar.'</td>';
        if ($traduccion->idioma) {
            $formulario_filtro .= '<tr><td>Texto a buscar ('.$traduccion->idioma['iso_code'].'):</td><td>'.$texto_buscar_traduccion.'</td>';
        }
        $formulario_filtro .= '
        <tr><td>Estado traducción:</td><td>'.$selector_estado.'</td>
        <tr><td colspan="2"><button type="submit">Aplicar filtro</button></td></tr></table></form>';

        $formulario_traduccion = $traduccion->getFormularioCategorias();
        break;
    case 'atributos':
        $formulario_filtro = '
        <h2>FILTRO</h2>
        <form class="filtro" method="post" action="#">
        <input type="hidden" name="accion" value="filtro" />
        <input type="hidden" name="paginacion" value="0" />
        <input type="hidden" name="tipo_traduccion" value="'.$traduccion->tipo_traduccion.'" />
        <table>
        <tr><td>Idioma destino:</td><td>'.$selector_idiomas.'</td>
        <tr><td>Característica:</td><td>'.$selector_caracteristicas.'</td>
        <tr><td>Texto a buscar (es):</td><td>'.$texto_buscar.'</td>';
        if ($traduccion->idioma) {
            $formulario_filtro .= '<tr><td>Texto a buscar ('.$traduccion->idioma['iso_code'].'):</td><td>'.$texto_buscar_traduccion.'</td>';
        }
        $formulario_filtro .= '
        <tr><td>Estado traducción:</td><td>'.$selector_estado.'</td>
        <tr><td colspan="2"><button type="submit">Aplicar filtro</button></td></tr></table></form>';

        $formulario_traduccion = $traduccion->getFormularioAtributos();
        break;
    default:
        $formulario_filtro = '
        <h2>Seleccionar que traducir</h2>
        <form class="filtro" method="post" action="#">
        <table>
        <tr><td>'.$selector_tipo_traduccion.'</td></tr>
        <tr><td><button type="submit">Enviar</button></td></tr></table>
        </form>';

}

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tabla de Productos</title>
    <script src="https://ana.preproduccion.a-alvarez.com/static/assets/js/vendor/jquery-1.11.1.min.js"></script>
    <style>'.$css.'</style>
    <script>'.$javascript.'</script>
</head>

<body>
    <div style="width: 100%; text-align: right;"><a href="'.$traduccion->url.'"><- Volver al inicio</a></div>
    <div>
        '.$formulario_filtro.'
    </div>
    <div class="productos">
        '.$formulario_traduccion.'

    </div>
</body>
</html>';

/*
        <div>
            <div class="categorias">
                <h2>BUSCAR Y MODIFICAR CATEGORÍAS</h2>
                <p>En este apartado puedes realizar una búsqueda de la categoría que quieras traducir. En la segunda columna de la tabla puedes realizar la traducción directamente. También sirve para buscar todas las categorías que contengan una palabra determinada (por ejemplo: caza), y te mostrará un listado más amplio.</p>
                <form method="GET" action="">
                    <label for="search">Buscar en categorías:</label>
                    <input type="text" id="search" name="search" required>
                    <button type="submit" name="lang" value="es">Buscar en español</button>
                    <button type="submit" name="lang" value="fr">Buscar en francés</button>
                </form>

                <div>
                    <?php
                    // Lógica PHP para manejar la búsqueda y mostrar resultados
                    $search = isset($_GET['search']) ? $_GET['search'] : '';
                    $lang = isset($_GET['lang']) ? $_GET['lang'] : '';

                    if (!empty($search) && !empty($lang)) {
                        $search = Db::getInstance()->escape($search);

                        if ($lang == 'es') {
                            $query_cat = "SELECT c1.id_category, c1.name AS name_es, c2.name AS name_fr
                          FROM aalv_category_lang c1
                          JOIN aalv_category_lang c2 ON c1.id_category = c2.id_category
                          WHERE c1.id_lang = 1 AND c2.id_lang = 1 AND (c1.name LIKE '%$search%' OR c2.name LIKE '%$search%')";
                        } elseif ($lang == 'fr') {
                            $query_cat = "SELECT c1.id_category, c1.name AS name_es, c2.name AS name_fr
                          FROM aalv_category_lang c1
                          JOIN aalv_category_lang c2 ON c1.id_category = c2.id_category
                          WHERE c1.id_lang = 1 AND c2.id_lang = 3 AND (c1.name LIKE '%$search%' OR c2.name LIKE '%$search%')";
                        }

                        $resultados = Db::getInstance()->ExecuteS($query_cat);

                        if ($resultados) {
                            echo '<form method="POST" action="">';
                            echo '<table>';
                            echo '<thead>
                    <tr>
                        <th>ID Categoría</th>
                        <th>Categoría en Español</th>
                        <th>Categoría en Francés</th>
                    </tr>
                  </thead>';
                            echo '<tbody>';

                            foreach ($resultados as $cat) {
                                $name_es = htmlspecialchars($cat['name_es']);
                                $name_fr = htmlspecialchars($cat['name_fr']);
                                echo "<tr>
                        <td>" . htmlspecialchars($cat['id_category']) . "</td>
                        <td>$name_es</td>
                        <td>
                            <input type='text' class='fr' name='categorias[" . htmlspecialchars($cat['id_category']) . "]' value='$name_fr' required style='width: 97%;'>
                        </td>
                      </tr>";
                            }

                            echo '</tbody>';
                            echo '</table>';
                            echo '<button type="submit" name="update_categories" value="update">Aprobado</button>';
                            echo '</form>';
                        } else {
                            echo "<table><tbody><tr><td colspan='3'>No se encontraron categorías para: " . htmlspecialchars($search) . "</td></tr></tbody></table>";
                        }
                    } else {
                        echo "<table><tbody><tr><td colspan='3'>Por favor, ingrese un término de búsqueda y seleccione un idioma.</td></tr></tbody></table>";
                    }
                    ?>
                </div>
            </div>

            <?php
            if (isset($_POST['update_categories']) && $_POST['update_categories'] === 'update') {
                if (isset($_POST['categorias']) && is_array($_POST['categorias'])) {
                    foreach ($_POST['categorias'] as $id_category => $value) {
                        $category_name = Db::getInstance()->escape($value);
                        $id_category = Db::getInstance()->escape($id_category);

                        Db::getInstance()->execute("UPDATE aalv_category_lang SET `name` = '$category_name' WHERE id_lang = 3 AND id_category = $id_category");
                    }
                    echo "<p>Actualización completada.</p>";
                } else {
                    echo "<p>No se recibieron datos válidos para actualizar.</p>";
                }
            }
            ?>

            <?php
            if (isset($_POST['update_text']) && $_POST['update_text'] === 'update_text') {
                $search_replace = isset($_POST['search_replace']) ? trim($_POST['search_replace']) : '';
                $replacement_text = isset($_POST['replacement_text']) ? trim($_POST['replacement_text']) : '';

                if (!empty($search_replace) && !empty($replacement_text)) {
                    $search_replace_escaped = Db::getInstance()->escape($search_replace);
                    $replacement_text_escaped = Db::getInstance()->escape($replacement_text);

                    // Construcción y ejecución de la consulta UPDATE
                    $query = "UPDATE aalv_product_lang
                  SET name = REPLACE(name, '$search_replace_escaped', '$replacement_text_escaped')
                  WHERE id_lang = 3
                  AND (
                      name LIKE '% $search_replace_escaped %'
                      OR name LIKE '$search_replace_escaped %'
                      OR name LIKE '% $search_replace_escaped'
                      OR name = '$search_replace_escaped'
                  )";

                    Db::getInstance()->execute($query);

                    echo "<p>Actualización de texto completada.</p>";
                } else {
                    echo "<p>Por favor, complete ambos campos para actualizar el texto.</p>";
                }
            }
            ?>


            <!-- Formulario para buscar productos -->
            <div style="color: #006600; background-color: #e0e0e0; padding: 20px;">

                <div style="padding: 1rem;">
                    <h2>BUSCAR POR PALABRA [PRODUCTOS]</h2>
                    <p>Puedes buscar palabras dentro de los nombres de los productos (por ejemplo: carrete). Te mostrará un listado de todos los productos que contienen esa palabra en el nombre.</p>
                    <form method="GET" action="">
                        <label for="search_product">Buscar todos los productos que tengan la palabra:</label>
                        <input type="text" id="search_product" name="search_product" required>
                        <button type="submit">Buscar</button>
                    </form>
                    <form method="POST" action="">
                        <button type="submit" name="clear_results_product">Limpiar resultados</button>
                    </form>
                </div>


                <?php
                if (isset($_GET['search_product'])) {
                    $search_word = isset($_GET['search_product']) ? trim($_GET['search_product']) : '';


                    if (!empty($search_word)) {
                        $search_word = Db::getInstance()->escape($search_word);

                        $query_select = "SELECT id_product, name
                         FROM aalv_product_lang
                         WHERE id_lang = 3
                         AND (
                             name LIKE '% $search_word %'
                             OR name LIKE '$search_word %'
                             OR name LIKE '% $search_word'
                             OR name = '$search_word'
                         )";

                        $resultados = Db::getInstance()->ExecuteS($query_select);

                        if ($resultados) {


                            echo '<table>';
                            echo '<thead>
                    <tr>
                        <th>ID PRODUCTO</th>
                        <th>PRODUCTO</th>
                    </tr>
                  </thead>';
                            echo '<tbody>';

                            foreach ($resultados as $prod) {
                                echo "<tr>
                        <td>" . htmlspecialchars($prod['id_product']) . "</td>
                        <td>" . htmlspecialchars($prod['name']) . "</td>
                      </tr>";
                            }

                            echo "</tbody>
                  </table>";
                        } else {
                            echo "<p>No se encontraron productos que contengan la palabra '{$search_word}'.</p>";
                        }
                    } else {
                        echo "<p>Por favor, ingrese una palabra para buscar.</p>";
                    }
                }


                if (isset($_POST['clear_results'])) {
                    echo "<p>Resultados limpiados.</p>";
                }
                ?>


                <div style="padding: 1rem;">
                    <h2>MODIFICAR PALABRAS EN PRODUCTOS</h2>
                    <p>Funcionalidad que cambia una palabra por otra en el nombre de los productos. Por ejemplo - texto a modificar: cane; texto sustituto: canne. </p>
                    <p>Te ayudará a sustituir palabras que se hayan traducido mal y que se repitan en muchos productos.</p>
                    <p>¡OJO! Ten en cuenta que distingue entre mayúsculas y minúsculas. Es decir, tendrás que sustituit tanto 'CANE', como 'cane', puesto que si solo sustituyes en mayúscula quedará mal siempre que esté en minúscula. </p>

                    <form method="POST" action="">
                        <label for="search_replace">Texto a modificar:</label>
                        <input type="text" id="search_replace" name="search_replace" required>
                        <label for="replacement_text">Texto sustituto:</label>
                        <input type="text" id="replacement_text" name="replacement_text" required>
                        <button type="submit" name="update_text" value="update_text">Actualizar Texto</button>
                    </form>
                </div>
            </div>

                <?php
                if (isset($_POST['update_text']) && $_POST['update_text'] === 'update_text') {
                    $search_replace = isset($_POST['search_replace']) ? trim($_POST['search_replace']) : '';
                    $replacement_text = isset($_POST['replacement_text']) ? trim($_POST['replacement_text']) : '';

                    if (!empty($search_replace) && !empty($replacement_text)) {
                        $search_replace_escaped = Db::getInstance()->escape($search_replace);
                        $replacement_text_escaped = Db::getInstance()->escape($replacement_text);


                        // Construcción y ejecución de la consulta UPDATE
                        $query = "UPDATE aalv_product_lang
                  SET name = REPLACE(name, '$search_replace_escaped', '$replacement_text_escaped')
                  WHERE id_lang = 3
                  AND (
                      name LIKE '%$search_replace_escaped %'
                      OR name LIKE '$search_replace_escaped %'
                      OR name LIKE '% $search_replace_escaped'
                      OR name = '$search_replace_escaped'
                  )";

                        Db::getInstance()->execute($query);

                        echo "<p>Actualización de texto completada.</p>";
                    } else {
                        echo "<p>Por favor, complete ambos campos para actualizar el texto.</p>";
                    }
                }
                ?>

                <!-- Formulario para buscar categorías -->

                <div style="padding: 1rem;">
                    <h2>BUSCAR POR PALABRA [CATEGORÍAS]</h2>
                    <form method="GET" action="">
                        <label for="search_category">Buscar todas las categorías que tengan la palabra:</label>
                        <input type="text" id="search_category" name="search_category" required>
                        <button type="submit">Buscar</button>
                    </form>
                    <form method="POST" action="">
                        <button type="submit" name="clear_results_category">Limpiar resultados</button>
                    </form>
                </div>

            </div>

            <?php
            if (isset($_GET['search_category'])) {
                $search_cat = isset($_GET['search_category']) ? trim($_GET['search_category']) : '';


                if (!empty($search_cat)) {
                    $search_cat = Db::getInstance()->escape($search_cat);

                    $query_select_cat = "SELECT id_category, name
                         FROM aalv_category_lang
                         WHERE id_lang = 3
                         AND (
                             name LIKE '% $search_cat %'
                             OR name LIKE '$search_cat %'
                             OR name LIKE '% $search_cat'
                             OR name = '$search_cat'
                         )";

                    $resultados_cat = Db::getInstance()->ExecuteS($query_select_cat);

                    if ($resultados_cat) {


                        echo '<table>';
                        echo '<thead>
                    <tr>
                        <th>ID CATEGORÍA</th>
                        <th>CATEGORÍA</th>
                    </tr>
                  </thead>';
                        echo '<tbody>';

                        foreach ($resultados_cat as $cat) {
                            echo "<tr>
                        <td>" . htmlspecialchars($cat['id_category']) . "</td>
                        <td>" . htmlspecialchars($cat['name']) . "</td>
                      </tr>";
                        }

                        echo "</tbody>
                  </table>";
                    } else {
                        echo "<p>No se encontraron productos que contengan la palabra '{$search_cat}'.</p>";
                    }
                } else {
                    echo "<p>Por favor, ingrese una palabra para buscar.</p>";
                }
            }


            if (isset($_POST['clear_results'])) {
                echo "<p>Resultados limpiados.</p>";
            }
            ?>


        <div style="padding: 1rem;">
            <h2>MODIFICAR PALABRAS EN CATEGORÍAS</h2>
            <form method="POST" action="">
                <label for="search_replace_cat">Texto a modificar:</label>
                <input type="text" id="search_replace_cat" name="search_replace_cat" required>
                <label for="replacement_text">Texto sustituto:</label>
                <input type="text" id="replacement_text_cat" name="replacement_text_cat" required>
                <button type="submit" name="update_text_cat" value="update_text_cat">Actualizar Texto</button>
            </form>
        </div>


            <?php
            if (isset($_POST['update_text_cat']) && $_POST['update_text_cat'] === 'update_text_cat') {
                $search_replace_cat = isset($_POST['search_replace_cat']) ? trim($_POST['search_replace_cat']) : '';
                $replacement_text = isset($_POST['replacement_cat']) ? trim($_POST['replacement_text_cat']) : '';

                if (!empty($search_replace_cat) && !empty($replacement_text_cat)) {
                    $search_replace_escaped_cat = Db::getInstance()->escape($search_replace_cat);
                    $replacement_text_escaped_cat = Db::getInstance()->escape($replacement_text_cat);


                    // Construcción y ejecución de la consulta UPDATE
                    $query_cat = "UPDATE aalv_category_lang
                  SET name = REPLACE(name, '$search_replace_escaped_cat', '$replacement_text_escaped_cat')
                  WHERE id_lang = 3
                  AND (
                      name LIKE '%$search_replace_escaped_cat %'
                      OR name LIKE '$search_replace_escaped_cat %'
                      OR name LIKE '% $search_replace_escaped_cat'
                      OR name = '$search_replace_escaped_cat'
                  )";

                    Db::getInstance()->execute($query_cat);

                    echo "<p>Actualización de texto completada.</p>";
                } else {
                    echo "<p>Por favor, complete ambos campos para actualizar el texto.</p>";
                }
            }
            ?>
*/
