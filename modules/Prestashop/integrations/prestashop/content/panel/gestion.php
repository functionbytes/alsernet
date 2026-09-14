<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
require _PS_ADMIN_DIR_.'/../config/config.panel.inc.php';
include dirname(__FILE__).'/init.php';
require '../vendor/autoload.php'; // Para trabajar con archivos XLSX, usa la librería PhpSpreadsheet.

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validar la extensión
        $allowedExtensions = ['csv', 'xls', 'xlsx'];
        if (in_array($fileExtension, $allowedExtensions)) {
            // Procesar según el tipo de archivo
            switch ($fileExtension) {
                case 'csv':
                    $data = processCSV($fileTmpPath);
                    break;
                case 'xls':
                    $data = processXLS($fileTmpPath);
                    break;
                case 'xlsx':
                    $data = processXLSX($fileTmpPath);
                    break;
            }
            if ($data) {
                displayData($data);
            }
        } else {
            showError("Extensión de archivo no permitida: $fileExtension");
        }
    } else {
        showError('Error al subir el archivo.');
    }
}

/**
 * Normaliza precios a formato estándar: sin miles y con '.' como decimal.
 * Ejemplos:
 *  "2,859,99"   -> "2859.99"
 *  "1.234,50"   -> "1234.50"
 *  "1,234.50"   -> "1234.50"
 *  "1.234.567"  -> "1234567"
 *  "9,5"        -> "9.5"
 *  "1234"       -> "1234"
 */
function normalizeIncomingPrice($price)
{
    if ($price === null) {
        return null;
    }

    // 1) quitar moneda, espacios (incl. NBSP) y cualquier char que no sea dígito, coma o punto
    $s = trim((string) $price);
    $s = preg_replace('/[^\d,.\-]+/u', '', $s);
    if ($s === '' || $s === '-') {
        return null;
    }

    // 2) localizar el separador decimal real: el último '.' o ','
    $lastComma = strrpos($s, ',');
    $lastDot = strrpos($s, '.');

    // helper para reconstruir con '.' decimal conservando nº de decimales detectado
    $rebuild = function (string $orig, int $decLen) {
        $digits = preg_replace('/\D+/', '', $orig); // solo dígitos
        if ($digits === '') {
            return null;
        }
        if ($decLen <= 0) {
            return ltrim($digits, '0') === '' ? '0' : $digits;
        }
        if (strlen($digits) <= $decLen) {
            // 0.xxx
            $intPart = '0';
            $decPart = str_pad($digits, $decLen, '0', STR_PAD_LEFT);

            return $intPart.'.'.$decPart;
        }
        $intPart = substr($digits, 0, -$decLen);
        $decPart = substr($digits, -$decLen);
        $intPart = ltrim($intPart, '0'); // evitar "000123"
        if ($intPart === '') {
            $intPart = '0';
        }

        return $intPart.'.'.$decPart;
    };

    if ($lastComma !== false && $lastDot !== false) {
        // Ambos existen: el decimal es el que esté más a la derecha
        $decPos = max($lastComma, $lastDot);
        $decLen = strlen($s) - $decPos - 1;

        return $rebuild($s, $decLen);
    }

    // Solo hay ',', o solo '.'
    $sep = ($lastComma !== false) ? ',' : (($lastDot !== false) ? '.' : null);
    if ($sep === null) {
        // sin separadores -> número entero
        $digits = preg_replace('/\D+/', '', $s);

        return $digits === '' ? null : ltrim($digits, '0') === '' ? '0' : $digits;
    }

    $sepPos = strrpos($s, $sep);
    $decLen = strlen($s) - $sepPos - 1;

    // Heurística: si después del separador hay 1 o 2 dígitos -> decimal; si no, tratar como miles (sin decimales)
    if ($decLen === 1 || $decLen === 2) {
        return $rebuild($s, $decLen);
    }

    // Probablemente separadores de miles → quitar todo y sin decimales
    $digits = preg_replace('/\D+/', '', $s);

    return $digits === '' ? null : ltrim($digits, '0') === '' ? '0' : $digits;
}

