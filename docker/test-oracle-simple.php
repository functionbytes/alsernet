<?php

echo "=== Prueba Rápida de Conexión Oracle 9i ===\n\n";

// Configurar timeout de 5 segundos
ini_set('oci8.ping_interval', 5);
ini_set('default_socket_timeout', 5);

$username = 'lectura';
$password = 'alsernet';
$connection_string = '192.168.253.8:1521/GESTCENT';

echo "Intentando conectar a: $connection_string\n";
echo "Usuario: $username\n\n";

$start = microtime(true);

// Intentar conexión
$conn = @oci_connect($username, $password, $connection_string, 'AL32UTF8');

$elapsed = round(microtime(true) - $start, 2);

if ($conn) {
    echo "✓ CONEXIÓN EXITOSA! (tiempo: {$elapsed}s)\n\n";

    // Obtener versión del servidor
    $version = oci_server_version($conn);
    echo "Versión de Oracle: $version\n\n";

    // Probar una consulta simple
    $sql = 'SELECT SYSDATE FROM DUAL';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);

    $row = oci_fetch_array($stid, OCI_ASSOC);
    echo 'Fecha del servidor: '.$row['SYSDATE']."\n";

    oci_close($conn);
} else {
    $error = oci_error();
    echo "✗ ERROR DE CONEXIÓN (tiempo: {$elapsed}s)\n";
    if ($error) {
        echo 'Código: '.$error['code']."\n";
        echo 'Mensaje: '.$error['message']."\n";
    }
}

echo "\n=== Fin de la prueba ===\n";
