<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

$familiaarmas = '100000896,100000895,100000900,100000903,100000944,100000893,100001434';
$subfamiliaarmas = '100001658,100003022,100001710,100001712,100001716,100003023,100001687,100001700,100001711,100003028';

$arrayfam = explode(',', $familiaarmas);
$arraysubfam = explode(',', $subfamiliaarmas);

$productid = Tools::getValue('p');
if (Context::getContext()->isAppiOS()) {

    $features = Product::getFeaturesStatic((int) $productid);
    foreach ($features as $featureitem) {
        if ($featureitem['id_feature'] == Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE')) {
            if ($featureitem['id_feature_value'] == Configuration::get('BAN_PRODUCT_FEATURE_VALUE_PRODUCT_TYPE_WEAPON')) {
                echo 'OK';
                exit();
            }
            if ($featureitem['id_feature_value'] == 10101) {
                echo 'OK';
                exit();
            }
            if ($featureitem['id_feature_value'] == 10102) {
                echo 'OK';
                exit();
            }
        }

        if ($featureitem['id_feature'] == 12) {// familia
            $familia = Db::getInstance()->getValue('SELECT value FROM aalv_feature_value_lang WHERE id_feature_value='.$featureitem['id_feature_value'].' and id_lang=1');
            if (in_array($familia, $arrayfam)) {
                echo 'OK';
                exit();
            }
        }

        if ($featureitem['id_feature'] == 13) {// subfamilia
            $subfamilia = Db::getInstance()->getValue('SELECT value FROM aalv_feature_value_lang WHERE id_feature_value='.$featureitem['id_feature_value'].' and id_lang=1');
            if (in_array($subfamilia, $arraysubfam)) {
                echo 'OK';
                exit();
            }

        }

    }
}
echo 'KO';