// Nombre de atributos "Grupo - Valor, Grupo - Valor" por id_product_attribute
function getAttributesNameByIdProductAttribute($idProductAttribute, $idLang = 1)
{
    $query = '
        SELECT agl.name AS group_name, al.name AS attribute_name
        FROM '._DB_PREFIX_.'product_attribute_combination pac
        INNER JOIN '._DB_PREFIX_.'attribute a ON pac.id_attribute = a.id_attribute
        INNER JOIN '._DB_PREFIX_.'attribute_lang al ON a.id_attribute = al.id_attribute AND al.id_lang = '.(int) $idLang.'
        INNER JOIN '._DB_PREFIX_.'attribute_group_lang agl ON a.id_attribute_group = agl.id_attribute_group AND agl.id_lang = '.(int) $idLang.'
        WHERE pac.id_product_attribute = '.(int) $idProductAttribute;

    $rows = Db::getInstance()->executeS($query);
    if (! $rows) {
        return '';
    }
    $parts = [];
    foreach ($rows as $r) {
        $parts[] = $r['group_name'].' - '.$r['attribute_name'];
    }

    return implode(', ', $parts);
}

// Devuelve todas las combinaciones hermanas de un producto: id_product_attribute, reference
function getSisterCombinations($idProduct)
{
    $sql = 'SELECT pa.id_product_attribute, pa.reference
            FROM '._DB_PREFIX_.'product_attribute pa
            INNER JOIN '._DB_PREFIX_.'product_attribute_shop pas
                ON pas.id_product_attribute = pa.id_product_attribute AND pas.id_shop = 1
            WHERE pa.id_product = '.(int) $idProduct;

    return Db::getInstance()->executeS($sql) ?: [];
}

// Calcula el precio PS (con impuestos) de una combinación o producto simple
function calcPsPriceWithTax($idProduct, $idProductAttribute, $idCountry)
{
    $specific_price = null;

    return Product::priceCalculation(
        1,                 // id_shop
        (int) $idProduct,   // id_product
        (int) $idProductAttribute,
        (int) $idCountry,   // id_country
        0,                 // id_state
        '',                // zipcode
        1,                 // id_currency
        3,                 // id_group
        1,                 // quantity
        true,              // use_tax
        2,                 // decimals
        false,             // only_reduc
        true,              // use_reduc
        true,              // with_ecotax
        $specific_price,   // PS lo rellena si aplica
        true,              // use_group_reduction
        0,                 // id_customer
        true,              // use_customer_price
        0,                 // id_cart
        0,                 // real_quantity
        0                  // id_customization
    );
}

// Función para procesar archivos CSV
function processCSV($filePath)
{
    $data = [];
    if (($handle = fopen($filePath, 'r')) !== false) {
        $rowNumber = 1;
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (empty(trim($row[0])) || empty(trim($row[1]))) {
                fclose($handle);
                showError("El archivo CSV contiene una línea vacía en la columna 0 o 1 (Fila $rowNumber).");

                return null;
            }
            $data[] = [$row[0], $row[1]];
            $rowNumber++;
        }
        fclose($handle);
    } else {
        showError('No se pudo abrir el archivo CSV.');

        return null;
    }

    return $data;
}

// Función para procesar archivos XLS
function processXLS($filePath)
{
    $data = [];
    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $rowNumber = 1;

        foreach ($rows as $row) {
            $col0 = isset($row[0]) ? trim($row[0]) : '';
            $col1 = isset($row[1]) ? trim($row[1]) : '';
            if (empty($col0) || empty($col1)) {
                showError("El archivo XLS contiene una línea vacía en la columna 0 o 1 (Fila $rowNumber).");

                return null;
            }
            $data[] = [$col0, $col1];
            $rowNumber++;
        }
    } catch (Exception $e) {
        showError("Error al procesar el archivo XLS: {$e->getMessage()}");

        return null;
    }

    return $data;
}

// Función para procesar archivos XLSX
function processXLSX($filePath)
{
    $data = [];
    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $rowNumber = 1;

        foreach ($rows as $row) {
            $col0 = isset($row[0]) ? trim($row[0]) : '';
            $col1 = isset($row[1]) ? trim($row[1]) : '';
            if (empty($col0) || empty($col1)) {
                continue;
            }
            $data[] = [$col0, $col1];
            $rowNumber++;
        }
    } catch (Exception $e) {
        showError("Error al procesar el archivo XLSX: {$e->getMessage()}");

        return null;
    }

    return $data;
}

