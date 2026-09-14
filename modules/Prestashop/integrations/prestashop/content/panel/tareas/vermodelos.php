<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function getfieldvalue($dbh, $sql)
{
    $rows = $dbh->query($sql);
    foreach ($rows as $row) {
        return $row[0];
    }
}

function getdatarows($dbh, $sql)
{
    return $dbh->query($sql);
}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT * FROM producto where id_modelo in (100006646,100015132,100024735,100025250,100032457,100042095,100042096,100042097,100042099,100042537,100042538,100042547,100045040,100049417,100049418,100049419)');
foreach ($rows as $row) {
    echo '<br/>'.$row['id'].' - '.$row['id_modelo'];
}

echo '<br/>acaba';
