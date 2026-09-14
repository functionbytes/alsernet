<?php
ini_set('max_execution_time', 1760000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

$archivo = 'pruebacaracteristicas.txt';
$limit = 0;
$url = 'https://a-alvarez.com/panel/pruebacaracteristicas.php?';

// Verifica si el archivo existe
if (file_exists($archivo)) {
    // Lee todo el contenido del archivo
    $contenido = file_get_contents($archivo);

    // Imprime el contenido del archivo
    $limit = nl2br($contenido); // nl2br() convierte los saltos de línea en etiquetas <br>
} else {
    echo 'El archivo no existe.';
}

if ($_REQUEST['id_attribute_group']) {
    $where_attribute = ' and aa.id_attribute_group = '.$_REQUEST['id_attribute_group'];
}
if ($_REQUEST['paginacion']) {
    $limit = $_REQUEST['paginacion'];
}

$contador = Db::getInstance()->ExecuteS('select count(*) contador from (select
                                            aal.id_attribute,
                                            GROUP_CONCAT(aal.name) as name
                                        from
                                            aalv_attribute aa
                                            left join aalv_attribute_lang aal on aal.id_attribute = aa.id_attribute
                                        where
                                            aal.id_lang in (1,3)
                                        GROUP BY aal.id_attribute) atributos')[0];

$sql = Db::getInstance()->ExecuteS("select
                                        aa.id_attribute_group,
                                        aal.id_attribute,
                                        GROUP_CONCAT(aal.name SEPARATOR ';-') as name
                                    from
                                        aalv_attribute aa
                                        left join aalv_attribute_lang aal on aal.id_attribute = aa.id_attribute
                                    where
                                        aal.id_lang in (1,3)
                                        ".$where_attribute.'
                                    GROUP BY aal.id_attribute
                                    limit 20 OFFSET '.$limit);

$total_registros = $contador['contador'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibe los valores de los inputs
    foreach ($_POST as $key => $value) {
        if ($key == 'paginacion' || $key == 'id_attribute_group') {
            continue;
        }
        foreach ($sql as $k => $val) {
            if ($val['id_attribute'] == $key) {
                $name = explode(',', $val['name']);
                if ($name[1] != $value) {
                    // dump("UPDATE aalv_attribute_lang SET `name` = '".pSQL($value)."' where id_lang = 3 AND id_attribute = ".$key);
                    Db::getInstance()->execute("UPDATE aalv_attribute_lang SET `name` = '".pSQL($value)."' where id_lang = 3 AND id_attribute = ".$key);
                }
            }
        }
    }
    /*
    $nuevoContenido = $limit;
    if (file_put_contents($archivo, $nuevoContenido) !== false) {
        header("Location: https://a-alvarez.com/panel/pruebacaracteristicas.php");
    }
    */
    $sql = Db::getInstance()->ExecuteS("select
                                            aa.id_attribute_group,
                                            aal.id_attribute,
                                            GROUP_CONCAT(aal.name SEPARATOR ';-') as name
                                        from
                                            aalv_attribute aa
                                            left join aalv_attribute_lang aal on aal.id_attribute = aa.id_attribute
                                        where
                                            aal.id_lang in (1,3)
                                            ".$where_attribute.'
                                        GROUP BY aal.id_attribute
                                        limit 20 OFFSET '.$limit);

}
if ($limit !== null) {
    $siguiente_pagina = $limit + 20;
    $anterior_pagina = $limit > 0 ? $limit - 20 : 0;
    $url_siguiente = '&paginacion='.$siguiente_pagina;
    $url_anterior = '&paginacion='.$anterior_pagina;
}
// var_dump("select
// aa.id_attribute_group,
// aal.id_attribute,
// GROUP_CONCAT(aal.name SEPARATOR ';') as name
// from
// aalv_attribute aa
// left join aalv_attribute_lang aal on aal.id_attribute = aa.id_attribute
// where
// aal.id_lang in (1,3)
// ".$where_attribute."
// GROUP BY aal.id_attribute
// limit 20 OFFSET ".$limit);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tabla de Productos</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 5px;
            text-align: left;
        }
        input.changed {
            background-color: #d4edda;
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
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var inputs = document.querySelectorAll('.fr');
            inputs.forEach(function(input) {
                input.addEventListener('blur', function() {
                    input.classList.add('changed');
                });
            });
        });
    </script>
</head>
<body>
<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo '<div class="mensaje">Actualizados los productos enviados.</div>';
    }
?>
<form method="post" action="#">
    <input type="hidden" name="paginacion" value="<?php echo $limit; ?>" >
    Grupo de atributos: <input type="text" value="<?php echo $_REQUEST['id_attribute_group']; ?>" name="id_attribute_group" /><button type="submit">Filtrar</button>

    <table style="margin-top: 25px;">
        <thead>
            <tr>
                <th>ID Atributos</th>
                <th>Atributo</th>
                <th>Nombre Español</th>
                <th>Nombre Francés</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sql as $producto) {

                $name = explode(';-', $producto['name']);
                // if($producto['id_attribute'] == 3221){
                //     echo "<br>";
                //     var_dump($producto['name']);
                //     echo "<br>";
                //     var_dump(explode(";",$producto['name']));
                // }
                $atributo = Db::getInstance()->ExecuteS('select id_attribute_group, name
                from aalv_attribute_group_lang
                where id_attribute_group='.$producto['id_attribute_group'])[0];
                ?>
            <tr>
                <td style="width: 7%;"><?php echo htmlspecialchars($producto['id_attribute']); ?></td>
                <td style="width: 7%;"><?php echo htmlspecialchars($atributo['name'].' ('.$atributo['id_attribute_group'].')'); ?></td>
                <td style="width: 45%;"><?php echo htmlspecialchars($name[0]); ?></td>
                <td>
                    <?php
                    if (strpos($name[1], "'") !== false) { ?>
                        <input type="text" class='fr' name="<?php echo $producto['id_attribute']; ?>" value=<?php echo '"'.$name[1].'"'; ?> required style="width: 97%;">
                    <?php } else { ?>
                        <input type="text" class='fr' name="<?php echo $producto['id_attribute']; ?>" value=<?php echo "'".$name[1]."'"; ?> required style="width: 97%;">
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <div style="text-align: right; margin: 10px;"><a href="<?php echo $url.$url_anterior; ?>"><</a> <?php echo $limit; ?>/<?php echo $total_registros; ?> <a href="<?php echo $url.$url_siguiente; ?>">></a></div>
    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>