// Función para mostrar un mensaje de error
function showError($message)
{
    echo "<div class='alert alert-danger'>$message</div>";
    echo "<a href='{$_SERVER['PHP_SELF']}' class='btn btn-primary'>Volver a cargar</a>";
    exit;
}

function displayData($data)
{
    $id_country = (int) $_POST['country'];
    $idLang = 1;

    // 1) Parse del CSV (ref, price normalizado) preservando orden
    $rows = [];
    foreach ($data as $row) {
        $ref = trim($row[0]);
        $price = normalizeIncomingPrice($row[1]);
        if ($ref === '' || $price === null || $price === '') {
            continue;
        }
        $rows[] = ['ref' => $ref, 'price' => $price];
    }

    if (! $rows) {
        showError('El archivo no contiene referencias válidas.');
    }

    // 2) Agrupar por producto
    $productRefs = []; // id_product => ['refs_in_file' => [ref => price]]
    $ref2product = []; // ref (de combinación) => id_product

    foreach ($rows as $item) {
        $ref = $item['ref'];
        $price = $item['price'];

        // Buscar como combinación
        $comb = Db::getInstance()->executeS("
            SELECT apa.id_product_attribute, apa.id_product
            FROM aalv_product_attribute apa
            WHERE apa.reference = '".pSQL($ref)."' and apa.id_product != 0
            LIMIT 1
        ");
        if (count($comb) != 0) {
            $idProduct = (int) $comb[0]['id_product'];
            $productRefs[$idProduct]['refs_in_file'][$ref] = $price;
            $ref2product[$ref] = $idProduct;

            continue;
        }

        // Buscar como producto padre
        $prod = Db::getInstance()->executeS("
            SELECT ap.id_product
            FROM aalv_product ap
            WHERE ap.reference = '".pSQL($ref)."'
            LIMIT 1
        ");
        if (count($prod) != 0) {
            $idProduct = (int) $prod[0]['id_product'];
            $productRefs[$idProduct]['refs_in_file'][$ref] = $price;
        }
    }

    $precio_combinaciones = []; // salida: ref => [reference, price|null, combinacion, nombre, precio_ps]

    // 3) Construcción de salida
    foreach ($productRefs as $idProduct => $info) {
        $refsInFile = $info['refs_in_file'];
        $product = new Product($idProduct);

        // ¿Tiene combinaciones?
        $sisters = getSisterCombinations($idProduct);

        if (empty($sisters)) {
            // Producto simple
            $extinto = Db::getInstance()->executeS('
                SELECT 1 FROM aalv_combinacionunica_import
                WHERE estado_gestion = 0 AND id_product = '.$idProduct.'
                LIMIT 1
            ');
            if ($extinto) {
                continue;
            }

            // tomar la primera ref del archivo (debería ser la del padre)
            foreach ($refsInFile as $ref => $price) {
                $psPrice = calcPsPriceWithTax($idProduct, 0, $id_country);
                $precio_combinaciones[$ref] = [
                    'reference' => $ref,
                    'price' => $price,
                    'combinacion' => '',
                    'nombre' => isset($product->name[$idLang]) ? $product->name[$idLang] : '',
                    'precio_ps' => $psPrice,
                ];
                break;
            }

            continue;
        }

        // Producto con combinaciones:
        // Mapa ref->precio SOLO de las combinaciones presentes en el archivo.
        $fileRefPrices = [];
        foreach ($sisters as $sis) {
            $r = $sis['reference'];
            if (isset($refsInFile[$r])) {
                $fileRefPrices[$r] = $refsInFile[$r];
            }
        }
        // Nota: si el archivo trae solo la ref del padre (y no de hermanas), $fileRefPrices quedará vacío.
        // Por la nueva regla, NO propagamos: las hermanas no presentes salen con PVP vacío.

        foreach ($sisters as $sis) {
            $sisRef = $sis['reference'];
            $sisIdA = (int) $sis['id_product_attribute'];

            // Extinto por combinación
            $extinto = Db::getInstance()->executeS('
                SELECT 1 FROM aalv_combinaciones_import
                WHERE estado_gestion = 0 AND id_product_attribute = '.$sisIdA.'
                LIMIT 1
            ');
            if ($extinto) {
                continue;
            }

            $psPrice = calcPsPriceWithTax($idProduct, $sisIdA, $id_country);
            $attrs = getAttributesNameByIdProductAttribute($sisIdA, $idLang);

            $outPrice = isset($fileRefPrices[$sisRef]) ? $fileRefPrices[$sisRef] : ''; // vacío si no vino en archivo

            $precio_combinaciones[$sisRef] = [
                'reference' => $sisRef,
                'price' => $outPrice,
                'combinacion' => $attrs,
                'nombre' => isset($product->name[$idLang]) ? $product->name[$idLang] : '',
                'precio_ps' => $psPrice,
            ];
        }
    }

    // 4) CSV
    $columnas = ['Insertar', 'Código', 'Descripción', 'PVP', 'Precio PS'];
    $nombreArchivo = 'mi_archivo_'.date('Y-m-d_H-i-s').'.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$nombreArchivo.'"');

    $archivo = fopen('php://output', 'w');
    if ($archivo !== false) {
        fwrite($archivo, "\xEF\xBB\xBF");
        fputcsv($archivo, $columnas, ';');

        foreach ($precio_combinaciones as $fila) {
            $nombre = '';
            if (! empty($fila['nombre'])) {
                $nombre .= $fila['nombre'];
            }
            if (! empty($fila['combinacion'])) {
                $nombre .= ' ('.$fila['combinacion'].')';
            }

            // PVP puede estar vacío ('') si la hermana no vino en el archivo
            $priceOut = $fila['price'];

            $nuevaFila = [
                '',
                $fila['reference'],
                $nombre,
                $priceOut,
                $fila['precio_ps'],
            ];
            fputcsv($archivo, $nuevaFila, ';');
        }

        fclose($archivo);
        exit;
    } else {
        header('Location: gestion.php?status=no');
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'si') {
    echo "<div class='alert alert-success'>Datos procesados correctamente</div>";
} elseif (isset($_GET['status']) && $_GET['status'] == 'no') {
    echo "<div class='alert alert-danger'>Error al descargar el fichero</div>";
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Archivos</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Estilo para la pantalla de carga */
        .loading-screen {
            display: none;
            /* Ocultar la pantalla de carga al inicio */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            width: 3rem;
            height: 3rem;
            border: 4px solid #fff;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Subir Archivos</h1>
        <!-- Formulario -->
        <form id="uploadForm" method="POST" enctype="multipart/form-data">
            <!-- Select -->
            <div class="form-group">
                <label for="categorySelect">Selecciona una categoría</label>
                <select class="form-control" id="country" name="country">
                    <option value="" disabled selected>Selecciona una opción</option>
                    <?php
                    $country = Db::getInstance()->executeS('select ac.id_country, al.name from aalv_country ac
                        inner join aalv_lang al on al.iso_code = ac.iso_code COLLATE utf8mb4_unicode_ci');
foreach ($country as $value) {
    echo '<option value="'.$value['id_country'].'">'.$value['name'].'</option>';
}
?>
                </select>
            </div>
            <!-- File Upload -->
            <div class="form-group">
                <label for="fileInput">Subir Archivo</label>
                <input type="file" class="form-control-file" id="fileInput" name="file" accept=".csv,.xls,.xlsx" required>
                <small class="form-text text-muted">
                    Solo se permiten archivos con extensión: .csv, .xls, .xlsx<br>
                    Solo se aceptan dos columnas, la columna de referencia y de precio.
                </small>
            </div>
            <!-- Submit Button -->
            <button type="submit" id="submitButton" class="btn btn-primary">Subir archivo</button>
        </form>
    </div>

    <!-- Pantalla de carga -->
    <div class="loading-screen d-flex" id="loadingScreen">
        <div class="loading-spinner"></div>
    </div>

    <script>
        // Asegurarse de que la pantalla de carga esté oculta al principio
        document.getElementById('loadingScreen').style.setProperty('display', 'none', 'important');

        // Mostrar la pantalla de carga al presionar el botón de subir
        document.getElementById('submitButton').addEventListener('click', function(event) {
            // Mostrar la pantalla de carga
            document.getElementById('loadingScreen').style.display = 'flex';
        });

        // Prevenir errores al recargar la página sin enviar el formulario
        document.getElementById('uploadForm').addEventListener('submit', function() {
            setTimeout(() => {
                // Ocultar la pantalla de carga después de un pequeño retraso
                document.getElementById('loadingScreen').style.setProperty('display', 'none', 'important');
            }, 3000); // Opcional: Oculta la pantalla después de 3 segundos
        });
    </script>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>