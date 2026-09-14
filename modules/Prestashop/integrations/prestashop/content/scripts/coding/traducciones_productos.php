<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';

$idiomas_traduccion = [];
$productos_traduccion = false;
$categorias_traduccion = false;
$todo = true;
$id_producto = '';
$id_categoria = '';

if (is_array($argv)) {
    foreach ($argv as $parametro) {
        if (strpos($parametro, 'idiomas') !== false) {
            $idiomas_traduccion = explode(',', str_replace('idiomas=', '', $parametro));
        }
        if (strpos($parametro, 'producto=') !== false) {
            $id_producto = str_replace('producto=', '', $parametro);

        }
        if (strpos($parametro, 'productos=') !== false) {
            $listado_productos = str_replace('productos=', '', $parametro);

        }
        if (strpos($parametro, 'categoria=') !== false) {
            $id_categoria = str_replace('categoria=', '', $parametro);
            $where_categoria = ' AND id_category = '.$id_categoria;
        }
        if ($parametro == 'categorias') {
            $categorias_traduccion = true; // Por ahora no se traducen
            $todo = false;
        }
        if ($parametro == 'productos') {
            $productos_traduccion = true;
            $todo = false;
        }
        if ($parametro == 'sobreescribe') {
            $sobreescribe = true;
        }
        if ($parametro == 'diccionario') {
            $diccionario_traduccion = true;
            $todo = false;
        }
        if ($parametro == 'debug') {
            $debug = true;
        }
    }
}

if ($idiomas_traduccion) {
    $idiomas = [];
    foreach ($idiomas_traduccion as $iso_code) {
        $idioma = Db::getInstance()->ExecuteS("SELECT * FROM aalv_lang WHERE iso_code = '".$iso_code."'");
        if ($idioma) {
            $idiomas[] = $idioma[0];
        }
    }
} else {
    $sql = 'SELECT * FROM aalv_lang WHERE id_lang > 1';
    $idiomas = Db::getInstance()->ExecuteS($sql);
}

