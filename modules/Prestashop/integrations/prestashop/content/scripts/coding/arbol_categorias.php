<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../../config/config.inc.php';

$GLOBALS['contador'] = 0;

function construirArbolCategorias($parentId = 0, $id_cate = 0, $concate = '')
{

    $categories = [
        '3,1696,1698,1699,1697,1700,1701,1702,52,404,405,403,406,407,63,387,392,386,393,391,394,389,388,390,397,384,385,396,383,55,56,42,104759,61,62,40,39,38,41,44,57,58,65,380,379,381,54,66,43,82453,64732,49,64,45,415,414,413,60,53,
4,210,209,212,211,213,180,239,188,34,202,184,193,189,28,191,196,18,15,234,242,243,203,207,235,240,245,214,220,238,233,228,229,192,190,195,222,206,236,221,201,241,226,230,223,224,225,708,237,1705,1707,1708,1704,1706,1703,104758,614,606,608,609,615,613,611,612,185,603,610,625,621,178,607,604,624,181,617,616,618,619,182,12708,217,2098,2100,2097,2096,2099,208,2759,2784,2769,2764,2804,2774,2794,2799,2789,2809,22,216,104776,104777,186,219,179,198,197,84756,641,642,639,640,643,227,634,636,633,104773,631,637,638,632,635,218,596,602,589,590,599,601,600,598,597,215,177,187,11223,104788,
5,900,893,895,887,891,66229,894,903,902,888,897,889,885,890,899,884,898,896,886,901,892,261,904,915,907,911,913,906,910,908,912,909,905,262,1768,1773,1766,1771,1764,1775,1770,1765,1774,1767,924,923,926,925,269,270,253,271,246,272,281,280,282,276,247,283,284,278,275,291,279,288,285,292,249,16,252,258,290,257,259,287,35,289,29,19,251,273,23,1052,1058,1054,1057,1053,1055,1056,1059,1060,274,293,250,
6,420,419,421,422,423,424,417,416,418,120,476,477,475,472,480,474,471,473,470,479,478,137,125,135,104,449,440,102,457,436,442,434,444,446,105,437,435,451,456,113,438,106,450,127,439,447,445,448,441,432,103,126,433,101,128_SEL,110,99,111,107,443,119,118,131,133,136,487,488,486,484,489,491,485,490,482,132,112,122_SEL,494,506,497,495,505,499,504,496,503,498,502,466,459,467,465,460,461,464,463,468,469,462,130,123_SEL,109,21,1660,1661,1663,1662,1664,124,14,33,140,1381,1382,1383,1384,1385,1386,1387,1388,1389,1390,1391,1392,1393,1394,1395,1396,1397,134,98,108,
7,990,991,988,989,994,993,992,155,2212,2209,2207,2206,2208,2211,2213,2210,142,151,152,153,154,160,156,158,161,172,162,159,157,163,166,165,169,2375,2377,2378,2374,2379,2380,2383,2384,2385,148,141,143,144,170,2605,2618,2606,2615,2612,2611,2608,2607,2604,2609,2613,2614,2616,150,1801,1803,1805,1802,1812,1807,1806,1811,1808,1810,1809,1800,1804,167,173,149,
8,69,79,90,72,77,75,71,78,96,94,80,88,67,89,82,68,85,81,93,83,70,91,84,32,92,26,13,87,86,97,74,
9,322,307,309,24,324,313,325,323,306,305,320,303,317,311,330,36,328,30,320,329,304,302,301,321,326,308,312,327,1260,310,319,
10,1411,1412,1410,1413,1188,1192,2599,2600,2594,2596,2595,2597,1183,1194,1777,1778,1191,1193,1185,1184,
11,374,25,333,336,339,341,340,357,349,354,352,37,31,364,362,342,331,332,373,365,346,371,359,17,338,348,356,347,337,370,335,367,368,84757,369,20,375,351,361,345,363,372,343,353,360,358,355',
    ];

    $categoryIds = array_map('intval', explode(',', $categories[0]));

    $query = "SELECT
                cate.id_category,
                acl.name,
                cate.level_depth,
                IF(cate.active = 0,'No Activo','Activa') AS active
            FROM
                aalv_category cate
                LEFT JOIN aalv_category_lang acl ON acl.id_category = cate.id_category
            WHERE
                acl.id_lang = 1 AND
                cate.id_parent = $parentId";
    $columna = Db::getInstance()->ExecuteS($query);

    $result = []; // Arreglo para almacenar los resultados

    if (count($columna) != 0) {
        foreach ($columna as $key => $value) {
            if ($value['id_category'] == 12 || $value['id_category'] == 2820 || $value['id_category'] == 2821 || $value['id_category'] == 104762 || $value['id_category'] == 104787) {
                continue;
            }
            if ($id_cate == $value['id_category']) {
                if ($id_cate == 3) {
                    $concate = 'GOL';
                } elseif ($id_cate == 4) {
                    $concate = 'CAZ';
                } elseif ($id_cate == 5) {
                    $concate = 'PES';
                } elseif ($id_cate == 6) {
                    $concate = 'HIP';
                } elseif ($id_cate == 7) {
                    $concate = 'BUC';
                } elseif ($id_cate == 8) {
                    $concate = 'NAU';
                } elseif ($id_cate == 9) {
                    $concate = 'ESQ';
                } elseif ($id_cate == 10) {
                    $concate = 'PAD';
                } elseif ($id_cate == 11) {
                    $concate = 'AVE';
                }
                $id_cate++;

            }
            $categoryId = $value['id_category'];
            $categoryName = $value['name'];
            $active = $value['active'];

            // Contar la cantidad de productos en esta categoría (deberás ajustar esto según tu esquema de base de datos)
            $queryCount = "SELECT
                                COUNT(*) as productCount
                            FROM
                                aalv_category_product
                            WHERE
                                id_category = $categoryId";
            $resultCount = Db::getInstance()->ExecuteS($queryCount);
            $productCount = 0;

            if (count($columna) != 0) {
                $productCount = $resultCount[0]['productCount'];
            }

            $existeEnMenu = in_array($categoryId, $categoryIds) ? ' Existe en el menú' : '';

            // Agregar los datos de la categoría actual al resultado
            $categoria = [
                'id' => $categoryId,
                'text' => '['.$categoryId.'] ['.$categoryName.'] ['.$active.'] ['.$productCount.']'.$existeEnMenu,
                // 'text' => '['.$categoryId.'] ['.$categoryName.'] ['.$active.'] ['.$concate.str_pad($GLOBALS['contador'], 6, '0', STR_PAD_LEFT).']'
                // 'text' => $categoryName,
                // 'codigo' => $concate.str_pad($GLOBALS['contador'], 6, '0', STR_PAD_LEFT),
                // 'active' => $active
            ];
            $GLOBALS['contador']++;

            // Llamar recursivamente para las categorías hijas con un nivel aumentado
            $categoria['children'] = construirArbolCategorias($categoryId, $id_cate, $concate);

            if (count($categoria['children']) > 0) {
                $categoria['state'] = ['opened' => true];
            }

            $result[] = $categoria;
        }
    }

    return $result;
}

// Llamada inicial a la función y convertir el resultado en JSON
$jsonData = construirArbolCategorias(2);
// echo json_encode($jsonData);
// var_dump($jsonData);
// die();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Árbol de Categorías</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css" />
</head>
<body>
    <div id="tree"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>
    <script src="tree.js"></script>
</body>
</html>
<script>
    $(document).ready(function() {
        // Proporciona tu JSON de categorías aquí
        const jsonData = <?php echo json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>

        // Configura jsTree
        $('#tree').jstree({
            'core': {
                'data': jsonData,
                'opened': true // Abre todo el árbol al cargar
            }
        });

        // Manejar el evento "loaded.jstree" para pintar los nodos después de cargar el árbol
        $('#tree').on('loaded.jstree', function() {
            pintarNodosNoActivos();
        });

        function pintarNodosNoActivos() {
            const tree = $('#tree').jstree(true);
            const allNodes = tree.get_json('#', { flat: true });

            allNodes.forEach(node => {
                if (node.text.includes("[No Activo]")) {
                    document.getElementById(node.id).style.color = "red";
                }else{
                    document.getElementById(node.id).style.color = "blue";
                }
            });
        }
    });

</script>
