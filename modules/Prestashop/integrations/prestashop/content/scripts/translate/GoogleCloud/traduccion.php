<?php

require 'vendor/autoload.php';

$projectID = 'strong-land-122310';

use Google\Cloud\Translate\V3\TranslationServiceClient;

$translationClient = new TranslationServiceClient;
$targetLanguage = 'fr';

$dbOkitup = connectOkitup();
mysqli_set_charset($dbOkitup, 'utf8mb4');

$sql = 'select
id_product,
name,
description,
link_rewrite,
meta_description,
meta_title
from
aalv_product_lang
where
id_lang = 1
and id_shop = 1
and id_product IN (732, 755, 756, 757, 760, 761, 763, 764, 767, 1462, 1463, 1797, 1799, 3183, 3184, 3185, 3186, 3187, 6314, 7487, 7488, 11237, 11363, 13448, 16328, 16329, 19512, 23799, 34100, 34101, 34908, 35500, 35501, 35502, 35503, 35504, 35505, 35506, 35507, 35513, 35514, 35517, 35520, 35522, 37927, 38253, 38277, 38425, 38431, 38522, 38523, 42093, 42094, 46002, 46101, 47219, 47289, 50128, 51244, 55541, 56232, 57290, 57422, 57423, 57427, 57429, 57433, 57435, 57440, 57441, 57443, 57445, 57447, 57448, 59490, 59491, 59497, 59499, 59500, 59501, 59507, 59508, 59509, 59510, 59511, 59516, 59517, 59519, 59520, 59522, 59523, 59525, 59528, 59530, 59532, 59534, 59536, 59537, 59555, 59556, 59592, 59634, 59635, 59640, 59641, 59642, 59644, 59645, 59648, 59649, 59650, 60273, 61563, 62902, 63896, 63904, 63912, 63917, 63920, 64397, 64404, 64619, 64620, 64630, 64656, 64692, 64718, 64719, 64722, 64881, 64897)
';

$r = mysqli_query($dbOkitup, $sql);
while ($producto = mysqli_fetch_assoc($r)) {
    $content = [$producto['name'], $producto['description'], $producto['link_rewrite'], $producto['meta_description'], $producto['meta_title']];
    $response = $translationClient->translateText(
        $content,
        $targetLanguage,
        TranslationServiceClient::locationName($projectID, 'global')
    );

    echo $producto['id_product']."\n";

    foreach ($response->getTranslations() as $key => $translation) {
        switch ($key) {
            case 0:
                $nombre = $translation->getTranslatedText();
                break;
            case 1:
                $descripcion = $translation->getTranslatedText();
                break;
            case 2:
                $link_rewrite = $translation->getTranslatedText();
                break;
            case 3:
                $meta_description = $translation->getTranslatedText();
                break;
            case 4:
                $meta_title = $translation->getTranslatedText();
                break;
        }

    }
    if ($nombre) {
        $sql = "UPDATE aalv_product_lang SET name='".str_replace("'", "\'", $nombre)."',
        description='".str_replace("'", "\'", $descripcion)."',
        link_rewrite='".str_replace("'", "\'", $link_rewrite)."',
        meta_description='".str_replace("'", "\'", $meta_description)."',
        meta_title='".str_replace("'", "\'", $meta_title)."'
        WHERE id_product='".$producto['id_product']."' AND id_lang=3 AND id_shop=1";
        mysqli_query($dbOkitup, $sql);
    }
}
echo 'Fin';
exit;

function connectOkitup()
{

    return $dbcon;
}