foreach ($idiomas as $idioma) {
    // Traducción de productos
    if ($todo || $productos_traduccion || $id_producto || $listado_productos) {
        if ($id_producto) {
            echo 'El producto '.$id_producto.' se traduce a: '.$idioma['iso_code']."\n";
            exec('/usr/bin/php /var/www/clients/client1/web1/web/bin/console dgcontenttranslation:translate --from_lang=es --dest_lang='.$idioma['iso_code'].' --tables="product_lang" --overwrite=on --range='.$id_producto.':'.$id_producto);
            peticionget('https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product='.$id_producto);

            continue;
        }

        if ($sobreescribe && $listado_productos) {
            $sql = 'SELECT * from (SELECT
                                    p.id_product,   (select l1.name 
                                                        from aalv_product_lang l1 
                                                        where l1.id_product=p.id_product 
                                                            AND l1.id_lang=1) as es, 
                                                    (select l2.name
                                                        from aalv_product_lang l2 
                                                        where l2.id_product=p.id_product 
                                                            AND l2.id_lang='.$idioma['id_lang'].') as '.$idioma['iso_code'].'
                                    FROM aalv_product p
                                    WHERE p.active = 1) as traducciones 
                    WHERE id_product IN ('.$listado_productos.')
                    ORDER by id_product DESC';

        } else {
            if ($listado_productos) {
                $where_productos = ' AND id_product IN ('.$listado_productos.')';
            }
            $sql = 'SELECT * from (SELECT
                                p.id_product,   (select l1.name 
                                                    from aalv_product_lang l1 
                                                    where l1.id_product=p.id_product 
                                                        AND l1.id_lang=1) as es, 
                                                (select l2.name
                                                    from aalv_product_lang l2 
                                                    where l2.id_product=p.id_product 
                                                        AND l2.id_lang='.$idioma['id_lang'].') as '.$idioma['iso_code'].'
                                FROM aalv_product p
                                WHERE p.active = 1) as traducciones 
                WHERE es='.$idioma['iso_code']." and es!=''
                ".$where_productos.'
                ORDER by id_product DESC';
        }

        $productos = Db::getInstance()->ExecuteS($sql);

        foreach ($productos as $producto) {
            if (! $producto['es']) {
                continue;
            }
            if ($producto['es'] == $producto[$idioma['iso_code']] || $sobreescribe) {
                if ($sobreescribe) {
                    echo 'El producto '.$producto['id_product'].' ya está traducido, se sobreescribe: '.$idioma['iso_code']."\n";
                } else {
                    echo 'El producto '.$producto['id_product'].' no está traducido: '.$idioma['iso_code']."\n";
                }
                exec('/usr/bin/php /var/www/clients/client1/web1/web/bin/console dgcontenttranslation:translate --from_lang=es --dest_lang='.$idioma['iso_code'].' --tables="product_lang" --overwrite=on --range='.$producto['id_product'].':'.$producto['id_product']);
                peticionget('https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product='.$producto['id_product']);
            }
        }
    }

    // Traducción de categorías
    /*
    if ($todo || $categorias_traduccion || $id_categoria) {

        $sql = "SELECT * from (SELECT
                                p.id_category,   (select l1.name
                                                    from aalv_category_lang l1
                                                    where l1.id_category=p.id_category
                                                        AND l1.id_lang=1) as es,
                                                (select l2.name
                                                    from aalv_category_lang l2
                                                    where l2.id_category=p.id_category
                                                        AND l2.id_lang=".$idioma['id_lang'].") as ".$idioma['iso_code']."
                                FROM aalv_category p
                                WHERE p.active = 1) as traducciones
                WHERE id_category IN (15,18,22,28,34,96,174,175,176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,199,201,202,203,204,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,240,242,243,244,245,11223,84756,705,706,707,708,709,710,711,712,713,714)";

        $sql = "SELECT * from (SELECT
                                p.id_category,   (select l1.name
                                                    from aalv_category_lang l1
                                                    where l1.id_category=p.id_category
                                                        AND l1.id_lang=1) as es,
                                                (select l2.name
                                                    from aalv_category_lang l2
                                                    where l2.id_category=p.id_category
                                                        AND l2.id_lang=".$idioma['id_lang'].") as ".$idioma['iso_code']."
                                FROM aalv_category p
                                WHERE p.active = 1) as traducciones
                WHERE es=".$idioma['iso_code']." and es!=''
                ".$where_producto;

        $categorias = Db::getInstance()->ExecuteS($sql);
        foreach ($categorias as $categoria) {
            if (!$categoria['es']) continue;
            if ($categoria['es'] == $categoria[$idioma['iso_code']] || $sobreescribe) {
                echo "La categoría ".$categoria['id_category']." no está traducida: ".$idioma['iso_code']."\n";
                exec('/usr/bin/php /var/www/clients/client1/web1/web/bin/console dgcontenttranslation:translate --from_lang=es --dest_lang='.$idioma["iso_code"].' --tables="category_lang" --overwrite=on --range='.$categoria["id_category"].':'.$categoria["id_category"]);
                peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&category=" . $categoria['id_category']);
            }
        }
    }
    */

    // Diccionario inteligente
    if ($todo || $diccionario_traduccion) {
        $diccionario = json_decode(Db::getInstance()->getValue("SELECT value FROM aalv_configuration WHERE name='dingedi_smart_dictionary'"));
        foreach ($diccionario as $palabra) {
            if ($palabra->from == 1) {
                $palabra_origen = $palabra->{'1'};
                if (! property_exists('palabra', $idioma['id_lang'])) {
                    continue;
                }
                $palabra_destino = $palabra->{$idioma['id_lang']};

                if ($palabra_destino && $palabra_origen != $palabra_destino) {
                    if ($palabra->caseSensitive == true) {
                        $replace = "REGEXP_REPLACE(name, '(?i)".$palabra_origen."', '".$palabra_destino."')";
                    } else {
                        $replace = "REPLACE(name, '".$palabra_origen."', '".$palabra_destino."')";
                    }

                    $sql_nombre = Db::getInstance()->ExecuteS('SELECT name FROM aalv_product_lang WHERE id_lang = '.$idioma['id_lang']." AND name LIKE '%".$palabra_origen."%'");
                    if ($sql_nombre) {
                        echo 'Traducimos la palabra "'.$palabra_origen.'" como "'.$palabra_destino.'" en el campo nombre del idioma: '.$idioma['iso_code']."\n";
                        Db::getInstance()->ExecuteS('UPDATE aalv_product_lang SET name='.$replace.' WHERE id_lang = '.$idioma['id_lang']." AND name LIKE '%".$palabra_origen."%'");
                    }

                    $sql_descripcion = Db::getInstance()->ExecuteS('SELECT description FROM aalv_product_lang WHERE id_lang = '.$idioma['id_lang']." AND description LIKE '%".$palabra_destino."%'");
                    if ($sql_descripcion) {
                        echo 'Traducimos la palabra "'.$palabra_origen.'" como "'.$palabra_destino.'" en el campo descripción del idioma: '.$idioma['iso_code']."\n";
                        Db::getInstance()->ExecuteS("UPDATE aalv_product_lang SET description=REPLACE(description, '".$palabra_origen."', '".$palabra_destino."') WHERE id_lang = ".$idioma['id_lang']." AND description LIKE '%".$palabra_origen."%'");
                    }
                }
            }
        }
    }
}

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
}
