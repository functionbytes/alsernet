<?php

// Revisar semanalmente las tablas MyISAM a optimizar (= con data_free > 0)

include  dirname(__FILE__).'/../config/config.inc.php';

$db = Db::getInstance();
$aUpdated = [];
$aError = [];
// $sOk = " \033[1;32m[OK]\033[0m";
// $sNotok = " \033[1;31m[KO]\033[0m";


// Buscar las tablas a optimizar
$aStatusTables = $db->executeS("SHOW TABLE STATUS WHERE Engine <> 'InnoDB' AND Data_free > 0;");
if (!empty($aStatusTables)) {
    foreach ($aStatusTables as $i => $aTable) {
        $tableName = $aTable["Name"];
        $bOptimized = $db->execute("OPTIMIZE TABLE `" . $tableName ."`;");

        echo str_pad("\n- Optimizando " . $tableName, 50, '.');

        if ($bOptimized) {
            $aUpdated[] = $tableName;
            echo "[OK]";
        } else {
            $aError[] = $tableName;
            echo "[NOT_OK]";
        }
    } // foreach
} else {
    echo "No hay tablas a optimizar.";
}
echo "\n";


// Notificar por correo
$message = '';
$message .= (!empty($aUpdated)) ? "<p>Listado de tablas optimizadas:</p><ul><li>".implode('</li><li>', $aUpdated)."</li></ul>" : "";
$message .= (!empty($aError)) ? "<p>Listado de tablas NO optimizadas correctamente:</p><ul><li>".implode('</li><li>', $aError)."</li></ul>" : "";

if (!empty($aUpdated) || !empty($aError)) {
    $data = ['{message}' => $message];
    $destin = [_PS_PROGRAMACION_EMAIL_, _PS_SISTEMAS_EMAIL_];

    Mail::Send(Configuration::get('PS_LANG_DEFAULT'),
            'integracion', // template
            "[PrestaShop Alvarez] Tablas optimizadas", // Subject
            $data,
            $destin,
            Configuration::get('PS_SHOP_NAME'), // to_name
            Configuration::get('PS_SHOP_EMAIL'), // from
            'Alvarez PrestaShop' // from_name
    );
}


die();
