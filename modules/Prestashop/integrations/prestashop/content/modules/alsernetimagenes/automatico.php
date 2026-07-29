<?php
// Incluir los archivos de configuración y de inicialización de PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

// Realizar una consulta a la base de datos para obtener un modelo de producto sin imagen asociada.
// Se buscan modelos en la tabla 'aalv_product_attribute' y se excluyen aquellos que ya tienen imágenes en 'aalv_product_attribute_image'.
// También se asegura de que el modelo esté en el idioma con ID 1, el producto esté activo y visible en ambas categorías.
// Finalmente, se excluyen los modelos que ya están presentes en la tabla 'aalv_alsernet_imagenes'.
$db = Db::getInstance()->executeS("SELECT DISTINCT(imp.id_modelo)
                                    FROM aalv_product_attribute att
                                    LEFT JOIN aalv_product_attribute_image img ON img.id_product_attribute = att.id_product_attribute
                                    LEFT JOIN aalv_product prod ON prod.id_product = att.id_product
                                    LEFT JOIN aalv_product_import imp ON imp.id_product = att.id_product
                                    LEFT JOIN aalv_product_lang lang ON lang.id_product = att.id_product
                                    LEFT JOIN aalv_image ima ON ima.id_product = att.id_product
                                    WHERE img.id_image IS NULL
                                    AND lang.id_lang = 1
                                    AND prod.active = true AND prod.visibility = 'both'
                                    AND imp.id_modelo NOT IN (SELECT exc.id_modelo FROM aalv_alsernet_imagenes exc)
                                    AND prod.reference = ''
                                    ORDER BY RAND()
                                    LIMIT 1");

// Devolver la respuesta en formato JSON, con el ID del modelo seleccionado o NULL si no se encontró ningún modelo.
echo json_encode($db[0]['id_modelo']);
