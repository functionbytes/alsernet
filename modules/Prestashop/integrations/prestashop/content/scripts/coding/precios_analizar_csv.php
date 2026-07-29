<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';

// Mapeo de número a texto
$nombreMapeado = [
    '6' => 'ES',
    '15' => 'PT',
    '8' => 'FR',
    '1' => 'DE',
    '10' => 'IT',
    '2' => 'AU',
    // Agrega más según necesites
];

// Carpeta donde están los archivos
$directorio = '/home/alvarez/web/scripts/coding/';

// Array para guardar los datos
$datosArchivos = [];

// Buscar archivos con patrón
foreach (glob($directorio . 'precios_diferencias_*.csv') as $archivo) {
    // Obtener nombre base del archivo
    $nombreBase = basename($archivo, '.csv'); // precios_diferencias_2

    // Extraer número del nombre
    if (preg_match('/precios_diferencias_(\d+)/', $nombreBase, $coincidencias)) {
        $numero = $coincidencias[1];
        $nombreTexto = $nombreMapeado[$numero] ?? 'DESCONOCIDO';

        // Corregido: delimitador explícito ';'
        $contenido = array_map(function($linea) {
            return str_getcsv($linea, ';');
        }, file($archivo));

        // Guardar en el array principal
        $datosArchivos[$nombreTexto] = $contenido;
    }
}

// Arrays para separar los registros
$registrosCero = [];
$registrosOtros = [];

// Recorrer los datos para clasificar los registros
foreach ($datosArchivos as $region => $registros) {

    foreach ($registros as $registro) {

        // Ignorar filas vacías o mal formateadas
        if (count($registro) < 1) continue;

        // Verificar si el último valor es '0'
        $ultimoValor = trim(end($registro));
        // var_dump($ultimoValor);die();

        if ($ultimoValor === '0') {
            $registrosCero[$region][] = $registro;
        } else {
            $registrosOtros[$region][] = $registro;
        }
    }
}

// Nuevo array para agrupar por referencia
$referenciasPorPais = [];

foreach ($registrosCero as $pais => $registros) {
    foreach ($registros as $registro) {
        $referencia = $registro[1];

        // Inicializamos si no existe
        if (!isset($referenciasPorPais[$referencia])) {
            $referenciasPorPais[$referencia] = [];
        }

        // Evitar repetir países
        if (!in_array($pais, $referenciasPorPais[$referencia])) {
            $referenciasPorPais[$referencia][] = $pais;
        }
    }
}

// Convertimos a string con países separados por coma
$referenciasConPaises = [];

foreach ($referenciasPorPais as $referencia => $paises) {
    $referenciasConPaises[$referencia] = implode(',', $paises);
}


// Comenzamos a construir el HTML en una variable
$html = "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Reporte de Diferencias</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 40px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
<h1>Reporte de Diferencias</h1>
<h2>Registros con diferencias por país</h2>";

// Tabla por país: $registrosOtros
foreach ($registrosOtros as $pais => $registros) {
    $html .= "<h3>País: $pais</h3>";
    $html .= "<table>";
    $html .= "<tr>
                <th>id_product</th>
                <th>reference</th>
                <th>precio_ps</th>
                <th>precio_angular</th>
              </tr>";

    foreach ($registros as $registro) {
        if (count($registro) < 4) continue;

        list($id_product, $reference, $precio_ps, $precio_angular) = $registro;

        $html .= "<tr>
                    <td>" . htmlspecialchars($id_product) . "</td>
                    <td>" . htmlspecialchars($reference) . "</td>
                    <td>" . htmlspecialchars($precio_ps) . "</td>
                    <td>" . htmlspecialchars($precio_angular) . "</td>
                  </tr>";
    }

    $html .= "</table>";
}

// Segunda tabla: referencias encontradas en múltiples países
$html .= "<h2>Referencias detectadas en múltiples países</h2>";
$html .= "<table>";
$html .= "<tr>
            <th>Referencia</th>
            <th>Países</th>
          </tr>";

foreach ($referenciasConPaises as $referencia => $paises) {
    $html .= "<tr>
                <td>" . htmlspecialchars($referencia) . "</td>
                <td>" . htmlspecialchars($paises) . "</td>
              </tr>";
}

$html .= "</table>
</body>
</html>";


$dest = [];
        $dest[] = "alvarez@alsernet.es";

        $data=['{message}'=>$html];
        Mail::Send(    1,
                        'integracion',
                        "Integracion",
                        $data,
                        $dest,
                        Configuration::get('PS_SHOP_NAME'),
                        'desarrollotest@a-alvarez.com',
                        'desarrollotest',
                        [],
                        null,
                        _PS_MAIL_DIR_,
                        false,
                        1
                    );